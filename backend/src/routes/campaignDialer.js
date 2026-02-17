const express = require('express');
const Joi = require('joi');
const { authenticate, requirePermissions } = require('../middleware/auth');
const config = require('../config');
const campaignDialerService = require('../services/campaignDialerService');

const router = express.Router();
const dialerRoles = Array.isArray(config.frontend?.allowedRoles)
  ? config.frontend.allowedRoles
  : [];
const authMiddleware = dialerRoles.length ? authenticate(dialerRoles) : authenticate();
const dialPermission = config.permissions?.callDial || 'dial';

router.post('/start', authMiddleware, requirePermissions([dialPermission]), async (req, res) => {
  const schema = Joi.object({
    campaignId: Joi.number().integer().positive().required(),
    agent: Joi.string().trim().min(1).max(100).required()
  });
  const { error, value } = schema.validate(req.body || {});
  if (error) {
    return res.status(400).json({ message: error.message });
  }
  try {
    const nextLead = await campaignDialerService.startRun({
      userId: req.user.id,
      campaignId: value.campaignId,
      agent: value.agent.trim()
    });
    return res.json({ ok: true, next: nextLead });
  } catch (err) {
    return res.status(400).json({ message: err.message });
  }
});

router.post('/stop', authMiddleware, requirePermissions([dialPermission]), async (req, res) => {
  try {
    await campaignDialerService.stopRun({ userId: req.user.id });
    return res.json({ ok: true });
  } catch (err) {
    return res.status(400).json({ message: err.message });
  }
});

router.get('/next', authMiddleware, requirePermissions([dialPermission]), async (req, res) => {
  const schema = Joi.object({
    lastLeadId: Joi.number().integer().positive().optional(),
    lastLeadStatus: Joi.string().valid('called', 'failed').optional()
  });
  const { error, value } = schema.validate(req.query || {});
  if (error) {
    return res.status(400).json({ message: error.message });
  }
  try {
    const nextLead = await campaignDialerService.nextLead({
      userId: req.user.id,
      lastLeadId: value.lastLeadId ? Number(value.lastLeadId) : undefined,
      lastLeadStatus: value.lastLeadStatus
    });
    return res.json({ ok: true, next: nextLead });
  } catch (err) {
    return res.status(400).json({ message: err.message });
  }
});

module.exports = router;
