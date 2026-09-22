const crypto = require('crypto');
const fs = require('fs/promises');
const path = require('path');
const { WebSocket, WebSocketServer } = require('ws');
const config = require('../config');
const { buildSalesAgentPrompt } = require('../aiSalesAgentProfile');
const freeswitch = require('../lib/freeswitch');

const sessions = new Map();
let server;

const activeSessionCount = () => sessions.size;
const waitUntilReady = async (callId, timeoutMs = 15000) => {
  const deadline = Date.now() + timeoutMs;
  while (Date.now() < deadline) {
    const session = sessions.get(callId);
    if (session?.ready) return true;
    await new Promise((resolve) => setTimeout(resolve, 50));
  }
  return false;
};
const safeEqual = (a, b) => {
  const left = Buffer.from(String(a || ''));
  const right = Buffer.from(String(b || ''));
  return left.length === right.length && crypto.timingSafeEqual(left, right);
};

const pcmWav = (pcm, sampleRate) => {
  const header = Buffer.alloc(44);
  header.write('RIFF', 0);
  header.writeUInt32LE(36 + pcm.length, 4);
  header.write('WAVE', 8);
  header.write('fmt ', 12);
  header.writeUInt32LE(16, 16);
  header.writeUInt16LE(1, 20);
  header.writeUInt16LE(1, 22);
  header.writeUInt32LE(sampleRate, 24);
  header.writeUInt32LE(sampleRate * 2, 28);
  header.writeUInt16LE(2, 32);
  header.writeUInt16LE(16, 34);
  header.write('data', 36);
  header.writeUInt32LE(pcm.length, 40);
  return Buffer.concat([header, pcm]);
};

const playCompletedTurn = async (session) => {
  const chunks = session.outputTurnChunks.splice(0);
  if (!chunks.length) return;

  const sampleRate = session.outputSampleRate || 24000;
  const pcm = Buffer.concat(chunks);
  const directory = path.join(config.freeswitch.recordingsPath, 'ai-playback');
  const filePath = path.join(directory, `${session.callId}-${Date.now()}.wav`);
  await fs.mkdir(directory, { recursive: true });
  await fs.writeFile(filePath, pcmWav(pcm, sampleRate));
  await freeswitch.broadcastAudio(session.callId, filePath);
  console.log('[ai-agent] playback queued', {
    callId: session.callId,
    bytes: pcm.length,
    sampleRate,
    filePath
  });

  const durationMs = Math.ceil((pcm.length / (sampleRate * 2)) * 1000);
  setTimeout(() => fs.unlink(filePath).catch(() => {}), durationMs + 10000);
};

const closeSession = (callId, code = 1000, reason = 'Call ended') => {
  const session = sessions.get(callId);
  if (!session) return;
  sessions.delete(callId);
  if (session.heartbeatTimer) clearInterval(session.heartbeatTimer);
  if (session.geminiTimeoutTimer) clearTimeout(session.geminiTimeoutTimer);
  if (session.silencePromptTimer) clearTimeout(session.silencePromptTimer);
  for (const socket of [session.gemini, session.freeswitch]) {
    if (socket && (socket.readyState === WebSocket.OPEN || socket.readyState === WebSocket.CONNECTING)) {
      try { socket.close(code, reason); } catch (_err) {}
    }
  }
};

const sendSetup = (session) => {
  const voiceMap = { professional: 'Charon', warm: 'Aoede', confident: 'Fenrir' };
  const prompt = buildSalesAgentPrompt({ offerSummary: session.settings.goal });
  session.gemini.send(JSON.stringify({
    setup: {
      model: `models/${config.aiAgent.model.replace(/^models\//, '')}`,
      generationConfig: {
        responseModalities: ['AUDIO'],
        speechConfig: {
          languageCode: 'en-US',
          voiceConfig: { prebuiltVoiceConfig: { voiceName: voiceMap[session.settings.voice] || config.aiAgent.voice } }
        }
      },
      realtimeInputConfig: {
        automaticActivityDetection: {
          disabled: false,
          startOfSpeechSensitivity: 'START_SENSITIVITY_HIGH',
          endOfSpeechSensitivity: 'END_SENSITIVITY_HIGH',
          prefixPaddingMs: 200,
          silenceDurationMs: 700
        }
      },
      systemInstruction: { parts: [{ text: `${prompt}\n\nCall continuity rules: Keep the conversation active until the caller hangs up. After greeting, listen and answer every caller turn. Never announce that you are ending the call and never stop after only one response. Keep replies concise and finish each reply with a useful question when appropriate.` }] },
      inputAudioTranscription: {},
      outputAudioTranscription: {}
    }
  }));
};

const scheduleSilenceFollowUp = (session, delayMs = 12000) => {
  if (session.silencePromptTimer) clearTimeout(session.silencePromptTimer);
  session.silencePromptTimer = setTimeout(() => {
    if (sessions.get(session.callId) !== session || !session.ready || session.gemini.readyState !== WebSocket.OPEN) {
      return;
    }
    if (Date.now() - session.lastCallerSpeechAt < 3000) {
      scheduleSilenceFollowUp(session, 4000);
      return;
    }
    session.gemini.send(JSON.stringify({
      realtimeInput: {
        text: 'The caller is still connected but has been quiet. Continue naturally with one brief helpful follow-up question. Do not end the call.'
      }
    }));
    console.log('[ai-agent] requested silence follow-up', { callId: session.callId });
  }, delayMs);
};

