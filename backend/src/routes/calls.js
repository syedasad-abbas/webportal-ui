const express = require('express');
const Joi = require('joi');
const { authenticate, requirePermissions } = require('../middleware/auth');
const config = require('../config');
const callService = require('../services/callService');
const callControlService = require('../services/callControlService');

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
    return res.status(500).json({ message: err.message });
  }
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
    await callControlService.hangup({ uuid: req.params.uuid, userId: req.user.id });
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

module.exports = router;
