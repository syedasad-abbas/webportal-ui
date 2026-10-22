const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
function fixture() {
    const node = () => ({children: [], dataset: {}, handlers: {}, textContent: '',
        append(...items) { this.children.push(...items); items.forEach(item => item.parent = this); },
        remove() { this.parent.children = this.parent.children.filter(item => item !== this); },
        setAttribute() {}, addEventListener(name, fn) { this.handlers[name] = fn; }});
    const list = node(), status = node();
    const root = { dataset: {callsUrl: '/calls', transcriptUrl: '/transcript/__UUID__'}, querySelector: selector => selector === '[data-call-list]' ? list : status };
    let calls = [], fail = false, next;
    const box = { document: {getElementById: () => root, createElement: node},
        window: {addEventListener() {}}, AbortSignal, Date, setInterval() {}, clearInterval() {}, clearTimeout() {},
        setTimeout(fn) { next = fn; }, fetch: async url => ({ok: !fail, status: 503, json: async () => url === '/calls' ? {calls} : {transcript: '<b>Customer words</b>'}}) };
    vm.runInNewContext(fs.readFileSync(__dirname + '/../../public/js/active-call-transcripts.js', 'utf8'), box);
    return {list, status, set: value => calls = value, fail: () => fail = true,
        async tick() { await new Promise(setImmediate); await next(); await new Promise(setImmediate); }};
}
test('adds new calls, refreshes customer text safely, removes ended call and panel', async () => {
    const f = fixture();
    f.set([{uuid:'one', caller:'Customer', destination:'8899', started:Date.now()}]);
    await f.tick();
    assert.equal(f.list.children.length, 1);
    const button = f.list.children[0].children[0];
    button.handlers.click(); await new Promise(setImmediate);
    assert.equal(f.list.children[0].children[1].textContent, '<b>Customer words</b>');
    f.set([]); await f.tick();
    assert.equal(f.list.children.length, 0);
    assert.match(f.status.textContent, /No active/);
});
test('failed refresh does not label stale calls as live', async () => {
    const f = fixture(); f.set([{uuid:'one', started:Date.now()}]); await f.tick();
    f.fail(); await f.tick();
    assert.equal(f.list.children.length, 0);
    assert.match(f.status.textContent, /unavailable/);
});
