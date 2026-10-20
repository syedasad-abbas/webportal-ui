const express = require('express');
const crypto = require('crypto');
const Joi = require('joi');
const db = require('../db');
const config = require('../config');
const settings = require('../services/aiAgentSettingsService');
const fs = require('../lib/freeswitch');
const bridge = require('../services/geminiLiveBridge');
const { requireInternalToken } = require('../middleware/auth');
const { createService } = require('../services/vicidialAiConferenceService');

const router = express.Router();
router.use('/customer', require('./vicidialCustomerAi'));
const enabled = () => process.env.VICIDIAL_AI_ENABLED === 'true';
const sourceIp = () => process.env.VICIDIAL_AI_SERVER_IP || '';
const same = (a, b) => {
  const x = Buffer.from(String(a || '')); const y = Buffer.from(String(b || ''));
  return x.length === y.length && crypto.timingSafeEqual(x, y);
};
const service = createService({
  settings: () => enabled() ? settings.get() : Promise.resolve(null),
  mappedAgents: async () => (await db.query(`SELECT v.agent_user FROM vicidial_credentials v
    JOIN users u ON u.id = v.user_id WHERE u.dialer_provider = 'vicidial'`)).rows.map((r) => r.agent_user)
    .filter((agent) => !require('../services/vicidialCustomerAiService').customerAgents().has(agent)),
  start: async (uuid) => {
    const url = new URL(config.aiAgent.bridgeUrl);
    url.searchParams.set('call_id', uuid);
    url.searchParams.set('token', config.aiAgent.bridgeToken);
    url.searchParams.set('context', 'vicidial-conference');
    await fs.startAudioStream(uuid, url.toString(), { callId: uuid });
    const ready = await bridge.waitUntilReady(uuid, config.aiAgent.connectTimeoutMs * 2 + 10000);
    console.info('[vicidial-ai] voice connection', { uuid, ready });
    return ready;
  },
  connected: bridge.hasSession,
  stop: async (uuid) => {
    bridge.closeSession(uuid);
    await fs.hangupCall(uuid);
  }
});
const timer = setInterval(() => service.reap().catch(() => {}), 2000);
timer.unref();

const snapshotSchema = Joi.object({ calls: Joi.array().max(200).items(Joi.object({
  agent: Joi.string().pattern(/^[a-zA-Z0-9_-]{1,20}$/).required(),
  conference: Joi.string().pattern(/^\d{1,20}$/).required(),
  callId: Joi.string().pattern(/^[a-zA-Z0-9_.-]{1,80}$/).required()
})).required() });

// Dedicated worker secret; never sent to the browser or used as a SIP password.
router.post('/sync', async (req, res) => {
  const secret = process.env.VICIDIAL_AI_SYNC_TOKEN;
  const remote = String(req.socket.remoteAddress || '').replace(/^::ffff:/, '');
  if (!enabled() || !secret || secret.length < 32 || !sourceIp()) return res.sendStatus(503);
  if (remote !== sourceIp() || !same(req.headers['x-vicidial-ai-token'], secret)) return res.sendStatus(403);
  const { error, value } = snapshotSchema.validate(req.body);
  if (error) return res.status(400).json({ ok: false });
  try { return res.json({ ok: true, joins: await service.sync(value.calls) }); }
  catch (err) {
    console.warn('[vicidial-ai] snapshot unavailable', { error: err.message });
    return res.status(503).json({ ok: false });
  }
});

const legSchema = Joi.object({
  token: Joi.string().optional(), // existing internal-token middleware's form field
  reservation: Joi.string().hex().length(64).required(),
  uuid: Joi.string().guid().required(),
  source: Joi.string().required()
});
for (const action of ['claim', 'start', 'lease']) {
  router.post(`/${action}`, requireInternalToken, async (req, res) => {
    const { error, value } = legSchema.validate(req.body);
    if (!enabled() || error || !sourceIp() || value.source !== sourceIp()) return res.status(403).json({ ok: false });
    try {
      const ok = action === 'claim'
        ? await service.claim(value.reservation, value.uuid)
        : await service.lease(value.reservation, value.uuid, action === 'start');
      return res.status(ok ? 200 : 409).json({ ok });
    } catch { return res.status(503).json({ ok: false }); }
  });
}
module.exports = router;
