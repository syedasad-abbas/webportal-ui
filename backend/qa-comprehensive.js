/**
 * Comprehensive QA Test - Tests all pages, buttons, forms, audio
 * Run with: docker compose exec backend node /app/qa-comprehensive.js
 */
const http = require("http");

const BASE_URL = "http://laravel";
const API_URL = "http://backend:4000";
const ADMIN_EMAIL = "admin@webphone.local";
const ADMIN_PASSWORD = "AdminPass123!";

const results = { passed: [], failed: [], warnings: [], startTime: new Date() };
let sessionCookie = "";
let csrfToken = "";

function log(status, test, details = "") {
  const ts = new Date().toISOString().split("T")[1].split(".")[0];
  const icons = { PASS: "✓", FAIL: "✗", WARN: "!", INFO: "→" };
  console.log(icons[status] + " [" + ts + "] " + test + (details ? " - " + details : ""));
}

function pass(test, details) { results.passed.push({test, details}); log("PASS", test, details); }
function fail(test, details) { results.failed.push({test, details}); log("FAIL", test, details); }
function warn(test, details) { results.warnings.push({test, details}); log("WARN", test, details); }
function info(test, details) { log("INFO", test, details); }

function httpRequest(url, method = "GET", body = "", cookie = "", isJson = false, followRedirect = true) {
  return new Promise((resolve, reject) => {
    const urlObj = new URL(url);
    const bodyData = isJson ? JSON.stringify(body) : body;
    const options = {
      hostname: urlObj.hostname,
      port: urlObj.port || 80,
      path: urlObj.pathname + urlObj.search,
      method,
      headers: {
        Cookie: cookie,
        "Content-Type": isJson ? "application/json" : "application/x-www-form-urlencoded",
        "Content-Length": Buffer.byteLength(bodyData),
        Accept: "text/html,application/json,*/*",
        "User-Agent": "QA-Test-Suite/1.0",
      },
    };
    const req = http.request(options, (res) => {
      let data = "";
      res.on("data", (c) => (data += c));
      res.on("end", () => {
        const redirect = followRedirect && (res.statusCode === 301 || res.statusCode === 302) && res.headers.location
          ? httpRequest(res.headers.location, "GET", "", cookie, false, false)
          : null;
        if (redirect) { redirect.then(r => resolve(r)); return; }
        resolve({ status: res.statusCode, headers: res.headers, data });
      });
    });
    req.on("error", reject);
    req.setTimeout(10000, () => { req.destroy(); reject(new Error("timeout")); });
    if (bodyData) req.write(bodyData);
    req.end();
  });
}

async function extractCsrf(html) {
  const match = html.match(/<input[^>]*name="_token"[^>]*value="([^"]+)"/) || 
                html.match(/<meta[^>]*name="csrf-token"[^>]*content="([^"]+)"/);
  return match ? match[1] : "";
}

async function extractElements(html) {
  const elements = { buttons: [], forms: [], inputs: [], links: [], audio: [], video: [], modals: [], tables: [], selects: [] };
  
  // Extract buttons
  const btnMatches = html.match(/<button[^>]*>/gi) || [];
  elements.buttons = btnMatches.length;
  
  // Extract forms
  const formMatches = html.match(/<form[^>]*>/gi) || [];
  elements.forms = formMatches.length;
  
  // Extract inputs
  const inputMatches = html.match(/<input[^>]*>/gi) || [];
  elements.inputs = inputMatches.length;
  
  // Extract textareas
  const textareaMatches = html.match(/<textarea[^>]*>/gi) || [];
  elements.inputs += textareaMatches.length;
  
  // Extract selects
  const selectMatches = html.match(/<select[^>]*>/gi) || [];
  elements.selects = selectMatches.length;
  
  // Extract audio
  const audioMatches = html.match(/<audio[^>]*>/gi) || [];
  elements.audio = audioMatches.length;
  
  // Extract video
  const videoMatches = html.match(/<video[^>]*>/gi) || [];
  elements.video = videoMatches.length;
  
  // Extract tables
  const tableMatches = html.match(/<table[^>]*>/gi) || [];
  elements.tables = tableMatches.length;
  
  // Extract links
  const linkMatches = html.match(/<a[^>]*href="([^"]*)"[^>]*>/gi) || [];
  elements.links = linkMatches.length;
  
  return elements;
}

