const crypto = require('crypto');

// Reservations are capabilities for ONE AI leg, never a customer/agent UUID.
// A missing snapshot, disabled AI, or backend restart fails closed.
function createService({ settings, mappedAgents, start, stop, connected = () => true, now = Date.now, leaseMs = 15000 }) {
  const calls = new Map();
  const tokens = new Map();
  let queue = Promise.resolve();
  const eligible = (s) => s?.enabled && s.ready && s.updatedBy && ['inbound', 'both'].includes(s.callDirection);
  const keyOf = (c) => `${c.agent}:${c.conference}:${c.callId}`;
  const live = (c) => c && !c.closed && now() < c.expires;
  const close = (c) => {
    c.closed = true;
    if (c.uuid) return stop(c.uuid).catch(() => {});
    return Promise.resolve();
  };
  const serialize = (fn) => {
    const work = queue.then(fn);
    queue = work.catch(() => {});
    return work;
  };

  async function sync(snapshot) {
    return serialize(async () => {
      const [s, agents] = await Promise.all([settings(), mappedAgents()]);
      const allowed = new Set(agents);
      const seen = new Set();
      const conferences = new Set();
      const joins = [];
      for (const row of snapshot) {
        const key = keyOf(row);
        seen.add(key);
        if (!eligible(s) || !allowed.has(row.agent) || conferences.has(row.conference)) continue;
        conferences.add(row.conference);
        let call = calls.get(key);
        // Never resurrect an ended/failed call, including an expired lease.
        if (call && !live(call)) { await close(call); continue; }
        if (!call) {
          call = { ...row, key, token: crypto.randomBytes(32).toString('hex'), expires: now() + leaseMs };
          calls.set(key, call);
          tokens.set(call.token, call);
        }
        call.expires = now() + leaseMs;
        // Re-delivery is safe: the worker persists attempts BEFORE Originate.
        if (!call.uuid) joins.push({ ...row, token: call.token });
      }
      for (const [key, call] of calls) {
        if (!seen.has(key) || !eligible(s) || !allowed.has(call.agent)) await close(call);
        // Keep tombstones for the entire live call, release once absent.
        if (!seen.has(key)) { calls.delete(key); tokens.delete(call.token); }
      }
      return joins;
    });
  }

  async function claim(token, uuid) {
    const call = tokens.get(token);
    if (!live(call) || call.uuid) return false;
    // Set synchronously before any async work: duplicate SIP legs cannot claim.
    call.uuid = uuid;
    return true;
  }

  async function lease(token, uuid, begin = false) {
    const call = tokens.get(token);
    if (!live(call) || call.uuid !== uuid) return false;
    const s = await settings();
    if (!live(call) || !eligible(s)) { await close(call); return false; }
    if (call.ready && !connected(uuid)) { await close(call); return false; }
    if (begin && !call.started) {
      call.started = true;
      // Start in background; Lua continues checking leases during Gemini setup.
      Promise.resolve().then(() => live(call) && start(uuid)).then(async (ready) => {
        if (!ready || !live(call)) await close(call);
        else call.ready = true;
      }).catch(() => close(call));
    }
    return true;
  }

  async function reap() {
    for (const call of calls.values()) if (!live(call)) await close(call);
  }
  return { sync, claim, lease, reap };
}

module.exports = { createService };
