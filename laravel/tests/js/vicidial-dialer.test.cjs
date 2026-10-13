const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../../public/js/vicidial-dialer.js'), 'utf8');

function setup(apiReady = true) {
    const events = {};
    const requests = [];
    const operations = [];
    const elements = {};
    const buttons = {};
    for (const action of ['connect', 'ready', 'pause', 'hangup', 'disposition', 'lead']) buttons[action] = { dataset: { viciAction: action } };
    for (const action of ['register', 'answer', 'mute', 'audio']) buttons[action] = { dataset: { viciLocal: action } };
    for (const id of ['vicidial-config', 'vici-feedback', 'vici-phone-status', 'vici-media-status', 'vici-disposition', 'vici-lead']) elements[id] = { textContent: '', value: '' };
    elements['vicidial-config'].textContent = JSON.stringify({ apiReady, webrtc: {}, commandUrl: '/admin/vicidial/command', csrf: 'token', leadLookup: false });
    let click;
    elements['vicidial-page'] = { querySelectorAll: () => Object.values(buttons), addEventListener: (type, cb) => { if (type === 'click') click = cb; } };
    let response = { ok: true, json: async () => ({ ok: true, message: 'Request accepted' }) };
    const window = {
        addEventListener: (type, cb) => { events[type] = cb; },
        clearInterval() {}, setInterval() { return 1; },
        DialerWebRTC: class {
            async ensureClient() { operations.push('register'); }
            async answerIncoming() { operations.push('answer'); }
            async setMuted(value) { operations.push(['mute', value]); }
            async resumeAudio() {}
            async disconnect() { operations.push('disconnect'); }
            async leaveConference() { operations.push('leave'); }
        }
    };
    vm.runInNewContext(source, {
        window,
        document: { readyState: 'complete', hidden: false, getElementById: id => elements[id] },
        fetch: async (url, options) => { requests.push({ url, options, body: JSON.parse(options.body) }); return response; }
    });
    return { events, requests, operations, elements, buttons,
        click: action => click({ target: { closest: () => buttons[action] } }),
        fail: () => { response = { ok: false, json: async () => ({ message: 'Agent not logged in' }) }; }
    };
}

test('ready requires an answered agent audio connection', async () => {
    const s = setup();
    assert.equal(s.buttons.ready.disabled, true);
    await s.click('register');
    assert.equal(s.buttons.ready.disabled, true);
    assert.equal(s.requests.length, 0);
    s.events['dialer:sip-incoming']();
    await s.click('answer');
    assert.deepEqual(s.operations, ['register', 'answer']);
    s.events['dialer:sip-answered']();
    assert.equal(s.buttons.ready.disabled, false);
});

test('customer hangup goes to API and never tears down the agent phone', async () => {
    const s = setup();
    s.events['dialer:sip-answered']();
    await s.click('hangup');
    assert.equal(s.requests[0].body.action, 'hangup');
    assert.equal(s.requests[0].options.headers['X-CSRF-TOKEN'], 'token');
    assert.deepEqual(s.operations, []);
    assert.equal(s.buttons.ready.disabled, false);
});

test('API errors remain errors and controls recover', async () => {
    const s = setup();
    s.fail();
    await s.click('hangup');
    assert.equal(s.elements['vici-feedback'].textContent, 'Agent not logged in');
    assert.equal(s.buttons.hangup.disabled, false);
});

test('empty disposition is not submitted and audio loss disables ready', async () => {
    const s = setup();
    await s.click('disposition');
    assert.equal(s.requests.length, 0);
    s.events['dialer:sip-answered']();
    s.events['dialer:sip-disconnected']();
    assert.equal(s.buttons.ready.disabled, true);
    assert.equal(s.buttons.register.disabled, false);
});

 test('phone-only mode permits registration and incoming audio without API requests', async () => {
    const s = setup(false);
    await s.click('register');
    assert.match(s.elements['vici-phone-status'].textContent, /Waiting/);
    s.events['dialer:sip-registered']();
    assert.match(s.elements['vici-phone-status'].textContent, /registered/);
    s.events['dialer:sip-incoming']();
    await s.click('answer');
    s.events['dialer:sip-answered']();
    assert.equal(s.buttons.ready.disabled, true);
    assert.equal(s.buttons.hangup.disabled, true);
    s.events.pagehide();
    assert.equal(s.requests.length, 0);
 });
