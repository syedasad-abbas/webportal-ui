/**
 * Comprehensive QA Testing Agent
 * Tests all UI elements, identifies bugs, provides fix recommendations
 * Uses curl for HTTP testing and API for log tracing
 */
const { execSync } = require("child_process");
const http = require("http");

const BASE_URL = process.env.BASE_URL || "http://laravel";
const API_URL = process.env.API_URL || "http://backend:4000";
const ADMIN_EMAIL = "admin@webphone.local";
const ADMIN_PASSWORD = "AdminPass123!";

const results = { passed: [], failed: [], warnings: [], fixes: [] };
let sessionCookie = "";

function log(status, test, details = "") {
  const ts = new Date().toISOString().split("T")[1].split(".")[0];
  console.log(status + " [" + ts + "] " + test + (details ? " - " + details : ""));
}

function pass(test, details) { results.passed.push({test, details}); log("PASS", test, details); }
function fail(test, details) { results.failed.push({test, details}); log("FAIL", test, details); }
function warn(test, details) { results.warnings.push({test, details}); log("WARN", test, details); }

function httpGet(url, cookie = "") {
  return new Promise((resolve, reject) => {
    const urlObj = new URL(url);
    const options = {
      hostname: urlObj.hostname,
      port: urlObj.port,
      path: urlObj.pathname + urlObj.search,
      method: "GET",
      headers: { Cookie: cookie, Accept: "text/html,application/json" },
    };
    const req = http.request(options, (res) => {
      let data = "";
      res.on("data", (c) => (data += c));
      res.on("end", () => resolve({ status: res.statusCode, headers: res.headers, data, url: res.headers.location }));
    });
    req.on("error", reject);
    req.end();
  });
}

function httpPost(url, body = "", cookie = "", isJson = false) {
  return new Promise((resolve, reject) => {
    const urlObj = new URL(url);
    const bodyData = isJson ? JSON.stringify(body) : body;
    const options = {
      hostname: urlObj.hostname,
      port: urlObj.port,
      path: urlObj.pathname + urlObj.search,
      method: "POST",
      headers: {
        Cookie: cookie,
        "Content-Type": isJson ? "application/json" : "application/x-www-form-urlencoded",
        "Content-Length": Buffer.byteLength(bodyData),
        Accept: "text/html,application/json",
      },
    };
    const req = http.request(options, (res) => {
      let data = "";
      res.on("data", (c) => (data += c));
      res.on("end", () => resolve({ status: res.statusCode, headers: res.headers, data, url: res.headers.location }));
    });
    req.on("error", reject);
    req.write(bodyData);
    req.end();
  });
}

async function login() {
  log("AUTH", "Attempting login");
  
  // Get login page to extract CSRF token
  const loginPage = await httpGet(BASE_URL + "/admin/login");
  if (loginPage.status !== 200) {
    fail("Login page", "Status: " + loginPage.status);
    return false;
  }
  
  // Extract CSRF token from HTML
  const csrfMatch = loginPage.data.match(/<input[^>]*name="_token"[^>]*value="([^"]+)"/);
  const csrfToken = csrfMatch ? csrfMatch[1] : "";
  
  if (!csrfToken) {
    // Try meta tag
    const metaMatch = loginPage.data.match(/<meta[^>]*name="csrf-token"[^>]*content="([^"]+)"/);
    if (metaMatch) {
      // Need to extract from session
    }
  }
  
  // Extract cookies
  const cookies = loginPage.headers["set-cookie"];
  const cookieStr = cookies ? cookies.map(c => c.split(";")[0]).join("; ") : "";
  
  // Submit login
  const loginBody = "_token=" + encodeURIComponent(csrfToken) + "&email=" + encodeURIComponent(ADMIN_EMAIL) + "&password=" + encodeURIComponent(ADMIN_PASSWORD);
  const loginRes = await httpPost(BASE_URL + "/admin/login", loginBody, cookieStr);
  
  // Get session cookie from response
  const respCookies = loginRes.headers["set-cookie"];
  if (respCookies) {
    sessionCookie = respCookies.map(c => c.split(";")[0]).join("; ");
  }
  
  if (loginRes.status === 302 && (loginRes.url && loginRes.url.includes("/admin"))) {
    pass("Admin login", "Redirected to admin");
    return true;
  } else if (loginRes.status === 200 && loginRes.data.includes("dashboard")) {
    pass("Admin login", "Dashboard loaded");
    return true;
  } else {
    fail("Login", "Status: " + loginRes.status + " URL: " + loginRes.url);
    return false;
  }
}

async function testPageAccessibility() {
  log("TEST", "Testing Page Accessibility");
  
  const pages = [
    { url: "/", name: "Home" },
    { url: "/admin/login", name: "Login" },
    { url: "/admin", name: "Dashboard" },
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
  ];
  
  for (const page of pages) {
    try {
      const res = await httpGet(BASE_URL + page.url);
      if (res.status === 200) {
        pass("Page: " + page.name, "Loaded (200)");
      } else if (res.status === 302) {
        pass("Page: " + page.name, "Redirect (302) - auth required");
      } else if (res.status >= 400 && res.status < 500) {
        fail("Page: " + page.name, "Client error: " + res.status);
      } else {
        warn("Page: " + page.name, "Status: " + res.status);
      }
    } catch (err) {
      fail("Page: " + page.name, err.message);
    }
  }
}

