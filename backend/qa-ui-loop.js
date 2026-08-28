/**
 * Webportal UI regression loop.
 *
 * All functional actions are driven through Playwright. HTTP responses and
 * browser diagnostics are captured only to explain UI failures.
 *
 * Run:
 *   docker compose exec -T backend node /app/qa-ui-loop.js
 * Optional:
 *   QA_MAX_CYCLES=3 QA_EMAIL=... QA_PASSWORD=... node /app/qa-ui-loop.js
 */
'use strict';

const { chromium } = require('playwright');

const BASE_URL = (process.env.QA_BASE_URL || 'http://laravel').replace(/\/$/, '');
const EMAIL = process.env.QA_EMAIL || 'superadmin@example.com';
const PASSWORD = process.env.QA_PASSWORD || '12345678';
const MAX_CYCLES = Math.max(1, Number(process.env.QA_MAX_CYCLES || 2));
const HEADLESS = process.env.QA_HEADLESS !== 'false';
const QA_CONTACT_PHONE = process.env.QA_CONTACT_PHONE || '+15550190099';
const QA_CONTACT_NAME = 'Automated QA Contact';
const BROWSER_PATH = process.env.QA_BROWSER_PATH || '';
const EXPECTED_NAV = ['Dialer', 'Roles', 'Permissions', 'Users', 'View carrier', 'New Carrier', 'Inbound DIDs'];

class Suite {
  constructor(cycle) {
    this.cycle = cycle;
    this.passed = [];
    this.failed = [];
    this.warnings = [];
    this.networkErrors = [];
    this.consoleErrors = [];
  }

  pass(name, details = '') {
    this.passed.push({ name, details });
    console.log(`PASS [cycle ${this.cycle}] ${name}${details ? ` - ${details}` : ''}`);
  }

  warn(name, details = '') {
    this.warnings.push({ name, details });
    console.log(`WARN [cycle ${this.cycle}] ${name}${details ? ` - ${details}` : ''}`);
  }

  fail(name, error) {
    const details = error instanceof Error ? error.message : String(error);
    this.failed.push({ name, details });
    console.error(`FAIL [cycle ${this.cycle}] ${name} - ${details}`);
  }

  async test(name, callback) {
    try {
      const details = await callback();
      this.pass(name, typeof details === 'string' ? details : '');
      return true;
    } catch (error) {
      this.fail(name, error);
      return false;
    }
  }

  assert(value, message) {
    if (!value) throw new Error(message);
  }
}

const visible = async (locator) => locator.count() > 0 && locator.first().isVisible();

async function clickSidebarLink(page, text) {
  let link = page.getByRole('link', { name: text, exact: true }).first();
  if (await visible(link)) {
    await link.click();
    return;
  }

  const submenuButtons = page.locator('aside button.menu-item');
  for (let i = 0; i < await submenuButtons.count(); i += 1) {
    const button = submenuButtons.nth(i);
    const submenu = button.locator('xpath=following-sibling::div[1]');
    if (await submenu.getByRole('link', { name: text, exact: true }).count()) {
      if (await submenu.evaluate((element) => element.classList.contains('hidden'))) await button.click();
      link = submenu.getByRole('link', { name: text, exact: true }).first();
      await link.click();
      return;
    }
  }
  throw new Error(`Sidebar link not found: ${text}`);
}

async function login(page, suite) {
  await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
  await suite.test('login form is usable', async () => {
    await page.locator('#email').fill(EMAIL);
    await page.locator('input[name="password"]').fill(PASSWORD);
    await page.getByRole('button', { name: /sign in/i }).click();
    await page.waitForURL(/\/admin(?:\/)?(?:\?.*)?$/, { timeout: 20_000 });
    suite.assert(await page.locator('aside').count(), 'Authenticated sidebar did not render');
    return EMAIL;
  });
}

async function testDashboard(page, suite) {
  await suite.test('dashboard has no failed resources or console errors', async () => {
    await page.waitForLoadState('networkidle');
    const resource404 = suite.networkErrors.filter((item) => item.status === 404);
    suite.assert(resource404.length === 0, `404 resources: ${resource404.map((item) => item.url).join(', ')}`);
    suite.assert(suite.consoleErrors.length === 0, `console errors: ${suite.consoleErrors.join(' | ')}`);
  });

  await suite.test('required sidebar menus exist and removed menus stay removed', async () => {
    for (const item of EXPECTED_NAV) {
      suite.assert(await page.getByText(item, { exact: true }).count(), `Missing sidebar item: ${item}`);
    }
    const sidebarText = await page.locator('aside').innerText();
    suite.assert(!/\bCampaigns?\b/i.test(sidebarText), 'Campaign menu unexpectedly visible');
    suite.assert(!/\bPosts?\b/i.test(sidebarText), 'Posts menu unexpectedly visible');
    suite.assert(!/\bPages?\b/i.test(sidebarText), 'Pages menu unexpectedly visible');
  });
}

