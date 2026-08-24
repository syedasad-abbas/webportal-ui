const express = require('express');
const { requireInternalToken } = require('../middleware/auth');
const inboundCallService = require('../services/inboundCallService');
const inboundDidService = require('../services/inboundDidService');

const router = express.Router();

// FreeSWITCH (Lua dialplan script) reports a new inbound call that is parked
// waiting for an agent. We kick off round-robin dispatch and return the
// conference the caller should join.
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

  // Always answer immediately with the conference so the caller can be parked
  // there; dispatch runs in the background and rings agents.
  const conference = inboundCallService.conferenceFor(uuid);

  inboundCallService
    .dispatch({ uuid, did: normalizedDid, callerIdNumber })
    .then((result) => console.log('[inbound] dispatch finished', { uuid, result }))
    .catch((err) => console.error('[inbound] dispatch error', { uuid, error: err.message }));

  return res.json({
    ok: true,
    conference,
    did: normalizedDid,
    carrierId: inboundDid.carrier_id
  });
});

// FreeSWITCH notifies that the inbound caller hung up (e.g. while we were
// still ringing agents) so we stop failover.
router.post('/inbound/:uuid/hangup', requireInternalToken, (req, res) => {
  inboundCallService.stop(req.params.uuid);
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
