const db = require('../db');
const config = require('../config');

const defaults = {
  enabled: false,
  goal: 'Collect the patient name, phone number, preferred appointment date, and doctor name.',
  mode: 'lead',
  voice: 'professional',
  humanHandoff: true
};

const legacySalesGoals = new Set([
  'Qualify the lead and book a follow-up call.',
  'Qualify the lead and book a follow-up call'
]);

const normalizeGoal = (goal) => (!goal || legacySalesGoals.has(goal) ? defaults.goal : goal);

// The live model is intentionally not stored per-record. It is controlled
// exclusively by the GEMINI_LIVE_MODEL environment variable so there is a
// single source of truth and no hardcoded model choice anywhere in the code.
const normalize = (row = {}) => ({
  enabled: Boolean(row.enabled),
  goal: normalizeGoal(row.goal),
  mode: row.mode || defaults.mode,
  voice: row.voice || defaults.voice,
  humanHandoff: row.human_handoff === undefined ? defaults.humanHandoff : Boolean(row.human_handoff),
  updatedBy: row.updated_by || null,
  ready: Boolean(config.aiAgent.apiKey),
  model: config.aiAgent.model,
  activeSessions: require('./geminiLiveBridge').activeSessionCount()
});

const get = async () => {
  const result = await db.query('SELECT * FROM ai_agent_settings WHERE id = 1');
  return normalize(result.rows[0]);
};

const update = async (settings, userId) => {
  const value = { ...defaults, ...settings };
  if (value.enabled && !config.aiAgent.apiKey) {
    const err = new Error('GEMINI_API_KEY is not configured');
    err.statusCode = 503;
    throw err;
  }
  const result = await db.query(
    `INSERT INTO ai_agent_settings
      (id, enabled, goal, mode, voice, human_handoff, updated_by, created_at, updated_at)
     VALUES (1, $1, $2, $3, $4, $5, $6, NOW(), NOW())
     ON CONFLICT (id) DO UPDATE SET
       enabled = EXCLUDED.enabled, goal = EXCLUDED.goal, mode = EXCLUDED.mode,
       voice = EXCLUDED.voice,
       human_handoff = EXCLUDED.human_handoff,
       updated_by = EXCLUDED.updated_by, updated_at = NOW()
     RETURNING *`,
    [value.enabled, value.goal, value.mode, value.voice, value.humanHandoff, userId]
  );
  return normalize(result.rows[0]);
};

module.exports = { get, update };
