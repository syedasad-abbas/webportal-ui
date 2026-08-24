const { randomUUID } = require('crypto');
const db = require('../db');
const config = require('../config');
const freeswitch = require('../lib/freeswitch');
const { emitToUser } = require('../socket');
const { scheduleMetricsBroadcast } = require('./metricsService');

const presenceMinutes = config.metrics?.presenceMinutes || 5;
// How long to ring one agent before advancing to the next.
const ringTimeoutSeconds = Number(process.env.INBOUND_RING_TIMEOUT_SECONDS) || 20;
// How many distinct agents to try before giving up on the call.
const maxAttempts = Number(process.env.INBOUND_MAX_ATTEMPTS) || 0; // 0 = try whole pool once

// Track in-flight inbound dispatches so we can stop failover when the caller hangs up.
const activeDispatches = new Map(); // inboundUuid -> { stopped }

const conferenceFor = (inboundUuid) => `in-${inboundUuid}`;

const logInboundCall = async ({ userId, callerId, did, callUuid }) => {
  await db.query(
    `INSERT INTO call_logs (user_id, direction, destination, caller_id, status, call_uuid, created_at, updated_at)
     VALUES ($1, 'inbound', $2, $3, 'queued', $4, NOW(), NOW())`,
    [userId, did || null, callerId || null, callUuid]
  );
  scheduleMetricsBroadcast();
};

const markCallAnswered = async (callUuid, userId) => {
  await db.query(
    `UPDATE call_logs
        SET connected_at = COALESCE(connected_at, NOW()),
            status = 'in_call',
            user_id = $2,
            updated_at = NOW()
      WHERE call_uuid = $1`,
    [callUuid, userId]
  );
  scheduleMetricsBroadcast();
};

const markCallEnded = async (callUuid, finalStatus) => {
  await db.query(
    `UPDATE call_logs
        SET ended_at = COALESCE(ended_at, NOW()),
            status = $2,
            updated_at = NOW()
      WHERE call_uuid = $1 AND ended_at IS NULL`,
    [callUuid, finalStatus]
  );
  scheduleMetricsBroadcast();
};

// Online agents = users with a recent session AND a SIP credential (so we can ring them).
const getOnlineAgents = async () => {
  const result = await db.query(
    `SELECT DISTINCT u.id,
            u.external_name,
            u.email,
            sc.sip_username
       FROM sessions s
       JOIN users u ON u.id = s.user_id
       JOIN sip_credentials sc ON sc.user_id = u.id
      WHERE s.user_id IS NOT NULL
        AND sc.sip_username IS NOT NULL
        AND sc.sip_username <> ''
        AND s.last_activity >= EXTRACT(EPOCH FROM NOW())::int - ($1 * 60)
      ORDER BY u.id`,
    [presenceMinutes]
  );
  return result.rows;
};

// Round-robin cursor persisted in inbound_rr_state (single row).
const getCursor = async () => {
  const result = await db.query('SELECT id, last_user_id FROM inbound_rr_state ORDER BY id LIMIT 1');
  if (result.rowCount === 0) {
    const inserted = await db.query(
      'INSERT INTO inbound_rr_state (last_user_id, created_at, updated_at) VALUES (NULL, NOW(), NOW()) RETURNING id, last_user_id'
    );
    return inserted.rows[0];
  }
  return result.rows[0];
};

const setCursor = async (id, lastUserId) => {
  await db.query('UPDATE inbound_rr_state SET last_user_id = $2, updated_at = NOW() WHERE id = $1', [
    id,
    lastUserId
  ]);
};

// Order the pool starting just after the last-served agent, wrapping around.
const orderPoolRoundRobin = (agents, lastUserId) => {
  if (!lastUserId) {
    return agents;
  }
  const idx = agents.findIndex((a) => a.id === lastUserId);
  if (idx === -1) {
    return agents;
  }
  return [...agents.slice(idx + 1), ...agents.slice(0, idx + 1)];
};

// Originate a leg to one agent's registered WebRTC contact, landing in the shared conference.
const ringAgent = async ({ inboundUuid, agent, conferenceName }) => {
  const legUuid = randomUUID();
  // Registered WebRTC users belong to FreeSWITCH's directory domain (normally
  // the host's LAN IP).  The carrier-facing/public SIP IP is not necessarily a
  // directory domain and must not be used for user lookups.
  const domain =
    config.freeswitch.directoryDomain ||
    (await freeswitch.getGlobalVar('domain')) ||
    config.freeswitch.externalSipIp ||
    config.freeswitch.host;
  const dialString = `user/${agent.sip_username}@${domain}`;
  const variables = [
    `origination_uuid=${legUuid}`,
    `originate_timeout=${ringTimeoutSeconds}`,
    'ignore_early_media=true',
    'hangup_after_bridge=false',
    `inbound_uuid=${inboundUuid}`,
    `leg_for_user=${agent.id}`
  ];

  const response = await freeswitch.originateCall({
    endpoint: dialString,
    variables,
    application: `&conference(${conferenceName}@default)`
  });
  return { legUuid, response };
};

