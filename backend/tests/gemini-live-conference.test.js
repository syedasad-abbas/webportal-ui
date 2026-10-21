const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const path = require('node:path');
const { EventEmitter } = require('node:events');
const uuid = '12345678-1234-4234-8234-123456789abc';

// Run the actual bridge with fake media/network/filesystem boundaries. No
// credentials, sockets, FreeSWITCH commands or Gemini requests are used.
function fixture(conference = true) {
  class Socket extends EventEmitter {
    static OPEN = 1;
    constructor() { super(); this.readyState = 1; this.sent = []; }
    send(data) { this.sent.push(JSON.parse(data)); }
    close() { this.readyState = 3; }
  }
  const breaks = [], broadcasts = [], removed = [];
  const disk = { mkdir: async () => {}, writeFile: async () => {}, unlink: async (p) => removed.push(p) };
  const media = {
    breakAudioPlayback: async (id) => { breaks.push(id); },
    broadcastAudio: async (id, file) => { broadcasts.push({ id, file }); }
  };
  const context = {
    require: (name) => {
      if (name === 'ws') return { WebSocket: Socket, WebSocketServer: Socket };
      if (name === 'fs/promises') return disk;
      if (name === './crmLiveTools') return { createHandler: () => ({ tools: () => [], handle() {}, cancel() {} }) };
      if (name === '../config') return { aiAgent: { connectTimeoutMs: 1000 }, freeswitch: { recordingsPath: '/test' } };
      if (name === '../aiSalesAgentProfile') return { buildAgentPrompt: () => '' };
      if (name === '../lib/freeswitch') return media;
      return require(name);
    },
    module: { exports: {} }, Buffer, URL,
    console: { log() {}, warn() {}, error() {} },
    setTimeout: () => 1, clearTimeout() {}, setInterval: () => 1, clearInterval() {}
  };
  vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../src/services/geminiLiveBridge.js'), 'utf8')
    + '\nmodule.exports.testing = { attachGemini, sessions, sendInitialGreeting, handleGeminiMessage, playAudioSegment };', context);
  const api = context.module.exports.testing;
  const socket = new Socket();
  api.attachGemini(socket, { url: typeof conference === 'string' ? `/ai-audio?context=${conference}` : conference ? '/ai-audio?context=vicidial-conference' : '/ai-audio' }, uuid,
    { greeting: 'Welcome. What is your name?' });
  const session = api.sessions.get(uuid);
  session.ready = true;
  return { api, session, socket, disk, media, breaks, broadcasts, removed };
}

test('conference introduces assistance without reusing normal intake greeting', () => {
  const f = fixture(); f.api.sendInitialGreeting(f.session);
  const text = f.session.gemini.sent[0].realtimeInput.text;
  assert.match(text, /joining an existing customer-agent conversation/);
  assert.match(text, /Then listen/);
  assert.ok(!text.includes('Welcome. What is your name?'));
});

test('ordinary call still uses the configured greeting', () => {
  const f = fixture(false); f.api.sendInitialGreeting(f.session);
  assert.match(f.session.gemini.sent[0].realtimeInput.text, /Welcome\. What is your name\?/);
});

test('conference forwards incoming speech during greeting, generation and playback', () => {
  const f = fixture();
  f.session.modelGenerating = true;
  f.session.playbackQueueDepth = 2;
  f.socket.emit('message', Buffer.from([1, 0, 2, 0]), true);
  assert.equal(f.session.gemini.sent.length, 1);
  assert.equal(f.session.gemini.sent[0].realtimeInput.audio.data, 'AQACAA==');
});

test('ordinary calls retain greeting and playback gates, then forward idle audio', () => {
  const f = fixture(false);
  const audio = () => f.socket.emit('message', Buffer.from([1, 0]), true);
  audio();
  f.session.initialGreetingComplete = true;
  f.session.modelGenerating = true; audio();
  f.session.modelGenerating = false; f.session.playbackQueueDepth = 1; audio();
  assert.equal(f.session.gemini.sent.length, 0);
  f.session.playbackQueueDepth = 0; audio();
  assert.equal(f.session.gemini.sent.length, 1);
});

