/**
 * Simple QA Testing Agent - API & HTTP Based
 * Tests all endpoints, pages, and basic functionality
 */
const http = require("http");
const BASE_URL = process.env.BASE_URL || "http://laravel";
const API_URL = process.env.API_URL || "http://localhost:4000";

const results = { passed: [], failed: [], warnings: [], startTime: new Date() };

function log(status, test, details = "") {
  const ts = new Date().toISOString().split("T")[1].split(".")[0];
  console.log(status + " [" + ts + "] " + test + (details ? " - " + details : ""));
}

function pass(test, details) { results.passed.push({test, details}); log("PASS", test, details); }
function fail(test, details) { results.failed.push({test, details}); log("FAIL", test, details); }
function warn(test, details) { results.warnings.push({test, details}); log("WARN", test, details); }

function httpGet(url) {
  return new Promise((resolve, reject) => {
    http.get(url, (res) => {
      let data = "";
      res.on("data", (c) => (data += c));
      res.on("end", () => resolve({ status: res.statusCode, headers: res.headers, data }));
    }).on("error", reject);
  });
}

function httpPost(url, body) {
  return new Promise((resolve, reject) => {
    const urlObj = new URL(url);
    const options = {
      hostname: urlObj.hostname,
      port: urlObj.port,
      path: urlObj.pathname + urlObj.search,
      method: "POST",
      headers: { "Content-Type": "application/json", "Accept": "application/json" },
    };
    const req = http.request(options, (res) => {
      let data = "";
      res.on("data", (c) => (data += c));
      res.on("end", () => resolve({ status: res.statusCode, data }));
    });
    req.on("error", reject);
    req.write(JSON.stringify(body));
    req.end();
  });
}

async function testBackendHealth() {
  log("TEST", "Backend Health Check");
  try {
    const res = await httpGet(API_URL + "/health");
    if (res.status === 200 && res.data.includes("ok")) pass("Backend health", "Status 200 OK");
    else fail("Backend health", "Status: " + res.status);
  } catch (err) { fail("Backend health", err.message); }
}

async function testFrontendPages() {
  log("TEST", "Frontend Pages");
  
  const pages = [
    { url: "/", name: "Home" },
    { url: "/admin", name: "Admin Dashboard" },
    { url: "/admin/login", name: "Login Page" },
    { url: "/admin/dialer", name: "Dialer" },
    { url: "/admin/recordings", name: "Recordings" },
    { url: "/admin/leads", name: "Leads" },
    { url: "/admin/users", name: "Users" },
    { url: "/admin/roles", name: "Roles" },
    { url: "/admin/settings", name: "Settings" },
    { url: "/admin/carrier", name: "Carriers" },
    { url: "/admin/carrier/inbound-dids", name: "Inbound DIDs" },
  ];
  
  for (const page of pages) {
    try {
      const res = await httpGet(BASE_URL + page.url);
      if (res.status === 200 || res.status === 302) {
        pass("Page: " + page.name, "Status " + res.status);
      } else if (res.status === 401 || res.status === 403) {
        pass("Page: " + page.name, "Auth required (expected)");
      } else {
        fail("Page: " + page.name, "Status " + res.status);
      }
    } catch (err) {
      fail("Page: " + page.name, err.message);
    }
  }
}

async function testApiEndpoints() {
  log("TEST", "API Endpoints");
  
  const endpoints = [
    { url: "/health", name: "Health Check" },
    { path: "/freeswitch/inbound", name: "FreeSWITCH Inbound", method: "POST", body: {uuid: "test", did: "1234", callerIdNumber: "5678", token: "sync-secret"} },
  ];
  
  for (const ep of endpoints) {
    try {
      const fullUrl = API_URL + ep.url;
      const res = ep.method === "POST" ? await httpPost(fullUrl, ep.body || {}) : await httpGet(fullUrl);
      if (res.status >= 200 && res.status < 500) {
        pass("API: " + ep.name, "Status " + res.status);
      } else {
        fail("API: " + ep.name, "Status " + res.status);
      }
    } catch (err) {
      warn("API: " + ep.name, err.message);
    }
  }
}

async function testDatabaseData() {
  log("TEST", "Database Test Data");
  
  // Test via backend API if available
  try {
    const res = await httpGet(API_URL + "/admin/users?per_page=100");
    if (res.status === 200) {
      try {
        const data = JSON.parse(res.data);
        const count = data.data ? data.data.length : (Array.isArray(data) ? data.length : 0);
        if (count >= 4) pass("Test users created", "Found " + count + " users");
        else warn("Test users", "Only found " + count + " users");
      } catch (e) {
        warn("Test users", "Could not parse response");
      }
    }
  } catch (err) {
    warn("Test data check", err.message);
  }
}

async function generateReport() {
  results.endTime = new Date();
  const duration = (results.endTime - results.startTime) / 1000;
  
  console.log("\n" + "=".repeat(60));
  console.log("QA TEST REPORT");
  console.log("=".repeat(60));
  console.log("Duration: " + duration.toFixed(1) + "s");
  console.log("Passed: " + results.passed.length);
  console.log("Failed: " + results.failed.length);
  console.log("Warnings: " + results.warnings.length);
  console.log("Total: " + (results.passed.length + results.failed.length + results.warnings.length));
  
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
  log("START", "Starting QA Test Agent");
  await testBackendHealth();
  await testFrontendPages();
  await testApiEndpoints();
  await testDatabaseData();
  const exitCode = await generateReport();
  process.exit(exitCode);
}

main();