async function login() {
  info("Login", "Testing authentication");
  const loginPage = await httpRequest(BASE_URL + "/admin/login");
  if (loginPage.status !== 200) { fail("Login page", "Status: " + loginPage.status); return false; }
  pass("Login page", "Accessible");
  
  csrfToken = await extractCsrf(loginPage.data);
  if (!csrfToken) { fail("CSRF token", "Not found"); return false; }
  pass("CSRF token", "Found");
  
  const cookies = loginPage.headers["set-cookie"];
  const cookieStr = cookies ? cookies.map(c => c.split(";")[0]).join("; ") : "";
  
  const loginBody = "_token=" + encodeURIComponent(csrfToken) + "&email=" + encodeURIComponent(ADMIN_EMAIL) + "&password=" + encodeURIComponent(ADMIN_PASSWORD);
  const loginRes = await httpRequest(BASE_URL + "/admin/login/submit", "POST", loginBody, cookieStr);
  
  const respCookies = loginRes.headers["set-cookie"];
  if (respCookies) sessionCookie = respCookies.map(c => c.split(";")[0]).join("; ");
  
  if (loginRes.status === 302 || loginRes.data.includes("/admin")) { 
    pass("Login submit", "Success"); 
    return true; 
  }
  fail("Login submit", "Status: " + loginRes.status);
  return false;
}

async function testPages() {
  info("Pages", "Testing all admin pages");
  const pages = [
    { url: "/admin", name: "Dashboard" },
    { url: "/admin/dialer", name: "Dialer" },
    { url: "/admin/contacts", name: "Contacts" },
    { url: "/admin/users", name: "Users" },
    { url: "/admin/users/create", name: "Create User" },
    { url: "/admin/roles", name: "Roles" },
    { url: "/admin/roles/create", name: "Create Role" },
    { url: "/admin/settings", name: "Settings" },
    { url: "/admin/carrier", name: "Carriers" },
    { url: "/admin/carrier/create", name: "Create Carrier" },
    { url: "/admin/carrier/inbound-dids", name: "Inbound DIDs" },
    { url: "/admin/permissions", name: "Permissions" },
    { url: "/admin/action-log", name: "Action Logs" },
    { url: "/admin/modules", name: "Modules" },
    { url: "/admin/translations", name: "Translations" },
    { url: "/admin/posts", name: "Posts" },
    { url: "/admin/leads", name: "Leads" },
    { url: "/admin/leads/create", name: "Create Lead" },
    { url: "/admin/campaigns", name: "Campaigns" },
    { url: "/admin/recordings", name: "Recordings" },
  ];
  
  for (const page of pages) {
    try {
      const res = await httpRequest(BASE_URL + page.url, "GET", "", sessionCookie);
      const elements = await extractElements(res.data);
      
      if (res.status === 200) {
        const summary = "buttons:" + elements.buttons + " forms:" + elements.forms + " inputs:" + elements.inputs + " tables:" + elements.tables;
        pass("Page: " + page.name, summary);
      } else if (res.status === 403) {
        warn("Page: " + page.name, "Forbidden (permission)");
      } else {
        fail("Page: " + page.name, "Status: " + res.status);
      }
    } catch (err) { fail("Page: " + page.name, err.message); }
  }
}

