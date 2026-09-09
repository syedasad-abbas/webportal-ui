const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { EventEmitter } = require('node:events');
const root = path.resolve(__dirname, '../..');
function load(relative, mocks = {}, extra = {}) {
  const filename = path.join(root, relative);
  const module = { exports: {} };
  vm.runInNewContext(fs.readFileSync(filename, 'utf8'), {
    module, exports: module.exports, Buffer, console, setTimeout, clearTimeout,
    require(name) { return name in mocks ? mocks[name] : require(name); }, ...extra
  }, { filename });
  return module.exports;
}
function outcomeFixture() {
  const sockets = [];
  class Socket extends EventEmitter {
    constructor() { super(); sockets.push(this); this.writes = []; }
    write(data) { this.writes.push(data); }
    connect() {}
    destroy() { this.destroyed = true; }
  }
  const outcomes = load('backend/src/lib/callOutcomes.js', {
    net: { Socket }, '../config': { freeswitch: { password: 'test', port: 1, host: 'test' } }
  }, { setTimeout: () => ({ unref() {} }), clearTimeout() {} });
  return { outcomes, sockets };
}
test('final 503 and busy details survive channel removal; other events are ignored', () => {
  const { outcomes } = outcomeFixture();
  outcomes.remember({ 'Unique-ID': 'a', 'Event-Name': 'CHANNEL_HANGUP_COMPLETE',
    variable_sip_term_status: '503', variable_sip_term_phrase: 'Account Inactive', 'Hangup-Cause': 'NORMAL_TEMPORARY_FAILURE' });
  assert.equal(outcomes.get('a').sipStatus, 503);
  assert.equal(outcomes.get('a').sipReason, 'Account Inactive');
  outcomes.remember({ 'Unique-ID': 'b', 'Event-Name': 'CHANNEL_HANGUP_COMPLETE',
    variable_sip_invite_failure_status: '486', 'Hangup-Cause': 'USER_BUSY' });
  assert.equal(outcomes.get('b').sipStatus, 486);
  outcomes.remember({ 'Unique-ID': 'ignored', 'Event-Name': 'CHANNEL_ANSWER' });
  assert.equal(outcomes.get('ignored'), null);
});
test('event subscription parses fragmented UTF-8 frames and starts only once', () => {
  const { outcomes, sockets } = outcomeFixture();
  outcomes.start(); outcomes.start();
  assert.equal(sockets.length, 1);
  const socket = sockets[0];
  socket.emit('data', Buffer.from('Content-Type: auth/request\n\n'));
  socket.emit('data', Buffer.from('Content-Type: command/reply\nReply-Text: +OK accepted\n\n'));
  assert.equal(socket.writes[1], 'event json CHANNEL_HANGUP_COMPLETE\n\n');
  const body = JSON.stringify({ 'Event-Name': 'CHANNEL_HANGUP_COMPLETE', 'Unique-ID': 'utf8',
    variable_sip_term_status: '503', variable_sip_term_phrase: 'Unavailable — retry' });
  const frame = Buffer.from(`Content-Type: text/event-json\nContent-Length: ${Buffer.byteLength(body)}\n\n${body}`);
  for (const byte of frame) socket.emit('data', Buffer.from([byte]));
  assert.equal(outcomes.get('utf8').sipReason, 'Unavailable — retry');
});
function statusFixture(vars = {}, exists = true, final = null, row = {}) {
  const updates = [];
  const service = load('backend/src/services/callControlService.js', {
    '../db': { query: async (sql, args) => {
      if (sql.startsWith('SELECT')) return { rowCount: 1, rows: [{ id: 1, call_uuid: 'a', created_at: '2020-01-01', ...row }] };
      updates.push({ sql, args }); return { rowCount: 1 };
    } },
    '../lib/freeswitch': { callExists: async () => { if (exists instanceof Error) throw exists; return exists; },
      getChannelVar: async (_, key) => vars[key] || null },
    '../lib/callOutcomes': { get: () => final },
    './metricsService': { scheduleMetricsBroadcast() {} }
  });
  return { service, updates };
}
test('executing dialplan is not treated as answered; 180 rings and answer starts call', async () => {
  for (const [vars, expected] of [
    [{ channel_state: 'CS_EXECUTE' }, 'trying'],
    [{ sip_last_response_text: '180 Ringing' }, 'ringing'],
    [{ answered_epoch: '1234' }, 'in_call'],
    [{ sip_last_response_text: '407 Proxy Authentication Required' }, 'trying']
  ]) {
    const { service } = statusFixture(vars);
    assert.equal((await service.getStatus({ uuid: 'a', userId: 1 })).status, expected);
  }
});
test('final failure is returned and persisted even after FreeSWITCH removes channel', async () => {
  for (const code of [503, 486]) {
    const final = { sipStatus: code, sipReason: 'Final reason', hangupCause: 'USER_BUSY' };
    const { service, updates } = statusFixture({}, false, final);
    const result = await service.getStatus({ uuid: 'a', userId: 1 });
    assert.equal(result.status, 'ended');
    assert.equal(result.sipStatus, code);
    assert.ok(updates.some(({ args }) => args[0] === code));
  }
});
test('temporary status connection errors never mark a call completed', async () => {
  const { service, updates } = statusFixture({}, new Error('ESL unavailable'));
  await assert.rejects(service.getStatus({ uuid: 'a', userId: 1 }), /ESL unavailable/);
  assert.equal(updates.length, 0);
});
test('new queued calls get a short channel-creation grace period', async () => {
  const { service, updates } = statusFixture({}, false, null, { created_at: new Date().toISOString() });
  assert.equal((await service.getStatus({ uuid: 'a', userId: 1 })).status, 'trying');
  assert.equal(updates.length, 0);
});
function tones() {
  const timers = new Map(); const nodes = []; let next = 0;
  class AudioContext {
    constructor() { this.state = 'running'; this.currentTime = 0; this.destination = {}; }
    resume() { return Promise.resolve(); }
    createGain() { return { gain: { setValueAtTime() {}, linearRampToValueAtTime() {} }, connect() {}, disconnect() {} }; }
    createOscillator() {
      const node = { frequency: {}, connect() {}, disconnect() {}, start() {}, stop(when) { if (when === undefined) this.stopped = true; } };
      nodes.push(node); return node;
    }
  }
  const window = { AudioContext };
  vm.runInNewContext(fs.readFileSync(path.join(root, 'laravel/public/js/dialer-feedback.js'), 'utf8'), {
    window, setTimeout(fn) { timers.set(++next, fn); return next; }, clearTimeout(id) { timers.delete(id); }
  });
  const feedback = new window.DialerFeedback(); feedback.unlock();
  return { feedback, timers, nodes };
}
test('calling/ringing are distinct and stop immediately on answer or hangup', () => {
  const { feedback, nodes, timers } = tones();
  feedback.update('trying'); assert.equal(feedback.kind, 'calling');
  assert.equal(timers.size, 1);
  feedback.update('ringing'); assert.equal(feedback.kind, 'ringing');
  assert.equal(nodes[0].stopped, true);
  assert.equal(nodes.at(-1).frequency.value, 480);
  const count = nodes.length;
  feedback.update('ringing'); assert.equal(nodes.length, count);
  feedback.update('in_call'); assert.equal(timers.size, 0);
  assert.equal(feedback.nodes.length, 0);
  feedback.update('trying'); feedback.update('completed', null, null, true);
  assert.equal(feedback.kind, null);
});
test('503 and busy play different bounded patterns, and next call cancels failure sound', () => {
  for (const [code, kind, pulses] of [[503, 'unavailable', 6], [486, 'busy', 4], [600, 'busy', 4]]) {
    const { feedback, timers } = tones();
    feedback.update('ended', code); assert.equal(feedback.kind, kind);
    let count = 1;
    while (timers.size && count < 20) {
      const [id, fn] = timers.entries().next().value; timers.delete(id); fn(); count++;
    }
    assert.equal(count, pulses); assert.equal(timers.size, 0);
    feedback.update('trying'); assert.equal(feedback.kind, 'calling');
    feedback.stop(); assert.equal(timers.size, 0);
  }
});
test('unsupported browser audio does not break the dialer', () => {
  const window = {};
  vm.runInNewContext(fs.readFileSync(path.join(root, 'laravel/public/js/dialer-feedback.js'), 'utf8'), { window, clearTimeout, setTimeout });
  const feedback = new window.DialerFeedback();
  assert.doesNotThrow(() => { feedback.unlock(); feedback.update('trying'); feedback.stop(); });
});
test('consecutive calls keep registration and transport; explicit disconnect still closes it', async () => {
  let hangups = 0; let disconnects = 0; let unregisters = 0;
  const window = { clearInterval() {}, dispatchEvent() {} };
  const sandbox = { window, document: { querySelector() { return {}; } }, console,
    CustomEvent: class {}, SimpleUser: class {} };
  const source = fs.readFileSync(path.join(root, 'laravel/resources/js/dialer/webrtc-client.js'), 'utf8')
    .replace(/^import .*;\n/, '').replace('export default DialerWebRTC;', '');
  vm.runInNewContext(source, sandbox);
  const rtc = new window.DialerWebRTC();
  const client = { session: {}, async hangup() { hangups++; this.session = null; },
    async disconnect() { disconnects++; }, async unregister() { unregisters++; } };
  rtc.simpleUser = client;
  await rtc.leaveConference();
  assert.equal(rtc.simpleUser, client); assert.equal(disconnects, 0);
  client.session = {}; await rtc.leaveConference(); assert.equal(hangups, 2);
  await rtc.disconnect(); assert.equal(disconnects, 1); assert.equal(unregisters, 1);
});

