// Separate per-customer endpoint. Never originates, redirects or kills calls.
// The SIP dialplan owns this leg and must create a NEW leg for each customer.
const customerAgents = () => new Set((process.env.VICIDIAL_CUSTOMER_AI_AGENTS || '')
  .split(',').map((s) => s.trim()).filter((s) => /^[0-9]{1,20}$/.test(s)));
// A VM's SIP traffic may be source-NATed while its worker uses another path.
// Keep an exact source match without changing the conference worker's address.
const customerSourceIp = (env = process.env) =>
  env.VICIDIAL_CUSTOMER_AI_SOURCE_IP || env.VICIDIAL_AI_SERVER_IP || '';

function createCustomerService({ enabled, allowedAgents, sourceIp, settings, channelVar,
  start, connected, close, now = Date.now, startupMs = 75000 }) {
  const sessions = new Map();
  const locks = new Map();
  const eligible = async () => {
    const s = await settings();
    return enabled() && s?.enabled && s.ready && s.updatedBy && ['inbound', 'both'].includes(s.callDirection);
  };
  const validUuid = (id) => /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(id || '');
  async function authorize(uuid) {
    if (!validUuid(uuid) || !enabled() || !sourceIp()) return false;
    const [source, destination, agent, customer] = await Promise.all([
      'sip_network_ip', 'destination_number', 'sip_h_X-Vici-AI-Agent', 'sip_h_X-Vici-Customer-ID'
    ].map((name) => channelVar(uuid, name)));
    if (source !== sourceIp() || destination !== 'vici-ai-customer'
      || !allowedAgents().has(agent) || !/^[0-9]+\.[0-9]+$/.test(customer || '')) return false;
    return Boolean(await eligible());
  }
  async function begin(uuid) {
    if (!validUuid(uuid)) return false;
    if (locks.has(uuid)) return locks.get(uuid);
    const task = (async () => {
      if (!(await authorize(uuid))) return false;
      if (sessions.has(uuid)) return !sessions.get(uuid).failed;
      const record = { deadline: now() + startupMs, expires: now() + 15000, ready: false, failed: false };
      sessions.set(uuid, record);
      Promise.resolve().then(() => start(uuid)).then((ready) => {
        if (sessions.get(uuid) !== record) { close(uuid); return; }
        record.ready = Boolean(ready); record.failed = !ready;
        if (!ready) close(uuid);
      }).catch(() => { record.failed = true; close(uuid); });
      return true;
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
  return { authorize, begin, lease, end, reap };
}
module.exports = { createCustomerService, customerAgents, customerSourceIp };