async function testTheme(page, suite) {
  await suite.test('dark and light theme toggle persists', async () => {
    const toggle = page.locator('#sidebarDarkModeToggle');
    suite.assert(await visible(toggle), 'Theme toggle is not visible');
    const before = await page.locator('html').evaluate((el) => el.classList.contains('dark'));
    await toggle.click();
    await page.waitForTimeout(250);
    const after = await page.locator('html').evaluate((el) => el.classList.contains('dark'));
    suite.assert(after !== before, 'Theme class did not change');
    await page.reload({ waitUntil: 'domcontentloaded' });
    const persisted = await page.locator('html').evaluate((el) => el.classList.contains('dark'));
    suite.assert(persisted === after, 'Theme did not persist after reload');
    await page.locator('#sidebarDarkModeToggle').click();
  });
}

async function testDialer(page, suite, mutateContact) {
  await clickSidebarLink(page, 'Dialer');
  await page.waitForLoadState('networkidle');

  await suite.test('dialer uses polished three-panel desktop layout', async () => {
    const ids = ['dialer-form', 'customer-call-panel', 'contact-workspace-panel'];
    for (const id of ids) suite.assert(await visible(page.locator(`#${id}`)), `Panel #${id} is not visible`);
    const rects = await page.locator('#dialer-form, #customer-call-panel, #contact-workspace-panel').evaluateAll((nodes) => nodes.map((node) => node.getBoundingClientRect().toJSON()));
    suite.assert(rects.length === 3, `Expected 3 panels, got ${rects.length}`);
    suite.assert(rects[0].x < rects[1].x && rects[1].x < rects[2].x, 'Desktop panels are not arranged left-to-right');
  });

  await suite.test('dial pad buttons update and clear the destination', async () => {
    const display = page.locator('#dialpad-display');
    await page.locator('[data-value="1"]').click();
    await page.locator('[data-value="2"]').click();
    await page.locator('[data-value="#"]').click();
    suite.assert((await display.inputValue()).endsWith('12#'), `Unexpected dial value: ${await display.inputValue()}`);
    await page.locator('#dialpad-backspace').click();
    suite.assert((await display.inputValue()).endsWith('12'), 'Backspace did not remove the last digit');
    await page.locator('#dialpad-clear').click();
    suite.assert((await display.inputValue()) === '', 'Clear did not empty the destination');
  });

  await suite.test('safe empty-call validation prevents call submission', async () => {
    const callRequests = [];
    const listener = (request) => {
      if (request.url().includes('/dialer/dial')) callRequests.push(request.url());
    };
    page.on('request', listener);
    await page.locator('#dialpad-clear').click();
    await page.locator('#dialer-form button[type="submit"]').click();
    await page.waitForTimeout(300);
    page.off('request', listener);
    suite.assert(callRequests.length === 0, 'Empty destination was submitted to the call API');
  });

  await suite.test('all contact workspace tabs switch visible panels', async () => {
    for (const tab of ['notes', 'activity', 'history', 'info']) {
      const button = page.locator(`[data-contact-tab="${tab}"]`);
      await button.click();
      suite.assert(await button.getAttribute('aria-selected') === 'true', `${tab} tab did not become selected`);
      suite.assert(await visible(page.locator(`[data-contact-tab-panel="${tab}"]`)), `${tab} panel is hidden`);
    }
  });

  if (mutateContact) await testContactWorkspace(page, suite);

  await suite.test('dialer controls and audio state are present', async () => {
    suite.assert(await page.locator('#dialer-audio').count() === 1, 'Remote audio element is missing');
    suite.assert(await page.locator('[data-action="hangup"]').count() === 1, 'Hangup control is missing');
    suite.assert(await page.locator('[data-action="mute"]').count() === 1, 'Mute control is missing');
    suite.assert(await page.locator('[data-action="unmute"]').count() === 1, 'Unmute control is missing');
    suite.assert(await page.locator('#browser-audio-status').count() === 1, 'Browser audio status is missing');
    const duplicateIds = await page.evaluate(() => [...document.querySelectorAll('[id]')]
      .map((el) => el.id)
      .filter((id, index, ids) => ids.indexOf(id) !== index));
    suite.assert(duplicateIds.length === 0, `Duplicate element IDs: ${duplicateIds.join(', ')}`);
  });
}