test('conference interruption clears stale output and breaks only AI playback', async () => {
  const f = fixture();
  f.session.outputTurnChunks.push(Buffer.from([1, 0]));
  f.session.outputTurnBytes = 2;
  f.api.handleGeminiMessage(f.session, Buffer.from(JSON.stringify({ serverContent: {
    interrupted: true, modelTurn: { parts: [{ inlineData: { data: 'AQACAA==' } }] }
  } })));
  await f.session.playbackChain;
  assert.deepEqual(f.breaks, [uuid]);
  assert.equal(f.session.outputTurnBytes, 0);
  assert.equal(f.session.outputTurnChunks.length, 0);
  assert.equal(f.session.playbackGeneration, 1);
  assert.equal(f.session.modelGenerating, false);
  assert.equal(f.session.gemini.readyState, 1);
  assert.equal(f.socket.readyState, 1);
  assert.equal(f.broadcasts.length, 0);
});

test('interruption during WAV write cannot broadcast stale conference speech', async () => {
  const f = fixture();
  f.disk.writeFile = async () => {
    f.api.handleGeminiMessage(f.session, Buffer.from('{"serverContent":{"interrupted":true}}'));
  };
  await f.api.playAudioSegment(f.session, Buffer.from([1, 0]), 24000, 0);
  assert.deepEqual(f.breaks, [uuid]);
  assert.equal(f.broadcasts.length, 0);
  assert.equal(f.removed.length, 1);
});

test('queued old-generation audio is discarded before any playback', async () => {
  const f = fixture(); f.session.playbackGeneration = 1;
  await f.api.playAudioSegment(f.session, Buffer.from([1, 0]), 24000, 0);
  assert.equal(f.broadcasts.length, 0);
});

test('subsequent playback waits for AI-only break completion', async () => {
  const f = fixture(); let release;
  f.media.breakAudioPlayback = () => new Promise((resolve) => { release = resolve; });
  f.api.handleGeminiMessage(f.session, Buffer.from('{"serverContent":{"interrupted":true}}'));
  let completed = false;
  f.session.playbackChain.then(() => { completed = true; });
  await Promise.resolve(); assert.equal(completed, false);
  release(); await f.session.playbackChain; assert.equal(completed, true);
});

for (const label of ['vicidial-customer']) {
  test(`${label} keeps customer greeting and sends speech during AI playback`, () => {
    const f = fixture(label);
    f.api.sendInitialGreeting(f.session);
    assert.match(f.session.gemini.sent[0].realtimeInput.text, /Welcome/);
    f.session.modelGenerating = true;
    f.session.playbackQueueDepth = 2;
    f.socket.emit('message', Buffer.from([1, 0]), true);
    assert.equal(f.session.gemini.sent.at(-1).realtimeInput.audio.data, 'AQA=');
    assert.equal(f.session.conference, false);
  });

  test(`${label} discards interrupted output, waits for break, and keeps call open`, async () => {
    const f = fixture(label); let release;
    f.media.breakAudioPlayback = (id) => {
      f.breaks.push(id);
      return new Promise(resolve => { release = resolve; });
    };
    f.session.outputTurnChunks.push(Buffer.from([1, 0]));
    f.session.outputTurnBytes = 2;
    f.api.handleGeminiMessage(f.session, Buffer.from(JSON.stringify({serverContent: {
      interrupted: true, modelTurn: {parts: [{inlineData: {data: 'AQA='}}]}
    }})));
    let finished = false;
    f.session.playbackChain.then(() => { finished = true; });
    await Promise.resolve(); assert.equal(finished, false);
    assert.equal(f.session.outputTurnBytes, 0);
    assert.equal(f.session.outputTurnChunks.length, 0);
    assert.deepEqual(f.breaks, [uuid]);
    release(); await f.session.playbackChain;
    await f.api.playAudioSegment(f.session, Buffer.from([1, 0]), 24000, 0);
    assert.equal(f.broadcasts.length, 0);
    assert.equal(f.socket.readyState, 1);
    assert.equal(f.session.gemini.readyState, 1);
    f.socket.emit('message', Buffer.from([2, 0]), true);
    assert.equal(f.session.gemini.sent.at(-1).realtimeInput.audio.data, 'AgA=');
    f.api.handleGeminiMessage(f.session, Buffer.from(JSON.stringify({serverContent: {
      modelTurn: {parts: [{inlineData: {data: 'AgA='}}]}
    }})));
    assert.equal(f.session.outputTurnBytes, 2);
  });

  test(`${label} interruption during file write prevents stale playback`, async () => {
    const f = fixture(label);
    f.disk.writeFile = async () => {
      f.api.handleGeminiMessage(f.session, Buffer.from('{"serverContent":{"interrupted":true}}'));
    };
    await f.api.playAudioSegment(f.session, Buffer.from([1, 0]), 24000, 0);
    assert.equal(f.broadcasts.length, 0);
    assert.equal(f.removed.length, 1);
  });
}
