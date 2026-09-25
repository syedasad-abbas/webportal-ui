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

const FIRST_OUTPUT_SEGMENT_MS = 1200;
const CONTINUATION_SEGMENT_MS = 2800;

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
  const segmentMs = session.outputTurnStarted
    ? CONTINUATION_SEGMENT_MS
    : FIRST_OUTPUT_SEGMENT_MS;
  const segmentBytes = Math.floor(sampleRate * 2 * (segmentMs / 1000));
  if (!flush && session.outputTurnBytes < segmentBytes) return;

  const pcm = Buffer.concat(session.outputTurnChunks.splice(0));
  const generation = session.playbackGeneration;
  session.outputTurnBytes = 0;
  if (!pcm.length) return;
  session.outputTurnStarted = true;
  session.playbackQueueDepth += 1;

  session.playbackChain = session.playbackChain
    .then(() => playAudioSegment(session, pcm, sampleRate, generation))
    .catch((err) => {
      console.error('[ai-agent] playback failed', { callId: session.callId, error: err.message });
    })
    .finally(() => {
      session.playbackQueueDepth = Math.max(0, session.playbackQueueDepth - 1);
      session.lastAgentPlaybackEndedAt = Date.now();
    });
};

const closeSession = (callId, code = 1000, reason = 'Call ended') => {
  const session = sessions.get(callId);
  if (!session) return;
  sessions.delete(callId);
  if (session.heartbeatTimer) clearInterval(session.heartbeatTimer);
  if (session.geminiTimeoutTimer) clearTimeout(session.geminiTimeoutTimer);
  if (session.silencePromptTimer) clearTimeout(session.silencePromptTimer);
  if (session.phoneFinalizeTimer) clearTimeout(session.phoneFinalizeTimer);
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
      tools: [{ googleSearch: {} }],
      realtimeInputConfig: {
        automaticActivityDetection: {
          // FreeSWITCH sends continuous low-level audio, so server VAD can
          // wait forever. Local energy VAD below supplies explicit boundaries.
          disabled: true
        }
      },
      systemInstruction: { parts: [{ text: `${prompt}\n\nCall continuity rules: Keep the conversation active until the caller hangs up. After greeting, listen and answer every caller turn. Never announce that you are ending the call and never stop after only one response. Keep replies concise and finish each reply with a useful question when appropriate. You receive the caller's live audio directly; transcribe carefully and preserve spelled names and digits exactly. Google Search is available only for general public hospital information when the hospital is identified. Never search for a patient or use web results to decide how the caller spells a name. Confirm uncertain names directly with the caller. Never infer doctor availability or claim a booking from search results.` }] },
      inputAudioTranscription: {
        // Keep recognition in English. Unrestricted auto-detection was
        // misclassifying Pakistani English as Spanish and German.
        languageCodes: ['en-IN', 'en-GB', 'en-US'],
        mode: 'VERBATIM',
        customVocabulary: [
          'patient name', 'full name', 'doctor name', 'hospital appointment',
          'mobile number', 'phone number', 'appointment date', 'spell my name',
          'Muhammad', 'Mohammad', 'Ahmed', 'Ahmad', 'Abdul', 'Rehman', 'Rahman',
          'Hussain', 'Hassan', 'Usman', 'Umer', 'Ayesha', 'Fatima', 'Zainab',
          'Qasim', 'Khan', 'Qureshi', 'Siddiqui', 'Sheikh', 'Chaudhry', 'Syed',
          'zero', 'oh', 'one', 'two', 'three', 'four', 'five', 'six', 'seven',
          'eight', 'nine', 'double zero', 'double one', 'double two',
          'double three', 'double four', 'double five', 'double six',
          'double seven', 'double eight', 'double nine', 'triple',
          'spell', 'spelling', 'letter by letter', 'space',
          'alpha', 'bravo', 'charlie', 'delta', 'echo', 'foxtrot', 'golf',
          'hotel', 'india', 'juliett', 'kilo', 'lima', 'mike', 'november',
          'oscar', 'papa', 'quebec', 'romeo', 'sierra', 'tango', 'uniform',
          'victor', 'whiskey', 'x-ray', 'yankee', 'zulu'
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
    if (
      session.silenceFollowUpSent ||
      session.modelGenerating ||
      session.playbackQueueDepth > 0 ||
      session.outputTurnBytes > 0 ||
      Date.now() - session.lastAgentPlaybackEndedAt < 1500
    ) {
      if (!session.silenceFollowUpSent) scheduleSilenceFollowUp(session, 2000);
      return;
    }
    if (Date.now() - session.lastCallerSpeechAt < 2500) {
      scheduleSilenceFollowUp(session, 2500);
      return;
    }
    session.silenceFollowUpSent = true;
    sendControlText(session, 'The caller is still connected but has been quiet. Ask one brief, natural follow-up question for the current missing appointment detail. Do not repeat the greeting and do not end the call.');
    console.log('[ai-agent] requested silence follow-up', { callId: session.callId });
  }, delayMs);
};

