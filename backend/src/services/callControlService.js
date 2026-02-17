const db = require('../db');
const freeswitch = require('../lib/freeswitch');
const { scheduleMetricsBroadcast } = require('./metricsService');

// FILE: backend/src/services/callControlService.js

// 1) Add this helper near parseSipResponse (top of file)
const toSipCode = (value) => {
  if (value == null) {
    return null;
  }
  const s = String(value).trim();
  if (!s) {
    return null;
  }
  if (/job-uuid/i.test(s)) {
    return null;
  }
  if (/^-ERR/i.test(s)) {
    return null;
  }
  const match = s.match(/\b([1-6]\d{2})\b/);
  if (!match) {
    return null;
  }
  const code = Number(match[1]);
  return Number.isInteger(code) ? code : null;
};

const parseSipResponse = (value) => {
  if (!value || typeof value !== 'string') {
    return { code: null, reason: null };
  }
  const trimmed = value.trim();
  if (!trimmed) {
    return { code: null, reason: null };
  }
  if (/job-uuid/i.test(trimmed)) {
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
  scheduleMetricsBroadcast();
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
  scheduleMetricsBroadcast();
};

// 2) Replace existing fetchCallDiagnostics with this
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

  let sipStatus =
    toSipCode(sipStatusRaw) ??
    toSipCode(lastResponseText) ??
    toSipCode(lastResponse);

  let sipReason = sipReasonRaw || null;

  // keep reason fallback, but only numeric for sipStatus
  if (!sipReason) {
    const progressDetails = parseSipResponse(lastResponseText || lastResponse);
    sipReason = progressDetails.reason || null;
    if (!sipStatus && progressDetails.code) {
      sipStatus = toSipCode(progressDetails.code);
    }
  }

  return {
    sipStatus: sipStatus || null,
    sipReason,
    hangupCause: hangupCauseRaw || null
  };
};


const getStatus = async ({ uuid, userId }) => {
  const call = await findCallByUuid(uuid, userId);
  const exists = await freeswitch.callExists(uuid);
  const diagnostics = await fetchCallDiagnostics(uuid);
  const conferenceName = call.call_uuid ? `call-${call.call_uuid}` : null;
  if (diagnostics.sipStatus || diagnostics.sipReason || diagnostics.hangupCause) {
    await updateCallDiagnostics(call.id, diagnostics);
  }

  if (!exists) {
    await updateCallCompletion(call.id, call.duration_seconds);
    const wasAnswered = Boolean(call.connected_at) || (call.duration_seconds && Number(call.duration_seconds) > 0);
    return {
      status: wasAnswered ? 'completed' : (call.status === 'completed' ? 'completed' : 'ended'),
      sipStatus: diagnostics.sipStatus ?? call.sip_status ?? null,
      sipReason: diagnostics.sipReason ?? call.sip_reason ?? null,
      hangupCause: diagnostics.hangupCause ?? call.hangup_cause ?? null,
      recordingPath: call.recording_path,
      durationSeconds: call.duration_seconds || 0,
      conferenceName
    };
  }

  const [answeredEpoch, billsec, callstate, channelState] = await Promise.all([
    freeswitch.getChannelVar(uuid, 'answered_epoch'),
    freeswitch.getChannelVar(uuid, 'billsec'),
    freeswitch.getChannelVar(uuid, 'callstate'),
    freeswitch.getChannelVar(uuid, 'channel_state')
  ]);

  const answered = (answeredEpoch && Number(answeredEpoch) > 0) ||
    (callstate && callstate.toUpperCase() === 'ACTIVE') ||
    (channelState && channelState.toUpperCase() === 'CS_EXECUTE');
  const durationSeconds = billsec ? Number(billsec) : 0;

  if (answered && !call.connected_at) {
    await db.query('UPDATE call_logs SET connected_at = NOW() WHERE id = $1', [call.id]);
    scheduleMetricsBroadcast();
  }

  let status = answered ? 'in_call' : 'ringing';
  const sipCode = diagnostics.sipStatus;
  if (!answered && sipCode) {
    if (sipCode >= 200 && sipCode < 300) {
      status = 'in_call';
    } else if (sipCode >= 400) {
      status = 'ended';
    } else if (sipCode >= 180 && sipCode < 200) {
      status = 'ringing';
    } else if (sipCode >= 100 && sipCode < 180) {
      status = 'trying';
    }
  }

  return {
    status,
    sipStatus: diagnostics.sipStatus ?? call.sip_status ?? null,
    sipReason: diagnostics.sipReason ?? call.sip_reason ?? null,
    hangupCause: diagnostics.hangupCause ?? call.hangup_cause ?? null,
    recordingPath: call.recording_path,
    durationSeconds,
    conferenceName
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

// 4) Update hangup() so it captures diagnostics in that path too
const hangup = async ({ uuid, userId }) => {
  const call = await findCallByUuid(uuid, userId);

  // capture any pre-hangup diagnostics
  const pre = await fetchCallDiagnostics(uuid);
  if (pre.sipStatus || pre.sipReason || pre.hangupCause) {
    await updateCallDiagnostics(call.id, pre);
  }

  await freeswitch.hangupCall(uuid);

  // try once more after hangup for final cause/code
  const post = await fetchCallDiagnostics(uuid);
  if (post.sipStatus || post.sipReason || post.hangupCause) {
    await updateCallDiagnostics(call.id, post);
  }

  await updateCallCompletion(call.id, call.duration_seconds);
};


const sendDtmf = async ({ uuid, digits, userId }) => {
  if (!digits) {
    throw new Error('Digits are required');
  }
  await findCallByUuid(uuid, userId);
  await freeswitch.sendDtmf(uuid, digits);
};

module.exports = {
  getStatus,
  mute,
  unmute,
  hangup,
  sendDtmf
};
