const { test } = require('node:test');
const assert = require('node:assert/strict');
const { createService } = require('../src/services/vicidialAiConferenceService');
const call = { agent: '6666', conference: '8600051', callId: '12345678.9' };
const uuid = '12345678-1234-4234-8234-123456789abc';
const tick = () => new Promise((resolve) => setImmediate(resolve));
function fixture() {
  let time = 1000;
  let agents = ['6666'];
  const settings = { enabled: true, ready: true, updatedBy: 1, callDirection: 'inbound' };
  const starts = []; const stops = [];
  const svc = createService({ settings: async () => settings, mappedAgents: async () => agents,
    start: async (id) => { starts.push(id); return true; }, stop: async (id) => { stops.push(id); },
    now: () => time });
  return { svc, settings, starts, stops, advance: (ms) => { time += ms; }, unmap: () => { agents = []; } };
}

test('eligible inbound customer gets one reservation and one voice leg', async () => {
  const { svc, starts } = fixture();
  const [join] = await svc.sync([call]);
  assert.match(join.token, /^[a-f0-9]{64}$/);
  assert.equal((await svc.sync([call]))[0].token, join.token);
  assert.equal(await svc.claim(join.token, uuid), true);
  assert.equal(await svc.claim(join.token, 'another'), false);
  assert.equal(await svc.lease(join.token, uuid, true), true);
  assert.equal(await svc.lease(join.token, uuid, true), true);
  await tick();
  assert.deepEqual(starts, [uuid]);
  assert.deepEqual(await svc.sync([call]), []);
});

test('disabled/unconfigured/outbound-only AI and unmapped agents do not join', async () => {
  for (const override of [{ enabled: false }, { ready: false }, { updatedBy: null }, { callDirection: 'outbound' }]) {
    const f = fixture(); Object.assign(f.settings, override);
    assert.deepEqual(await f.svc.sync([call]), []);
  }
  const f = fixture(); f.unmap();
  assert.deepEqual(await f.svc.sync([call]), []);
});

test('both directions allows inbound, but idle snapshot never originates', async () => {
  const f = fixture(); f.settings.callDirection = 'both';
  assert.deepEqual(await f.svc.sync([]), []);
  assert.equal((await f.svc.sync([call])).length, 1);
});

test('customer ends: only owned AI leg stopped; late claim/start rejected', async () => {
  const f = fixture(); const [join] = await f.svc.sync([call]);
  await f.svc.claim(join.token, uuid);
  await f.svc.sync([]);
  assert.deepEqual(f.stops, [uuid]);
  assert.equal(await f.svc.lease(join.token, uuid, true), false);
  assert.equal(await f.svc.claim(join.token, uuid), false);
  assert.deepEqual(f.starts, []);
});

test('worker lease expires and cannot be revived by a late snapshot', async () => {
  const f = fixture(); const [join] = await f.svc.sync([call]);
  await f.svc.claim(join.token, uuid);
  f.advance(15001); await f.svc.reap();
  assert.deepEqual(f.stops, [uuid]);
  assert.deepEqual(await f.svc.sync([call]), []);
  assert.equal(await f.svc.lease(join.token, uuid), false);
});

test('settings disable and mapping removal terminate only AI', async () => {
  for (const mutation of [(f) => { f.settings.enabled = false; }, (f) => f.unmap()]) {
    const f = fixture(); const [join] = await f.svc.sync([call]);
    await f.svc.claim(join.token, uuid); mutation(f);
    await f.svc.sync([call]);
    assert.deepEqual(f.stops, [uuid]);
    assert.equal(await f.svc.lease(join.token, uuid), false);
  }
});

test('unknown capability or a different UUID cannot start voice', async () => {
  const f = fixture(); const [join] = await f.svc.sync([call]);
  assert.equal(await f.svc.claim('unknown', uuid), false);
  await f.svc.claim(join.token, uuid);
  assert.equal(await f.svc.lease(join.token, 'other', true), false);
  assert.deepEqual(f.starts, []);
});