function pollFixture() {
  const source = fs.readFileSync(path.join(root, 'laravel/resources/views/backend/pages/dialer/index.blade.php'), 'utf8');
  const code = source.slice(source.indexOf('    const pollStatus = async () => {'), source.indexOf('\n    // Live call actions'));
  const pending = []; const statuses = []; const errors = [];
  const context = {
    callUuid: 'first', callGeneration: 1, hangupInProgress: false, pollInFlight: null,
    conferenceName: null, callActive: true, pollHandle: 1,
    fetch: () => new Promise((resolve) => pending.push(resolve)),
    setStatus: (...args) => statuses.push(args), showError: (error) => errors.push(error),
    setControls() {}, refreshStartButton() {}, clearInterval() {},
    isConnectedStatus: (s) => s === 'in_call',
    isTerminalStatus: (s) => ['ended', 'failed', 'completed'].includes(s)
  };
  vm.createContext(context); vm.runInContext(`${code}\nthis.poll = pollStatus;`, context);
  return { context, pending, statuses, errors };
}
test('late status response from a previous call cannot end the new call', async () => {
  const { context, pending, statuses } = pollFixture();
  const first = context.poll();
  context.callUuid = 'second'; context.callGeneration++;
  const second = context.poll();
  pending[0]({ ok: true, json: async () => ({ status: 'ended', sipStatus: 503 }) });
  await first; assert.equal(statuses.length, 0);
  pending[1]({ ok: true, json: async () => ({ status: 'ringing' }) });
  await second; assert.equal(statuses[0][0], 'ringing');
});
test('status requests do not overlap and HTTP failures preserve the call for retry', async () => {
  const { context, pending, statuses, errors } = pollFixture();
  const first = context.poll(); await context.poll(); assert.equal(pending.length, 1);
  pending[0]({ ok: false, status: 503 }); await first;
  assert.equal(context.callActive, true); assert.equal(statuses.length, 0);
  assert.equal(errors.length, 1);
  const retry = context.poll(); assert.equal(pending.length, 2);
  pending[1]({ ok: true, json: async () => ({ status: 'in_call' }) }); await retry;
  assert.equal(statuses[0][0], 'in_call');
});
test('carrier routing continues to use the account assignment and configured proxy', async () => {
  const requests = [];
  let record = { carrier_id: 7, sip_domain: 'configured.example', sip_port: 5070,
    outbound_proxy: 'proxy.example', default_caller_id: '12125550123', transport: 'tcp' };
  const service = load('backend/src/services/callService.js', {
    crypto: { randomUUID: () => 'test-uuid' },
    '../db': { query: async (sql) => {
      if (sql.includes('FROM users')) return { rowCount: 1, rows: [record] };
      if (sql.includes('FROM carrier_prefixes')) return { rows: [{ prefix: '99' }] };
      return { rows: [], rowCount: 1 };
    } },
    '../lib/freeswitch': { originateCall: async (request) => { requests.push(request); return { jobUuid: 'job' }; } },
    '../lib/carrierUtils': { normalizeGatewayName: ({ id }) => `carrier-${id}` },
    '../config': { defaults: {}, freeswitch: { advertisedSipIp: '192.0.2.1' } },
    '../lib/callOutcomes': { get: () => null }, './metricsService': { scheduleMetricsBroadcast() {} }
  }, { setTimeout() {}, console: { log() {}, warn() {}, error() {} } });
  await service.originate({ user: { id: 1 }, destination: '2025550123' });
  assert.equal(requests[0].gateway, 'carrier-7');
  assert.equal(requests[0].destination, '9912025550123');
  assert.ok(requests[0].variables.includes('sip_req_host=configured.example'));
  assert.ok(requests[0].variables.includes('sip_transport=tcp'));
  record = { ...record, carrier_id: 8, sip_domain: 'second.example' };
  await service.originate({ user: { id: 1 }, destination: '2025550123' });
  assert.equal(requests[1].gateway, 'carrier-8');
  assert.ok(requests[1].variables.includes('sip_req_host=second.example'));
});
