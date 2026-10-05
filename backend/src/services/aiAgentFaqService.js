const db = require('../db');

// Expected questions and the answers the agent may give for them. The
// administrator writes these in the AI configuration page and the prompt
// builder inlines every enabled pair into the system instruction, so the agent
// answers from stored text rather than inventing an answer on the call.
//
// Both fields are free text on purpose: the questions are phrased the way a
// caller would actually ask, and the answers may be a single sentence or a
// short script. Trimming is the only normalization, and a blank entry is
// dropped rather than stored, because an empty question or answer would reach
// the prompt as a line the model could try to read aloud.
// Reads either casing: `list()` sees snake_case database rows while
// `replaceAll` sees the camelCase payload the browser sends. Getting this wrong
// would silently store a disabled entry as enabled, because an unread
// `is_enabled` looks like "not set" and defaults to true.
const flagOf = (row) => (row.is_enabled !== undefined ? row.is_enabled : row.isEnabled);

const normalize = (row = {}) => ({
  id: row.id,
  question: String(row.question ?? '').trim(),
  answer: String(row.answer ?? '').trim(),
  // An explicit false is respected; only a missing flag counts as enabled.
  isEnabled: flagOf(row) === undefined ? true : Boolean(flagOf(row)),
  sortOrder: Number(row.sort_order) || 0,
  updatedBy: row.updated_by || null,
  updatedAt: row.updated_at || null
});

const isUsable = (entry) => Boolean(entry && entry.question && entry.answer);

const list = async () => {
  const result = await db.query(
    'SELECT * FROM ai_agent_faqs ORDER BY sort_order ASC, id ASC'
  );
  return result.rows.map(normalize);
};

// The prompt only needs the enabled pairs, and only their two strings. The
// stored list is loaded once per settings read and filtered when the prompt is
// built, so there is no second query to keep in step with this one.
const enabledEntries = (faqs) => (Array.isArray(faqs) ? faqs : [])
  .filter((entry) => entry && entry.isEnabled !== false)
  .map((entry) => ({ question: String(entry.question || '').trim(), answer: String(entry.answer || '').trim() }))
  .filter((entry) => entry.question && entry.answer);

// The page saves the whole ordered list in one request rather than issuing a
// request per row. That keeps the browser side free of id bookkeeping and
// makes ordering explicit: the position in the payload is the stored order.
// Existing rows are updated in place and anything the payload drops is
// deleted, so the stored list always matches what the administrator sees.
const replaceAll = async (entries, userId) => {
  // Trim and drop the unusable rows first, then number what is left, so the
  // stored sort_order stays contiguous instead of keeping the gaps a dropped
  // row would leave behind.
  const rows = (Array.isArray(entries) ? entries : [])
    .map((entry) => normalize(entry))
    .filter(isUsable)
    .map((entry, index) => ({ ...entry, sortOrder: index }));

  const client = await db.pool.connect();
  try {
    await client.query('BEGIN');
    await client.query('DELETE FROM ai_agent_faqs');
    for (const row of rows) {
      await client.query(
        `INSERT INTO ai_agent_faqs
          (question, answer, is_enabled, sort_order, created_by, created_at, updated_at)
         VALUES ($1, $2, $3, $4, $5, NOW(), NOW())`,
        [row.question, row.answer, row.isEnabled, row.sortOrder, userId || null]
      );
    }
    await client.query('COMMIT');
  } catch (err) {
    await client.query('ROLLBACK');
    throw err;
  } finally {
    client.release();
  }
  return list();
};

module.exports = { list, enabledEntries, replaceAll, normalize };