test('concurrent snapshots cannot reserve duplicate legs', async () => {
  const { svc } = fixture();
  const [a, b] = await Promise.all([svc.sync([call]), svc.sync([call])]);
  assert.equal(a[0].token, b[0].token);
});

test('failed Gemini start terminates the AI leg and cannot retry same call', async () => {
  const stops = [];
  const svc = createService({ settings: async () => ({ enabled: true, ready: true, updatedBy: 1, callDirection: 'inbound' }),
    mappedAgents: async () => ['6666'], start: async () => { throw Error('unavailable'); },
    stop: async (id) => stops.push(id) });
  const [join] = await svc.sync([call]); await svc.claim(join.token, uuid);
  await svc.lease(join.token, uuid, true); await tick();
  assert.deepEqual(stops, [uuid]);
  assert.equal(await svc.lease(join.token, uuid), false);
  assert.deepEqual(await svc.sync([call]), []);
});

test('backend restart rejects old tokens', async () => {
  const [join] = await fixture().svc.sync([call]);
  assert.equal(await fixture().svc.claim(join.token, uuid), false);
});

test('call ending during Gemini connection cannot leave a late AI leg', async () => {
  let finish; const stopped = [];
  const svc = createService({ settings: async () => ({ enabled: true, ready: true, updatedBy: 1, callDirection: 'inbound' }),
    mappedAgents: async () => ['6666'], start: () => new Promise((resolve) => { finish = resolve; }),
    stop: async (id) => stopped.push(id) });
  const [join] = await svc.sync([call]); await svc.claim(join.token, uuid);
  await svc.lease(join.token, uuid, true); await tick();
  await svc.sync([]); finish(true); await tick();
  assert.ok(stopped.length > 0);
  assert.ok(stopped.every((id) => id === uuid));
  assert.equal(await svc.lease(join.token, uuid), false);
});

test('loss of established Gemini session removes AI without ending customer call', async () => {
  let connected = true; const stopped = [];
  const svc = createService({ settings: async () => ({ enabled: true, ready: true, updatedBy: 1, callDirection: 'inbound' }),
    mappedAgents: async () => ['6666'], start: async () => true, connected: () => connected,
    stop: async (id) => stopped.push(id) });
  const [join] = await svc.sync([call]); await svc.claim(join.token, uuid);
  await svc.lease(join.token, uuid, true); await tick(); connected = false;
  assert.equal(await svc.lease(join.token, uuid), false);
  assert.deepEqual(stopped, [uuid]);
});

test('Gemini readiness timeout stops only the claimed AI UUID and never retries', async () => {
  const stopped = [], started = [];
  const svc = createService({
    settings: async () => ({ enabled: true, ready: true, updatedBy: 1, callDirection: 'inbound' }),
    mappedAgents: async () => ['6666'],
    start: async (id) => { started.push(id); return false; },
    stop: async (id) => stopped.push(id)
  });
  const [join] = await svc.sync([call]);
  await svc.claim(join.token, uuid);
  await svc.lease(join.token, uuid, true); await tick();
  assert.deepEqual(started, [uuid]);
  assert.deepEqual(stopped, [uuid]);
  assert.equal(await svc.lease(join.token, uuid, true), false);
  assert.deepEqual(await svc.sync([call]), []);
  assert.deepEqual(started, [uuid]);
});

test('failed AI hangup never falls back to another channel or resurrects the leg', async () => {
  const stopped = [];
  const svc = createService({
    settings: async () => ({ enabled: true, ready: true, updatedBy: 1, callDirection: 'inbound' }),
    mappedAgents: async () => ['6666'], start: async () => { throw Error('Gemini failure'); },
    stop: async (id) => { stopped.push(id); throw Error('ESL unavailable'); }
  });
  const [join] = await svc.sync([call]);
  await svc.claim(join.token, uuid);
  await svc.lease(join.token, uuid, true); await tick();
  assert.deepEqual(stopped, [uuid]);
  assert.equal(await svc.lease(join.token, uuid, true), false);
  assert.deepEqual(await svc.sync([call]), []);
  assert.ok(stopped.every((id) => id === uuid));
});