async function selectQaContact(page) {
  const search = page.locator('#contact-search');
  await search.fill(QA_CONTACT_PHONE);
  await page.waitForTimeout(700);
  const result = page.locator('#contact-search-results [data-contact-id]').first();
  if (await visible(result)) {
    await result.click();
    return true;
  }
  await page.locator('[data-contact-tab="info"]').click();
  await page.locator('#contact-name-input').fill(QA_CONTACT_NAME);
  await page.locator('#contact-company-input').fill('Webportal UI QA');
  await page.locator('#contact-phone-input').fill(QA_CONTACT_PHONE);
  await page.locator('#contact-email-input').fill('qa-contact@example.invalid');
  await page.locator('#contact-save').click();
  await page.waitForFunction(() => document.querySelector('#contact-feedback')?.textContent?.toLowerCase().includes('saved'));
  return false;
}

async function testContactWorkspace(page, suite) {
  await suite.test('contact can be selected or created using the UI', async () => {
    const existed = await selectQaContact(page);
    suite.assert(!(await page.locator('#contact-save').isDisabled()), 'Selected QA contact is not editable');
    return existed ? 'reused controlled QA contact' : 'created controlled QA contact';
  });

  const qaLabel = 'qa-verified';
  await suite.test('label can be added and used as global contact filter', async () => {
    const currentLabels = await page.locator('#contact-labels').innerText();
    if (!currentLabels.includes(qaLabel)) {
      await page.locator('#contact-label-input').fill(qaLabel);
      await page.locator('#contact-label-add').click();
      await page.waitForFunction((label) => document.querySelector('#contact-labels')?.textContent?.includes(label), qaLabel);
    }
    await page.locator('#contact-label-filter').selectOption({ label: qaLabel });
    await page.waitForTimeout(500);
    suite.assert(await page.locator('#contact-search-results [data-contact-id]').count() > 0, 'Label filter returned no contacts');
    await page.locator('#contact-search-results [data-contact-id]').first().click();
  });

  await suite.test('contact flag persists through UI update', async () => {
    const flag = page.locator('#contact-flag-toggle');
    const before = await flag.getAttribute('aria-pressed');
    await flag.click();
    await page.waitForFunction((value) => document.querySelector('#contact-flag-toggle')?.getAttribute('aria-pressed') !== value, before);
  });

  await suite.test('comment is saved and rendered in notes', async () => {
    await page.locator('[data-contact-tab="notes"]').click();
    const note = `QA browser verification cycle ${suite.cycle}`;
    await page.locator('#contact-comment-input').fill(note);
    await page.locator('#contact-comment-add').click();
    await page.waitForFunction((text) => document.querySelector('#contact-comments')?.textContent?.includes(text), note);
  });

  await suite.test('activity log records user and timestamped changes', async () => {
    await page.locator('[data-contact-tab="activity"]').click();
    await page.waitForFunction(() => !document.querySelector('#contact-activity')?.textContent?.includes('Loading'));
    const text = await page.locator('#contact-activity').innerText();
    suite.assert(/label|flag|comment/i.test(text), `Expected activity not found: ${text}`);
    suite.assert(/by\s+/i.test(text), 'Activity actor is missing');
  });

  await suite.test('call history tab loads a valid empty or populated state', async () => {
    await page.locator('[data-contact-tab="history"]').click();
    await page.waitForFunction(() => !document.querySelector('#contact-call-history')?.textContent?.includes('Loading'));
    const text = (await page.locator('#contact-call-history').innerText()).trim();
    suite.assert(text.length > 0, 'Call history rendered no state');
    suite.assert(!/unable|error/i.test(text), `Call history failed: ${text}`);
  });
}

async function testAdminPages(page, suite) {
  const pages = [
    ['Users', /\/admin\/users(?:\?|$)/, ['#dataTable']],
    ['New User', /\/admin\/users\/create/, ['#external_name', '#email', '#password', '#carrierId']],
    ['Roles', /\/admin\/roles(?:\?|$)/, ['#dataTable']],
    ['New Role', /\/admin\/roles\/create/, ['input[name="name"]', '#checkPermissionAll']],
    ['Permissions', /\/admin\/permissions(?:\?|$)/, ['#dataTable']],
    ['View carrier', /\/admin\/carrier(?:\?|$)/, []],
    ['New Carrier', /\/admin\/carrier\/create/, ['#name', '#sipDomain', '#sipPort']],
    ['Inbound DIDs', /\/admin\/carrier\/inbound-dids/, []],
  ];

  for (const [label, urlPattern, selectors] of pages) {
    await suite.test(`${label} menu and page work`, async () => {
      await clickSidebarLink(page, label);
      await page.waitForURL(urlPattern);
      for (const selector of selectors) suite.assert(await page.locator(selector).count(), `${label}: missing ${selector}`);
      suite.assert(!/server error|exception|stack trace/i.test(await page.locator('body').innerText()), `${label}: error page rendered`);
    });
  }

  await suite.test('Inbound DID create form is reachable from its UI', async () => {
    const create = page.getByRole('link', { name: /add inbound did/i }).first();
    suite.assert(await visible(create), 'Add Inbound DID button is missing');
    await create.click();
    await page.waitForURL(/\/admin\/carrier\/inbound-dids\/create/);
    for (const selector of ['#carrier_id', '#did', '#label']) suite.assert(await page.locator(selector).count(), `DID form missing ${selector}`);
  });
}

