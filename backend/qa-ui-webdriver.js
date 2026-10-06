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

const APP = (process.env.QA_BASE_URL || 'http://127.0.0.1:8080').replace(/\/$/, '');
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
  blocked: [],
  inventory: [],
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
  catch (error) {
    if (error.qaBlocked) { results.blocked.push({name, detail:error.message}); console.error('BLOCKED ' + name + ' - ' + error.message); }
    else fail(name, error);
    await captureScreenshot(name);
  }
}
function endpoint(path) { return '/session/' + sessionId + path; }
function execute(script, args) { return request('POST', endpoint('/execute/sync'), { script: script, args: args || [] }); }
function navigate(url) { return request('POST', endpoint('/url'), { url: url }); }

// Mutations use native WebDriver input, so hidden or covered controls cannot pass.
const ELEMENT_KEY = 'element-6066-11e4-a52e-4f735466cecf';
async function element(selector) {
  return request('POST', endpoint('/element'), { using: 'css selector', value: selector });
}
async function click(selector) {
  const target = typeof selector === 'string' ? await element(selector) : selector;
  let lastError;
  for (let attempt = 0; attempt < 25; attempt += 1) {
    try { return await request('POST', endpoint('/element/' + target[ELEMENT_KEY] + '/click'), {}); }
    catch (error) { lastError = error; if (!/obscures|not interactable|not clickable/.test(error.message)) throw error; await sleep(200); }
  }
  throw lastError;
}
async function fill(selector, text) {
  const target = await element(selector);
  const route = endpoint('/element/' + target[ELEMENT_KEY]);
  await request('POST', route + '/clear', {});
  await request('POST', route + '/value', { text });
}
async function visible(selector) {
  return execute('const e=document.querySelector(arguments[0]); if(!e)return false; const r=e.getBoundingClientRect(),s=getComputedStyle(e); return r.width>0 && r.height>0 && s.display!=="none" && s.visibility!=="hidden";', [selector]);
}
async function inventory() {
  const data = await execute('return {url:location.href,controls:[...document.querySelectorAll("button,a[href],input,select,textarea,[role=tab]")].map(e=>({tag:e.tagName,id:e.id,label:(e.getAttribute("aria-label")||e.innerText||e.getAttribute("placeholder")||e.title||"").trim().slice(0,100),type:e.type,disabled:!!e.disabled,visible:!!(e.getBoundingClientRect().width&&e.getBoundingClientRect().height)}))};');
  results.inventory.push(data);
}

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
  driver.on('error', function (error) { errors += error.message; });
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
  await fill('#email', EMAIL);
  await fill('input[name=password]', PASSWORD);
  await click('button[type=submit]');
  await waitFor(async function () { return execute('return location.pathname === "/admin" || location.pathname === "/admin/";'); }, 'Login did not reach the dashboard', 20000);
}

