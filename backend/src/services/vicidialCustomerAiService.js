// Separate per-customer endpoint. Never originates, redirects or kills calls.
// The SIP dialplan owns this leg and must create a NEW leg for each customer.
const customerAgents = () => new Set((process.env.VICIDIAL_CUSTOMER_AI_AGENTS || '')
  .split(',').map((s) => s.trim()).filter((s) => /^[0-9]{1,20}$/.test(s)));
// A VM's SIP traffic may be source-NATed while its worker uses another path.
// Keep an exact source match without changing the conference worker's address.
const customerSourceIp = (env = process.env) =>
  env.VICIDIAL_CUSTOMER_AI_SOURCE_IP || env.VICIDIAL_AI_SERVER_IP || '';
const db = require('../db');
const config = require('../config');

function createCustomerService({ enabled, allowedAgents, sourceIp, settings, channelVar,
  start, record, connected, close, now = Date.now, startupMs = 75000 }) {
  const sessions = new Map();
  const locks = new Map();
  const eligible = async () => {
    const s = await settings();
    return enabled() && s?.enabled && s.ready && s.updatedBy && ['inbound', 'both'].includes(s.callDirection);
  };
  const validUuid = (id) => /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(id || '');
  async function channelVars(uuid) {
    const [source, destination, agent, customer] = await Promise.all([
      'sip_network_ip', 'destination_number', 'sip_h_X-Vici-AI-Agent', 'sip_h_X-Vici-Customer-ID'
    ].map((name) => channelVar(uuid, name)));
    return { source, destination, agent, customer };
  }
  async function ensureCallLog(uuid, agent, customer, destination) {
    let userId = null;
    let recordingEnabled = true;
    if (agent) {
      const row = await db.query(
        `SELECT vc.user_id, u.recording_enabled
         FROM vicidial_credentials vc
         LEFT JOIN users u ON u.id = vc.user_id
         WHERE vc.agent_user = $1 LIMIT 1`,
        [agent]
      );
      userId = row.rows[0]?.user_id ?? null;
      recordingEnabled = row.rows[0]?.recording_enabled ?? true;
    }
    const customerDigits = String(customer || '').replace(/\D/g, '') || uuid.slice(0, 8);
    const recordingsPath = config.freeswitch.recordingsPath || '/var/recordings';
    const recordingPath = recordingEnabled
      ? `${recordingsPath.replace(/\/$/, '')}/${customerDigits}-${Date.now()}.wav`
      : null;
    const existing = await db.query('SELECT id FROM call_logs WHERE call_uuid = $1', [uuid]);
    const connectedAt = new Date().toISOString().slice(0, 19).replace('T', ' ');
    if (existing.rows.length === 0) {
      await db.query(
        `INSERT INTO call_logs (user_id, call_uuid, caller_id, destination, status, recording_path, connected_at)
         VALUES ($1, $2, $3, $4, 'in_call', $5, $6)`,
        [userId, uuid, customer, destination, recordingPath, connectedAt]
      );
    } else {
      await db.query(
        `UPDATE call_logs SET caller_id = COALESCE($3, caller_id), destination = COALESCE($4, destination),
         status = 'in_call', connected_at = COALESCE(connected_at, $5), recording_path = COALESCE($6, recording_path)
         WHERE call_uuid = $1 AND ended_at IS NULL`,
        [uuid, customer, destination, connectedAt, recordingPath]
      );
    }
    return recordingPath;
  }
  async function saveTranscript(uuid, transcript) {
    await db.query(
      'UPDATE call_logs SET transcript = $1 WHERE call_uuid = $2 AND ended_at IS NULL',
      [transcript, uuid]
    );
  }
  async function authorize(uuid) {
    if (!validUuid(uuid) || !enabled() || !sourceIp()) return false;
    const { source, destination, agent, customer } = await channelVars(uuid);
    if (source !== sourceIp() || destination !== 'vici-ai-customer'
      || !allowedAgents().has(agent) || !/^[0-9]+\.[0-9]+$/.test(customer || '')) return false;
    return Boolean(await eligible());
  }
  async function begin(uuid) {
    if (!validUuid(uuid)) return false;
    if (locks.has(uuid)) return locks.get(uuid);
    const task = (async () => {
      try {
        if (!(await authorize(uuid))) return false;
        const { agent, customer, destination } = await channelVars(uuid);
        const recordingPath = await ensureCallLog(uuid, agent, customer, destination);
        if (recordingPath && record) {
          try { await record(uuid, recordingPath); } catch (err) {
            console.error('[vicidial-ai] recording start failed', { uuid, error: err.message });
          }
        }
        if (sessions.has(uuid)) return !sessions.get(uuid).failed;
        const sessionRecord = { deadline: now() + startupMs, expires: now() + 15000, ready: false, failed: false };
        sessions.set(uuid, sessionRecord);
        Promise.resolve().then(() => start(uuid)).then((ready) => {
          if (sessions.get(uuid) !== sessionRecord) { close(uuid); return; }
          sessionRecord.ready = Boolean(ready); sessionRecord.failed = !ready;
          if (!ready) close(uuid);
        }).catch(() => { sessionRecord.failed = true; close(uuid); });
        return true;
      } catch (err) {
        console.error('[vicidial-ai] begin failed', { uuid, error: err.message });
        close(uuid);
        return false;
      }
    })();
    locks.set(uuid, task);
    try { return await task; } finally { locks.delete(uuid); }
  }
  function end(uuid) {
    if (!validUuid(uuid) || !sessions.has(uuid)) return;
    sessions.delete(uuid); close(uuid);
  }
  async function lease(uuid) {
    const s = sessions.get(uuid);
    if (!s) return false;
    if (now() >= s.expires || s.failed || !(await eligible()) || (s.ready ? !connected(uuid) : now() >= s.deadline)) {
      end(uuid); return false;
    }
    s.expires = now() + 15000;
    return true;
  }
  function reap() {
    for (const [uuid, s] of sessions) if (now() >= s.expires) end(uuid);
  }
  return { authorize, begin, lease, end, reap, saveTranscript };
}
module.exports = { createCustomerService, customerAgents, customerSourceIp };
