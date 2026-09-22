const express = require('express');
const aiService = require('../services/aiService');

const router = express.Router();

router.post('/start', (req, res) => {
  const { goal, mode, voice, autoNotes, humanHandoff } = req.body;
  // Socket-based session; respond with session info
  res.json({
    ok: true,
    message: 'Connect via /ai socket namespace to start session',
    settings: { goal, mode, voice, autoNotes, humanHandoff }
  });
});

router.post('/stop', (req, res) => {
  const { sessionId } = req.body;
  if (!sessionId) return res.status(400).json({ ok: false, message: 'sessionId required' });
  const stopped = aiService.stopSession(sessionId);
  res.json({ ok: stopped, sessionId });
});

router.get('/status', (req, res) => {
  const { sessionId } = req.query;
  if (!sessionId) return res.status(400).json({ ok: false, message: 'sessionId required' });
  const result = aiService.getSessionStatus(sessionId);
  res.json(result);
});

router.post('/audio', (req, res) => {
  const { sessionId, audio, sampleRate } = req.body;
  if (!sessionId) return res.status(400).json({ ok: false, message: 'sessionId required' });
  const result = aiService.handleAudioChunk(sessionId, { audio, sampleRate });
  res.json(result);
});

module.exports = router;