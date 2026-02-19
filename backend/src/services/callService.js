const { randomUUID } = require('crypto');
const db = require('../db');
const freeswitch = require('../lib/freeswitch');
const { normalizeGatewayName } = require('../lib/carrierUtils');
const config = require('../config');
const { scheduleMetricsBroadcast } = require('./metricsService');

const clientError = (message, statusCode = 400) => {
  const err = new Error(message);
  err.statusCode = statusCode;
  return err;
};

const toSipCode = (value) => {
  if (value == null) return null;
  const s = String(value).trim();
  if (!s) return null;
  if (/job-uuid/i.test(s)) return null;
  if (/^-ERR/i.test(s)) return null;

  const m = s.match(/\b([1-6]\d{2})\b/); // 100..699
  if (!m) return null;

  const code = Number(m[1]);
  return Number.isInteger(code) ? code : null;
};

const fetchCallDiagnostics = async (uuid) => {
  const [sipTermStatus, sipTermPhrase, hangupCause, sipLastResponse, sipLastResponseText] = await Promise.all([
    freeswitch.getChannelVar(uuid, 'sip_term_status'),
    freeswitch.getChannelVar(uuid, 'sip_term_phrase'),
    freeswitch.getChannelVar(uuid, 'hangup_cause'),
    freeswitch.getChannelVar(uuid, 'sip_last_response'),
    freeswitch.getChannelVar(uuid, 'sip_last_response_text')
  ]);

  const sipStatus =
    toSipCode(sipTermStatus) ??
    toSipCode(sipLastResponseText) ??
    toSipCode(sipLastResponse);

  return {
    sipStatus: sipStatus || null,
    sipReason: sipTermPhrase || sipLastResponseText || null,
    hangupCause: hangupCause || null
  };
};

const persistDiagnosticsByUuid = async ({ callUuid, userId, diagnostics }) => {
  if (!diagnostics?.sipStatus && !diagnostics?.sipReason && !diagnostics?.hangupCause) return;

  await db.query(
    `UPDATE call_logs
       SET sip_status = COALESCE($1, sip_status),
           sip_reason = COALESCE($2, sip_reason),
           hangup_cause = COALESCE($3, hangup_cause),
           updated_at = NOW()
     WHERE call_uuid = $4
       AND user_id = $5`,
    [diagnostics.sipStatus, diagnostics.sipReason, diagnostics.hangupCause, callUuid, userId]
  );
  scheduleMetricsBroadcast();
};


const normalizeDestination = (destination) => {
  if (!destination) {
    return destination;
  }
  const digits = destination.toString().replace(/\D+/g, '');
  const isUsNumber = digits.length === 10 || (digits.length === 11 && digits.startsWith('1'));
  if (!isUsNumber) {
    return null;
  }
  return digits.startsWith('1') ? `+${digits}` : `+1${digits}`;
};

const normalizeCallerId = (callerId) => {
  if (!callerId) {
    return null;
  }
  const digits = callerId.toString().replace(/\D+/g, '');
  if (!digits) {
    return null;
  }
  if (digits.length === 10) {
    return `+1${digits}`;
  }
  if (digits.length === 11 && digits.startsWith('1')) {
    return `+${digits}`;
  }
  return `+${digits}`;
};

const stripPlus = (value) => {
  if (!value) {
    return value;
  }
  return value.toString().replace(/^\+/, '');
};

const logCall = async ({ userId, destination, callerId, status, recordingPath, callUuid, connectedAt, endedAt }) => {
  await db.query(
    `INSERT INTO call_logs (user_id, destination, caller_id, status, recording_path, call_uuid, connected_at, ended_at, created_at, updated_at)
     VALUES ($1, $2, $3, $4, $5, $6, $7, $8, NOW(), NOW())`,
    [userId, destination, callerId, status, recordingPath, callUuid, connectedAt || null, endedAt || null]
  );
  scheduleMetricsBroadcast();
};


const selectCarrierPrefix = (prefixes) => {
  if (!Array.isArray(prefixes) || prefixes.length === 0) {
    return null;
  }
  return prefixes.find((entry) => entry.prefix) || null;
};

const applyDialPrefix = (destinationDigits, prefixEntry) => {
  if (!prefixEntry?.prefix) {
    return destinationDigits;
  }
  const prefixDigits = prefixEntry.prefix.toString().replace(/\D+/g, '');
  return `${prefixDigits}${destinationDigits}`;
};