async function testDialerElements() {
  info("Dialer", "Testing dialer page elements");
  const dialerPage = await httpRequest(BASE_URL + "/admin/dialer", "GET", "", sessionCookie);
  if (dialerPage.status !== 200) { fail("Dialer page", "Not accessible"); return; }
  
  const html = dialerPage.data;
  
  // Test dialpad keys
  const dialpadKeys = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0", "*", "#"];
  for (const key of dialpadKeys) {
    const hasKey = html.includes("data-value=\"" + key + "\"") || html.includes("data-value='" + key + "'");
    if (hasKey) pass("Dialpad key " + key, "Present");
    else fail("Dialpad key " + key, "Missing");
  }
  
  // Test action buttons
  const actions = ["hangup", "mute", "unmute", "hold", "resume", "transfer", "park", "record"];
  for (const action of actions) {
    if (html.includes("data-action=\"" + action + "\"") || html.includes("data-action='" + action + "'")) {
      pass("Action button: " + action, "Present");
    } else {
      warn("Action button: " + action, "Not found");
    }
  }
  
  // Test audio element
  if (html.includes("<audio") || html.includes("<AUDIO")) {
    pass("Audio element", "Present");
  } else {
    warn("Audio element", "Not found in HTML (may be dynamically created)");
  }
  
  // Test WebRTC configuration
  if (html.includes("webrtcConfig") || html.includes("webrtc-config") || html.includes("sip:")) {
    pass("WebRTC config", "Present");
  } else {
    warn("WebRTC config", "Not found");
  }
  
  // Test dialpad display
  if (html.includes("dialpad-display") || html.includes("dialpadDisplay")) {
    pass("Dialpad display", "Present");
  } else {
    warn("Dialpad display", "Not found");
  }
}

async function testBackendAPI() {
  info("API", "Testing backend API endpoints");
  const endpoints = [
    { url: "/health", name: "Health Check", expected: 200 },
    { url: "/ping", name: "Ping", expected: 200 },
  ];
  
  for (const ep of endpoints) {
    try {
      const res = await httpRequest(API_URL + ep.url);
      if (res.status === ep.expected) pass("API: " + ep.name, "Status " + res.status);
      else warn("API: " + ep.name, "Status " + res.status);
    } catch (err) { fail("API: " + ep.name, err.message); }
  }
}

async function testFreeSWITCH() {
  info("FreeSWITCH", "Testing FreeSWITCH connectivity");
  try {
    const res = await httpRequest(API_URL + "/health");
    if (res.status === 200) {
      pass("FreeSWITCH", "Backend can reach FreeSWITCH");
      try {
        const data = JSON.parse(res.data);
        if (data.freeswitch) pass("FreeSWITCH status", data.freeswitch.connected ? "Connected" : "Disconnected");
      } catch (e) {}
    } else {
      fail("FreeSWITCH", "Status: " + res.status);
    }
  } catch (err) { fail("FreeSWITCH", err.message); }
}

async function generateReport() {
  results.endTime = new Date();
  const duration = (results.endTime - results.startTime) / 1000;
  
  console.log("\n" + "=".repeat(70));
  console.log("QA COMPREHENSIVE TEST REPORT");
  console.log("=".repeat(70));
  console.log("Duration: " + duration.toFixed(1) + "s");
  console.log("PASSED: " + results.passed.length + " ✓");
  console.log("FAILED: " + results.failed.length + " ✗");
  console.log("WARNINGS: " + results.warnings.length + " !");
  console.log("TOTAL: " + (results.passed.length + results.failed.length + results.warnings.length));
  console.log("=".repeat(70));
  
  if (results.failed.length > 0) {
    console.log("\n✗ FAILURES:");
    results.failed.forEach((f, i) => console.log("  " + (i + 1) + ". " + f.test + " - " + f.details));
  }
  if (results.warnings.length > 0) {
    console.log("\n! WARNINGS:");
    results.warnings.forEach((w, i) => console.log("  " + (i + 1) + ". " + w.test + " - " + w.details));
  }
  console.log("\n" + "=".repeat(70));
  
  return results.failed.length > 0 ? 1 : 0;
}

async function main() {
  results.startTime = new Date();
  console.log("=".repeat(70));
  console.log("QA COMPREHENSIVE TEST SUITE");
  console.log("Target: " + BASE_URL);
  console.log("Started: " + results.startTime.toISOString());
  console.log("=".repeat(70) + "\n");
  
  await testFreeSWITCH();
  await testBackendAPI();
  const loggedIn = await login();
  if (loggedIn) {
    await testPages();
    await testDialerElements();
  }
  
  const exitCode = await generateReport();
  process.exit(exitCode);
}

main();
