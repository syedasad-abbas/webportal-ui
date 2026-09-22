const { WebSocket } = require('ws');
const config = require('../config');

const activeSessions = new Map();

const startSession = (socket, settings) => {
  const sessionId = `ai_${Date.now()}_${Math.random().toString(36).slice(2, 9)}`;
  const session = {
    id: sessionId,
    socket,
    settings: settings || {},
    status: 'starting',
    transcript: [],
    startedAt: new Date().toISOString()
  };

  activeSessions.set(sessionId, session);
  session.status = 'listening';

  socket.emit('ai:status', {
    status: 'listening',
    detail: 'Listening for audio input'
  });

  return session;
};

const stopSession = (sessionId) => {
  const session = activeSessions.get(sessionId);
  if (!session) return false;
  activeSessions.delete(sessionId);
  if (session.socket) {
    session.socket.emit('ai:status', {
      status: 'idle',
      detail: 'Session stopped'
    });
    session.socket.emit('ai:stopped');
  }
  return true;
};

const handleAudioChunk = (sessionId, audioData) => {
  const session = activeSessions.get(sessionId);
  if (!session) return { ok: false, message: 'Session not found' };
  session.status = 'processing';
  return { ok: true, sessionId };
};

const getSessionStatus = (sessionId) => {
  const session = activeSessions.get(sessionId);
  if (!session) return { ok: false, message: 'Session not found' };
  return { ok: true, status: session.status, sessionId };
};

const stopAllSessions = () => {
  for (const [sessionId] of activeSessions) {
    stopSession(sessionId);
  }
};

const sendTranscript = (sessionId, role, text) => {
  const session = activeSessions.get(sessionId);
  if (!session) return;
  session.transcript.push({ role, text, timestamp: new Date().toISOString() });
  if (session.socket) {
    session.socket.emit('ai:transcript', { role, text });
  }
};

module.exports = {
  activeSessions,
  startSession,
  stopSession,
  handleAudioChunk,
  getSessionStatus,
  stopAllSessions,
  sendTranscript
};