const monitorCallProgress = async ({ callUuid, conferenceName, userId }) => {
  const start = Date.now();
  const timeoutMs = 2 * 60 * 1000;
  let answeredLogged = false;
  let conferenceLogged = false;

  const markAnswered = async () => {
    await db.query(
      `UPDATE call_logs
       SET connected_at = COALESCE(connected_at, NOW()),
           status = COALESCE(status, 'in_call'),
           updated_at = NOW()
       WHERE call_uuid = $1 AND user_id = $2 AND connected_at IS NULL`,
      [callUuid, userId]
    );
    scheduleMetricsBroadcast();
  };

  const markEnded = async (diagnostics = null) => {
    await db.query(
      `UPDATE call_logs
         SET ended_at = COALESCE(ended_at, NOW()),
             status = CASE WHEN connected_at IS NULL THEN 'ended' ELSE 'completed' END,
             sip_status = COALESCE($3, sip_status),
             sip_reason = COALESCE($4, sip_reason),
             hangup_cause = COALESCE($5, hangup_cause),
             updated_at = NOW()
       WHERE call_uuid = $1
         AND user_id = $2
         AND ended_at IS NULL`,
      [
        callUuid,
        userId,
        diagnostics?.sipStatus ?? null,
        diagnostics?.sipReason ?? null,
        diagnostics?.hangupCause ?? null
      ]
    );
    scheduleMetricsBroadcast();
  };

  const poll = async () => {
    if (Date.now() - start > timeoutMs) {
      return;
    }

    const diagnostics = await fetchCallDiagnostics(callUuid);
    await persistDiagnosticsByUuid({ callUuid, userId, diagnostics });

    const exists = await freeswitch.callExists(callUuid);
    if (!exists) {
      await markEnded(diagnostics);
      return;
    }

    if (!answeredLogged) {
      const answeredEpoch = await freeswitch.getChannelVar(callUuid, 'answered_epoch');
      if (answeredEpoch && Number(answeredEpoch) > 0) {
        console.log('[call] answered', { userId, callUuid });
        answeredLogged = true;
        await markAnswered();
      }
    }

    if (!conferenceLogged) {
      const [confName, confMemberId] = await Promise.all([
        freeswitch.getChannelVar(callUuid, 'conference_name'),
        freeswitch.getChannelVar(callUuid, 'conference_member_id')
      ]);
      if ((confName && confName === conferenceName) || (confMemberId && Number(confMemberId) > 0)) {
        console.log('[call] joined conference', { userId, callUuid, conference: conferenceName });
        conferenceLogged = true;
      }
      if (!answeredLogged) {
          answeredLogged = true;
          await markAnswered();
        }
    }

    setTimeout(poll, 1500);
  };

  setTimeout(() => {
    poll().catch((err) => {
      console.warn('[call] monitor failed', { userId, callUuid, error: err.message });
    });
  }, 1500);
};

