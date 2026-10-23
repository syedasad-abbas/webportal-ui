const express = require('express');
const { checkDb, checkFreeswitch, checkGemini, checkCrm, checkVicidial } = require('../services/healthCheckService');

const router = express.Router();

router.get('/', async (_req, res) => {
  const [db, fs, gemini, crm, vicidial] = await Promise.all([
    checkDb(),
    checkFreeswitch(),
    checkGemini(),
    checkCrm(),
    checkVicidial()
  ]);
  const dependencies = { db, freeswitch: fs, gemini, crm, vicidial };
  const anyError = Object.values(dependencies).some((d) => d.status === 'error');
  const anyNotConfigured = Object.values(dependencies).some((d) => d.status === 'not_configured');
  const overall = anyError ? 'degraded' : anyNotConfigured ? 'partial' : 'ok';
  res.json({ status: overall, dependencies });
});

module.exports = router;
