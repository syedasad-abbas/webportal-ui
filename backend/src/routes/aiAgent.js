const express = require('express');
const Joi = require('joi');
const { authenticate, requirePermissions } = require('../middleware/auth');
const config = require('../config');
const settings = require('../services/aiAgentSettingsService');
const faqs = require('../services/aiAgentFaqService');

const router = express.Router();
const guard = [authenticate(), requirePermissions(['ai_agent.configure'])];

// Raw bytes avoid retaining uploads on disk or accepting client-supplied paths.
const documentRequests = new Set();
router.post('/document-draft', ...guard, express.raw({ type: 'application/octet-stream', limit: '2mb' }), async (req, res) => {
  res.set('Cache-Control', 'no-store');
  if (documentRequests.has(req.user.id)) return res.status(429).json({ message: 'A document is already being processed for this user.' });
  documentRequests.add(req.user.id);
  try {
    const { extractDraft } = require('../services/aiDocumentService');
    const draft = await extractDraft(req.body, { apiKey: config.aiAgent.apiKey, model: config.aiAgent.textModel });
    return res.json({ ok: true, draft });
  } catch (err) {
    return res.status(err.statusCode || 502).json({ message: err.statusCode ? err.message : 'Document analysis is temporarily unavailable. Try again.' });
  } finally { documentRequests.delete(req.user.id); }
});

router.get('/', ...guard, async (_req, res, next) => {
  try { return res.json({ ok: true, settings: await settings.get() }); } catch (err) { return next(err); }
});

router.post('/assist', authenticate(), requirePermissions(['dialer.create_call']), async (req, res, next) => {
  const schema = Joi.object({
    question: Joi.string().trim().min(1).max(2000).required(),
    callNotes: Joi.string().trim().max(5000).allow('').default('')
  });
  const { error, value } = schema.validate(req.body);
  if (error) return res.status(400).json({ ok: false, message: error.message });
  if (!config.aiAgent.apiKey) {
    return res.status(503).json({ ok: false, message: 'AI assistance is not configured.' });
  }

  try {
    const current = await settings.get();
    const approvedFaqs = faqs.enabledEntries(current.faqs)
      .map((entry) => `Question: ${entry.question}\nApproved answer: ${entry.answer}`)
      .join('\n\n');
    const systemInstruction = [
      `You assist a human call-center agent. The configured duty is: ${current.role || 'call-center representative'}.`,
      `The configured call objective is: ${current.goal || 'help the caller with their request'}.`,
      'Suggest a concise, natural response the human agent can say. Do not claim a booking, action, policy, price, or fact unless the notes or approved answers confirm it.',
      'If the requested fact is not provided, say that it is unverified and suggest a short clarification or follow-up.',
      current.knowledgeInstructions ? `Administrator knowledge and conversation instructions (do not claim unperformed actions):\n${current.knowledgeInstructions}` : '',
      approvedFaqs ? `Approved question and answer pairs:\n${approvedFaqs}` : 'No approved question and answer pairs are configured.'
    ].join('\n\n');
    const response = await fetch(
      `https://generativelanguage.googleapis.com/v1beta/models/${encodeURIComponent(config.aiAgent.textModel)}:generateContent`,
      {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'x-goog-api-key': config.aiAgent.apiKey },
        signal: AbortSignal.timeout(15000),
        body: JSON.stringify({
          systemInstruction: { parts: [{ text: systemInstruction }] },
          contents: [{ role: 'user', parts: [{ text: `Agent question or context:\n${value.question}\n\nCall notes:\n${value.callNotes || 'None provided.'}` }] }],
          generationConfig: { temperature: 0.3, maxOutputTokens: 500 }
        })
      }
    );
    const payload = await response.json().catch(() => ({}));
    const responseParts = payload && payload.candidates && payload.candidates[0]
      && payload.candidates[0].content && payload.candidates[0].content.parts || [];
    const suggestion = responseParts
      .filter((part) => !part.thought)
      .map((part) => part.text || '')
      .join('\n')
      .trim();
    if (!response.ok || !suggestion) {
      return res.status(502).json({ ok: false, message: 'AI assistance could not generate a suggestion.' });
    }
    return res.json({ ok: true, suggestion });
  } catch (_err) {
    return res.status(502).json({ ok: false, message: 'AI assistance is temporarily unavailable.' });
  }
});

// Keys the service layer knows how to persist. Anything else the UI sends is
// accepted and ignored rather than rejecting the whole request, so an extra
// field in the browser never blocks saving the fields we do support.
const PERSISTED_KEYS = ['enabled', 'role', 'greeting', 'goal', 'mode', 'voice', 'humanHandoff', 'callDirection', 'knowledgeInstructions'];

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
    knowledgeInstructions: Joi.string().trim().max(10000).allow('', null),
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