// Watch one agent leg. Resolve 'answered' | 'failed'.
const watchLeg = (legUuid, inboundUuid) =>
  new Promise((resolve) => {
    let settled = false;
    const finish = (result) => {
      if (!settled) {
        settled = true;
        resolve(result);
      }
    };
    const started = Date.now();
    const poll = async () => {
      const dispatch = activeDispatches.get(inboundUuid);
      if (!dispatch || dispatch.stopped) {
        return finish('failed');
      }
      // Hard cap so we never poll forever.
      if (Date.now() - started > (ringTimeoutSeconds + 15) * 1000) {
        return finish('failed');
      }
      try {
        const exists = await freeswitch.callExists(legUuid);
        if (!exists) {
          return finish('failed');
        }
        const answeredEpoch = await freeswitch.getChannelVar(legUuid, 'answered_epoch');
        if (answeredEpoch && Number(answeredEpoch) > 0) {
          return finish('answered');
        }
      } catch (err) {
        return finish('failed');
      }
      setTimeout(poll, 1000);
    };
    setTimeout(poll, 800);
  });

// Main entry: an inbound caller is parked in `conferenceName`; ring agents round-robin.
const dispatch = async ({ uuid, did, callerIdNumber }) => {
  const conferenceName = conferenceFor(uuid);
  activeDispatches.set(uuid, { stopped: false });

  const agents = await getOnlineAgents();
  if (agents.length === 0) {
    console.warn('[inbound] no online agents', { uuid });
    activeDispatches.delete(uuid);
    return { ok: false, reason: 'no_agents', conference: conferenceName };
  }

  const cursor = await getCursor();
  const pool = orderPoolRoundRobin(agents, cursor.last_user_id);
  const attempts = maxAttempts > 0 ? Math.min(maxAttempts, pool.length) : pool.length;

  // Log the inbound call once (assigned to the first agent we try; updated on answer).
  await logInboundCall({ userId: pool[0].id, callerId: callerIdNumber, did, callUuid: uuid });

  console.log('[inbound] dispatching', {
    uuid,
    did,
    callerIdNumber,
    pool: pool.map((a) => a.sip_username),
    attempts
  });

  for (let i = 0; i < attempts; i += 1) {
    const state = activeDispatches.get(uuid);
    if (!state || state.stopped) {
      break;
    }
    const agent = pool[i];
    try {
      const { legUuid } = await ringAgent({ inboundUuid: uuid, agent, conferenceName });
      state.currentLegUuid = legUuid;
      state.currentUserId = agent.id;
      emitToUser(agent.id, 'incoming.call', {
        callUuid: uuid,
        legUuid,
        conference: conferenceName,
        callerIdNumber: callerIdNumber || null,
        did: did || null,
        agent: { id: agent.id, name: agent.external_name || agent.email }
      });
      console.log('[inbound] ringing agent', { uuid, agent: agent.sip_username, legUuid });

      // Advance the cursor to this agent so the next call starts after them.
      await setCursor(cursor.id, agent.id);

      const outcome = await watchLeg(legUuid, uuid);
      if (outcome === 'answered') {
        console.log('[inbound] answered', { uuid, agent: agent.sip_username });
        await markCallAnswered(uuid, agent.id);
        activeDispatches.delete(uuid);
        return { ok: true, answeredBy: agent.id, conference: conferenceName };
      }
      console.log('[inbound] no answer, advancing', { uuid, agent: agent.sip_username });
      emitToUser(agent.id, 'incoming.call.cancel', { callUuid: uuid, legUuid });
    } catch (err) {
      console.error('[inbound] ring failed', { uuid, agent: agent.sip_username, error: err.message });
      emitToUser(agent.id, 'incoming.call.cancel', { callUuid: uuid });
    }
  }

  console.warn('[inbound] exhausted agents', { uuid });
  await markCallEnded(uuid, 'missed');
  activeDispatches.delete(uuid);
  return { ok: false, reason: 'no_answer', conference: conferenceName };
};

// Called when the caller hangs up while we are still hunting for an agent.
const stop = (uuid) => {
  const state = activeDispatches.get(uuid);
  if (state) {
    state.stopped = true;
  }
};

// An agent explicitly declined the ringing call: kill the current leg so
// watchLeg resolves 'failed' and dispatch advances to the next agent.
const decline = async (uuid, userId) => {
  const state = activeDispatches.get(uuid);
  if (!state || state.stopped) {
    return { ok: false, reason: 'not_ringing' };
  }
  if (state.currentUserId !== userId) {
    return { ok: false, reason: 'not_current_agent' };
  }
  const leg = state.currentLegUuid;
  if (leg) {
    try {
      await freeswitch.hangupCall(leg);
    } catch (err) {
      console.warn('[inbound] decline hangup failed', { uuid, leg, error: err.message });
    }
  }
  emitToUser(userId, 'incoming.call.cancel', { callUuid: uuid, legUuid: leg });
  return { ok: true };
};

module.exports = {
  dispatch,
  stop,
  decline,
  conferenceFor,
  getOnlineAgents
};
