/**
 * Zero-dependency Firefox UI smoke/regression runner for this host.
 *
 * It starts geckodriver itself and drives the real UI through W3C WebDriver.
 * API calls are not used for functional assertions.
 *
 * Run: node backend/qa-ui-webdriver.js
 */
'use strict';

const http = require('http');
const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const APP = (process.env.QA_BASE_URL || 'http://127.0.0.1:18080').replace(/\/$/, '');
const DRIVER = process.env.QA_GECKODRIVER || '/snap/bin/geckodriver';
const PROFILE_ROOT = process.env.QA_FIREFOX_PROFILE_ROOT || path.resolve(__dirname, '..', '.qa-firefox');
const PORT = Number(process.env.QA_WEBDRIVER_PORT || 4446);
const EMAIL = process.env.QA_EMAIL || 'superadmin@example.com';
const PASSWORD = process.env.QA_PASSWORD || '12345678';
const results = { passed: [], failed: [] };
let driver;
let sessionId;

function request(method, path, body) {
  return new Promise(function (resolve, reject) {
    const data = body === undefined ? '' : JSON.stringify(body);
    const req = http.request({
      hostname: '127.0.0.1', port: PORT, path: path, method: method,
      headers: { 'Content-Type': 'application/json', 'Content-Length': Buffer.byteLength(data) }
    }, function (response) {
      let text = '';
      response.on('data', function (chunk) { text += chunk; });
      response.on('end', function () {
        try {
          const parsed = text ? JSON.parse(text) : {};
          if (parsed.value && parsed.value.error) return reject(new Error(parsed.value.message));
          resolve(parsed.value);
        } catch (error) { reject(error); }
      });
    });
    req.on('error', reject);
    req.setTimeout(30000, function () { req.destroy(new Error('WebDriver request timed out')); });
    if (data) req.write(data);
    req.end();
  });
}

function sleep(ms) { return new Promise(function (resolve) { setTimeout(resolve, ms); }); }
function pass(name, details) { results.passed.push(name); console.log('PASS ' + name + (details ? ' - ' + details : '')); }
function fail(name, error) { const detail = error instanceof Error ? error.message : String(error); results.failed.push({ name: name, detail: detail }); console.error('FAIL ' + name + ' - ' + detail); }
function assert(value, message) { if (!value) throw new Error(message); }
async function test(name, callback) { try { const detail = await callback(); pass(name, detail); } catch (error) { fail(name, error); } }
function endpoint(path) { return '/session/' + sessionId + path; }
function execute(script, args) { return request('POST', endpoint('/execute/sync'), { script: script, args: args || [] }); }
function navigate(url) { return request('POST', endpoint('/url'), { url: url }); }

async function waitFor(callback, message, timeout) {
  const end = Date.now() + (timeout || 15000);
  let value;
  while (Date.now() < end) {
    value = await callback();
    if (value) return value;
    await sleep(200);
  }
  throw new Error(message);
}

async function startDriver() {
  fs.mkdirSync(PROFILE_ROOT, { recursive: true });
  driver = spawn(DRIVER, ['--host', '127.0.0.1', '--port', String(PORT), '--profile-root', PROFILE_ROOT], { stdio: ['ignore', 'pipe', 'pipe'] });
  let errors = '';
  driver.stdout.on('data', function (chunk) { errors += String(chunk); });
  driver.stderr.on('data', function (chunk) { errors += String(chunk); });
  await waitFor(async function () {
    try { await request('GET', '/status'); return true; } catch (_) { return false; }
  }, 'geckodriver did not start: ' + errors, 10000);
  const session = await request('POST', '/session', {
    capabilities: { alwaysMatch: {
      browserName: 'firefox', acceptInsecureCerts: true,
      'moz:firefoxOptions': { args: ['-headless'], prefs: {
        'media.navigator.streams.fake': true,
        'media.navigator.permission.disabled': true
      } }
    } }
  });
  sessionId = session.sessionId;
  await request('POST', endpoint('/window/rect'), { width: 1600, height: 1000 });
}

async function login() {
  await navigate(APP + '/login');
  await execute('document.querySelector("#email").value=arguments[0]; document.querySelector("input[name=password]").value=arguments[1]; document.querySelector("button[type=submit]").click(); return true;', [EMAIL, PASSWORD]);
  await waitFor(async function () { return execute('return location.pathname === "/admin" || location.pathname === "/admin/";'); }, 'Login did not reach the dashboard', 20000);
}

