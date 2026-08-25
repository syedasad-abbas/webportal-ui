/**
 * QA Testing Agent for Webportal UI
 * Tests all UI elements, identifies bugs, and generates reports
 */
const { chromium } = require("playwright");
const http = require("http");

const BASE_URL = process.env.BASE_URL || "http://laravel";
const API_URL = process.env.API_URL || "http://localhost:4000";
const ADMIN_EMAIL = "admin@webphone.local";
const ADMIN_PASSWORD = "AdminPass123!";

const results = {
  passed: [],
  failed: [],
  warnings: [],
  startTime: new Date(),
  endTime: null,
};

function log(emoji, message, details = "") {
  const timestamp = new Date().toISOString().split("T")[1].split(".")[0];
  console.log(emoji + " [" + timestamp + "] " + message + (details ? " - " + details : ""));
}

function pass(test, details = "") {
  results.passed.push({ test, details });
  log("PASS", test, details);
}

function fail(test, details = "") {
  results.failed.push({ test, details });
  log("FAIL", test, details);
}

function warn(test, details = "") {
  results.warnings.push({ test, details });
  log("WARN", test, details);
}

// API helper for log tracing
function apiGet(path) {
  return new Promise((resolve, reject) => {
    http.get(API_URL + path, (res) => {
      let data = "";
      res.on("data", (chunk) => (data += chunk));
      res.on("end", () => {
        try {
          resolve({ status: res.statusCode, data: JSON.parse(data) });
        } catch (e) {
          resolve({ status: res.statusCode, data });
        }
      });
    }).on("error", reject);
  });
}

async function login(page) {
  log("AUTH", "Logging in as admin");
  await page.goto(BASE_URL + "/admin/login");
  await page.waitForSelector('input[name="email"]', { timeout: 10000 });
  await page.fill('input[name="email"]', ADMIN_EMAIL);
  await page.fill('input[name="password"]', ADMIN_PASSWORD);
  await page.click('button[type="submit"]');
  await page.waitForURL("**/admin/**", { timeout: 10000 });
  pass("Admin login");
}

async function testDashboard(page) {
  log("TEST", "Testing Dashboard");
  await page.goto(BASE_URL + "/admin");
  await page.waitForLoadState("networkidle");
  
  const cards = await page.locator(".card, .bg-white.rounded").count();
  if (cards > 0) pass("Dashboard stat cards visible", "Found " + cards + " cards");
  else fail("Dashboard stat cards", "No cards found");
  
  const charts = await page.locator("canvas").count();
  if (charts > 0) pass("Dashboard charts rendered", "Found " + charts + " charts");
  else warn("Dashboard charts", "No canvas elements found");
}

async function testSidebarMenu(page) {
  log("TEST", "Testing Sidebar Menu");
  
  const menuItems = [
    { name: "Dashboard", url: "/admin" },
    { name: "Dialer", url: "/admin/dialer" },
    { name: "Recordings", url: "/admin/recordings" },
    { name: "Leads", url: "/admin/leads" },
    { name: "Roles", url: "/admin/roles" },
    { name: "Users", url: "/admin/users" },
    { name: "Settings", url: "/admin/settings" },
  ];
  
  for (const item of menuItems) {
    try {
      await page.goto(BASE_URL + item.url);
      await page.waitForLoadState("networkidle", { timeout: 5000 });
      const title = await page.title();
      if (title.includes("Error") || title.includes("404")) {
        fail("Menu: " + item.name, "Page error: " + title);
      } else {
        pass("Menu: " + item.name, "Loaded successfully");
      }
    } catch (err) {
      fail("Menu: " + item.name, err.message);
    }
  }
}

async function testDialer(page) {
  log("TEST", "Testing Dialer");
  await page.goto(BASE_URL + "/admin/dialer");
  await page.waitForLoadState("networkidle");
  
  const dialpad = await page.locator("[data-value]").count();
  if (dialpad > 0) pass("Dialpad keys", "Found " + dialpad + " keys");
  else fail("Dialpad keys", "No dialpad keys found");
  
  const callBtn = await page.locator("text=Call, button:has-text('Call')").count();
  if (callBtn > 0) pass("Call button visible");
  else fail("Call button", "Call button not found");
  
  try {
    await page.click('[data-value="1"]');
    await page.click('[data-value="2"]');
    await page.click('[data-value="3"]');
    const display = await page.locator("#dialer-display, input[name='destination']").first().inputValue();
    if (display.includes("123")) pass("Dialpad input", "Display shows: " + display);
    else warn("Dialpad input", "Display shows: " + display);
  } catch (err) {
    fail("Dialpad input", err.message);
  }
  
  const contacts = await page.locator(".contact-item, [data-contact]").count();
  pass("Contacts section", "Found " + contacts + " contacts");
  
  const tabs = ["Notes & Comments", "Activity Log", "Call History", "Contact Info"];
  for (const tab of tabs) {
    const tabBtn = await page.locator("text=" + tab).count();
    if (tabBtn > 0) pass("Contact tab: " + tab);
    else warn("Contact tab: " + tab, "Tab not found");
  }
}

