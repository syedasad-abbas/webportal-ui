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
const RUN_ID = process.env.QA_RUN_ID || new Date().toISOString().replace(/[:.]/g, '-');
const ARTIFACT_DIR = process.env.QA_ARTIFACT_DIR || path.resolve(__dirname, '..', '.qa-artifacts', RUN_ID);
const REPORT_PATH = process.env.QA_REPORT_PATH || path.join(ARTIFACT_DIR, 'report.json');
const results = {
  runId: RUN_ID,
  startedAt: new Date().toISOString(),
  baseUrl: APP,
  browser: 'firefox',
  passed: [],
  failed: [],
  diagnostics: {},
  artifacts: []
};
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
function pass(name, details) { results.passed.push({ name: name, detail: details || '' }); console.log('PASS ' + name + (details ? ' - ' + details : '')); }
function fail(name, error) { const detail = error instanceof Error ? error.message : String(error); results.failed.push({ name: name, detail: detail }); console.error('FAIL ' + name + ' - ' + detail); }
function assert(value, message) { if (!value) throw new Error(message); }
function safeName(name) { return name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 80); }
async function captureScreenshot(name) {
  if (!sessionId) return;
  try {
    const encoded = await request('GET', endpoint('/screenshot'));
    const target = path.join(ARTIFACT_DIR, safeName(name) + '.png');
    fs.mkdirSync(ARTIFACT_DIR, { recursive: true });
    fs.writeFileSync(target, encoded, 'base64');
    results.artifacts.push(target);
  } catch (_) {}
}
async function test(name, callback) {
  try { const detail = await callback(); pass(name, detail); }
  catch (error) { fail(name, error); await captureScreenshot(name); }
}
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
  await test('required sidebar items are available', async function () {
    const data = await execute('return {links:[...document.querySelectorAll("aside a, aside button")].map(e=>e.textContent.trim())};');
    ['Dialer', 'Roles', 'Permissions', 'Users', 'View carrier', 'New Carrier', 'Inbound DIDs'].forEach(function (item) {
      assert(data.links.indexOf(item) !== -1, 'Missing ' + item);
    });
  });
  await test('dashboard has no failed resources', async function () {
    const failed = await execute('return performance.getEntriesByType("resource").filter(e=>e.responseStatus>=400).map(e=>({url:e.name,status:e.responseStatus}));');
    assert(failed.length === 0, JSON.stringify(failed));
  });
}