async function clickSidebar(text) {
  const clicked = await execute('const wanted=arguments[0]; const link=[...document.querySelectorAll("a")].find(e=>e.textContent.trim()===wanted); if(!link)return false; link.click(); return true;', [text]);
  assert(clicked, 'Sidebar link not found: ' + text);
  await sleep(500);
}

async function dashboardTests() {
  await test('login and dashboard render', async function () {
    assert(await execute('return !!document.querySelector("aside");'), 'Dashboard sidebar missing');
    return EMAIL;
  });
  await test('required and removed sidebar items', async function () {
    const data = await execute('const t=document.querySelector("aside").innerText; return {t:t, links:[...document.querySelectorAll("aside a, aside button")].map(e=>e.textContent.trim())};');
    ['Dialer', 'Roles', 'Permissions', 'Users', 'View carrier', 'New Carrier', 'Inbound DIDs'].forEach(function (item) {
      assert(data.links.indexOf(item) !== -1, 'Missing ' + item);
    });
    assert(!/campaign/i.test(data.t), 'Campaign menu is visible');
    assert(!/\bposts?\b/i.test(data.t), 'Post menu is visible');
    assert(!/\bpages?\b/i.test(data.t), 'Page menu is visible');
  });
  await test('dashboard has no failed resources', async function () {
    const failed = await execute('return performance.getEntriesByType("resource").filter(e=>e.responseStatus>=400).map(e=>({url:e.name,status:e.responseStatus}));');
    assert(failed.length === 0, JSON.stringify(failed));
  });
}

async function dialerTests() {
  await clickSidebar('Dialer');
  await waitFor(async function () { return execute('return location.pathname.endsWith("/admin/dialer") && !!document.querySelector("#dialer-form");'); }, 'Dialer did not load');
  await test('three-panel dialer desktop layout', async function () {
    const data = await execute('const ids=["dialer-form","customer-call-panel","contact-workspace-panel"]; const rects=ids.map(id=>document.getElementById(id).getBoundingClientRect()); return {count:rects.length,x:rects.map(r=>r.x),visible:rects.map(r=>r.width>0&&r.height>0)};');
    assert(data.visible.every(Boolean), 'A panel is hidden');
    assert(data.x[0] < data.x[1] && data.x[1] < data.x[2], 'Panels are not ordered left-to-right');
  });
  await test('dial pad, backspace, and clear', async function () {
    const values = await execute('const d=document.querySelector("#dialpad-display"); ["1","2","#"].forEach(v=>[...document.querySelectorAll("[data-value]")].find(e=>e.dataset.value===v).click()); const typed=d.value; document.querySelector("#dialpad-backspace").click(); const back=d.value; document.querySelector("#dialpad-clear").click(); return {typed:typed,back:back,clear:d.value};');
    assert(values.typed === '12#' && values.back === '12' && values.clear === '', JSON.stringify(values));
  });
  await test('empty call is blocked in browser', async function () {
    await execute('document.querySelector("#dialpad-clear").click(); document.querySelector("#dialer-form button[type=submit]").click(); return true;');
    await sleep(300);
    const data = await execute('return {path:location.pathname,alert:document.querySelector("#dialer-alert").textContent.trim(),callId:document.querySelector("#call-id-badge").textContent.trim(),value:document.querySelector("#dialpad-display").value};');
    assert(data.path.endsWith('/admin/dialer') && data.callId === '' && data.value === '', 'Empty call entered an active call state: ' + JSON.stringify(data));
    assert(data.alert.length > 0, 'Empty destination showed no validation feedback');
  });
  await test('all contact tabs switch', async function () {
    const states = await execute('const out={}; ["notes","activity","history","info"].forEach(tab=>{const b=[...document.querySelectorAll("[data-contact-tab]")].find(e=>e.dataset.contactTab===tab);b.click();const p=[...document.querySelectorAll("[data-contact-tab-panel]")].find(e=>e.dataset.contactTabPanel===tab);out[tab]=b.getAttribute("aria-selected")==="true"&&!p.classList.contains("hidden")});return out;');
    Object.keys(states).forEach(function (tab) { assert(states[tab], tab + ' tab did not activate'); });
  });
  await test('labels, notes, activity, history, call and audio controls exist', async function () {
    const data = await execute('const ids=["contact-label-input","contact-label-add","contact-comment-input","contact-comment-add","contact-activity","contact-call-history","dialer-audio","browser-audio-status","incoming-call-banner"];return {missing:ids.filter(id=>!document.getElementById(id)),keys:document.querySelectorAll("[data-value]").length,hangup:document.querySelectorAll("[data-action=hangup]").length,mute:document.querySelectorAll("[data-action=mute]").length,duplicates:[...document.querySelectorAll("[id]")].map(e=>e.id).filter((id,i,a)=>a.indexOf(id)!==i)};');
    assert(data.missing.length === 0, 'Missing: ' + data.missing.join(', '));
    assert(data.keys === 12 && data.hangup === 1 && data.mute === 1, JSON.stringify(data));
    assert(data.duplicates.length === 0, 'Duplicate IDs: ' + data.duplicates.join(', '));
  });
  await test('dark/light mode toggles and persists', async function () {
    const before = await execute('return document.documentElement.classList.contains("dark");');
    await execute('document.querySelector("#sidebarDarkModeToggle").click(); return true;');
    await sleep(300);
    const after = await execute('return document.documentElement.classList.contains("dark");');
    const changed = { before: before, after: after };
    assert(changed.before !== changed.after, 'Theme did not change');
    await request('POST', endpoint('/refresh'), {});
    const persisted = await execute('return document.documentElement.classList.contains("dark");');
    assert(persisted === changed.after, 'Theme did not persist');
  });
}

