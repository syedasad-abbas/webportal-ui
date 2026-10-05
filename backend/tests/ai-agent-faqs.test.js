const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function load(relative, mocks) {
  const filename = path.resolve(__dirname, '../src', relative);
  const module = { exports: {} };
  vm.runInNewContext(fs.readFileSync(filename, 'utf8'), {
    module, exports: module.exports, console: { warn() {}, log() {}, error() {} }, process, URL,
    require(name) { return name in mocks ? mocks[name] : require(name); }
  }, { filename });
  return module.exports;
}

// A stand-in for the pg wrapper that records what was asked and replays rows.
// `replaceAll` is the interesting path: it deletes and re-inserts inside a
// transaction, so both the client and pool queries have to be observable.
function dbFixture() {
  const calls = [];
  const state = { rows: [] };
  const client = {
    query(sql, params) {
      calls.push({ sql: sql.replace(/\s+/g, ' ').trim(), params });
      if (/^BEGIN|^COMMIT|^ROLLBACK/.test(sql.trim())) return Promise.resolve({ rows: [], rowCount: 0 });
      if (/^DELETE FROM ai_agent_faqs/.test(sql.trim())) { state.rows = []; return Promise.resolve({ rows: [], rowCount: 0 }); }
      if (/^INSERT INTO ai_agent_faqs/.test(sql.trim())) {
        state.rows.push({
          id: state.rows.length + 1,
          question: params[0],
          answer: params[1],
          is_enabled: params[2],
          sort_order: params[3]
        });
        return Promise.resolve({ rows: [], rowCount: 1 });
      }
      return Promise.resolve({ rows: [], rowCount: 0 });
    },
    release() {}
  };
  const db = {
    calls,
    state,
    query(sql) {
      const text = sql.replace(/\s+/g, ' ').trim();
      calls.push({ sql: text, params: [] });
      let rows = state.rows;
      // Honour the same WHERE and ORDER BY the real query uses, otherwise the
      // assertions would be checking this fixture rather than the service.
      if (/WHERE is_enabled = TRUE/.test(text)) rows = rows.filter((r) => r.is_enabled === true);
      rows = [...rows].sort((a, b) => (a.sort_order - b.sort_order) || (a.id - b.id));
      return Promise.resolve({ rows, rowCount: rows.length });
    },
    pool: { connect: () => Promise.resolve(client) }
  };
  return db;
}

function faqFixture() {
  const db = dbFixture();
  const service = load('services/aiAgentFaqService.js', { '../db': db });
  return { db, service };
}

// A joi stand-in whose every property and call returns another link, so a
// schema expression can be built without the real library installed.
const chain = () => new Proxy(function () {}, {
  get: (target, prop) => (prop === 'then' ? undefined : chain()),
  apply: () => chain()
});

test('list returns stored rows in order with camel-cased flags', async () => {
  const { db, service } = faqFixture();
  db.state.rows = [
    { id: 2, question: ' Where are you? ', answer: ' Main Street. ', is_enabled: false, sort_order: 1 },
    { id: 1, question: 'Fees?', answer: '2,000 rupees.', is_enabled: true, sort_order: 0 }
  ];
  const rows = await service.list();
  assert.equal(rows.length, 2);
  assert.equal(JSON.stringify(rows[0]), JSON.stringify({
    id: 1, question: 'Fees?', answer: '2,000 rupees.', isEnabled: true, sortOrder: 0, updatedBy: null, updatedAt: null
  }));
  assert.equal(rows[1].isEnabled, false);
  assert.equal(rows[1].question, 'Where are you?');
});

test('enabledEntries narrows the stored list to complete, active pairs', () => {
  const { service } = faqFixture();
  // The service reads camel-cased rows here; list() is what maps the database
  // columns into that shape.
  const entries = service.enabledEntries([
    { question: 'Fees?', answer: '2,000 rupees.', isEnabled: true },
    { question: 'Insurance?', answer: 'No.', isEnabled: false },
    { question: '   ', answer: 'blank question' },
    { question: 'Hours?', answer: '   ', isEnabled: true },
    { question: 'Where are you?', answer: 'Main Street.' }
  ]);
  assert.equal(JSON.stringify(entries), JSON.stringify([
    { question: 'Fees?', answer: '2,000 rupees.' },
    { question: 'Where are you?', answer: 'Main Street.' }
  ]));
  // vm.runInNewContext builds these in another realm, so compare the length
  // rather than the array identity.
  assert.equal(service.enabledEntries(undefined).length, 0);
  assert.equal(service.enabledEntries([]).length, 0);
});

test('replaceAll stores the payload order and drops half-typed rows', async () => {
  const { db, service } = faqFixture();
  db.state.rows = [{ id: 9, question: 'old', answer: 'old', is_enabled: true, sort_order: 0 }];

  const saved = await service.replaceAll([
    { question: ' Fees? ', answer: ' 2,000 rupees. ', isEnabled: true },
    { question: 'Hours?', answer: '', isEnabled: true },
    { question: '', answer: 'orphan answer', isEnabled: true },
    { question: 'Insurance?', answer: 'No.', isEnabled: false }
  ], 42);

  // Only the two complete rows survive, renumbered from zero in payload order.
  assert.equal(saved.length, 2);
  assert.equal(JSON.stringify(saved.map((r) => r.question)), JSON.stringify(['Fees?', 'Insurance?']));
  assert.equal(JSON.stringify(saved.map((r) => r.sortOrder)), JSON.stringify([0, 1]));
  assert.equal(saved[1].isEnabled, false);
  assert.equal(db.state.rows[0].created_by, undefined); // client rows carry no created_by
  assert.deepEqual(db.state.rows[0].question, 'Fees?');

  const sql = db.calls.map((c) => c.sql);
  assert.ok(sql.some((s) => s.startsWith('BEGIN')));
  assert.ok(sql.some((s) => s.startsWith('DELETE FROM ai_agent_faqs')));
  assert.ok(sql.some((s) => s.startsWith('COMMIT')));
  // The order is written explicitly rather than relying on insertion order.
  assert.equal(
    JSON.stringify(db.calls.find((c) => c.sql.startsWith('INSERT INTO ai_agent_faqs')).params),
    JSON.stringify(['Fees?', '2,000 rupees.', true, 0, 42])
  );
});

