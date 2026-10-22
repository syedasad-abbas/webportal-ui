const express = require('express');
const config = require('../config');
const fs = require('../lib/freeswitch');
const bridge = require('../services/geminiLiveBridge');
const settings = require('../services/aiAgentSettingsService');
const { requireInternalToken } = require('../middleware/auth');
const { createCustomerService, customerAgents, customerSourceIp } = require('../services/vicidialCustomerAiService');
const router = express.Router();
const service = createCustomerService({
  enabled: () => process.env.VICIDIAL_CUSTOMER_AI_ENABLED === 'true',
  allowedAgents: customerAgents,
  sourceIp: () => customerSourceIp(),
  settings: () => settings.get(), channelVar: fs.getChannelVar,
  start: async (uuid) => {
    const url = new URL(config.aiAgent.bridgeUrl);
    url.searchParams.set('call_id', uuid);
    url.searchParams.set('token', config.aiAgent.bridgeToken);
    // Customer greeting with interruption support on this isolated audio leg.
    url.searchParams.set('context', 'vicidial-customer');
    await fs.startAudioStream(uuid, url.toString(), { callId: uuid });
    return bridge.waitUntilReady(uuid, config.aiAgent.connectTimeoutMs * 2 + 10000);
  },
  record: fs.startRecording,
  connected: bridge.hasSession, close: bridge.closeSession
});
const timer = setInterval(() => service.reap(), 2000);
timer.unref();
router.use(requireInternalToken);
for (const action of ['authorize', 'begin', 'lease', 'end']) {
  router.post('/' + action, async (req, res) => {
    try {
      const uuid = req.body?.uuid;
      const ok = action === 'end' ? (service.end(uuid), true) : await service[action](uuid);
      return res.status(ok ? 200 : 409).json({ ok });
    } catch (err) {
      console.error('[vicidial-ai] action failed', { action, uuid, error: err.message });
      return res.status(503).json({ ok: false });
    }
  });
}
router.post('/transcript', async (req, res) => {
  try {
    const { uuid, transcript } = req.body || {};
    if (!/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(uuid || '')
      || typeof transcript !== 'string') return res.status(400).json({ ok: false });
    await service.saveTranscript(uuid, transcript);
    return res.json({ ok: true });
  } catch { return res.status(503).json({ ok: false }); }
});
module.exports = router;