async function clickSidebar(text) {
  const link = await execute('return [...document.querySelectorAll("aside a")].find(e=>e.textContent.trim()===arguments[0])||null;', [text]);
  assert(link, 'Sidebar link not found: ' + text);
  const opener = await execute('const link=arguments[0]; const menu=link.closest(".menu-dropdown"); if(menu && !menu.getBoundingClientRect().height)return menu.previousElementSibling; let p=link.parentElement; while(p && p.tagName!=="ASIDE"){if(getComputedStyle(p).display==="none" && p.previousElementSibling?.tagName==="BUTTON")return p.previousElementSibling;p=p.parentElement;}return null;', [link]);
  if (opener) await click(opener);
  await click(link);
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
    await click('#dialpad-clear');
    for (const key of ['1','2','#']) await click('[data-value="' + key + '"]');
    const typed = await execute('return document.querySelector("#dialpad-display").value;');
    await click('#dialpad-backspace');
    const back = await execute('return document.querySelector("#dialpad-display").value;');
    await click('#dialpad-clear');
    const values = {typed, back, clear: await execute('return document.querySelector("#dialpad-display").value;')};
    assert(values.typed === '12#' && values.back === '12' && values.clear === '', JSON.stringify(values));
  });
  await test('empty call is blocked in browser', async function () {
    const before = await execute('return performance.getEntriesByType("resource").filter(e=>e.name.includes("/admin/dialer/dial")).length;');
    await click('#dialpad-clear');
    await click('#dialer-form button[type=submit]');
    await sleep(500);
    const data = await execute('const alert=document.querySelector("#dialer-alert");const badge=document.querySelector("#call-id-badge");const input=document.querySelector("#dialpad-display");return {path:location.pathname,alert:alert?.textContent?.trim()||"",callId:badge?.textContent?.trim()||"",value:input?.value||"",hasForm:!!document.querySelector("#dialer-form"),dialRequests:performance.getEntriesByType("resource").filter(e=>e.name.includes("/admin/dialer/dial")).length};');
    assert(data.path.endsWith('/admin/dialer') && data.callId === '' && data.value === '' && data.hasForm, 'Empty call entered an active call state or left the dialer: ' + JSON.stringify(data));
    assert(data.alert.length > 0, 'Empty destination showed no validation feedback');
    assert(data.dialRequests === before, 'Empty destination was sent to the dial API');
  });
  await test('all contact tabs switch', async function () {
    for (const tab of ['notes', 'activity', 'history', 'info']) {
      await click('[data-contact-tab="' + tab + '"]');
      assert(await visible('[data-contact-tab-panel="' + tab + '"]'), tab + ' panel is not visible');
      assert(await execute('return document.querySelector(arguments[0]).getAttribute("aria-selected")==="true";', ['[data-contact-tab="' + tab + '"]']), tab + ' is not selected');
    }
  });
  await test('labels, notes, activity, history, call and audio controls exist', async function () {
    const data = await execute('const ids=["contact-label-input","contact-label-add","contact-comment-input","contact-comment-add","contact-activity","contact-call-history","dialer-audio","incoming-call-banner"];const audio=document.querySelector("#dialer-audio");return {missing:ids.filter(id=>!document.getElementById(id)),keys:document.querySelectorAll("[data-value]").length,hangup:document.querySelectorAll("[data-action=hangup]").length,mute:document.querySelectorAll("[data-call-proxy=mute]").length,audio:{autoplay:audio?.hasAttribute("autoplay"),playsInline:audio?.hasAttribute("playsinline")},duplicates:[...document.querySelectorAll("[id]")].map(e=>e.id).filter((id,i,a)=>a.indexOf(id)!==i)};');
    assert(data.missing.length === 0, 'Missing: ' + data.missing.join(', '));
    assert(data.keys === 12 && data.hangup === 1 && data.mute === 1, JSON.stringify(data));
    assert(data.audio.autoplay && data.audio.playsInline, 'Remote audio is not configured for automatic inline playback');
    assert(data.duplicates.length === 0, 'Duplicate IDs: ' + data.duplicates.join(', '));
  });
  await test('dark/light mode toggles and persists', async function () {
    const before = await execute('return document.documentElement.classList.contains("dark");');
    await click('#sidebarDarkModeToggle');
    await sleep(300);
    const after = await execute('return document.documentElement.classList.contains("dark");');
    const changed = { before: before, after: after };
    assert(changed.before !== changed.after, 'Theme did not change');
    await request('POST', endpoint('/refresh'), {});
    const persisted = await execute('return document.documentElement.classList.contains("dark");');
    assert(persisted === changed.after, 'Theme did not persist');
    await click('#sidebarDarkModeToggle');
  });
}

