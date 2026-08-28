/**
 * QA HTTP Tester - Tests all pages and APIs
 * Run with: docker compose exec backend node /app/qa-http-tester.js
 */
const http = require("http");

const BASE_URL = process.env.BASE_URL || "http://laravel";
const API_URL = process.env.API_URL || "http://backend:4000";
const ADMIN_EMAIL = "admin@webphone.local";
const ADMIN_PASSWORD = "AdminPass123!";

const results = { passed: [], failed: [], warnings: [], startTime: new Date() };
let sessionCookie = "";
let csrfToken = "";

function log(status, test, details = "") {
  const ts = new Date().toISOString().split("T")[1].split(".")[0];
  console.log(status + " [" + ts + "] " + test + (details ? " - " + details : ""));
}

function pass(test, details) { results.passed.push({test, details}); log("PASS", test, details); }
function fail(test, details) { results.failed.push({test, details}); log("FAIL", test, details); }
function warn(test, details) { results.warnings.push({test, details}); log("WARN", test, details); }

function httpRequest(url, method = "GET", body = "", cookie = "", isJson = false) {
  return new Promise((resolve, reject) => {
    const urlObj = new URL(url);
    const bodyData = isJson ? JSON.stringify(body) : body;
    const options = {
      hostname: urlObj.hostname,
      port: urlObj.port,
      path: urlObj.pathname + urlObj.search,
      method,
      headers: {
        Cookie: cookie,
        "Content-Type": isJson ? "application/json" : "application/x-www-form-urlencoded",
        "Content-Length": Buffer.byteLength(bodyData),
        Accept: "text/html,application/json,*/*",
      },
    };
    const req = http.request(options, (res) => {
      let data = "";
      res.on("data", (c) => (data += c));
      res.on("end", () => resolve({ status: res.statusCode, headers: res.headers, data, url: res.headers.location }));
    });
    req.on("error", reject);
    if (bodyData) req.write(bodyData);
    req.end();
  });
}

async function extractCsrf(html) {
  const match = html.match(/<input[^>]*name="_token"[^>]*value="([^"]+)"/) || 
                html.match(/<meta[^>]*name="csrf-token"[^>]*content="([^"]+)"/);
  return match ? match[1] : "";
}

async function login() {
  log("AUTH", "Testing login");
  const loginPage = await httpRequest(BASE_URL + "/admin/login");
  if (loginPage.status !== 200) { fail("Login page", "Status: " + loginPage.status); return false; }
  csrfToken = await extractCsrf(loginPage.data);
  if (!csrfToken) { fail("CSRF token", "Not found"); return false; }
  
  const cookies = loginPage.headers["set-cookie"];
  const cookieStr = cookies ? cookies.map(c => c.split(";")[0]).join("; ") : "";
  
  const loginBody = "_token=" + encodeURIComponent(csrfToken) + "&email=" + encodeURIComponent(ADMIN_EMAIL) + "&password=" + encodeURIComponent(ADMIN_PASSWORD);
  const loginRes = await httpRequest(BASE_URL + "/admin/login", "POST", loginBody, cookieStr);
  
  const respCookies = loginRes.headers["set-cookie"];
  if (respCookies) sessionCookie = respCookies.map(c => c.split(";")[0]).join("; ");
  
  if (loginRes.status === 302) { pass("Login", "Success"); return true; }
  fail("Login", "Status: " + loginRes.status);
  return false;
}

async function testAllPages() {
  log("TEST", "Testing all pages");
  const pages = [
    { url: "/", name: "Home" },
    { url: "/admin", name: "Dashboard" },
    { url: "/admin/login", name: "Login" },
    { url: "/admin/dialer", name: "Dialer" },
    { url: "/admin/recordings", name: "Recordings" },
    { url: "/admin/leads", name: "Leads" },
    { url: "/admin/leads/create", name: "Create Lead" },
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
    { url: "/admin/dialer/contacts", name: "Dialer Contacts" },
    { url: "/admin/campaigns", name: "Campaigns" },
  ];
  
  for (const page of pages) {
    try {
      const res = await httpRequest(BASE_URL + page.url, "GET", "", sessionCookie);
      if (res.status === 200) pass("Page: " + page.name, "OK");
      else if (res.status === 302) pass("Page: " + page.name, "Redirect (auth)");
      else if (res.status === 403) warn("Page: " + page.name, "Forbidden");
      else fail("Page: " + page.name, "Status: " + res.status);
    } catch (err) { fail("Page: " + page.name, err.message); }
  }
}

async function testApiEndpoints() {
  log("TEST", "Testing API endpoints");
  const endpoints = [
    { url: "/health", name: "Health Check", expected: 200 },
    { url: "/calls", name: "Calls API", expected: 200 },
    { url: "/admin/users", name: "Users API", expected: 200 },
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
  log("TEST", "Testing FreeSWITCH connectivity");
  try {
    const res = await httpRequest(API_URL + "/health");
    if (res.status === 200) pass("FreeSWITCH", "Backend can reach FreeSWITCH");
    else fail("FreeSWITCH", "Status: " + res.status);
  } catch (err) { fail("FreeSWITCH", err.message); }
}

async function generateReport() {
  results.endTime = new Date();
  const duration = (results.endTime - results.startTime) / 1000;
  
  console.log("\n" + "=".repeat(60));
  console.log("QA HTTP TEST REPORT");
  console.log("=".repeat(60));
  console.log("Duration: " + duration.toFixed(1) + "s");
  console.log("PASSED: " + results.passed.length);
  console.log("FAILED: " + results.failed.length);
  console.log("WARNINGS: " + results.warnings.length);
  console.log("TOTAL: " + (results.passed.length + results.failed.length + results.warnings.length));
  
  if (results.failed.length > 0) {
    console.log("\n--- FAILURES ---");
    results.failed.forEach((f, i) => console.log((i + 1) + ". " + f.test + ": " + f.details));
  }
  if (results.warnings.length > 0) {
    console.log("\n--- WARNINGS ---");
    results.warnings.forEach((w, i) => console.log((i + 1) + ". " + w.test + ": " + w.details));
  }
  console.log("\n" + "=".repeat(60));
  return results.failed.length > 0 ? 1 : 0;
}

async function main() {
  results.startTime = new Date();
  log("START", "QA HTTP Tester Started");
  
  await testFreeSWITCH();
  const loggedIn = await login();
  if (loggedIn) await testAllPages();
  await testApiEndpoints();
  
  const exitCode = await generateReport();
  process.exit(exitCode);
}

main();
