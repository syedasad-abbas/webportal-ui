const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../../public/js/vicidial-inline.js'), 'utf8');

function fixture(apiReady = true) {
    const events = {}; const requests = []; const buttons = {};
    const elements = {};
    let click; let accept = true; let failed = false; let audioFailed = false;
    const audioRequests = [];
    for (const name of ['connect', 'ready', 'pause', 'hangup', 'disposition', 'lead', 'dial', 'park', 'unpark', 'dtmf', 'conference', 'transfer', 'end_conference', 'logout', 'queue', 'end_browser_call']) {
        buttons[name] = { dataset: { viciAction: name } };
    }
    for (const name of ['status', 'feedback', 'lead', 'number', 'country', 'dtmf', 'target', 'disposition']) {
        elements[`vicidial-inline-${name}`] = { textContent: '', value: '' };
    }
    elements['vicidial-inline-config'] = { dataset: { config: JSON.stringify({ enabled: true, apiReady, commandUrl: '/command', csrf: 'csrf' }) } };
    vm.runInNewContext(source, {
        document: { readyState: 'complete', getElementById: (id) => elements[id],
            querySelectorAll: () => Object.values(buttons), addEventListener: (event, handler) => { click = handler; } },
        window: { addEventListener: (name, handler) => { events[name] = handler; }, confirm: () => accept,
            dispatchEvent: (event) => { audioRequests.push(event.type);
                if (audioFailed) event.detail.reject(new Error('SIP hangup failed'));
                else event.detail.resolve(); } },
        CustomEvent: class { constructor(type, options) { this.type = type; this.detail = options.detail; } },
        fetch: async (url, options) => {
            requests.push({ url, options, body: JSON.parse(options.body) });
            return { ok: !failed, json: async () => failed ? { message: 'Agent not logged in' } : { ok: true, message: 'Accepted' } };
        }
    });
    return { events, requests, elements, buttons, audioRequests,
        failAudio: () => { audioFailed = true; },
        connect: () => { events['dialer:sip-registered'](); events['dialer:sip-answered'](); },
        click: (name) => click({ target: { closest: () => buttons[name] } }),
        cancel: () => { accept = false; }, fail: () => { failed = true; } };
}

test('API customer controls require registered, answered agent audio', async () => {
    const f = fixture();
    assert.equal(f.buttons.dial.disabled, true);
    f.events['dialer:sip-registered']();
    assert.equal(f.buttons.ready.disabled, true);
    f.events['dialer:sip-answered']();
    assert.equal(f.buttons.ready.disabled, false);
    f.events['dialer:sip-disconnected']();
    assert.equal(f.buttons.hangup.disabled, true);
    await f.click('hangup'); assert.equal(f.requests.length, 0);
});

test('dial submits country code and customer number through the scoped API', async () => {
    const f = fixture(); f.connect();
    f.elements['vicidial-inline-number'].value = '3001234567';
    f.elements['vicidial-inline-country'].value = '92';
    await f.click('dial');
    assert.deepEqual(f.requests[0].body, { action: 'dial', value: '3001234567', phone_code: '92' });
    assert.equal(f.requests[0].options.headers['X-CSRF-TOKEN'], 'csrf');
    assert.equal(f.buttons.ready.disabled, false);
});

test('customer hangup and third-party hangup preserve audio state', async () => {
    const f = fixture(); f.connect();
    await f.click('hangup'); await f.click('end_conference');
    assert.deepEqual(f.requests.map((r) => r.body.action), ['hangup', 'end_conference']);
    assert.equal(f.buttons.ready.disabled, false);
});

test('missing API credentials prevents commands even with registered audio', async () => {
    const f = fixture(false); f.connect();
    await f.click('ready'); await f.click('hangup');
    assert.equal(f.requests.length, 0);
});

test('invalid dial and cancelled transfer do not send commands', async () => {
    const f = fixture(); f.connect();
    await f.click('dial');
    f.elements['vicidial-inline-target'].value = 'supervisor'; f.cancel();
    await f.click('transfer');
    assert.equal(f.requests.length, 0);
});

test('rejection displays feedback without claiming customer state changed', async () => {
    const f = fixture(); f.connect(); f.fail();
    await f.click('park');
    assert.equal(f.elements['vicidial-inline-feedback'].textContent, 'Agent not logged in');
    assert.equal(f.buttons.ready.disabled, false);
});


test('explicit browser hangup works without API credentials and sends no API command', async () => {
    const f = fixture(false);
    assert.equal(f.buttons.end_browser_call.disabled, true);
    f.connect(); await f.click('end_browser_call');
    assert.deepEqual(f.audioRequests, ['dialer:end-browser-call']);
    assert.equal(f.requests.length, 0);
    assert.equal(f.buttons.end_browser_call.disabled, true);
});