async function testContacts(page) {
  log("TEST", "Testing Contacts");
  await page.goto(BASE_URL + "/admin/dialer/contacts");
  await page.waitForLoadState("networkidle");
  
  const contacts = await page.locator("table tbody tr, .contact-card").count();
  if (contacts > 0) pass("Contact list", "Found " + contacts + " contacts");
  else fail("Contact list", "No contacts found");
}

async function testCallLogs(page) {
  log("TEST", "Testing Call Logs via API");
  
  try {
    const response = await apiGet("/calls");
    if (response.status === 200) {
      const count = Array.isArray(response.data) ? response.data.length : (response.data && response.data.data ? response.data.data.length : 0);
      pass("Call logs API", "Found " + count + " call records");
    } else {
      fail("Call logs API", "Status: " + response.status);
    }
  } catch (err) {
    fail("Call logs API", err.message);
  }
}

async function testRecordings(page) {
  log("TEST", "Testing Recordings");
  await page.goto(BASE_URL + "/admin/recordings");
  await page.waitForLoadState("networkidle");
  
  const rows = await page.locator("table tbody tr").count();
  pass("Recordings page", "Found " + rows + " recordings");
}

async function testUsers(page) {
  log("TEST", "Testing Users");
  await page.goto(BASE_URL + "/admin/users");
  await page.waitForLoadState("networkidle");
  
  const rows = await page.locator("table tbody tr").count();
  if (rows > 0) pass("User list", "Found " + rows + " users");
  else fail("User list", "No users found");
}

async function testCarriers(page) {
  log("TEST", "Testing Carriers");
  await page.goto(BASE_URL + "/admin/carrier");
  await page.waitForLoadState("networkidle");
  
  const rows = await page.locator("table tbody tr").count();
  if (rows > 0) pass("Carrier list", "Found " + rows + " carriers");
  else warn("Carrier list", "No carriers found");
}

async function testSettings(page) {
  log("TEST", "Testing Settings");
  await page.goto(BASE_URL + "/admin/settings");
  await page.waitForLoadState("networkidle");
  
  const tabs = ["General", "Appearance", "Content", "Integrations"];
  for (const tab of tabs) {
    const tabEl = await page.locator("text=" + tab).count();
    if (tabEl > 0) pass("Settings tab: " + tab);
    else warn("Settings tab: " + tab, "Tab not found");
  }
}

async function testApiHealth() {
  log("TEST", "Testing API Health");
  
  const endpoints = [
    { path: "/health", name: "Backend Health" },
  ];
  
  for (const ep of endpoints) {
    try {
      const response = await apiGet(ep.path);
      if (response.status >= 200 && response.status < 400) {
        pass("API: " + ep.name, "Status " + response.status);
      } else {
        fail("API: " + ep.name, "Status " + response.status);
      }
    } catch (err) {
      fail("API: " + ep.name, err.message);
    }
  }
}

async function generateReport() {
  results.endTime = new Date();
  const duration = (results.endTime - results.startTime) / 1000;
  
  console.log("\n" + "=".repeat(60));
  console.log("QA TEST REPORT");
  console.log("=".repeat(60));
  console.log("Duration: " + duration + "s");
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
  
  const browser = await chromium.launch({
    headless: true,
    args: ["--no-sandbox", "--disable-setuid-sandbox"],
  });
  
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 },
    ignoreHTTPSErrors: true,
  });
  
  const page = await context.newPage();
  
  try {
    await login(page);
    await testDashboard(page);
    await testSidebarMenu(page);
    await testDialer(page);
    await testContacts(page);
    await testCallLogs(page);
    await testRecordings(page);
    await testUsers(page);
    await testCarriers(page);
    await testSettings(page);
    await testApiHealth();
  } catch (err) {
    log("ERROR", "Fatal error", err.message);
  } finally {
    await browser.close();
  }
  
  const exitCode = await generateReport();
  process.exit(exitCode);
}

main();
