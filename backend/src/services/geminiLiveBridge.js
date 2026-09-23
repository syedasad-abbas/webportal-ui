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
const waitUntilReady = async (callId, timeoutMs = 35000) => {
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

const OUTPUT_SEGMENT_MS = 900;
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

const playAudioSegment = async (session, pcm, sampleRate, generation) => {
  if (
    sessions.get(session.callId) !== session ||
    generation !== session.playbackGeneration ||
    !pcm.length ||
    session.freeswitch.readyState !== WebSocket.OPEN
  ) return;
  session.playbackSegment += 1;
  const playbackWav = pcmWav(pcm, sampleRate);
  const directory = path.join(config.freeswitch.recordingsPath, 'ai-playback');
  const filePath = path.join(
    directory,
    `${session.callId}-${Date.now()}-${session.playbackSegment}.wav`
  );
  await fs.mkdir(directory, { recursive: true });
  await fs.writeFile(filePath, playbackWav);
  await freeswitch.broadcastAudio(session.callId, filePath);
  console.log('[ai-agent] playback segment broadcast', {
    callId: session.callId,
    bytes: playbackWav.length,
    sampleRate,
    format: 'wav'
  });

  const durationMs = Math.ceil((pcm.length / (sampleRate * 2)) * 1000);
  setTimeout(() => fs.unlink(filePath).catch(() => {}), durationMs + 5000);
  await new Promise((resolve) => setTimeout(resolve, Math.max(1, durationMs)));
};

const queueOutputAudio = (session, flush = false) => {
  const sampleRate = session.outputSampleRate || 24000;
  const segmentBytes = Math.floor(sampleRate * 2 * (OUTPUT_SEGMENT_MS / 1000));
  if (!flush && session.outputTurnBytes < segmentBytes) return;

  const pcm = Buffer.concat(session.outputTurnChunks.splice(0));
  const generation = session.playbackGeneration;
  session.outputTurnBytes = 0;
  if (!pcm.length) return;

  session.playbackChain = session.playbackChain
    .then(() => playAudioSegment(session, pcm, sampleRate, generation))
    .catch((err) => {
      console.error('[ai-agent] playback failed', { callId: session.callId, error: err.message });
    });
};

const closeSession = (callId, code = 1000, reason = 'Call ended') => {
  const session = sessions.get(callId);
  if (!session) return;
  sessions.delete(callId);
  if (session.heartbeatTimer) clearInterval(session.heartbeatTimer);
  if (session.geminiTimeoutTimer) clearTimeout(session.geminiTimeoutTimer);
  if (session.silencePromptTimer) clearTimeout(session.silencePromptTimer);
  if (session.connectTimer) clearTimeout(session.connectTimer);
  if (session.retryTimer) clearTimeout(session.retryTimer);
  for (const socket of [session.gemini, session.freeswitch]) {
    if (socket && (socket.readyState === WebSocket.OPEN || socket.readyState === WebSocket.CONNECTING)) {
      try { socket.close(code, reason); } catch (_err) {}
    }
  }
};

const sendSetup = (session) => {
  // Keep every UI style on a mature male voice. The previous `warm` mapping
  // selected Aoede, whose lighter delivery was unsuitable for this agent.
  const voiceMap = { professional: 'Orus', warm: 'Orus', confident: 'Orus' };
  const selectedVoice = voiceMap[session.settings.voice] || config.aiAgent.voice;
  session.selectedVoice = selectedVoice;
  const currentDate = new Intl.DateTimeFormat('en-GB', {
    timeZone: config.metrics?.activityTimezone || 'Asia/Karachi',
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  }).format(new Date());
  const prompt = buildSalesAgentPrompt({ offerSummary: session.settings.goal, currentDate });
  session.gemini.send(JSON.stringify({
    setup: {
      model: `models/${config.aiAgent.model.replace(/^models\//, '')}`,
      generationConfig: {
        responseModalities: ['AUDIO'],
        speechConfig: {
          languageCode: 'en-US',
          voiceConfig: { prebuiltVoiceConfig: { voiceName: selectedVoice } }
        }
      },
      realtimeInputConfig: {
        automaticActivityDetection: {
          disabled: false,
          startOfSpeechSensitivity: 'START_SENSITIVITY_HIGH',
          endOfSpeechSensitivity: 'END_SENSITIVITY_LOW',
          prefixPaddingMs: 300,
          silenceDurationMs: 1500
        }
      },
      systemInstruction: { parts: [{ text: `${prompt}\n\nCall continuity rules: Keep the conversation active until the caller hangs up. After greeting, listen and answer every caller turn. Never announce that you are ending the call and never stop after only one response. Keep replies concise and finish each reply with a useful question when appropriate.` }] },
      inputAudioTranscription: {
        // Bias recognition toward Pakistani/South-Asian English while keeping
        // common international English accents available as fallbacks.
        languageCodes: ['en-PK', 'en-IN', 'en-GB', 'en-US'],
        mode: 'VERBATIM',
        customVocabulary: [
          'appointment',
          'doctor',
          'patient',
          'consultant',
          'cardiologist',
          'dermatologist',
          'pediatrician',
          'gynecologist',
          'zero',
          'oh',
          'one',
          'two',
          'three',
          'four',
          'five',
          'six',
          'seven',
          'eight',
          'nine',
          'double',
          'triple',
          'country code',
          'area code',
          'Muhammad',
          'Mohammad',
          'Ahmed',
          'Ahmad',
          'Ali',
          'Hassan',
          'Hussain',
          'Abdullah',
          'Abdul Rehman',
          'Usman',
          'Umer',
          'Omar',
          'Hamza',
          'Bilal',
          'Imran',
          'Fatima',
          'Ayesha',
          'Aisha',
          'Zainab',
          'Maryam',
          'Khan',
          'Qureshi',
          'Siddiqui',
          'Sheikh',
          'Chaudhry',
          'Syed',
          'Raza',
          'Rizvi',
          'Bukhari',
          'Abbasi',
          'Malik',
          'Tariq',
          'Shahzad',
          'Shahid',
          'Sajid',
          'Faisal',
          'Fahad',
          'Farhan',
          'Salman',
          'Adnan',
          'Arslan',
          'Waqas',
          'Waqar',
          'Nouman',
          'Hafsa',
          'Hira',
          'Iqra',
          'Sana',
          'Saba',
          'Maham',
          'Mahnoor',
          'Laiba',
          'Rabia',
          'Nadia'
        ]
      },
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
      text: 'Start the call naturally. Briefly identify yourself as the hospital appointment assistant, warmly ask for the patient’s full name, and then wait for the complete answer.'
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
    console.log('[ai-agent] Gemini Live ready', {
      callId: session.callId,
      model: config.aiAgent.model,
      voice: session.selectedVoice
    });
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
      const chunk = Buffer.from(audio.data, 'base64');
      session.outputTurnChunks.push(chunk);
      session.outputTurnBytes += chunk.length;
      session.outputAudioChunks += 1;
      queueOutputAudio(session);
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
    queueOutputAudio(session, true);
    scheduleSilenceFollowUp(session);
  }
  if (content.interrupted) {
    // uuid_break also terminates the parked application and therefore hangs up
    // this direct AI call. Drop only audio that has not been queued for native
    // playback yet and keep the channel parked/alive.
    session.outputTurnChunks.length = 0;
    session.outputTurnBytes = 0;
    session.playbackGeneration += 1;
    console.log('[ai-agent] Gemini playback interrupted', { callId: session.callId });
  }
};

const connectGemini = (session) => {
  const endpoint = 'wss://generativelanguage.googleapis.com/ws/google.ai.generativelanguage.v1beta.GenerativeService.BidiGenerateContent';
  const apiKey = config.aiAgent.apiKey;
  const url = `${endpoint}?key=${encodeURIComponent(apiKey)}`;
  const { callId } = session;
  session.geminiConnectAttempt += 1;
  const attempt = session.geminiConnectAttempt;
  console.log('[ai-agent] creating Gemini WS', { callId, url: url.replace(/key=[^&]+/, 'key=***') });
  const gemini = new WebSocket(url, {
    perMessageDeflate: false,
    handshakeTimeout: config.aiAgent.connectTimeoutMs,
    keepAlive: 30000,
    family: 4,
  });
  session.gemini = gemini;
  session.ready = false;
  session.connectTimer = setTimeout(() => {
    if (sessions.get(callId) !== session || session.gemini !== gemini) return;
    console.log('[ai-agent] Gemini connect timeout', { callId, attempt, queue: session.queue.length });
    gemini.terminate();
  }, config.aiAgent.connectTimeoutMs);
  session.geminiTimeoutTimer = setTimeout(() => {
    console.log('[ai-agent] Gemini inactivity timeout', { callId });
    closeSession(callId, 1011, 'Gemini inactivity timeout');
  }, 120000);
  const logPrefix = () => `[ai-agent][${new Date().toISOString()}] ${callId}`;
  console.log(logPrefix(), 'Gemini connection attempt', { attempt, readyState: gemini.readyState });
  gemini.on('open', () => {
    if (session.gemini !== gemini) return;
    console.log(logPrefix(), 'Gemini open', { attempt, readyState: gemini.readyState });
    clearTimeout(session.connectTimer);
    session.connectTimer = null;
    session.geminiConnectAttempt = 0;
    sendSetup(session);
  });
  gemini.on('message', (data) => {
    if (session.gemini !== gemini) return;
    clearTimeout(session.geminiTimeoutTimer);
    session.geminiTimeoutTimer = setTimeout(() => {
      console.log('[ai-agent] Gemini inactivity timeout', { callId });
      closeSession(callId, 1011, 'Gemini inactivity timeout');
    }, 120000);
    handleGeminiMessage(session, data);
  });
  gemini.on('error', (err) => {
    console.error(logPrefix(), 'Gemini error', { attempt, error: err.message, code: err.code });
  });
  gemini.on('close', (code, reason) => {
    if (sessions.get(callId) !== session || session.gemini !== gemini) return;
    console.log(logPrefix(), 'Gemini close', {
      attempt,
      code,
      reason: reason?.toString(),
      transcript: session.transcript.length,
      inputAudioChunks: session.inputAudioChunks,
      outputAudioChunks: session.outputAudioChunks
    });
    clearTimeout(session.connectTimer);
    session.connectTimer = null;
    clearTimeout(session.geminiTimeoutTimer);
    session.geminiTimeoutTimer = null;
    session.ready = false;
    if (session.freeswitch.readyState === WebSocket.OPEN && attempt < 5) {
      const retryDelayMs = Math.min(4000, Math.max(1000, attempt * 1000));
      console.warn('[ai-agent] retrying Gemini connection', { callId, attempt: attempt + 1, retryDelayMs });
      session.retryTimer = setTimeout(() => connectGemini(session), retryDelayMs);
      return;
    }
    closeSession(callId, 1011, 'Gemini disconnected after retries');
  });
  gemini.on('ping', () => {
    console.log(logPrefix(), 'Gemini ping received');
  });
  gemini.on('pong', () => {
    console.log(logPrefix(), 'Gemini pong received');
  });
};

const attachGemini = (freeswitchSocket, request, callId, settings) => {
  const session = {
    callId,
    settings,
    freeswitch: freeswitchSocket,
    gemini: null,
    queue: [],
    ready: false,
    transcript: [],
    heartbeatTimer: null,
    geminiTimeoutTimer: null,
    connectTimer: null,
    retryTimer: null,
    geminiConnectAttempt: 0,
    inputAudioChunks: 0,
    outputAudioChunks: 0,
    outputTurnChunks: [],
    outputTurnBytes: 0,
    outputSampleRate: 24000,
    playbackChain: Promise.resolve(),
    playbackSegment: 0,
    playbackGeneration: 0,
    silencePromptTimer: null,
    lastCallerSpeechAt: Date.now()
  };
  sessions.set(callId, session);
  connectGemini(session);
  const logPrefix = () => `[ai-agent][${new Date().toISOString()}] ${callId}`;
  session.heartbeatTimer = setInterval(() => {
    const gemini = session.gemini;
    if (gemini?.readyState === WebSocket.OPEN) {
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
    if (session.ready && session.gemini?.readyState === WebSocket.OPEN) {
      session.gemini.send(payload);
    } else {
      // Retain the most recent audio while a slow Gemini handshake completes.
      // Dropping only the oldest chunk prevents an unbounded queue without
      // discarding everything the caller says during connection setup.
      if (session.queue.length >= 150) session.queue.shift();
      session.queue.push(payload);
      if (session.queue.length === 1 || session.queue.length % 25 === 0) {
        console.log(logPrefix(), 'queue audio', { queue: session.queue.length });
      }
    }
  });
  freeswitchSocket.on('close', () => {
    console.log(logPrefix(), 'FreeSWITCH closed');
    clearTimeout(session.connectTimer);
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
