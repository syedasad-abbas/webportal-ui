const { test } = require('node:test');
const assert = require('node:assert/strict');
const { createCustomerService, customerSourceIp } = require('../src/services/vicidialCustomerAiService');
const uuid = '12345678-1234-4234-8234-123456789abc';
const second = '12345678-1234-4234-8234-123456789def';
const tick = () => new Promise((r) => setImmediate(r));
function fixture(env = { VICIDIAL_AI_SERVER_IP: '172.16.253.142' }) {
  let time = 0;
  const state = { enabled: true, connected: true, result: true };
  const vars = { sip_network_ip: '172.16.253.142', destination_number: 'vici-ai-customer',
    'sip_h_X-Vici-AI-Agent': '9901', 'sip_h_X-Vici-Customer-ID': '123456.1' };
  const settings = { enabled: true, ready: true, updatedBy: 1, callDirection: 'inbound' };
  const starts = [], closes = [];
  const svc = createCustomerService({ enabled: () => state.enabled,
    allowedAgents: () => new Set(['9901']), sourceIp: () => customerSourceIp(env),
    settings: async () => settings, channelVar: async (_, key) => vars[key],
    start: async (id) => { starts.push(id); return state.result; },
    connected: () => state.connected, close: (id) => closes.push(id), now: () => time });
  return { svc, state, settings, vars, starts, closes, advance: (n) => { time += n; } };
}

test('dedicated endpoint requires exact source, destination, agent and customer identity', async () => {
  for (const key of ['sip_network_ip', 'destination_number', 'sip_h_X-Vici-AI-Agent', 'sip_h_X-Vici-Customer-ID']) {
    const f = fixture(); f.vars[key] = 'invalid';
    assert.equal(await f.svc.authorize(uuid), false);
    assert.equal(await f.svc.begin(uuid), false);
    assert.deepEqual(f.starts, []);
  }
  assert.equal(await fixture().svc.begin('invalid-uuid'), false);
});

test('feature and inbound settings gate the separate route', async () => {
  const f = fixture(); f.state.enabled = false;
  assert.equal(await f.svc.begin(uuid), false);
  f.state.enabled = true; f.settings.callDirection = 'outbound';
  assert.equal(await f.svc.begin(uuid), false);
  f.settings.callDirection = 'both';
  assert.equal(await f.svc.begin(uuid), true);
});

test('duplicate requests start one session; consecutive customer legs start separately', async () => {
  const f = fixture();
  await Promise.all([f.svc.begin(uuid), f.svc.begin(uuid)]); await tick();
  assert.deepEqual(f.starts, [uuid]);
  assert.equal(await f.svc.lease(uuid), true);
  f.svc.end(uuid);
  assert.deepEqual(f.closes, [uuid]);
  await f.svc.begin(second); await tick();
  assert.deepEqual(f.starts, [uuid, second]);
  assert.equal(await f.svc.lease(uuid), false);
  assert.equal(await f.svc.lease(second), true);
});

test('failed AI startup closes only its session and cannot obtain a lease', async () => {
  const f = fixture(); f.state.result = false;
  await f.svc.begin(uuid); await tick();
  assert.equal(await f.svc.lease(uuid), false);
  assert.ok(f.closes.every((id) => id === uuid));
});

test('AI disconnection and disabling AI revoke the dedicated lease', async () => {
  for (const mutate of [(f) => { f.state.connected = false; }, (f) => { f.settings.enabled = false; }]) {
    const f = fixture(); await f.svc.begin(uuid); await tick(); mutate(f);
    assert.equal(await f.svc.lease(uuid), false);
    assert.deepEqual(f.closes, [uuid]);
  }
});

test('missing Lua heartbeats release the AI session without channel operations', async () => {
  const f = fixture(); await f.svc.begin(uuid); await tick();
  f.advance(15001); f.svc.reap();
  assert.deepEqual(f.closes, [uuid]);
  assert.equal(await f.svc.lease(uuid), false);
});

test('unknown end request cannot close an ordinary FreeSWITCH AI session', () => {
  const f = fixture(); f.svc.end(second);
  assert.deepEqual(f.closes, []);
});

test('customer source override preserves default and conference server address', () => {
  const env = { VICIDIAL_AI_SERVER_IP: '172.16.253.142' };
  assert.equal(customerSourceIp(env), '172.16.253.142');
  env.VICIDIAL_CUSTOMER_AI_SOURCE_IP = '';
  assert.equal(customerSourceIp(env), '172.16.253.142');
  env.VICIDIAL_CUSTOMER_AI_SOURCE_IP = '192.168.18.31';
  assert.equal(customerSourceIp(env), '192.168.18.31');
  assert.equal(env.VICIDIAL_AI_SERVER_IP, '172.16.253.142');
  assert.equal(customerSourceIp({}), '');
});

test('NAT source must match exactly and still requires valid identity headers', async () => {
  const f = fixture({ VICIDIAL_AI_SERVER_IP: '172.16.253.142',
    VICIDIAL_CUSTOMER_AI_SOURCE_IP: '192.168.18.31' });
  assert.equal(await f.svc.authorize(uuid), false);
  f.vars.sip_network_ip = '192.168.18.31';
  assert.equal(await f.svc.authorize(uuid), true);
  f.vars.sip_network_ip = '192.168.18.32';
  assert.equal(await f.svc.authorize(uuid), false);
  f.vars.sip_network_ip = '192.168.18.31';
  f.vars['sip_h_X-Vici-AI-Agent'] = '6666';
  assert.equal(await f.svc.authorize(uuid), false);
  f.vars['sip_h_X-Vici-AI-Agent'] = '9901';
  f.vars['sip_h_X-Vici-Customer-ID'] = '';
  assert.equal(await f.svc.authorize(uuid), false);
});
