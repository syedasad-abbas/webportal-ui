const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { EventEmitter } = require('node:events');

function load(relative, mocks, extra = {}) {
  const filename = path.resolve(__dirname, relative);
  const module = { exports: {} };
  vm.runInNewContext(fs.readFileSync(filename, 'utf8'), {
    module, Buffer, process, URL, console, setTimeout, clearTimeout,
    require: (name) => name in mocks ? mocks[name] : require(name), ...extra
  }, { filename });
  return module.exports;
}

const uuid = '12345678-1234-1234-1234-123456789abc';
function fixture({ enabled = true, ai = false, failRecording = false, missed = false, failover = false } = {}) {
  const recordings = []; const updates = []; const errors = []; const settingsUsers = [];
  let legs = 0;
  const service = load('../src/services/inboundCallService.js', {
    crypto: { randomUUID: () => `leg-${++legs}` },
    '../config': { freeswitch: { directoryDomain: 'test', recordingsPath: '/var/recordings' },
      aiAgent: { bridgeUrl: 'ws://test', bridgeToken: 'test', connectTimeoutMs: 1 } },
    '../db': { query: async (sql, args) => {
      if (sql.includes('SELECT recording_enabled')) {
        settingsUsers.push(args[0]);
        return { rows: [{ recording_enabled: enabled }] };
      }
      if (sql.includes('FROM sessions')) return { rows: [
        { id: 1, sip_username: 'one' }, { id: 2, sip_username: 'two' }
      ] };
      if (sql.includes('SELECT id, last_user_id')) return { rowCount: 1, rows: [{ id: 1 }] };
      updates.push({ sql, args });
      return { rows: [], rowCount: 1 };
    } },
    '../lib/freeswitch': {
      originateCall: async () => ({}),
      callExists: async () => true,
      getChannelVar: async (leg) => {
        if (missed || (failover && leg === 'leg-1')) throw new Error('Unanswered leg');
        return '1234';
      },
      startAudioStream: async () => {},
      startRecording: async (...args) => {
        recordings.push(args);
        if (failRecording) throw new Error('Disk unavailable');
      }
    },
    '../socket': { emitToUser() {} },
    './metricsService': { scheduleMetricsBroadcast() {} },
    './aiAgentSettingsService': { get: async () => null },
    './geminiLiveBridge': { waitUntilReady: async () => true }
  }, {
    setTimeout: (fn) => setImmediate(fn),
    console: { log() {}, warn() {}, error: (...args) => errors.push(args) }
  });
  return { recordings, updates, errors, settingsUsers, run: () => service.dispatch({
    uuid, did: '123', callerIdNumber: '456',
    settings: ai ? { enabled: true, ready: true, updatedBy: 7 } : null
  }) };
}

for (const ai of [false, true]) {
  test(`${ai ? 'AI' : 'human'} inbound recording follows the assigned user's setting`, async () => {
    for (const enabled of [true, false]) {
      const f = fixture({ ai, enabled });
      const result = await f.run();
      assert.equal(result.ok, true);
      assert.deepEqual(f.settingsUsers, [ai ? 7 : 1]);
      const saved = f.updates.filter(({ sql }) => sql.includes('SET recording_path'));
      assert.equal(f.recordings.length, enabled ? 1 : 0);
      assert.equal(saved.length, enabled ? 1 : 0);
      if (enabled) {
        assert.equal(f.recordings[0][0], uuid);
        assert.equal(f.recordings[0][1], `/var/recordings/${ai ? 7 : 1}-inbound-${uuid}.wav`);
        assert.deepEqual(Array.from(saved[0].args), f.recordings[0]);
      }
    }
  });
  test(`${ai ? 'AI' : 'human'} recording failure preserves the answered call without a false recording link`, async () => {
    const f = fixture({ ai, failRecording: true });
    assert.equal((await f.run()).answeredBy, ai ? 'ai' : 1);
    assert.equal(f.recordings.length, 1);
    assert.equal(f.errors.length, 1);
    assert.equal(f.updates.some(({ sql }) => sql.includes('SET recording_path')), false);
  });
}

test('missed calls do not record; failover uses the answering agent setting and ownership', async () => {
  const missed = fixture({ missed: true });
  assert.equal((await missed.run()).reason, 'no_answer');
  assert.equal(missed.recordings.length, 0);
  assert.equal(missed.settingsUsers.length, 0);
  const answered = fixture({ failover: true });
  assert.equal((await answered.run()).answeredBy, 2);
  assert.deepEqual(answered.settingsUsers, [2]);
  assert.equal(answered.recordings[0][1], `/var/recordings/2-inbound-${uuid}.wav`);
});

test('FreeSWITCH recording API checks responses and rejects unsafe input', async () => {
  const commands = []; let reply = '+OK Success';
  class Socket extends EventEmitter {
    setTimeout() {}
    end() {}
    connect() { setImmediate(() => this.emit('data', Buffer.from('Content-Type: auth/request\n\n'))); }
    write(command) {
      setImmediate(() => {
        if (command.startsWith('auth ')) {
          this.emit('data', Buffer.from('Content-Type: command/reply\nReply-Text: +OK accepted\n\n'));
        } else {
          commands.push(command);
          this.emit('data', Buffer.from(`Content-Type: api/response\nContent-Length: ${Buffer.byteLength(reply)}\n\n${reply}`));
        }
      });
    }
  }
  const api = load('../src/lib/freeswitch.js', {
    net: { Socket }, '../config': { freeswitch: { host: 'test', port: 1, password: 'test' } }
  });
  await api.startRecording(uuid, '/var/recordings/test.wav');
  assert.equal(commands[0], `api uuid_record ${uuid} start /var/recordings/test.wav\n\n`);
  reply = '-ERR recording failed';
  await assert.rejects(api.startRecording(uuid, '/var/recordings/test.wav'), /recording failed/);
  await assert.rejects(api.startRecording('bad', '/var/recordings/test.wav'), /Invalid call UUID/);
  await assert.rejects(api.startRecording(uuid, '/var/recordings/test.wav\nstatus'), /Invalid recording path/);
  assert.equal(commands.length, 2);
});
