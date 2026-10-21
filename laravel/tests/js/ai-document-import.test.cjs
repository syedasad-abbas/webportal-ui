const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../../public/js/ai-document-import.js'), 'utf8');
function fixture() {
    const elements = {}, calls = [], changed = [];
    const ids = ['import','file','analyze','apply','status','preview','role','greeting','goal','knowledgeInstructions'];
    for (const id of ids.map(x => `ai-document-${x}`).concat(['ai-agent-role','ai-agent-greeting','ai-agent-goal','ai-agent-knowledge'])) {
        elements[id] = { value: 'Existing', hidden: true, dataset: { url: '/draft' }, handlers: {},
            addEventListener(event, fn) { this.handlers[event] = fn; }, dispatchEvent() { changed.push(id); } };
    }
    elements['ai-document-file'].files = [{ name: 'instructions.pdf', size: 100 }];
    let ok = true, confirm = true;
    const draft = { role: '<b>Receptionist</b>', greeting: '', goal: 'Help callers', knowledgeInstructions: 'Use confirmed details.' };
    vm.runInNewContext(source, {
        document: { readyState: 'complete', getElementById: id => elements[id], querySelector: () => ({ content: 'csrf' }) },
        window: { confirm: () => confirm }, Event: class {}, FormData: class { append() {} },
        fetch: async (url, options) => { calls.push({url, options}); return { ok, json: async () => ok ? { ok, draft } : { message: 'Unreadable file' } }; }
    });
    return { elements, calls, changed, fail: () => { ok = false; }, cancel: () => { confirm = false; },
        click: id => elements[`ai-document-${id}`].handlers.click() };
}

test('analysis previews literal text without updating or saving settings; apply preserves missing fields', async () => {
    const f = fixture(); await f.click('analyze');
    assert.equal(f.elements['ai-agent-role'].value, 'Existing');
    assert.equal(f.elements['ai-document-role'].value, '<b>Receptionist</b>');
    assert.equal(f.elements['ai-document-preview'].hidden, false);
    await f.click('apply');
    assert.equal(f.elements['ai-agent-role'].value, '<b>Receptionist</b>');
    assert.equal(f.elements['ai-agent-greeting'].value, 'Existing');
    assert.equal(f.calls.length, 1);
    assert.equal(f.calls[0].options.headers['X-CSRF-TOKEN'], 'csrf');
    assert.equal(f.changed.length, 3);
});

test('cancelled apply and failed analysis leave current form untouched', async () => {
    const f = fixture(); await f.click('analyze'); f.cancel(); await f.click('apply');
    assert.equal(f.changed.length, 0);
    f.fail(); await f.click('analyze');
    assert.equal(f.elements['ai-document-preview'].hidden, true);
    assert.equal(f.elements['ai-document-status'].textContent, 'Unreadable file');
    assert.equal(f.elements['ai-document-analyze'].disabled, false);
    assert.equal(f.changed.length, 0);
});

test('invalid file size or extension is rejected without upload', async () => {
    for (const file of [{ name: 'test.svg', size: 1 }, { name: 'test.pdf', size: 3000000 }]) {
        const f = fixture(); f.elements['ai-document-file'].files = [file];
        await f.click('analyze'); assert.equal(f.calls.length, 0);
    }
});
