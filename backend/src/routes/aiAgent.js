const express = require('express');
const Joi = require('joi');
const { authenticate, requirePermissions } = require('../middleware/auth');
const settings = require('../services/aiAgentSettingsService');
const faqs = require('../services/aiAgentFaqService');

const router = express.Router();
const guard = [authenticate(), requirePermissions(['ai_agent.configure'])];

router.get('/', ...guard, async (_req, res, next) => {
  try { return res.json({ ok: true, settings: await settings.get() }); } catch (err) { return next(err); }
});

// Keys the service layer knows how to persist. Anything else the UI sends is
// accepted and ignored rather than rejecting the whole request, so an extra
// field in the browser never blocks saving the fields we do support.
const PERSISTED_KEYS = ['enabled', 'role', 'greeting', 'goal', 'mode', 'voice', 'humanHandoff', 'callDirection'];

const pickPersisted = (value) => PERSISTED_KEYS.reduce((acc, key) => {
  if (value[key] !== undefined) acc[key] = value[key];
  return acc;
}, {});

router.put('/', ...guard, async (req, res, next) => {
  const schema = Joi.object({
    enabled: Joi.boolean().required(),
    // Free text on purpose: the duty assignment may be sales, appointment
    // booking, assistant, guide, support, or anything else the administrator
    // needs, so it is not restricted to a fixed list of roles.
    // Laravel's ConvertEmptyStringsToNull middleware turns a cleared field
    // into null before this runs, so null and '' must both be accepted. A
    // cleared field is a valid state: it means "no duty override" or "no
    // opening line", which the prompt builder handles.
    role: Joi.string().trim().max(200).allow('', null).default(''),
    greeting: Joi.string().trim().max(1000).allow('', null).default(''),
    goal: Joi.string().trim().max(2000).allow('', null).default(''),
    mode: Joi.string().valid('lead', 'assist', 'qualify').required(),
    voice: Joi.string().valid('man', 'woman', 'male', 'female', 'professional', 'warm', 'confident').required(),
    humanHandoff: Joi.boolean().required(),
    callDirection: Joi.string().valid('inbound', 'outbound', 'both').default('inbound')
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

// Expected questions and their approved answers. The whole ordered list is
// saved in one request so the browser never has to reconcile ids: the payload
// order is the stored order, and an entry the payload omits is removed.
// A row may arrive half typed while the administrator is still working on it.
// Validation allows that here and the service drops it, so one unfinished row
// cannot fail the save of the rows that are already complete.
const faqSchema = Joi.object({
  question: Joi.string().trim().max(500).allow('').default(''),
  answer: Joi.string().trim().max(2000).allow('').default(''),
  isEnabled: Joi.boolean().default(true)
});

router.get('/faqs', ...guard, async (_req, res, next) => {
  try { return res.json({ ok: true, faqs: await faqs.list() }); } catch (err) { return next(err); }
});

router.put('/faqs', ...guard, async (req, res, next) => {
  const schema = Joi.object({
    faqs: Joi.array().items(faqSchema).max(100).default([])
  });
  const { error, value } = schema.validate(req.body);
  if (error) return res.status(400).json({ ok: false, message: error.message });
  try {
    return res.json({ ok: true, faqs: await faqs.replaceAll(value.faqs, req.user.id) });
  } catch (err) {
    return next(err);
  }
});

module.exports = router;
