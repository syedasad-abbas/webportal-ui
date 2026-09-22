const express = require('express');
const { requireInternalToken } = require('../middleware/auth');
const inboundCallService = require('../services/inboundCallService');
const inboundDidService = require('../services/inboundDidService');
const freeswitch = require('../lib/freeswitch');
const aiSettings = require('../services/aiAgentSettingsService');
const config = require('../config');

const router = express.Router();

// FreeSWITCH reports a new inbound call. AI calls are answered immediately;
// human calls remain ringing until the selected browser agent answers.
router.post('/inbound', requireInternalToken, async (req, res) => {
  const { uuid, did, callerIdNumber } = req.body || {};
  if (!uuid) {
    return res.status(400).json({ ok: false, message: 'uuid is required' });
  }

  const normalizedDid = inboundDidService.normalizeDid(did);
  if (!normalizedDid) {
    return res.status(400).json({ ok: false, reason: 'did_required', message: 'did is required' });
  }

  let inboundDid;
  try {
    inboundDid = await inboundDidService.findActiveDid(normalizedDid);
  } catch (err) {
    console.error('[inbound] DID lookup failed', { uuid, did: normalizedDid, error: err.message });
    return res.status(500).json({ ok: false, reason: 'did_lookup_failed' });
  }

  if (!inboundDid) {
    console.warn('[inbound] rejected unconfigured DID', { uuid, did: normalizedDid });
    return res.status(404).json({ ok: false, reason: 'did_not_configured' });
  }

  // Return the routing mode immediately so Lua can either answer for AI or
  // leave the caller ringing while round-robin dispatch runs in the background.
  const conference = inboundCallService.conferenceFor(uuid);
  const settings = await aiSettings.get().catch((err) => {
    console.warn('[ai-agent] unable to determine inbound mode', { uuid, error: err.message });
    return null;
  });
  const ai = Boolean(settings?.enabled && settings?.ready && settings?.updatedBy);

  inboundCallService
    .dispatch({ uuid, did: normalizedDid, callerIdNumber, settings })
    .then((result) => console.log('[inbound] dispatch finished', { uuid, result }))
    .catch((err) => console.error('[inbound] dispatch error', { uuid, error: err.message }));

  return res.json({
    ok: true,
    conference,
    ai,
    did: normalizedDid,
    carrierId: inboundDid.carrier_id
  });
});

// FreeSWITCH Lua script reports that a call arrived at the ai-agent extension.
// If AI is enabled, we start uuid_audio_stream so the call is bridged to Gemini Live.
router.post('/ai-agent', requireInternalToken, async (req, res) => {
  const { uuid } = req.body || {};
  if (!uuid || typeof uuid !== 'string') {
    return res.status(400).json({ ok: false, message: 'uuid is required' });
  }

  let settings;
  try {
    settings = await aiSettings.get();
  } catch (err) {
    console.warn('[ai-agent] settings unavailable', { uuid, error: err.message });
  }

  if (!settings?.enabled || !settings?.ready || !settings?.updatedBy) {
    console.log('[ai-agent] skipping audio stream', { uuid, enabled: settings?.enabled, ready: settings?.ready });
    return res.json({ ok: true, skipped: true });
  }

  try {
    const url = new URL(config.aiAgent.bridgeUrl);
    url.searchParams.set('call_id', uuid);
    url.searchParams.set('token', config.aiAgent.bridgeToken);
    await freeswitch.startAudioStream(uuid, url.toString(), { callId: uuid });
    console.log('[ai-agent] audio stream started', { uuid, url: url.toString() });
    return res.json({ ok: true });
  } catch (err) {
    console.error('[ai-agent] audio stream failed', { uuid, error: err.message });
    return res.status(500).json({ ok: false, message: err.message });
  }
});

// FreeSWITCH notifies that the inbound caller hung up (e.g. while we were
// still ringing agents) so we stop failover.
router.post('/inbound/:uuid/hangup', requireInternalToken, async (req, res) => {
  await inboundCallService.stop(req.params.uuid);
  return res.json({ ok: true });
});

// Debug helper: list the agents currently eligible for round-robin.
router.get('/inbound/agents', requireInternalToken, async (_req, res) => {
  try {
    const agents = await inboundCallService.getOnlineAgents();
    return res.json({ ok: true, count: agents.length, agents });
  } catch (err) {
    return res.status(500).json({ ok: false, message: err.message });
  }
});

module.exports = router;