async function contactDataTests() {
  const phone = process.env.QA_CONTACT_PHONE || '+15550190099';
  const name = 'Automated QA Contact';
  let ready = false;
  await test('controlled contact saves and survives reload', async function () {
    await click('[data-contact-tab="info"]');
    await fill('#contact-search', phone);
    await sleep(900);
    const existing = await execute('return document.querySelector("#contact-search-results [data-contact-id]")||null;', [phone]);
    if (existing) {
      await click(existing);
      await sleep(500);
      assert(await execute('return document.querySelector("#contact-name-input").value===arguments[0] && document.querySelector("#contact-phone-input").value===arguments[1];', [name, phone]), 'Fixture phone belongs to non-QA contact; refusing to modify');
    }
    await click('[data-contact-tab="info"]');
    await fill('#contact-name-input', name);
    await fill('#contact-phone-input', phone);
    await fill('#contact-company-input', 'Webportal UI QA');
    await fill('#contact-email-input', 'qa-contact@example.invalid');
    await click('#contact-save');
    await waitFor(() => execute('return /saved/i.test(document.querySelector("#contact-feedback").textContent);'), 'Contact save showed no success');
    await request('POST', endpoint('/refresh'), {});
    await fill('#contact-search', phone);
    await waitFor(() => execute('return !!document.querySelector("#contact-search-results [data-contact-id]");'), 'Saved contact missing after reload');
    await click('#contact-search-results [data-contact-id]');
    await click('[data-contact-tab="info"]');
    await waitFor(() => execute('return document.querySelector("#contact-name-input").value===arguments[0] && document.querySelector("#contact-phone-input").value===arguments[1];', [name, phone]), 'Saved contact values did not persist');
    ready = true;
  });
  if (!ready) return;
  await test('QA contact label can be added and removed', async function () {
    const label = 'qa-loop-check';
    const selector = '[data-contact-label="' + label + '"]';
    if (await visible(selector)) await click(selector);
    await fill('#contact-label-input', label);
    await click('#contact-label-add');
    await waitFor(() => visible(selector), 'Added label did not appear');
    await click(selector);
    await waitFor(async () => !(await visible(selector)), 'Removed label remains visible');
  });
  await test('QA contact follow-up flag toggles and restores', async function () {
    const before = await execute('return document.querySelector("#contact-flag-toggle").getAttribute("aria-pressed");');
    try {
      await click('#contact-flag-toggle');
      await waitFor(() => execute('return document.querySelector("#contact-flag-toggle").getAttribute("aria-pressed")!==arguments[0];', [before]), 'Flag did not toggle');
    } finally {
      const after = await execute('return document.querySelector("#contact-flag-toggle").getAttribute("aria-pressed");');
      if (after !== before) {
        await click('#contact-flag-toggle');
        await waitFor(() => execute('return document.querySelector("#contact-flag-toggle").getAttribute("aria-pressed")===arguments[0];', [before]), 'Flag was not restored');
      }
    }
  });
  await test('contact comment persists and activity records change', async function () {
    const note = 'QA verification ' + RUN_ID;
    await click('[data-contact-tab="notes"]');
    await fill('#contact-comment-input', note);
    await click('#contact-comment-add');
    await waitFor(() => execute('return document.querySelector("#contact-comments").textContent.includes(arguments[0]);', [note]), 'Comment was not rendered');
    await request('POST', endpoint('/refresh'), {});
    await fill('#contact-search', phone);
    await waitFor(() => execute('return !!document.querySelector("#contact-search-results [data-contact-id]");'), 'Contact search did not load');
    await click('#contact-search-results [data-contact-id]');
    await click('[data-contact-tab="notes"]');
    await waitFor(() => execute('return document.querySelector("#contact-comments").textContent.includes(arguments[0]);', [note]), 'Comment missing after reload');
    await click('[data-contact-tab="activity"]');
    await waitFor(() => execute('return /comment/i.test(document.querySelector("#contact-activity").textContent);'), 'Comment activity was not recorded');
    await click('[data-contact-tab="history"]');
    await waitFor(() => execute('const t=document.querySelector("#contact-call-history").textContent.trim();return t.length>0&&!/loading|unable|error/i.test(t);'), 'Call history failed to load');
  });
}