async function dialerTests() {
  await clickSidebar('Dialer');
  await waitFor(async function () { return execute('return location.pathname.endsWith("/admin/dialer") && !!document.querySelector("#dialer-form");'); }, 'Dialer did not load');
  await test('desktop dialer work areas render usable content', async function () {
    const data = await execute('const visible=e=>{if(!e)return false;const s=getComputedStyle(e);const r=e.getBoundingClientRect();return s.display!=="none"&&s.visibility!=="hidden"&&(r.width>0&&r.height>0||s.display==="contents")}; const ids=["dialer-form","customer-call-panel","contact-workspace-panel"]; return {missing:ids.filter(id=>!document.getElementById(id)),usable:ids.map(id=>visible(document.getElementById(id))),viewport:document.documentElement.clientWidth,overflow:document.documentElement.scrollWidth};');
    assert(data.missing.length === 0, 'Missing work areas: ' + data.missing.join(', '));
    assert(data.usable.every(Boolean), 'A dialer work area has no usable layout: ' + JSON.stringify(data));
    assert(data.overflow <= data.viewport + 1, 'Desktop page overflows horizontally');
  });
  await test('dial pad, backspace, and clear', async function () {
    const values = await execute('const d=document.querySelector("#dialpad-display"); ["1","2","#"].forEach(v=>[...document.querySelectorAll("[data-value]")].find(e=>e.dataset.value===v).click()); const typed=d.value; document.querySelector("#dialpad-backspace").click(); const back=d.value; document.querySelector("#dialpad-clear").click(); return {typed:typed,back:back,clear:d.value};');
    assert(values.typed === '12#' && values.back === '12' && values.clear === '', JSON.stringify(values));
  });
  await test('empty call is blocked in browser', async function () {
    const before = await execute('return performance.getEntriesByType("resource").filter(e=>e.name.includes("/admin/dialer/dial")).length;');
    await execute('document.querySelector("#dialpad-clear").click(); document.querySelector("#dialer-form button[type=submit]").click(); return true;');
    await sleep(500);
    const data = await execute('const alert=document.querySelector("#dialer-alert");const badge=document.querySelector("#call-id-badge");const input=document.querySelector("#dialpad-display");return {path:location.pathname,alert:alert?.textContent?.trim()||"",callId:badge?.textContent?.trim()||"",value:input?.value||"",hasForm:!!document.querySelector("#dialer-form"),dialRequests:performance.getEntriesByType("resource").filter(e=>e.name.includes("/admin/dialer/dial")).length};');
    assert(data.path.endsWith('/admin/dialer') && data.callId === '' && data.value === '' && data.hasForm, 'Empty call entered an active call state or left the dialer: ' + JSON.stringify(data));
    assert(data.alert.length > 0, 'Empty destination showed no validation feedback');
    assert(data.dialRequests === before, 'Empty destination was sent to the dial API');
  });
  await test('all contact tabs switch', async function () {
    const states = await execute('const out={}; ["notes","activity","history","info"].forEach(tab=>{const b=[...document.querySelectorAll("[data-contact-tab]")].find(e=>e.dataset.contactTab===tab);b.click();const p=[...document.querySelectorAll("[data-contact-tab-panel]")].find(e=>e.dataset.contactTabPanel===tab);out[tab]=b.getAttribute("aria-selected")==="true"&&!p.classList.contains("hidden")});return out;');
    Object.keys(states).forEach(function (tab) { assert(states[tab], tab + ' tab did not activate'); });
  });
  await test('labels, notes, activity, history, call and audio controls exist', async function () {
    const data = await execute('const ids=["contact-label-input","contact-label-add","contact-comment-input","contact-comment-add","contact-activity","contact-call-history","dialer-audio","incoming-call-banner"];const audio=document.querySelector("#dialer-audio");return {missing:ids.filter(id=>!document.getElementById(id)),keys:document.querySelectorAll("[data-value]").length,hangup:document.querySelectorAll("[data-action=hangup]").length,mute:document.querySelectorAll("[data-call-proxy=mute]").length,audio:{autoplay:audio?.hasAttribute("autoplay"),playsInline:audio?.hasAttribute("playsinline")},duplicates:[...document.querySelectorAll("[id]")].map(e=>e.id).filter((id,i,a)=>a.indexOf(id)!==i)};');
    assert(data.missing.length === 0, 'Missing: ' + data.missing.join(', '));
    assert(data.keys === 12 && data.hangup === 1 && data.mute === 1, JSON.stringify(data));
    assert(data.audio.autoplay && data.audio.playsInline, 'Remote audio is not configured for automatic inline playback');
    assert(data.duplicates.length === 0, 'Duplicate IDs: ' + data.duplicates.join(', '));
  });
  await test('compact mute control toggles microphone state in the UI', async function () {
    const before = await execute('const button=document.querySelector("[data-call-proxy=mute]");if(!button)return null;const value=button.getAttribute("aria-pressed")||"false";button.click();return value;');
    assert(before !== null, 'Compact mute control is missing');
    await sleep(150);
    const after = await execute('return document.querySelector("[data-call-proxy=mute]")?.getAttribute("aria-pressed")||"false";');
    assert(after !== before, 'Compact mute state did not toggle');
    await execute('document.querySelector("[data-call-proxy=mute]").click();return true;');
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

  await test('every permission-visible sidebar route renders through the UI', async function () {
    await navigate(APP + '/admin');
    const routes = await execute('return [...new Set([...document.querySelectorAll("aside a[href]")].map(a=>JSON.stringify({label:a.textContent.trim(),href:a.getAttribute("href")})).filter(Boolean))].map(x=>JSON.parse(x)).filter(x=>x.href&&x.href!=="#"&&!x.href.startsWith("javascript:")&&!x.href.includes("logout"));');
    assert(routes.length > 0, 'No sidebar routes were discovered');
    const failures = [];
    for (const route of routes) {
      const target = route.href.startsWith('http') ? route.href : APP + (route.href.startsWith('/') ? route.href : '/' + route.href);
      if (!target.startsWith(APP)) continue;
      await navigate(target);
      await sleep(250);
      const state = await execute('return {body:document.body?.innerText||"",status:performance.getEntriesByType("navigation").slice(-1)[0]?.responseStatus||0};');
      if (state.status >= 400 || /server error|ErrorException|stack trace/i.test(state.body)) failures.push({ label: route.label, href: route.href, status: state.status });
    }
    assert(failures.length === 0, 'Broken sidebar destinations: ' + JSON.stringify(failures));
    return routes.length + ' routes checked';
  });
}

async function mobileTests() {
  await request('POST', endpoint('/window/rect'), { width: 390, height: 844 });
  await navigate(APP + '/admin/dialer');
  await test('mobile dialer controls remain usable without overflow', async function () {
    const data = await execute('const visible=e=>{if(!e)return false;const s=getComputedStyle(e),r=e.getBoundingClientRect();return s.display!=="none"&&s.visibility!=="hidden"&&r.width>0&&r.height>0};const required=["dialer-form","dialpad-display"];return {missing:required.filter(id=>!document.getElementById(id)),visible:required.map(id=>visible(document.getElementById(id))),viewport:document.documentElement.clientWidth,scroll:document.documentElement.scrollWidth,keys:document.querySelectorAll("[data-value]").length};');
    assert(data.missing.length === 0 && data.visible.every(Boolean), 'Primary mobile dialer controls are not usable: ' + JSON.stringify(data));
    assert(data.keys === 12, 'Mobile dialpad is incomplete');
    assert(data.scroll <= data.viewport + 1, 'Horizontal page overflow ' + data.scroll + '/' + data.viewport);
  });
}

async function apiDiagnostics() {
  return new Promise(function (resolve) {
    const target = new URL(process.env.QA_API_HEALTH_URL || 'http://127.0.0.1:4000/health');
    const req = http.get(target, { headers: { Accept: 'application/json', 'X-QA-Run-ID': RUN_ID } }, function (response) {
      let body = '';
      response.on('data', function (chunk) { body += chunk; });
      response.on('end', function () {
        let parsed = body;
        try { parsed = JSON.parse(body); } catch (_) {}
        resolve({ url: target.toString(), status: response.statusCode, body: parsed });
      });
    });
    req.setTimeout(5000, function () { req.destroy(); resolve({ url: target.toString(), status: 0, error: 'timeout' }); });
    req.on('error', function (error) { resolve({ url: target.toString(), status: 0, error: error.message }); });
  });
}

function writeReport() {
  results.finishedAt = new Date().toISOString();
  results.summary = { passed: results.passed.length, failed: results.failed.length };
  fs.mkdirSync(path.dirname(REPORT_PATH), { recursive: true });
  fs.writeFileSync(REPORT_PATH, JSON.stringify(results, null, 2) + '\n');
  console.log('QA REPORT: ' + REPORT_PATH);
}

async function main() {
  try {
    results.diagnostics.apiHealth = await apiDiagnostics();
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
  writeReport();
  process.exitCode = results.failed.length ? 1 : 0;
}

main();
