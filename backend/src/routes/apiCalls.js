const express = require('express');
const bridge = require('../services/geminiLiveBridge');

const router = express.Router();

router.get('/active-calls', (_req, res) => {
  const calls = bridge.getActiveCalls();
  res.json({ ok: true, calls, count: calls.length });
});

module.exports = router;