async function echoTests() {
  const number = process.env.QA_ECHO_NUMBER;
  if (!number) {
    results.blocked.push({name:'live call and audio', detail:'Set QA_ECHO_NUMBER to an authorized controlled echo destination.'});
    return;
  }
  await test('controlled echo call connects, plays audio, mutes and hangs up', async function () {
    await navigate(APP + '/admin/dialer');
    try {
      await fill('#dialpad-display', number);
      await click('#dialer-form button[type=submit]');
      await waitFor(async () => {
        const alert = await execute('return document.querySelector("#dialer-alert")?.textContent || "";');
        if (/Browser audio is not configured for this user/i.test(alert)) {
          const error = new Error(alert); error.qaBlocked = true; throw error;
        }
        return execute('const a=document.querySelector("#dialer-audio"); return !!a?.srcObject?.getAudioTracks().some(t=>t.readyState==="live") && !a.paused;');
      }, 'Echo call did not produce a playing remote audio track', 30000);
      const measurement = await request('POST', endpoint('/execute/async'), {script: `const done=arguments[arguments.length-1];
        (async()=>{const a=document.querySelector('#dialer-audio');const c=new AudioContext();
        try {await c.resume();const source=c.createMediaStreamSource(a.srcObject);const analyser=c.createAnalyser();source.connect(analyser);
        const data=new Float32Array(analyser.fftSize);let peak=0;const end=Date.now()+3000;
        while(Date.now()<end){analyser.getFloatTimeDomainData(data);for(const value of data)peak=Math.max(peak,Math.abs(value));await new Promise(r=>setTimeout(r,50));}
        done({peak,muted:a.muted,volume:a.volume});}catch(e){done({error:e.message});}finally{await c.close();}})();`, args:[]});
      assert(!measurement.error && measurement.peak > 0.001 && !measurement.muted && measurement.volume > 0, 'Remote sound was silent or blocked: '+JSON.stringify(measurement));
      const mute = '[data-call-proxy="mute"]';
      await click(mute);
      await waitFor(() => execute('return document.querySelector("[data-call-proxy=mute]").getAttribute("aria-pressed")==="true";'), 'Mute UI did not activate');
      await click(mute);
      await waitFor(() => execute('return document.querySelector("[data-call-proxy=mute]").getAttribute("aria-pressed")==="false";'), 'Unmute UI did not activate');
    } finally {
      const active = await execute('return !document.querySelector("[data-action=hangup]")?.disabled;');
      if (active) {
        await click(await visible('[data-call-proxy="hangup"]') ? '[data-call-proxy="hangup"]' : '[data-action="hangup"]');
        await waitFor(() => execute('return document.querySelector("[data-action=hangup]").disabled;'), 'Hangup did not end call');
      }
    }
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
    const clicked = await execute('const a=[...document.querySelectorAll("a")].find(e=>/add inbound did/i.test(e.textContent));return a||null;');
    assert(clicked, 'Add Inbound DID link missing');
    await click(clicked);
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
      await navigate(APP + '/admin');
      await clickSidebar(route.label);
      await sleep(250);
      await inventory();
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
  await test('mobile contact tabs reveal their content', async function () {
    for (const tab of ['notes', 'activity', 'history', 'info']) {
      await click('[data-contact-tab="' + tab + '"]');
      assert(await visible('[data-contact-tab-panel="' + tab + '"]'), tab + ' mobile panel is hidden');
    }
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
  results.summary = { passed: results.passed.length, failed: results.failed.length, blocked: results.blocked.length };
  results.coverage = { exhaustive: false, scope: 'UI navigation, dialpad, contact data, theme, layout and configured echo call', unverified: ['Inbound call fixture, carrier/PSTN calls and recordings', 'Role-by-role authorization and administrative CRUD', 'AI agent conversations and all settings combinations', 'Human speaker audibility and microphone hardware'] };
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
    await contactDataTests();
    await echoTests();
    await adminTests();
    await mobileTests();
  } catch (error) {
    fail('runner', error);
    await captureScreenshot('runner');
  } finally {
    if (sessionId) await request('DELETE', endpoint('')).catch(function () {});
    if (driver) driver.kill('SIGTERM');
  }
  console.log('\nQA RESULT: ' + results.passed.length + ' passed, ' + results.failed.length + ' failed, ' + results.blocked.length + ' blocked');
  if (results.failed.length) console.log(JSON.stringify(results.failed, null, 2));
  writeReport();
  process.exitCode = results.failed.length ? 1 : results.blocked.length ? 2 : 0;
}

main();