const sendInitialGreeting = (session) => {
  session.gemini.send(JSON.stringify({
    realtimeInput: {
      text: 'Start the call now in English only. Briefly identify yourself as the hospital AI appointment assistant, then ask for the patient’s full name.'
    }
  }));
};

const handleGeminiMessage = (session, data) => {
  let message;
  try {
    message = JSON.parse(data.toString());
  } catch (err) {
    console.warn('[ai-agent] ignored non-JSON Gemini message', { callId: session.callId, error: err.message });
    return;
  }
  if (message.error) {
    console.error('[ai-agent] Gemini API error', {
      callId: session.callId,
      code: message.error.code || null,
      status: message.error.status || null,
      message: message.error.message || 'Unknown Gemini error'
    });
    closeSession(session.callId, 1011, 'Gemini API error');
    return;
  }
  if (message.setupComplete) {
    session.ready = true;
    console.log('[ai-agent] Gemini Live ready', { callId: session.callId, model: config.aiAgent.model });
    sendInitialGreeting(session);
    for (const chunk of session.queue.splice(0)) session.gemini.send(chunk);
    return;
  }
  const content = message.serverContent || {};
  const parts = content.modelTurn?.parts || [];
  for (const part of parts) {
    const audio = part.inlineData || part.inline_data;
    if (audio?.data) {
      const mimeType = audio.mimeType || audio.mime_type || 'audio/pcm;rate=24000';
      const sampleRate = Number((mimeType.match(/rate=(\d+)/i) || [])[1]) || 24000;
      session.outputSampleRate = sampleRate;
      session.outputTurnChunks.push(Buffer.from(audio.data, 'base64'));
      session.outputAudioChunks += 1;
    }
  }
  const inputText = content.inputTranscription?.text;
  const outputText = content.outputTranscription?.text;
  if (inputText) session.transcript.push({ role: 'caller', text: inputText });
  if (outputText) session.transcript.push({ role: 'agent', text: outputText });
  if (content.turnComplete) {
    console.log(
      `[ai-agent] Gemini turn complete callId=${session.callId}` +
      ` inputAudioChunks=${session.inputAudioChunks}` +
      ` outputAudioChunks=${session.outputAudioChunks}` +
      ` transcriptEntries=${session.transcript.length}`
    );
    session.playbackChain = session.playbackChain
      .then(() => playCompletedTurn(session))
      .catch((err) => {
        console.error('[ai-agent] playback failed', { callId: session.callId, error: err.message });
      });
    scheduleSilenceFollowUp(session);
  }
  if (content.interrupted) {
    // uuid_break also terminates the parked application and therefore hangs up
    // this direct AI call. Drop only audio that has not been queued for native
    // playback yet and keep the channel parked/alive.
    session.outputTurnChunks.length = 0;
    console.log('[ai-agent] Gemini playback interrupted', { callId: session.callId });
  }
};

