const { test } = require('node:test');
const assert = require('node:assert/strict');
const { extractDraft, documentType, LIMIT } = require('../src/services/aiDocumentService');
const pdf = Buffer.from('%PDF-1.7 test');
const draft = { role: 'Receptionist', greeting: '', goal: 'Help callers', knowledgeInstructions: 'Office opens at nine.' };
const reply = value => ({ ok: true, json: async () => ({ candidates: [{ finishReason: 'STOP', content: { parts: [{ text: JSON.stringify(value) }] } }] }) });

test('detects supported signatures and rejects empty, oversized and disguised files', () => {
  assert.equal(documentType(pdf), 'application/pdf');
  assert.equal(documentType(Buffer.from([255,216,255,224])), 'image/jpeg');
  assert.equal(documentType(Buffer.from([137,80,78,71,13,10,26,10])), 'image/png');
  for (const bytes of [Buffer.alloc(0), Buffer.from('<script>PDF</script>'), {}, Buffer.alloc(LIMIT + 1)]) {
    assert.throws(() => documentType(bytes));
  }
});

test('sends inline document using configured model and returns only approved draft fields', async () => {
  let request;
  const result = await extractDraft(pdf, { apiKey: 'secret', model: 'configured-model', request: async (url, options) => {
    request = { url, options, body: JSON.parse(options.body) };
    return reply({ ...draft, enabled: true, voice: 'changed' });
  } });
  assert.deepEqual(result, draft);
  assert.match(request.url, /configured-model:generateContent$/);
  assert.equal(request.options.headers['x-goog-api-key'], 'secret');
  assert.equal(request.body.contents[0].parts[0].inlineData.mimeType, 'application/pdf');
  assert.equal(request.body.contents[0].parts[0].inlineData.data, pdf.toString('base64'));
});

test('rejects unsupported input before any provider request', async () => {
  let called = false;
  await assert.rejects(extractDraft(Buffer.from('bad'), { apiKey: 'x', request: async () => { called = true; } }), /Unsupported/);
  assert.equal(called, false);
});

test('provider errors and incomplete extraction cannot become a saved draft', async () => {
  await assert.rejects(extractDraft(pdf, { apiKey: '', model: 'x' }), /not configured/);
  for (const response of [
    { ok: false },
    { ok: true, json: async () => ({ candidates: [{ finishReason: 'MAX_TOKENS' }] }) },
    reply({ ...draft, role: 'x'.repeat(201) }),
    reply({}),
    reply({ role: '', greeting: '', goal: '', knowledgeInstructions: '' })
  ]) {
    await assert.rejects(extractDraft(pdf, { apiKey: 'x', model: 'x', request: async () => response }));
  }
});