test('replaceAll rolls back and rethrows when an insert fails', async () => {
  const { db, service } = faqFixture();
  let inserts = 0;
  db.pool.connect = () => Promise.resolve({
    query(sql) {
      const trimmed = sql.trim();
      if (/^BEGIN|^COMMIT/.test(trimmed)) return Promise.resolve({ rows: [] });
      if (/^ROLLBACK/.test(trimmed)) { db.calls.push({ sql: 'ROLLBACK' }); return Promise.resolve({ rows: [] }); }
      if (/^INSERT/.test(trimmed)) {
        inserts += 1;
        if (inserts === 2) return Promise.reject(new Error('insert failed'));
        return Promise.resolve({ rows: [], rowCount: 1 });
      }
      return Promise.resolve({ rows: [], rowCount: 0 });
    },
    release() {}
  });
  await assert.rejects(
    service.replaceAll([{ question: 'a', answer: '1' }, { question: 'b', answer: '2' }], 1),
    /insert failed/
  );
  assert.ok(db.calls.some((c) => c.sql === 'ROLLBACK'));
  assert.ok(!db.calls.some((c) => c.sql === 'COMMIT'));
});

test('every AI agent route, settings and FAQ alike, sits behind the same guard', () => {
  const registered = [];
  // Record what each verb registers and how many guards precede the handler,
  // without standing up an HTTP server.
  const router = {};
  ['get', 'put', 'post', 'delete'].forEach((verb) => {
    router[verb] = (...args) => {
      registered.push({
        verb,
        path: args[0],
        // The trailing argument is the handler; everything between the path and
        // the handler is the guard chain.
        guards: args.slice(1, -1).filter((a) => typeof a === 'function').length
      });
      return router;
    };
  });
  const express = { Router: () => router };
  const noop = () => () => null;

  load('routes/aiAgent.js', {
    express,
    // joi is not installed in this checkout and the schemas are built when the
    // module loads, so a chainable no-op stands in: every property and call
    // yields another link, so `Joi.string().trim().max(5)` never trips. No
    // handler runs in this case, so validate() is never called.
    joi: new Proxy({}, { get: () => chain() }),
    '../middleware/auth': { authenticate: noop, requirePermissions: noop },
    '../services/aiAgentSettingsService': { get: async () => ({}), update: async () => ({}) },
    '../services/aiAgentFaqService': { list: async () => [], replaceAll: async () => [] }
  });

  const faqGet = registered.find((r) => r.verb === 'get' && r.path === '/faqs');
  const faqPut = registered.find((r) => r.verb === 'put' && r.path === '/faqs');
  const settingsGet = registered.find((r) => r.verb === 'get' && r.path === '/');
  const settingsPut = registered.find((r) => r.verb === 'put' && r.path === '/');

  assert.ok(faqGet && faqPut, 'both FAQ routes are registered');
  assert.ok(settingsGet && settingsPut, 'the settings routes are still registered');
  // authenticate() + requirePermissions() on every route.
  for (const route of [faqGet, faqPut, settingsGet, settingsPut]) {
    assert.equal(route.guards, 2, `${route.verb.toUpperCase()} ${route.path} is guarded`);
  }
});

test('the prompt inlines only enabled, complete questions and answers', () => {
  const { buildAgentPrompt } = require('../src/aiSalesAgentProfile');
  const prompt = buildAgentPrompt({
    role: 'clinic receptionist',
    goal: 'Book an appointment',
    currentDate: '1 January 2026',
    faqs: [
      { question: 'What are the consultation fees?', answer: 'A consultation is 2,000 rupees.', isEnabled: true },
      { question: 'Do you accept insurance?', answer: 'We do not.', isEnabled: false },
      { question: '   ', answer: 'blank question is dropped' },
      { question: 'Where are you located?', answer: 'Main Street.' }
    ]
  });

  assert.ok(prompt.includes('What are the consultation fees?'));
  assert.ok(prompt.includes('A consultation is 2,000 rupees.'));
  assert.ok(prompt.includes('Where are you located?'));
  assert.ok(!prompt.includes('Do you accept insurance?'), 'a disabled entry is not in the prompt');
  assert.ok(!prompt.includes('blank question is dropped'));
  assert.ok(!/\{\{\w+\}\}/.test(prompt), 'no placeholder is left unresolved');
  // The numbering follows the stored order.
  assert.ok(prompt.indexOf('What are the consultation fees?') < prompt.indexOf('Where are you located?'));
  assert.ok(prompt.includes('1. If the caller asks:'), 'entries are numbered');
});

test('with no expected questions the prompt says so instead of leaving a gap', () => {
  const { buildAgentPrompt } = require('../src/aiSalesAgentProfile');
  for (const faqs of [undefined, [], [{ question: 'q' }]]) {
    const prompt = buildAgentPrompt({ role: 'x', goal: 'y', faqs });
    assert.ok(prompt.includes('No expected questions are configured.'), `handled ${JSON.stringify(faqs)}`);
    assert.ok(!/\{\{\w+\}\}/.test(prompt));
  }
});