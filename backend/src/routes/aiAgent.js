const express = require('express');
const Joi = require('joi');
const { authenticate, requirePermissions } = require('../middleware/auth');
const settings = require('../services/aiAgentSettingsService');

const router = express.Router();
const guard = [authenticate(), requirePermissions(['dialer.create_call'])];

router.get('/', ...guard, async (_req, res, next) => {
  try { return res.json({ ok: true, settings: await settings.get() }); } catch (err) { return next(err); }
});

router.put('/', ...guard, async (req, res, next) => {
  const schema = Joi.object({
    enabled: Joi.boolean().required(),
    goal: Joi.string().trim().max(2000).required(),
    mode: Joi.string().valid('lead', 'assist', 'qualify').required(),
    voice: Joi.string().valid('professional', 'warm', 'confident').required(),
    humanHandoff: Joi.boolean().required()
  });
  const { error, value } = schema.validate(req.body);
  if (error) return res.status(400).json({ ok: false, message: error.message });
  try {
    return res.json({ ok: true, settings: await settings.update(value, req.user.id) });
  } catch (err) {
    if (err.statusCode) return res.status(err.statusCode).json({ ok: false, message: err.message });
    return next(err);
  }
});

module.exports = router;