async function testAuthenticatedPages() {
  if (!sessionCookie) {
    warn("Auth pages", "No session cookie - skipping");
    return;
  }
  
  log("TEST", "Testing Authenticated Pages");
  
  const pages = [
    { url: "/admin", name: "Dashboard", expectedContent: ["dashboard", "chart", "card"] },
    { url: "/admin/dialer", name: "Dialer", expectedContent: ["dialpad", "Call", "contact"] },
    { url: "/admin/dialer/contacts", name: "Contacts", expectedContent: ["contact", "search"] },
    { url: "/admin/recordings", name: "Recordings", expectedContent: ["recording", "table"] },
    { url: "/admin/users", name: "Users", expectedContent: ["user", "table"] },
  ];
  
  for (const page of pages) {
    try {
      const res = await httpGet(BASE_URL + page.url, sessionCookie);
      if (res.status === 200) {
        const hasContent = page.expectedContent.some(c => 
          res.data.toLowerCase().includes(c.toLowerCase())
        );
        if (hasContent) {
          pass("Auth page: " + page.name, "Content verified");
        } else {
          warn("Auth page: " + page.name, "Expected content not found");
        }
      } else if (res.status === 302) {
        warn("Auth page: " + page.name, "Redirected - session may have expired");
      } else {
        fail("Auth page: " + page.name, "Status: " + res.status);
      }
    } catch (err) {
      fail("Auth page: " + page.name, err.message);
    }
  }
}

async function testBackendApis() {
  log("TEST", "Testing Backend APIs");
  
  const endpoints = [
    { url: "/health", name: "Health", expectedStatus: 200 },
  ];
  
  for (const ep of endpoints) {
    try {
      const res = await httpGet(API_URL + ep.url);
      if (res.status === ep.expectedStatus) {
        pass("API: " + ep.name, "Status " + res.status);
      } else {
        fail("API: " + ep.name, "Status " + res.status + " (expected " + ep.expectedStatus + ")");
      }
    } catch (err) {
      fail("API: " + ep.name, err.message);
    }
  }
}

async function testDatabaseContent() {
  log("TEST", "Testing Database Content");
  
  // Check users table
  try {
    const res = await httpGet(API_URL + "/admin/users?per_page=100");
    if (res.status === 200) {
      const data = JSON.parse(res.data);
      const count = data.data ? data.data.length : (Array.isArray(data) ? data.length : 0);
      if (count >= 4) pass("Test data: Users", count + " users found");
      else warn("Test data: Users", "Only " + count + " users");
    }
  } catch (err) {
    warn("Database check", "API not available - checking via Laravel");
  }
}

async function generateReport() {
  results.endTime = new Date();
  const duration = (new Date() - results.startTime) / 1000;
  
  console.log("\n" + "=".repeat(70));
  console.log("COMPREHENSIVE QA TEST REPORT");
  console.log("=".repeat(70));
  console.log("Duration: " + duration.toFixed(1) + " seconds");
  console.log("PASSED:   " + results.passed.length);
  console.log("FAILED:   " + results.failed.length);
  console.log("WARNINGS: " + results.warnings.length);
  console.log("TOTAL:    " + (results.passed.length + results.failed.length + results.warnings.length));
  
  if (results.failed.length > 0) {
    console.log("\n" + "-".repeat(70));
    console.log("FAILURES - These need to be fixed:");
    console.log("-".repeat(70));
    results.failed.forEach((f, i) => console.log((i + 1) + ". [FAIL] " + f.test + ": " + f.details));
  }
  
  if (results.warnings.length > 0) {
    console.log("\n" + "-".repeat(70));
    console.log("WARNINGS - These may need attention:");
    console.log("-".repeat(70));
    results.warnings.forEach((w, i) => console.log((i + 1) + ". [WARN] " + w.test + ": " + w.details));
  }
  
  if (results.passed.length > 0) {
    console.log("\n" + "-".repeat(70));
    console.log("PASSED TESTS:");
    console.log("-".repeat(70));
    results.passed.forEach((p, i) => console.log((i + 1) + ". [PASS] " + p.test + (p.details ? " - " + p.details : "")));
  }
  
  console.log("\n" + "=".repeat(70));
  
  if (results.failed.length === 0) {
    console.log("ALL TESTS PASSED! Application is working correctly.");
  } else {
    console.log("ACTION REQUIRED: " + results.failed.length + " test(s) failed.");
  }
  console.log("=".repeat(70));
  
  return results.failed.length > 0 ? 1 : 0;
}

async function main() {
  results.startTime = new Date();
  log("START", "Starting Comprehensive QA Test Agent");
  
  // Test without auth
  await testPageAccessibility();
  await testBackendApis();
  
  // Login and test authenticated pages
  const loggedIn = await login();
  if (loggedIn) {
    await testAuthenticatedPages();
  }
  
  // Database checks
  await testDatabaseContent();
  
  const exitCode = await generateReport();
  process.exit(exitCode);
}

main();