const sendInitialGreeting = (session) => {
  sendControlText(session, 'Start the call naturally. Briefly identify yourself as the hospital appointment assistant, warmly ask for the patient’s full name, and then wait for the complete answer.');
};

const PHONE_DIGIT_WORDS = Object.freeze({
  zero: '0', oh: '0', o: '0', nought: '0',
  one: '1', won: '1', two: '2', too: '2', three: '3', tree: '3',
  four: '4', fower: '4', five: '5', fife: '5', six: '6',
  seven: '7', eight: '8', ate: '8', nine: '9', niner: '9'
});

const extractSpokenDigits = (text) => {
  const tokens = String(text || '').toLowerCase().match(/\d+|[a-z]+/g) || [];
  let repeat = 1;
  let digits = '';
  for (const token of tokens) {
    if (token === 'double') {
      repeat = 2;
      continue;
    }
    if (token === 'triple') {
      repeat = 3;
      continue;
    }
    const value = /^\d+$/.test(token) ? token : PHONE_DIGIT_WORDS[token];
    if (value === undefined) {
      repeat = 1;
      continue;
    }
    digits += value.repeat(repeat);
    repeat = 1;
  }
  return digits;
};

const expectedPakistaniPhoneLength = (digits) => {
  if (digits.startsWith('03')) return 11;
  if (digits.startsWith('923')) return 12;
  if (digits.startsWith('00923')) return 14;
  return null;
};