async function adminTests() {
  const pages = [
    ['Users', '/admin/users', ['#dataTable']], ['New User', '/admin/users/create', ['#external_name','#email','#password','#carrierId']],
    ['Roles', '/admin/roles', ['#dataTable']], ['New Role', '/admin/roles/create', ['input[name=name]','#checkPermissionAll']],
    ['Permissions', '/admin/permissions', ['#dataTable']], ['View carrier', '/admin/carrier', []],
    ['New Carrier', '/admin/carrier/create', ['#name','#sipDomain','#sipPort']], ['Inbound DIDs', '/admin/carrier/inbound-dids', []]
  ];
  for (const item of pages) {
    await test(item[0] + ' sidebar page', async function () {
      await clickSidebar(item[0]);
      await waitFor(async function () { return execute('return location.pathname===arguments[0];', [item[1]]); }, item[0] + ' route did not load');
      const missing = await execute('return arguments[0].filter(s=>!document.querySelector(s));', [item[2]]);
      assert(missing.length === 0, 'Missing: ' + missing.join(', '));
      assert(!await execute('return /server error|ErrorException|stack trace/i.test(document.body.innerText);'), 'Error page rendered');
    });
  }
  await test('Inbound DID add form', async function () {
    const clicked = await execute('const a=[...document.querySelectorAll("a")].find(e=>/add inbound did/i.test(e.textContent));if(!a)return false;a.click();return true;');
    assert(clicked, 'Add Inbound DID link missing');
    await waitFor(async function () { return execute('return location.pathname.endsWith("/inbound-dids/create");'); }, 'DID form did not load');
    const missing = await execute('return ["#carrier_id","#did","#label"].filter(s=>!document.querySelector(s));');
    assert(missing.length === 0, 'DID fields missing: ' + missing.join(', '));
  });
}

async function mobileTests() {
  await request('POST', endpoint('/window/rect'), { width: 390, height: 844 });
  await navigate(APP + '/admin/dialer');
  await test('mobile panels stack without overflow', async function () {
    const data = await execute('const r=["dialer-form","customer-call-panel","contact-workspace-panel"].map(id=>document.getElementById(id).getBoundingClientRect());return {tops:r.map(x=>x.top),widths:r.map(x=>x.width),viewport:document.documentElement.clientWidth,scroll:document.documentElement.scrollWidth};');
    assert(data.tops[0] < data.tops[1] && data.tops[1] < data.tops[2], 'Panels do not stack');
    assert(data.widths.every(function (w) { return w <= data.viewport + 1; }), 'Panel exceeds viewport');
    assert(data.scroll <= data.viewport + 1, 'Horizontal page overflow ' + data.scroll + '/' + data.viewport);
  });
}

async function main() {
  try {
    await startDriver();
    await login();
    await dashboardTests();
    await dialerTests();
    await adminTests();
    await mobileTests();
  } catch (error) {
    fail('runner', error);
  } finally {
    if (sessionId) await request('DELETE', endpoint('')).catch(function () {});
    if (driver) driver.kill('SIGTERM');
  }
  console.log('\nQA RESULT: ' + results.passed.length + ' passed, ' + results.failed.length + ' failed');
  if (results.failed.length) console.log(JSON.stringify(results.failed, null, 2));
  process.exitCode = results.failed.length ? 1 : 0;
}

main();
