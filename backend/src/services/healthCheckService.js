const config = require('../config');
const db = require('../db');
const fs = require('../lib/freeswitch');

/**
 * Health check for all AI service dependencies.
 * Returns detailed status for each dependency so operators can diagnose
 * failures before they affect live calls.
 */
const checkDb = async () => {
  const start = Date.now();
  try {
    await db.query('SELECT 1');
    return { status: 'ok', latencyMs: Date.now() - start };
  } catch (err) {
    return { status: 'error', latencyMs: Date.now() - start, error: err.message };
  }
};

const checkFreeswitch = async () => {
  const start = Date.now();
  try {
    await fs.getChannelSnapshot();
    return { status: 'ok', latencyMs: Date.now() - start };
  } catch (err) {
    return { status: 'error', latencyMs: Date.now() - start, error: err.message };
  }
};

const checkGemini = async () => {
  const start = Date.now();
  const apiKey = config.aiAgent.apiKey;
  const model = config.aiAgent.textModel;
  const modelFullName = `models/${model}`;
  if (!apiKey) {
    return { status: 'not_configured', latencyMs: 0, error: 'GEMINI_API_KEY not set' };
  }
  try {
    // Validate the API key by listing available models.
    const listRes = await fetch(
      `https://generativelanguage.googleapis.com/v1beta/models?key=${encodeURIComponent(apiKey)}`,
      { signal: AbortSignal.timeout(10000) }
    );
    if (!listRes.ok) {
      return { status: 'error', latencyMs: Date.now() - start, error: `Model list HTTP ${listRes.status}` };
    }
    const listData = await listRes.json().catch(() => ({}));
    const available = Array.isArray(listData.models)
      ? listData.models.map((m) => m.name || m.id || '').filter(Boolean)
      : [];
    const modelAvailable = available.includes(modelFullName) || available.includes(model);
    if (!modelAvailable && available.length > 0) {
      return {
        status: 'error',
        latencyMs: Date.now() - start,
        error: `Configured text model "${model}" not found. Available: ${available.slice(0, 5).join(', ')}`
      };
    }
    // Model is configured and available in the project. The text model is used
    // for transcript summarization and document draft generation.
    return { status: 'ok', latencyMs: Date.now() - start };
  } catch (err) {
    return { status: 'error', latencyMs: Date.now() - start, error: err.message };
  }
};

const checkCrm = async () => {
  const start = Date.now();
  const baseUrl = config.crm?.baseUrl || process.env.CRM_API_URL || process.env.TEST_CRM_API_URL || 'http://127.0.0.1:8080/api/test-crm/v1';
  const token = config.crm?.token || process.env.CRM_API_TOKEN || process.env.TEST_CRM_API_TOKEN || '';
  if (!baseUrl || !token) {
    return { status: 'not_configured', latencyMs: 0, error: 'CRM_API_URL or CRM_API_TOKEN not set' };
  }
  try {
    const res = await fetch(`${baseUrl.replace(/\/$/, '')}/reports/summary`, {
      headers: { Authorization: `Bearer ${token}` },
      signal: AbortSignal.timeout(10000)
    });
    return { status: res.ok ? 'ok' : 'error', latencyMs: Date.now() - start, error: res.ok ? null : `HTTP ${res.status}` };
  } catch (err) {
    return { status: 'error', latencyMs: Date.now() - start, error: err.message };
  }
};

const checkVicidial = async () => {
  const start = Date.now();
  const enabled = process.env.VICIDIAL_AI_ENABLED === 'true' || process.env.VICIDIAL_CUSTOMER_AI_ENABLED === 'true';
  if (!enabled) {
    return { status: 'not_configured', latencyMs: 0, error: 'VICIDIAL_AI not enabled' };
  }
  try {
    const sourceIp = process.env.VICIDIAL_AI_SERVER_IP || process.env.VICIDIAL_CUSTOMER_AI_SOURCE_IP || '';
    if (!sourceIp) {
      return { status: 'not_configured', latencyMs: 0, error: 'VICIDIAL source IP not set' };
    }
    return { status: 'ok', latencyMs: Date.now() - start };
  } catch (err) {
    return { status: 'error', latencyMs: Date.now() - start, error: err.message };
  }
};

module.exports = { checkDb, checkFreeswitch, checkGemini, checkCrm, checkVicidial };