const isNegativeConfirmation = (text) => /\b(no|nope|wrong|incorrect|not correct|not right|isn'?t right|isn'?t correct|heard me wrong|got it wrong|didn'?t say that)\b/i.test(text || '');
const isPositiveConfirmation = (text) => /\b(yes|yeah|correct|right|that is right|that'?s right)\b/i.test(text || '');
const isPhoneRepeatRequest = (text) => /\b(repeat|say again|read back|what (?:number|digits)|what did (?:i|you) say)\b/i.test(text || '');

const normalizePatientName = (text) => String(text || '')
  .replace(/^[\s"'“”]+|[\s"'“”.,!?]+$/g, '')
  .replace(/^(?:(?:yes|yeah|no|nope)[,\s]+)?(?:(?:my|the patient'?s?)\s+(?:full\s+)?name\s+is|(?:the\s+)?name\s+is|the\s+patient\s+is|i\s+said|it\s+is|it'?s|this\s+is|i\s+am|i'?m)\s+/i, '')
  .replace(/\s+(?:is|that'?s)\s+the\s+(?:patient'?s?\s+)?name$/i, '')
  .replace(/\s+/g, ' ')
  .trim();

const correctedNameFromReply = (text) => {
  const source = String(text || '');
  const explicitValue = source.match(
    /(?:i\s+(?:said|meant)|(?:the\s+)?(?:correct\s+)?name\s+is|it\s+is|it'?s|change\s+it\s+to)\s+(.+)$/i
  )?.[1];
  if (explicitValue) return normalizePatientName(explicitValue);
  const afterNo = source.replace(/^\s*(?:no|nope)\b[\s,.:;-]*/i, '');
  if (/^(?:that'?s?\s+)?(?:wrong|incorrect|not\s+(?:right|correct))\b/i.test(afterNo)) return '';
  return normalizePatientName(afterNo);
};

const looksLikePatientName = (text) => (
  /[a-z][a-z'-]+/i.test(text || '') &&
  !isNegativeConfirmation(text) &&
  !isPositiveConfirmation(text)
);

const PHONETIC_LETTERS = Object.freeze({
  alpha: 'A', bravo: 'B', charlie: 'C', delta: 'D', echo: 'E', foxtrot: 'F',
  golf: 'G', hotel: 'H', india: 'I', juliett: 'J', juliet: 'J', kilo: 'K',
  lima: 'L', mike: 'M', november: 'N', oscar: 'O', papa: 'P', quebec: 'Q',
  romeo: 'R', sierra: 'S', tango: 'T', uniform: 'U', victor: 'V',
  whiskey: 'W', whisky: 'W', xray: 'X', yankee: 'Y', zulu: 'Z'
});

const extractSpelledName = (text) => {
  const tokens = String(text || '').toLowerCase().match(/[a-z]+/g) || [];
  const parts = [''];
  let letterCount = 0;
  for (const token of tokens) {
    if (token === 'space') {
      if (parts[parts.length - 1]) parts.push('');
      continue;
    }
    const letter = token.length === 1 ? token.toUpperCase() : PHONETIC_LETTERS[token];
    if (!letter) continue;
    parts[parts.length - 1] += letter;
    letterCount += 1;
  }
  if (letterCount < 2) return '';
  return parts.filter(Boolean).map((part) => (
    `${part.charAt(0)}${part.slice(1).toLowerCase()}`
  )).join(' ');
};

const sendControlText = (session, text) => {
  if (!session.ready || session.gemini?.readyState !== WebSocket.OPEN) return;
  if (session.silencePromptTimer) {
    clearTimeout(session.silencePromptTimer);
    session.silencePromptTimer = null;
  }
  if (text) session.modelGenerating = true;
  session.gemini.send(JSON.stringify({ realtimeInput: { text } }));
};

const sendActivitySignal = (session, signal) => {
  if (!session.ready || session.gemini?.readyState !== WebSocket.OPEN) return;
  session.gemini.send(JSON.stringify({ realtimeInput: { [signal]: {} } }));
};

const discardPendingOutput = (session) => {
  session.outputTurnChunks.length = 0;
  session.outputTurnBytes = 0;
  session.playbackGeneration += 1;
};

const sendAudioPayload = (session, payload) => {
  if (!session.ready || session.gemini?.readyState !== WebSocket.OPEN) return false;
  session.gemini.send(payload);
  return true;
};

const handleCallerAudio = (session, payload, rms, durationMs) => {
  const speechStartThreshold = 420;
  const speechContinueThreshold = 260;

  if (!session.callerSpeechActive) {
    session.audioPreRoll.push(payload);
    if (session.audioPreRoll.length > 5) session.audioPreRoll.shift();
    session.speechCandidateFrames = rms >= speechStartThreshold
      ? session.speechCandidateFrames + 1
      : 0;
    if (session.speechCandidateFrames < 2) return;

    session.callerSpeechActive = true;
    session.callerSilenceMs = 0;
    session.speechCandidateFrames = 0;
    sendActivitySignal(session, 'activityStart');
    for (const bufferedPayload of session.audioPreRoll.splice(0)) {
      sendAudioPayload(session, bufferedPayload);
    }
    return;
  }

  sendAudioPayload(session, payload);
  if (rms >= speechContinueThreshold) {
    session.callerSilenceMs = 0;
    return;
  }

  session.callerSilenceMs += durationMs;
  if (session.callerSilenceMs < 650) return;

  // Explicit manual-VAD boundaries finalize every caller utterance even when
  // the FreeSWITCH stream continues carrying background noise.
  sendActivitySignal(session, 'activityEnd');
  session.callerSpeechActive = false;
  session.callerSilenceMs = 0;
  session.audioPreRoll.length = 0;
  console.log('[ai-agent] caller audio turn ended', { callId: session.callId });
};

const confirmCapturedName = (session, name) => {
  session.nameCandidate = name;
  session.nameAwaitingConfirmation = true;
  session.nameControlResponse = true;
  sendControlText(
    session,
    `Internal verified speech capture: the caller gave the patient name as “${name}”. ` +
    'Repeat that exact name naturally and ask only whether you heard it correctly. Do not ask for the phone number yet.'
  );
};

const confirmCapturedPhone = (session, digits) => {
  if (session.phoneFinalizeTimer) clearTimeout(session.phoneFinalizeTimer);
  session.phoneFinalizeTimer = null;
  session.phoneAwaitingConfirmation = true;
  session.phoneControlResponse = true;
  sendControlText(
    session,
    `Internal verified phone capture: the caller said ${digits.split('').join(' ')}. ` +
    'Read these exact digits back once in small groups and ask only whether the number is correct.'
  );
};

const scheduleGenericPhoneConfirmation = (session) => {
  if (session.phoneFinalizeTimer) clearTimeout(session.phoneFinalizeTimer);
  if (session.phoneDigits.length < 7 || session.phoneDigits.length > 15) return;
  session.phoneFinalizeTimer = setTimeout(() => {
    session.phoneFinalizeTimer = null;
    if (
      sessions.get(session.callId) === session &&
      session.phoneCollecting &&
      !session.phoneAwaitingConfirmation
    ) {
      discardPendingOutput(session);
      confirmCapturedPhone(session, session.phoneDigits);
    }
  }, 1200);
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
    // Pre-ready media mainly contains ringing, room noise, and speech spoken
    // before the greeting. Replaying it here interrupts and confuses turn one.
    session.queue.length = 0;
    session.callerSpeechActive = false;
    session.callerSilenceMs = 0;
    session.speechCandidateFrames = 0;
    session.audioPreRoll.length = 0;
    sendInitialGreeting(session);
    return;
  }
  const content = message.serverContent || {};
  const inputText = content.inputTranscription?.text || '';
  const outputText = content.outputTranscription?.text || '';
  // Direct-audio mode: the model hears caller audio natively, so this
  // deterministic name/number buffer double-checks critical details before
  // allowing playback. Final transcripts may arrive after turnComplete, so
  // control prompts are sent immediately instead of waiting for that event.
  let suppressControlledOutput = (
    (session.initialGreetingComplete && session.nameCollecting && !session.nameAwaitingConfirmation && !session.nameControlResponse) ||
    (session.phoneCollecting && !session.phoneAwaitingConfirmation && !session.phoneControlResponse)
  );

  if (inputText && session.nameCollecting) {
    const capturedName = normalizePatientName(inputText);
    const spelledName = extractSpelledName(inputText);
    const usableName = spelledName || capturedName;
    console.log('[ai-agent] patient name transcript', {
      callId: session.callId,
      text: capturedName
    });
    if (session.nameAwaitingConfirmation) {
      if (isNegativeConfirmation(inputText)) {
        const correctedName = correctedNameFromReply(inputText);
        session.nameCandidate = '';
        session.nameAwaitingConfirmation = false;
        session.nameCorrectionCount += 1;
        session.nameControlResponse = true;
        discardPendingOutput(session);
        if (looksLikePatientName(correctedName)) {
          confirmCapturedName(session, correctedName);
        } else {
          sendControlText(
            session,
            'The caller said the patient name is wrong. Apologize briefly and ask: “Please spell the full patient name letter by letter, saying space between name parts.” Do not reuse the previous name.'
          );
        }
      } else if (isPositiveConfirmation(inputText)) {
        session.nameCollecting = false;
        session.nameAwaitingConfirmation = false;
        session.nameConfirmed = true;
      }
    } else if (spelledName || looksLikePatientName(usableName)) {
      discardPendingOutput(session);
      confirmCapturedName(session, usableName);
    }
  }

  if (inputText && session.phoneCollecting) {
    // inputTranscription is a finalized utterance, not an incremental delta.
    // Each utterance is therefore appended in full, including repeated groups
    // such as "zero" followed by another "zero".
    const inputDelta = inputText;

    if (isPhoneRepeatRequest(inputDelta) && session.phoneDigits) {
      session.phoneControlResponse = true;
      sendControlText(
        session,
        `The caller asked you to repeat the phone number. Repeat only these captured digits: ` +
        `${session.phoneDigits.split('').join(' ')}. Then ask whether they are correct.`
      );
    } else if (session.phoneAwaitingConfirmation) {
      if (isNegativeConfirmation(inputDelta)) {
        session.phoneDigits = '';
        session.phoneAwaitingConfirmation = false;
        session.phoneControlResponse = true;
        discardPendingOutput(session);
        sendControlText(
          session,
          'The caller said the phone number is wrong. Apologize briefly and ask for the complete number again from the beginning. Do not reuse previous digits.'
        );
      } else if (isPositiveConfirmation(inputDelta)) {
        session.phoneCollecting = false;
        session.phoneAwaitingConfirmation = false;
        session.phoneConfirmed = true;
      }
    } else {
      if (session.phoneFinalizeTimer) clearTimeout(session.phoneFinalizeTimer);
      session.phoneFinalizeTimer = null;
      const newDigits = extractSpokenDigits(inputDelta);
      if (newDigits) {
        session.phoneDigits += newDigits;
        const expectedLength = expectedPakistaniPhoneLength(session.phoneDigits);
        console.log('[ai-agent] phone digits buffered', {
          callId: session.callId,
          digits: session.phoneDigits.length,
          expected: expectedLength
        });
        if (expectedLength && session.phoneDigits.length === expectedLength) {
          discardPendingOutput(session);
          confirmCapturedPhone(session, session.phoneDigits);
        } else if (expectedLength && session.phoneDigits.length > expectedLength) {
          session.phoneDigits = '';
          session.phoneControlResponse = true;
          discardPendingOutput(session);
          sendControlText(
            session,
            'The captured phone number contains too many digits. Ask the caller to repeat the complete number once from the beginning.'
          );
        } else if (!expectedLength) {
          // Unknown local/international formats are accepted at 7–15 digits.
          // A short timer allows another spoken group to arrive before readback.
          scheduleGenericPhoneConfirmation(session);
        }
      }
    }
  }
  if (content.interrupted) {
    session.outputTurnChunks.length = 0;
    session.outputTurnBytes = 0;
    session.playbackGeneration += 1;
    session.modelGenerating = false;
    console.log('[ai-agent] Gemini playback interrupted', { callId: session.callId });
  }
  const parts = content.modelTurn?.parts || [];
  for (const part of parts) {
    const audio = part.inlineData || part.inline_data;
    if (audio?.data) {
      session.modelGenerating = true;
      const mimeType = audio.mimeType || audio.mime_type || 'audio/pcm;rate=24000';
      const sampleRate = Number((mimeType.match(/rate=(\d+)/i) || [])[1]) || 24000;
      session.outputSampleRate = sampleRate;
      const chunk = Buffer.from(audio.data, 'base64');
      session.outputAudioChunks += 1;
      if (suppressControlledOutput) continue;
      session.outputTurnChunks.push(chunk);
      session.outputTurnBytes += chunk.length;
      queueOutputAudio(session);
    }
  }
  if (inputText) session.transcript.push({ role: 'caller', text: inputText });
  if (outputText) {
    session.transcript.push({ role: 'agent', text: outputText });
    session.agentTranscriptWindow = `${session.agentTranscriptWindow} ${outputText}`.slice(-300);
  }
  if (content.turnComplete) {
    session.modelGenerating = false;
    console.log(
      `[ai-agent] Gemini turn complete callId=${session.callId}` +
      ` inputAudioChunks=${session.inputAudioChunks}` +
      ` outputAudioChunks=${session.outputAudioChunks}` +
      ` transcriptEntries=${session.transcript.length}`
    );
    if (suppressControlledOutput) {
      session.outputTurnChunks.length = 0;
      session.outputTurnBytes = 0;
      session.playbackGeneration += 1;
    } else {
      queueOutputAudio(session, true);
    }
    session.outputTurnStarted = false;
    if (session.initialGreetingPending) {
      session.initialGreetingPending = false;
      session.playbackChain = session.playbackChain.then(() => {
        if (sessions.get(session.callId) === session) {
          session.initialGreetingComplete = true;
          session.lastAgentPlaybackEndedAt = Date.now();
          console.log('[ai-agent] initial greeting completed', { callId: session.callId });
        }
      });
    }
    if (!session.phoneCollecting && !session.phoneConfirmed && /\b(phone|mobile|contact)\b[\s\S]{0,80}\b(number|reach|call)\b/i.test(session.agentTranscriptWindow)) {
      session.phoneCollecting = true;
      session.phoneDigits = '';
      console.log('[ai-agent] phone collection mode started', { callId: session.callId });
    }

    if (session.nameControlResponse) {
      session.nameControlResponse = false;
    } else if (session.phoneControlResponse) {
      session.phoneControlResponse = false;
    }

    session.lastInputTranscription = '';
    session.agentTranscriptWindow = '';
    if (
      !session.silenceFollowUpSent &&
      (!session.phoneCollecting || session.phoneAwaitingConfirmation)
    ) scheduleSilenceFollowUp(session);
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
    outputTurnStarted: false,
    outputSampleRate: 24000,
    playbackChain: Promise.resolve(),
    playbackQueueDepth: 0,
    playbackSegment: 0,
    playbackGeneration: 0,
    lastAgentPlaybackEndedAt: 0,
    modelGenerating: false,
    silencePromptTimer: null,
    silenceFollowUpSent: false,
    initialGreetingPending: true,
    initialGreetingComplete: false,
    inputPeakMaximum: 0,
    inputRmsMaximum: 0,
    callerSpeechActive: false,
    callerSilenceMs: 0,
    speechCandidateFrames: 0,
    audioPreRoll: [],
    nameCollecting: true,
    nameConfirmed: false,
    nameAwaitingConfirmation: false,
    nameCandidate: '',
    nameCorrectionCount: 0,
    nameControlResponse: false,
    phoneCollecting: false,
    phoneConfirmed: false,
    phoneAwaitingConfirmation: false,
    phoneConfirmationRequest: null,
    phoneRepeatRequest: null,
    phoneCorrectionRequest: false,
    phoneControlResponse: false,
    phoneDigits: '',
    phoneFinalizeTimer: null,
    lastInputTranscription: '',
    agentTranscriptWindow: '',
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
    let sumSquares = 0;
    let sampleCount = 0;
    for (let offset = 0; offset + 1 < pcm.length; offset += 2) {
      const sample = pcm.readInt16LE(offset);
      peak = Math.max(peak, Math.abs(sample));
      sumSquares += sample * sample;
      sampleCount += 1;
    }
    const rms = sampleCount ? Math.sqrt(sumSquares / sampleCount) : 0;
    const durationMs = sampleCount ? (sampleCount / 16000) * 1000 : 0;
    session.inputPeakMaximum = Math.max(session.inputPeakMaximum, peak);
    session.inputRmsMaximum = Math.max(session.inputRmsMaximum, rms);
    if (peak >= 500) {
      session.lastCallerSpeechAt = Date.now();
      // A new spoken number group cancels generic-format readback until that
      // group has also been finalized and appended.
      if (session.phoneFinalizeTimer) {
        clearTimeout(session.phoneFinalizeTimer);
        session.phoneFinalizeTimer = null;
      }
    }
    if (peak >= 1200) {
      session.silenceFollowUpSent = false;
      if (session.silencePromptTimer) {
        clearTimeout(session.silencePromptTimer);
        session.silencePromptTimer = null;
      }
    }
    session.inputAudioChunks += 1;
    if (session.inputAudioChunks % 100 === 0) {
      console.log('[ai-agent] caller audio level', {
        callId,
        peak: session.inputPeakMaximum,
        rms: Math.round(session.inputRmsMaximum),
        speechActive: session.callerSpeechActive
      });
      session.inputPeakMaximum = 0;
      session.inputRmsMaximum = 0;
    }
    clearTimeout(session.geminiTimeoutTimer);
    session.geminiTimeoutTimer = setTimeout(() => {
      console.log('[ai-agent] Gemini inactivity timeout', { callId });
      closeSession(callId, 1011, 'Gemini inactivity timeout');
    }, 120000);
    const payload = JSON.stringify({ realtimeInput: { audio: { data: pcm.toString('base64'), mimeType: 'audio/pcm;rate=16000' } } });
    if (session.ready && session.gemini?.readyState === WebSocket.OPEN) {
      if (session.initialGreetingComplete) {
        handleCallerAudio(session, payload, rms, durationMs);
      }
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
  freeswitchSocket.on('close', (code, reason) => {
    console.log(logPrefix(), 'FreeSWITCH closed', {
      code,
      reason: reason?.toString() || '',
      inputAudioChunks: session.inputAudioChunks,
      outputAudioChunks: session.outputAudioChunks,
      playbackQueueDepth: session.playbackQueueDepth
    });
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
