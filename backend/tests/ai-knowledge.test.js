const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const path = require('node:path');
const { buildAgentPrompt } = require('../src/aiSalesAgentProfile');

function fixture() {
  let row = { knowledge_instructions: 'Existing guidance' };
  const calls = [];
  const module = { exports: {} };
  vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../src/services/aiAgentSettingsService.js'), 'utf8'), {
    module, console,
    require(name) {
      if (name === '../db') return { query: async (sql, params) => {
        calls.push({ sql, params });
        if (params && params[9] !== null) row.knowledge_instructions = params[9];
        if (params && params[10] !== null) row.sales_script = params[10];
        return { rows: [{ ...row }] };
      } };
      if (name === '../config') return { aiAgent: { apiKey: 'test', model: 'test' } };
      if (name === './aiAgentFaqService') return { list: async () => [] };
      if (name === './geminiLiveBridge') return { activeSessionCount: () => 0 };
      throw Error(name);
    }
  });
  return { service: module.exports, calls };
}

test('knowledge instructions save and reload without becoming FAQ entries', async () => {
  const f = fixture();
  await f.service.update({ knowledgeInstructions: '  Ask one question at a time.  ' }, 81);
  const saved = await f.service.get();
  assert.equal(saved.knowledgeInstructions, 'Ask one question at a time.');
  assert.equal(saved.faqs.length, 0);
  assert.equal(f.calls[0].params[8], 81);
  assert.match(f.calls[0].sql, /knowledge_instructions = COALESCE\(\$10, ai_agent_settings.knowledge_instructions\)/);
});

test('legacy updates preserve knowledge and explicit clearing removes it', async () => {
  const f = fixture();
  assert.equal((await f.service.update({}, 81)).knowledgeInstructions, 'Existing guidance');
  assert.equal(f.calls[0].params[9], null);
  assert.equal((await f.service.update({ knowledgeInstructions: null }, 81)).knowledgeInstructions, '');
  assert.equal(f.calls[1].params[9], '');
});

test('live prompt includes knowledge as literal text alongside greeting and FAQs', () => {
  const prompt = buildAgentPrompt({ greeting: 'Welcome to the clinic.',
    knowledgeInstructions: 'Ask one question at a time. Literal {{goal}} and $&.',
    faqs: [{ question: 'When are you open?', answer: 'From nine to five.', isEnabled: true }] });
  assert.ok(prompt.includes('Ask one question at a time. Literal {{goal}} and $&.'));
  assert.ok(prompt.includes('Welcome to the clinic.'));
  assert.ok(prompt.includes('From nine to five.'));
  assert.ok(!buildAgentPrompt({ knowledgeInstructions: '' }).includes('Ask one question at a time. Literal'));
});

test('saved sales script reaches live prompt verbatim and clearing is supported', async () => {
 const f=fixture();const text='STEP A: review caller values. Literal {{goal}} and $&.';
 await f.service.update({salesScript:text},81);
 const saved=await f.service.get();
 assert.equal(saved.salesScript,text);
 assert.ok(buildAgentPrompt({salesScript:saved.salesScript}).includes(text));
 assert.equal((await f.service.update({salesScript:''},81)).salesScript,'');
});
