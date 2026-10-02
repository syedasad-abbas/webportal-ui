const express = require('express');
const Joi = require('joi');
const { authenticate, requirePermissions } = require('../middleware/auth');
const settings = require('../services/aiAgentSettingsService');

const router = express.Router();
const guard = [authenticate(), requirePermissions(['dialer.create_call'])];

router.get('/', ...guard, async (_req, res, next) => {
  try { return res.json({ ok: true, settings: await settings.get() }); } catch (err) { return next(err); }
});

// Keys the service layer knows how to persist. Anything else the UI sends is
// accepted and ignored rather than rejecting the whole request, so an extra
// field in the browser never blocks saving the fields we do support.
const PERSISTED_KEYS = ['enabled', 'goal', 'mode', 'voice', 'humanHandoff'];

const pickPersisted = (value) => PERSISTED_KEYS.reduce((acc, key) => {
  if (value[key] !== undefined) acc[key] = value[key];
  return acc;
}, {});

router.put('/', ...guard, async (req, res, next) => {
  const schema = Joi.object({
    enabled: Joi.boolean().required(),
    goal: Joi.string().trim().max(2000).required(),
    mode: Joi.string().valid('lead', 'assist', 'qualify').required(),
    voice: Joi.string().valid('man', 'woman', 'male', 'female', 'professional', 'warm', 'confident').required(),
    humanHandoff: Joi.boolean().required()
  }).unknown(true);
  const { error, value } = schema.validate(req.body);
  if (error) return res.status(400).json({ ok: false, message: error.message });
  const persisted = pickPersisted(value);
  const ignoredKeys = Object.keys(value).filter((key) => !PERSISTED_KEYS.includes(key));
  if (ignoredKeys.length) console.warn('[ai-agent] ignoring unsupported settings fields', ignoredKeys);
  try {
    return res.json({ ok: true, settings: await settings.update(persisted, req.user.id) });
  } catch (err) {
    if (err.statusCode) return res.status(err.statusCode).json({ ok: false, message: err.message });
    return next(err);
  }
});

module.exports = router;