const attachGemini = (freeswitchSocket, request, callId, settings) => {
  const endpoint = 'wss://generativelanguage.googleapis.com/ws/google.ai.generativelanguage.v1beta.GenerativeService.BidiGenerateContent';
  const apiKey = config.aiAgent.apiKey;
  const url = `${endpoint}?key=${encodeURIComponent(apiKey)}`;
  console.log('[ai-agent] creating Gemini WS', { callId, url: url.replace(/key=[^&]+/, 'key=***') });
  const gemini = new WebSocket(url, {
    perMessageDeflate: false,
    handshakeTimeout: 15000,
    keepAlive: 30000,
  });
  const session = {
    callId,
    settings,
    freeswitch: freeswitchSocket,
    gemini,
    queue: [],
    ready: false,
    transcript: [],
    heartbeatTimer: null,
    geminiTimeoutTimer: null,
    inputAudioChunks: 0,
    outputAudioChunks: 0,
    outputTurnChunks: [],
    outputSampleRate: 24000,
    playbackChain: Promise.resolve(),
    silencePromptTimer: null,
    lastCallerSpeechAt: Date.now()
  };
  sessions.set(callId, session);
  const connectTimer = setTimeout(() => {
    console.log('[ai-agent] Gemini connect timeout', { callId, queue: session.queue.length });
    closeSession(callId, 1011, 'Gemini connection timeout');
  }, config.aiAgent.connectTimeoutMs);
  session.geminiTimeoutTimer = setTimeout(() => {
    console.log('[ai-agent] Gemini inactivity timeout', { callId });
    closeSession(callId, 1011, 'Gemini inactivity timeout');
  }, 120000);
  const logPrefix = () => `[ai-agent][${new Date().toISOString()}] ${callId}`;
  console.log(logPrefix(), 'attachGemini after WS create', { readyState: gemini.readyState });
  gemini.on('open', () => {
    console.log(logPrefix(), 'Gemini open', { readyState: gemini.readyState });
    clearTimeout(connectTimer);
    sendSetup(session);
  });
  gemini.on('message', (data) => {
    clearTimeout(session.geminiTimeoutTimer);
    session.geminiTimeoutTimer = setTimeout(() => {
      console.log('[ai-agent] Gemini inactivity timeout', { callId });
      closeSession(callId, 1011, 'Gemini inactivity timeout');
    }, 120000);
    handleGeminiMessage(session, data);
  });
  gemini.on('error', (err) => {
    console.error(logPrefix(), 'Gemini error', { error: err.message, code: err.code });
    clearTimeout(connectTimer);
    clearTimeout(session.geminiTimeoutTimer);
  });
  gemini.on('close', (code, reason) => {
    console.log(logPrefix(), 'Gemini close', {
      code,
      reason: reason?.toString(),
      transcript: session.transcript.length,
      inputAudioChunks: session.inputAudioChunks,
      outputAudioChunks: session.outputAudioChunks
    });
    clearTimeout(connectTimer);
    clearTimeout(session.geminiTimeoutTimer);
    closeSession(callId, 1011, 'Gemini disconnected');
  });
  gemini.on('ping', () => {
    console.log(logPrefix(), 'Gemini ping received');
  });
  gemini.on('pong', () => {
    console.log(logPrefix(), 'Gemini pong received');
  });
  session.heartbeatTimer = setInterval(() => {
    if (gemini.readyState === WebSocket.OPEN) {
      try { gemini.ping(); } catch (_err) {
        console.error(logPrefix(), 'Failed to send ping', { error: _err.message });
      }
    }
  }, 25000);
  freeswitchSocket.on('message', (data, isBinary) => {
    if (!isBinary) return;
    const pcm = Buffer.from(data);
    let peak = 0;
    for (let offset = 0; offset + 1 < pcm.length; offset += 2) {
      peak = Math.max(peak, Math.abs(pcm.readInt16LE(offset)));
    }
    if (peak >= 500) session.lastCallerSpeechAt = Date.now();
    session.inputAudioChunks += 1;
    clearTimeout(session.geminiTimeoutTimer);
    session.geminiTimeoutTimer = setTimeout(() => {
      console.log('[ai-agent] Gemini inactivity timeout', { callId });
      closeSession(callId, 1011, 'Gemini inactivity timeout');
    }, 120000);
    const payload = JSON.stringify({ realtimeInput: { audio: { data: pcm.toString('base64'), mimeType: 'audio/pcm;rate=16000' } } });
    if (session.ready && gemini.readyState === WebSocket.OPEN) {
      gemini.send(payload);
    } else if (session.queue.length < 100) {
      session.queue.push(payload);
      console.log(logPrefix(), 'queue audio', { queue: session.queue.length });
    }
  });
  freeswitchSocket.on('close', () => {
    console.log(logPrefix(), 'FreeSWITCH closed');
    clearTimeout(connectTimer);
    clearTimeout(session.geminiTimeoutTimer);
    closeSession(callId);
  });
  freeswitchSocket.on('error', (err) => console.error(logPrefix(), 'FreeSWITCH error', { error: err.message }));
};

const initGeminiLiveBridge = (httpServer, getSettings) => {
  if (server) return server;
  server = new WebSocketServer({ noServer: true });
  httpServer.on('upgrade', async (request, socket, head) => {
    const url = new URL(request.url, 'http://127.0.0.1');
    console.log('[ai-agent] ALL upgrade request:', request.url, 'pathname:', url.pathname);
    if (url.pathname !== '/ai-audio') return;
    const callId = url.searchParams.get('call_id');
    const token = url.searchParams.get('token');
    console.log('[ai-agent] ai-audio request', { callId, tokenPresent: Boolean(token) });
    if (!/^[0-9a-f-]{36}$/i.test(callId || '') || !safeEqual(token, config.aiAgent.bridgeToken)) {
      console.log('[ai-agent] rejecting unauthorized');
      socket.write('HTTP/1.1 401 Unauthorized\r\nConnection: close\r\n\r\n');
      socket.destroy();
      return;
    }
    const settings = await getSettings().catch((err) => {
      console.log('[ai-agent] settings error:', err.message);
      return null;
    });
    console.log('[ai-agent] settings loaded:', settings?.enabled, 'apiKey:', !!config.aiAgent.apiKey, 'sessions:', sessions.size, 'max:', config.aiAgent.maxSessions);
    if (!settings?.enabled || !config.aiAgent.apiKey || sessions.size >= config.aiAgent.maxSessions) {
      console.log('[ai-agent] rejecting unavailable');
      socket.write('HTTP/1.1 503 Service Unavailable\r\nConnection: close\r\n\r\n');
      socket.destroy();
      return;
    }
    server.handleUpgrade(request, socket, head, (ws) => attachGemini(ws, request, callId, settings));
  });
  return server;
};

module.exports = { initGeminiLiveBridge, closeSession, activeSessionCount, waitUntilReady };