test('cancelled browser hangup preserves the phone connection', async () => {
    const f = fixture(); f.connect(); f.cancel();
    await f.click('end_browser_call');
    assert.equal(f.audioRequests.length, 0);
    assert.equal(f.buttons.end_browser_call.disabled, false);
});

test('SIP hangup failure is shown without claiming the call ended', async () => {
    const f = fixture(); f.connect(); f.failAudio();
    await f.click('end_browser_call');
    assert.equal(f.elements['vicidial-inline-feedback'].textContent, 'SIP hangup failed');
    assert.equal(f.buttons.end_browser_call.disabled, false);
});

function browserHangupFixture(overrides = {}) {
    const blade = fs.readFileSync(path.join(__dirname, '../../resources/views/backend/pages/dialer/index.blade.php'), 'utf8');
    const start = blade.indexOf("window.addEventListener('dialer:end-browser-call'");
    const end = blade.indexOf("window.addEventListener('dialer:sip-disconnected', handleTransportDisconnect)", start);
    assert.ok(start >= 0 && end > start);
    let handler; let byeCount = 0;
    const context = { isVicidial: true, callUuid: null, directSipActive: true,
        webRtcClient: { withSessionOp: (op) => op(), simpleUser: { session: {}, hangup: async () => { byeCount++; } } },
        window: { addEventListener: (_, fn) => { handler = fn; } }, ...overrides };
    vm.runInNewContext(blade.slice(start, end), context);
    return { invoke: () => new Promise((resolve, reject) => handler({ detail: { resolve, reject } })),
        count: () => byeCount };
}

test('browser control hangs up only the current direct SIP session', async () => {
    const f = browserHangupFixture(); await f.invoke(); assert.equal(f.count(), 1);
});

test('browser control refuses ordinary FreeSWITCH and managed call UUIDs', async () => {
    for (const override of [{ isVicidial: false }, { callUuid: 'existing-fs-call' }, { directSipActive: false }]) {
        const f = browserHangupFixture(override);
        await assert.rejects(f.invoke(), /No direct VICIdial/);
        assert.equal(f.count(), 0);
    }
});

test('browser SIP failure propagates to the control instead of false success', async () => {
    const f = browserHangupFixture({ webRtcClient: { withSessionOp: (op) => op(),
        simpleUser: { session: {}, hangup: async () => { throw Error('BYE rejected'); } } } });
    await assert.rejects(f.invoke(), /BYE rejected/);
});

function hangupRoutingFixture(apiReady = true) {
    const blade = fs.readFileSync(path.join(__dirname, '../../resources/views/backend/pages/dialer/index.blade.php'), 'utf8');
    const f = fixture(apiReady); f.connect();
    let pending; let proxy;
    const selectors = [];
    const document = { querySelector: (selector) => {
        selectors.push(selector);
        const match = selector.match(/^\[data-vici-action="([a-z_]+)"\]$/);
        assert.ok(match, `Unexpected hangup target: ${selector}`);
        return { click: () => { pending = f.click(match[1]); } };
    } };
    const start = blade.indexOf("                    if (isVicidial) {", blade.indexOf('// Live call actions'));
    const end = blade.indexOf('                    await webRtcClient?.leaveConference();', start);
    assert.ok(start >= 0 && end > start);
    const main = vm.runInNewContext(`(async () => { ${blade.slice(start, end)} })`, { isVicidial: true, document });
    const compactStart = blade.indexOf("    compactCallWindow?.querySelectorAll('[data-call-proxy]')");
    const compactEnd = blade.indexOf('    compactMuteButton?', compactStart);
    assert.ok(compactStart >= 0 && compactEnd > compactStart);
    const button = { dataset: { callProxy: 'hangup' }, addEventListener: (_, handler) => { proxy = handler; } };
    vm.runInNewContext(blade.slice(compactStart, compactEnd), {
        vicidialMode: true, document, compactCallWindow: { querySelectorAll: () => [button] }
    });
    return { ...f, selectors, main: async () => { await main(); await pending; },
        compact: async () => { proxy(); await pending; } };
}

test('main and compact VICIdial Hangup end the browser call without the customer API', async () => {
    for (const control of ['main', 'compact']) {
        const f = hangupRoutingFixture(false);
        await f[control]();
        assert.deepEqual(f.selectors, ['[data-vici-action="end_browser_call"]']);
        assert.deepEqual(f.audioRequests, ['dialer:end-browser-call']);
        assert.equal(f.requests.length, 0);
    }
});

test('main and compact Hangup preserve agent audio when confirmation is cancelled', async () => {
    for (const control of ['main', 'compact']) {
        const f = hangupRoutingFixture(); f.cancel();
        await f[control]();
        assert.equal(f.audioRequests.length, 0);
        assert.equal(f.requests.length, 0);
        assert.equal(f.buttons.end_browser_call.disabled, false);
    }
});