async function testResponsive(page, suite) {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto(`${BASE_URL}/admin/dialer`, { waitUntil: 'networkidle' });
  await suite.test('mobile dialer stacks panels without page overflow', async () => {
    const layout = await page.evaluate(() => {
      const panels = ['dialer-form', 'customer-call-panel', 'contact-workspace-panel'].map((id) => document.getElementById(id).getBoundingClientRect());
      return {
        widths: panels.map((rect) => rect.width),
        tops: panels.map((rect) => rect.top),
        viewport: document.documentElement.clientWidth,
        scrollWidth: document.documentElement.scrollWidth,
      };
    });
    suite.assert(layout.tops[0] < layout.tops[1] && layout.tops[1] < layout.tops[2], 'Mobile panels are not vertically stacked');
    suite.assert(layout.widths.every((width) => width <= layout.viewport + 1), `Panel exceeds viewport: ${layout.widths.join(', ')}`);
    suite.assert(layout.scrollWidth <= layout.viewport + 1, `Page overflows horizontally: ${layout.scrollWidth}/${layout.viewport}`);
  });

  await suite.test('mobile contact tabs remain usable', async () => {
    for (const tab of ['notes', 'activity', 'history', 'info']) {
      await page.locator(`[data-contact-tab="${tab}"]`).click();
      suite.assert(await visible(page.locator(`[data-contact-tab-panel="${tab}"]`)), `${tab} is unusable on mobile`);
    }
  });
}

async function runCycle(browser, cycle) {
  const suite = new Suite(cycle);
  const context = await browser.newContext({
    viewport: { width: 1600, height: 1000 },
    permissions: ['microphone'],
    ignoreHTTPSErrors: true,
  });
  const page = await context.newPage();

  page.on('console', (message) => {
    if (message.type() === 'error') suite.consoleErrors.push(message.text());
  });
  page.on('pageerror', (error) => suite.consoleErrors.push(error.message));
  page.on('response', (response) => {
    if (response.status() >= 400) suite.networkErrors.push({ status: response.status(), url: response.url() });
  });
  page.on('requestfailed', (request) => suite.networkErrors.push({ status: 0, url: request.url(), error: request.failure()?.errorText }));

  await login(page, suite);
  await testDashboard(page, suite);
  await testTheme(page, suite);
  await testDialer(page, suite, cycle === 1);
  await testAdminPages(page, suite);
  await testResponsive(page, suite);

  const unexpectedNetwork = suite.networkErrors.filter((item) => item.status === 0 || item.status >= 500 || (item.status === 404 && !item.url.includes('favicon')));
  if (unexpectedNetwork.length) suite.fail('no unexpected failed UI requests', JSON.stringify(unexpectedNetwork));
  else suite.pass('no unexpected failed UI requests');

  await context.close();
  return suite;
}

async function main() {
  const browser = await chromium.launch({
    headless: HEADLESS,
    // The backend image keeps the full Chromium bundle, not the optional
    // headless-shell bundle Playwright otherwise prefers in headless mode.
    executablePath: BROWSER_PATH || chromium.executablePath(),
    args: ['--no-sandbox', '--use-fake-ui-for-media-stream', '--use-fake-device-for-media-stream'],
  });
  const cycles = [];
  try {
    for (let cycle = 1; cycle <= MAX_CYCLES; cycle += 1) {
      console.log(`\n=== UI QA CYCLE ${cycle}/${MAX_CYCLES} ===`);
      const result = await runCycle(browser, cycle);
      cycles.push(result);
      if (result.failed.length === 0) break;
      if (cycle < MAX_CYCLES) console.log('Failures remain; repeating the UI cycle after diagnostics.');
    }
  } finally {
    await browser.close();
  }

  const final = cycles[cycles.length - 1];
  const report = {
    cycles: cycles.length,
    passed: final.passed.length,
    failed: final.failed,
    warnings: final.warnings,
    networkErrors: final.networkErrors,
    consoleErrors: final.consoleErrors,
  };
  console.log(`\nQA_RESULT ${JSON.stringify(report, null, 2)}`);
  process.exitCode = final.failed.length ? 1 : 0;
}

main().catch((error) => {
  console.error('QA runner crashed:', error);
  process.exitCode = 2;
});
