const express = require('express');
const Joi = require('joi');
const { authenticate, requirePermissions } = require('../middleware/auth');
const config = require('../config');
const callService = require('../services/callService');
const callControlService = require('../services/callControlService');
const inboundCallService = require('../services/inboundCallService');
const db = require('../db');

const router = express.Router();
const dialPermission = config.permissions?.callDial || 'dial';

router.post('/', authenticate(), requirePermissions([dialPermission]), async (req, res) => {
  console.log('Incoming call payload:', req.body);
  const schema = Joi.object({
    destination: Joi.string().required()
  });

  const { error, value } = schema.validate(req.body);
  if (error) {
    return res.status(400).json({ message: error.message });
  }

  try {
    const response = await callService.originate({
      user: req.user,
      destination: value.destination
    });

    return res.json({
      status: response.status,
      callUuid: response.callUuid,
      conference: response.conference
    });
  } catch (err) {
    const statusCode = Number.isInteger(err?.statusCode) ? err.statusCode : 500;
    return res.status(statusCode).json({ message: err.message });
  }
});

router.get('/last', authenticate(), requirePermissions([dialPermission]), async (req, res) => {
  const result = await db.query(
    `SELECT call_uuid, direction, destination, caller_id, status, notes,
            connected_at, ended_at, created_at
       FROM call_logs
      WHERE user_id = $1
      ORDER BY created_at DESC
      LIMIT 2`,
    [req.user.id]
  );
  return res.json({
    call: result.rows[0] || null,
    previousCall: result.rows[1] || null
  });
});

router.put('/:uuid/notes', authenticate(), requirePermissions([dialPermission]), async (req, res) => {
  const schema = Joi.object({ notes: Joi.string().allow('').max(5000).required() });
  const { error, value } = schema.validate(req.body);
  if (error) return res.status(400).json({ message: error.message });

  const result = await db.query(
    `UPDATE call_logs
        SET notes = $1, updated_at = NOW()
      WHERE call_uuid = $2 AND user_id = $3
      RETURNING call_uuid, notes, updated_at`,
    [value.notes, req.params.uuid, req.user.id]
  );
  if (result.rowCount === 0) return res.status(404).json({ message: 'Call not found' });
  return res.json(result.rows[0]);
});

router.get('/:uuid', authenticate(), requirePermissions([dialPermission]), async (req, res) => {
  try {
    const status = await callControlService.getStatus({
      uuid: req.params.uuid,
      userId: req.user.id
    });
    return res.json(status);
  } catch (err) {
    return res.status(404).json({ message: err.message });
  }
});

router.post('/:uuid/mute', authenticate(), requirePermissions([dialPermission]), async (req, res) => {
  try {
    await callControlService.mute({ uuid: req.params.uuid, userId: req.user.id });
    return res.json({ status: 'muted' });
  } catch (err) {
    return res.status(400).json({ message: err.message });
  }
});

router.post('/:uuid/unmute', authenticate(), requirePermissions([dialPermission]), async (req, res) => {
  try {
    await callControlService.unmute({ uuid: req.params.uuid, userId: req.user.id });
    return res.json({ status: 'unmuted' });
  } catch (err) {
    return res.status(400).json({ message: err.message });
  }
});

router.post('/:uuid/hangup', authenticate(), requirePermissions([dialPermission]), async (req, res) => {
  try {
    await callControlService.hangup({
      uuid: req.params.uuid,
      userId: req.user.id,
      durationSeconds: req.body?.durationSeconds
    });
    return res.json({ status: 'ended' });
  } catch (err) {
    return res.status(400).json({ message: err.message });
  }
});

router.post('/:uuid/dtmf', authenticate(), requirePermissions([dialPermission]), async (req, res) => {
  const digits = (req.body && req.body.digits) || '';
  if (!digits) {
    return res.status(400).json({ message: 'Digits are required' });
  }
  try {
    await callControlService.sendDtmf({
      uuid: req.params.uuid,
      digits,
      userId: req.user.id
    });
    return res.json({ status: 'sent' });
  } catch (err) {
    return res.status(400).json({ message: err.message });
  }
});

// Agent declines a ringing inbound call -> advance round-robin to the next agent.
router.post('/:uuid/decline', authenticate(), requirePermissions([dialPermission]), async (req, res) => {
  try {
    const result = await inboundCallService.decline(req.params.uuid, req.user.id);
    return res.json(result);
  } catch (err) {
    return res.status(400).json({ message: err.message });
  }
});

module.exports = router;
