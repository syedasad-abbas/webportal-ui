const db = require('../db');
const config = require('../config');
const faqService = require('./aiAgentFaqService');

const defaults = {
  enabled: false,
  role: 'inbound call assistant',
  greeting: '',
  goal: 'Collect the patient name, phone number, preferred appointment date, and doctor name.',
  mode: 'lead',
  voice: 'man',
  humanHandoff: true,
  callDirection: 'inbound'
};

const supportsDirection = (settings, direction) => {
  const callDirection = settings && settings.callDirection ? settings.callDirection : defaults.callDirection;
  return callDirection === 'both' || callDirection === direction;
};

// `role`, `greeting` and `goal` are free text chosen by the administrator, so
// the only normalization left is trimming. A cleared field is preserved as
// empty on purpose: removing the duty override or the opening line must leave
// the agent without them rather than silently restoring an appointment duty.
// The default applies only when the column has never been set, and null (how
// Laravel sends a cleared field) counts as cleared, not as missing.
const normalizeRole = (role) => (
  role === undefined ? defaults.role : String(role ?? '').trim()
);
const normalizeGreeting = (greeting) => (
  greeting === undefined ? '' : String(greeting ?? '').trim()
);
const normalizeGoal = (goal) => (
  goal === undefined ? defaults.goal : String(goal ?? '').trim()
);

// The live model is intentionally not stored per-record. It is controlled
// exclusively by the GEMINI_LIVE_MODEL environment variable so there is a
// single source of truth and no hardcoded model choice anywhere in the code.
const normalize = (row = {}) => ({
  enabled: Boolean(row.enabled),
  role: normalizeRole(row.role),
  greeting: normalizeGreeting(row.greeting),
  goal: normalizeGoal(row.goal),
  mode: row.mode || defaults.mode,
  voice: row.voice || defaults.voice,
  humanHandoff: row.human_handoff === undefined ? defaults.humanHandoff : Boolean(row.human_handoff),
  callDirection: ['inbound', 'outbound', 'both'].includes(row.call_direction)
    ? row.call_direction
    : defaults.callDirection,
  updatedBy: row.updated_by || null,
  ready: Boolean(config.aiAgent.apiKey),
  model: config.aiAgent.model,
  activeSessions: require('./geminiLiveBridge').activeSessionCount()
});

const get = async () => {
  const result = await db.query('SELECT * FROM ai_agent_settings WHERE id = 1');
  const settings = normalize(result.rows[0]);
  // The expected questions travel with the settings so the configuration page
  // and the live prompt both read them from a single request. A failure here
  // must not take the whole settings object down, so it degrades to no entries.
  try {
    settings.faqs = await faqService.list();
  } catch (err) {
    console.error('[ai-agent] expected questions unavailable:', err.message);
    settings.faqs = [];
  }
  return settings;
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
      (id, enabled, role, greeting, goal, mode, voice, human_handoff, call_direction, updated_by, created_at, updated_at)
     VALUES (1, $1, $2, $3, $4, $5, $6, $7, $8, $9, NOW(), NOW())
     ON CONFLICT (id) DO UPDATE SET
       enabled = EXCLUDED.enabled, role = EXCLUDED.role, greeting = EXCLUDED.greeting,
       goal = EXCLUDED.goal, mode = EXCLUDED.mode, voice = EXCLUDED.voice,
       human_handoff = EXCLUDED.human_handoff,
       call_direction = EXCLUDED.call_direction,
       updated_by = EXCLUDED.updated_by, updated_at = NOW()
     RETURNING *`,
    [
      value.enabled,
      normalizeRole(value.role),
      normalizeGreeting(value.greeting),
      normalizeGoal(value.goal),
      value.mode,
      value.voice,
      value.humanHandoff,
      value.callDirection,
      userId
    ]
  );
  return normalize(result.rows[0]);
};

module.exports = { get, update, supportsDirection };