const originate = async ({ user, destination, callerId }) => {
  const originationUuid = randomUUID();
  const conferenceName = `call-${originationUuid}`;
  const normalizedDestination = normalizeDestination(destination);
  if (!normalizedDestination) {
    throw clientError('Only US/CA destinations (+1) are allowed', 400);
  }
  const userResult = await db.query(
    `SELECT users.id,
            users.carrier_id,
            users.full_name,
            users.recording_enabled,
            carriers.default_caller_id,
            carriers.caller_id_required,
            carriers.sip_domain,
            carriers.sip_port,
            carriers.transport,
            carriers.outbound_proxy,
            carriers.registration_required,
            carriers.registration_username,
            carriers.registration_password
     FROM users
     LEFT JOIN carriers ON carriers.id = users.carrier_id
     WHERE users.id = $1`,
    [user.id]
  );

  if (userResult.rowCount === 0) {
    throw new Error('User not found');
  }

  const record = userResult.rows[0];
  const fallbackCallerId = config.defaults.carrierCallerId || null;
  let prefixEntries = [];
  if (record.carrier_id) {
    const prefixResult = await db.query(
      `SELECT prefix, caller_id
         FROM carrier_prefixes
        WHERE carrier_id = $1
        ORDER BY created_at ASC`,
      [record.carrier_id]
    );
    prefixEntries = prefixResult.rows || [];
  }
  const selectedPrefix = selectCarrierPrefix(prefixEntries, normalizedDestination);
  const destinationDigits = stripPlus(normalizedDestination);
  const prefixedDestination = applyDialPrefix(destinationDigits, selectedPrefix);
  const resolvedCallerId = record.default_caller_id || callerId || fallbackCallerId || null;
  const callerIdUser = normalizeCallerId(resolvedCallerId);
  const callerIdSipUser = stripPlus(callerIdUser);
  const recordingEnabled = record.recording_enabled;
  const recordingPath = recordingEnabled
    ? `${config.freeswitch.recordingsPath}/${user.id}-${Date.now()}.wav`
    : null;

  if (!record.sip_domain) {
    throw new Error('Carrier domain is not configured');
  }

  const domainPart = record.sip_port ? `${record.sip_domain}:${record.sip_port}` : record.sip_domain;
  const outboundProxy = typeof record.outbound_proxy === 'string' ? record.outbound_proxy.trim() : record.outbound_proxy;
  const useGateway = Boolean(record.registration_required || outboundProxy);
  const gatewayName = useGateway ? normalizeGatewayName({ id: record.carrier_id }) : null;
  if (useGateway && !gatewayName) {
    throw new Error('Carrier gateway is not configured');
  }
  const preferredFromHost = config.freeswitch.externalSipIp || config.freeswitch.host || record.sip_domain || null;
  const fromHostBase = preferredFromHost;
  const fromHostWithPort = fromHostBase && record.sip_port
    ? `${fromHostBase}:${record.sip_port}`
    : fromHostBase;
  const toUser = useGateway ? (prefixedDestination || destinationDigits) : prefixedDestination || destinationDigits;
  const requestUser = toUser;
  const endpoint = useGateway ? null : `sofia/external/${requestUser}@${domainPart}`;
  const transport = (record.transport || 'udp').toLowerCase();
  const channelVars = [
    `sip_transport=${transport}`,
    `origination_uuid=${originationUuid}`,
    `sip_req_user=${requestUser}`,
    `sip_to_user=${toUser}`
  ];
  if (useGateway && record.sip_domain) {
    channelVars.push(`sip_req_host=${record.sip_domain}`);
    if (record.sip_port) {
      channelVars.push(`sip_req_port=${record.sip_port}`);
    }
  }
  if (!useGateway) {
    channelVars.push(`sip_to_host=${domainPart}`);
  }
  if (callerIdSipUser) {
    channelVars.push(`sip_from_user=${callerIdSipUser}`);
    if (fromHostBase) {
      channelVars.push(`sip_from_host=${fromHostBase}`);
    }
    if (useGateway && record.sip_port) {
      channelVars.push(`sip_from_port=${record.sip_port}`);
    }
    if (fromHostWithPort) {
      const pai = `<sip:${callerIdSipUser}@${fromHostWithPort}>`;
      channelVars.push(`sip_from_uri=sip:${callerIdSipUser}@${fromHostWithPort}`);
      channelVars.push(`sip_h_P-Asserted-Identity=${pai}`);
      channelVars.push(`sip_h_P-Preferred-Identity=${pai}`);
    }
  }
  // Disable Remote-Party-ID to avoid carriers that reject it.
  channelVars.push('sip_rpid_type=none');
  if (record.registration_username) {
    channelVars.push(`sip_auth_username=${record.registration_username}`);
  }
  if (record.registration_password) {
    channelVars.push(`sip_auth_password=${record.registration_password}`);
  }

  let jobUuid = null;
  try {
    console.log('[call] originate', {
      userId: user.id,
      destination: normalizedDestination,
      requestUser,
      toUser,
      callerId: resolvedCallerId,
      sipHost: domainPart,
      transport
    });
    const originateResult = await freeswitch.originateCall({
      endpoint,
      callerId: callerIdSipUser,
      recordingPath,
      variables: channelVars,
      gateway: useGateway ? gatewayName : null,
      destination: useGateway ? toUser : null,
      application: `&conference(${conferenceName}@default)`
    });
    jobUuid = originateResult.jobUuid || originationUuid;
    await logCall({
      userId: user.id,
      destination: normalizedDestination,
      callerId: resolvedCallerId,
      status: 'queued',
      recordingPath,
      callUuid: originationUuid
    });
    monitorCallProgress({ callUuid: originationUuid, conferenceName, userId: user.id });
    return { status: 'queued', callUuid: originationUuid, conference: conferenceName };
  } catch (err) {
    console.error('[call] originate failed', {
      userId: user.id,
      destination: normalizedDestination,
      requestUser,
      toUser,
      callerId: resolvedCallerId,
      sipHost: domainPart,
      transport,
      error: err.message
    });
    await logCall({
      userId: user.id,
      destination: normalizedDestination,
      callerId: resolvedCallerId,
      status: 'failed',
      recordingPath,
      callUuid: originationUuid,
      endedAt: new Date()
    });
    const failCode = toSipCode(err.message);
    if (failCode) {
      await db.query(
        `UPDATE call_logs
           SET sip_status = COALESCE($1, sip_status),
               sip_reason = COALESCE($2, sip_reason),
               updated_at = NOW()
         WHERE call_uuid = $3
           AND user_id = $4`,
        [failCode, err.message, originationUuid, user.id]
      );
    }
    throw err;
  }
};

module.exports = {
  originate
};
