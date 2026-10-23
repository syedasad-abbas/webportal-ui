const express = require('express');
const config = require('../config');
const { checkDb, checkFreeswitch, checkGemini, checkCrm } = require('../services/healthCheckService');

const router = express.Router();

router.get('/validate', async (_req, res) => {
  const checks = [];
  const add = (name, result) => checks.push({ name, ...result });

  // Required secrets
  add('JWT_SECRET', { configured: !!config.jwtSecret && config.jwtSecret !== 'change_me' });
  add('DB_PASSWORD', { configured: !!config.db.password && config.db.password !== 'supersecret' });
  add('FREESWITCH_PASSWORD', { configured: !!config.freeswitch.password && config.freeswitch.password !== 'ClueCon' });
  add('DEFAULT_ADMIN_PASSWORD', { configured: !!config.defaults.adminPassword && config.defaults.adminPassword !== 'AdminPass123!' });

  // AI Agent
  add('GEMINI_API_KEY', { configured: !!config.aiAgent.apiKey });
  add('AI_AUDIO_BRIDGE_URL', { configured: !!config.aiAgent.bridgeUrl });

  // CRM
  add('CRM_API_URL', { configured: !!(config.crm?.baseUrl || process.env.CRM_API_URL || process.env.TEST_CRM_API_URL) });
  add('CRM_API_TOKEN', { configured: !!(config.crm?.token || process.env.CRM_API_TOKEN || process.env.TEST_CRM_API_TOKEN) });

  // VICIdial
  add('VICIDIAL_AI_ENABLED', { configured: process.env.VICIDIAL_AI_ENABLED === 'true' || process.env.VICIDIAL_CUSTOMER_AI_ENABLED === 'true' });
  add('VICIDIAL_SOURCE_IP', { configured: !!(process.env.VICIDIAL_AI_SERVER_IP || process.env.VICIDIAL_CUSTOMER_AI_SOURCE_IP) });

  // Live dependency checks
  const [db, fs, gemini, crm] = await Promise.all([checkDb(), checkFreeswitch(), checkGemini(), checkCrm()]);
  add('database', { status: db.status, latencyMs: db.latencyMs });
  add('freeswitch', { status: fs.status, latencyMs: fs.latencyMs });
  add('gemini', { status: gemini.status, latencyMs: gemini.latencyMs });
  add('crm', { status: crm.status, latencyMs: crm.latencyMs });

  const allConfigured = checks.every((c) => c.configured !== false && c.status !== 'error');
  res.json({ valid: allConfigured, checks });
});

module.exports = router;
