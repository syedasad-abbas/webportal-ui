const db = require('../db');
const freeswitch = require('../lib/freeswitch');

const parseSipResponse = (value) => {
  if (!value || typeof value !== 'string') {
    return { code: null, reason: null };
  }
  const trimmed = value.trim();
  if (!trimmed) {
    return { code: null, reason: null };
  }
  const match = trimmed.match(/(\d{3})\s*(.*)/);
  if (match) {
    const code = Number(match[1]);
    return {
      code: Number.isNaN(code) ? null : code,
      reason: match[2]?.trim() || null
    };
  }
  const numeric = Number(trimmed);
  if (!Number.isNaN(numeric)) {
    return { code: numeric, reason: null };
  }
  return { code: null, reason: trimmed };
};

const findCallByUuid = async (uuid, userId) => {
  const result = await db.query(
    'SELECT * FROM call_logs WHERE call_uuid = $1 AND user_id = $2 ORDER BY created_at DESC LIMIT 1',
    [uuid, userId]
  );
  if (result.rowCount === 0) {
    throw new Error('Call not found');
  }
  return result.rows[0];
};

const updateCallCompletion = async (callId, durationSeconds) => {
  await db.query(
    `UPDATE call_logs
     SET status = 'completed',
         ended_at = COALESCE(ended_at, NOW()),
         duration_seconds = COALESCE(duration_seconds, $1)
     WHERE id = $2`,
    [durationSeconds || null, callId]
  );
};

const updateCallDiagnostics = async (callId, diagnostics) => {
  await db.query(
    `UPDATE call_logs
     SET sip_status = COALESCE($1, sip_status),
         sip_reason = COALESCE($2, sip_reason),
         hangup_cause = COALESCE($3, hangup_cause)
     WHERE id = $4`,
    [
      diagnostics.sipStatus ?? null,
      diagnostics.sipReason ?? null,
      diagnostics.hangupCause ?? null,
      callId
    ]
  );
};

const fetchCallDiagnostics = async (uuid) => {
  const [
    sipStatusRaw,
    sipReasonRaw,
    hangupCauseRaw,
    lastResponse,
    lastResponseText
  ] = await Promise.all([
    freeswitch.getChannelVar(uuid, 'sip_term_status'),
    freeswitch.getChannelVar(uuid, 'sip_term_phrase'),
    freeswitch.getChannelVar(uuid, 'hangup_cause'),
    freeswitch.getChannelVar(uuid, 'sip_last_response'),
    freeswitch.getChannelVar(uuid, 'sip_last_response_text')
  ]);

  let sipStatus = sipStatusRaw && !Number.isNaN(Number(sipStatusRaw))
    ? Number(sipStatusRaw)
    : null;
  let sipReason = sipReasonRaw || null;
  const progressDetails = parseSipResponse(lastResponseText || lastResponse);
  if (!sipStatus && progressDetails.code) {
    sipStatus = progressDetails.code;
  }
  if (!sipReason && progressDetails.reason) {
    sipReason = progressDetails.reason;
  }
  return {
    sipStatus,
    sipReason: sipReason || null,
    hangupCause: hangupCauseRaw || null
  };
};

const getStatus = async ({ uuid, userId }) => {
  const call = await findCallByUuid(uuid, userId);
  const exists = await freeswitch.callExists(uuid);
  const diagnostics = await fetchCallDiagnostics(uuid);
  if (diagnostics.sipStatus || diagnostics.sipReason || diagnostics.hangupCause) {
    await updateCallDiagnostics(call.id, diagnostics);
  }

  if (!exists) {
    await updateCallCompletion(call.id, call.duration_seconds);
    return {
      status: call.status === 'completed' ? 'completed' : 'ended',
      sipStatus: diagnostics.sipStatus ?? call.sip_status ?? null,
      sipReason: diagnostics.sipReason ?? call.sip_reason ?? null,
      hangupCause: diagnostics.hangupCause ?? call.hangup_cause ?? null,
      recordingPath: call.recording_path,
      durationSeconds: call.duration_seconds || 0
    };
  }

  const answeredEpoch = await freeswitch.getChannelVar(uuid, 'answered_epoch');
  const billsec = await freeswitch.getChannelVar(uuid, 'billsec');

  const answered = answeredEpoch && Number(answeredEpoch) > 0;
  const durationSeconds = billsec ? Number(billsec) : 0;

  if (answered && !call.connected_at) {
    await db.query('UPDATE call_logs SET connected_at = NOW() WHERE id = $1', [call.id]);
  }

  return {
    status: answered ? 'in_call' : 'ringing',
    sipStatus: diagnostics.sipStatus ?? call.sip_status ?? null,
    sipReason: diagnostics.sipReason ?? call.sip_reason ?? null,
    hangupCause: diagnostics.hangupCause ?? call.hangup_cause ?? null,
    recordingPath: call.recording_path,
    durationSeconds
  };
};

const mute = async ({ uuid, userId }) => {
  await findCallByUuid(uuid, userId);
  await freeswitch.muteCall(uuid);
};

const unmute = async ({ uuid, userId }) => {
  await findCallByUuid(uuid, userId);
  await freeswitch.unmuteCall(uuid);
};

const hangup = async ({ uuid, userId }) => {
  const call = await findCallByUuid(uuid, userId);
  await freeswitch.hangupCall(uuid);
  await updateCallCompletion(call.id, call.duration_seconds);
};

module.exports = {
  getStatus,
  mute,
  unmute,
  hangup
};
