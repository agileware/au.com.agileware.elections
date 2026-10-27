import { test as base, expect, type Browser, type BrowserContext, type Page } from '@playwright/test';
import users from './test-users.json';
import { personaUrl } from './personas';

/** PHP and template output that must never reach a visitor. */
const PHP_OUTPUT = /(?:Warning|Notice|Deprecated|Fatal error|Parse error)\s*:|Undefined (?:array key|variable|index|property)|Smarty/;

/** Console/network noise that is not the extension's. */
const IGNORED_CONSOLE = [/favicon/i, /Failed to load resource: the server responded with a status of 404/];

type Guard = { name: string; problems: string[] };

/**
 * Watches a page for the things that signal a functional error: uncaught exceptions,
 * console errors and failed requests to election AJAX endpoints. Checked at the end of
 * every test.
 */
function watch(page: Page, name: string, guards: Guard[]) {
  const guard: Guard = { name, problems: [] };
  guards.push(guard);
  page.on('pageerror', (e) => guard.problems.push(`page error: ${e.message}`));
  page.on('console', (m) => {
    if (m.type() === 'error' && !IGNORED_CONSOLE.some((r) => r.test(m.text()))) {
      guard.problems.push(`console error: ${m.text()}`);
    }
  });
  page.on('response', (r) => {
    if (/Election/i.test(r.url()) && r.status() >= 400) {
      guard.problems.push(`HTTP ${r.status()} for ${r.url()}`);
    }
  });
}

/** Fails if PHP warnings, notices or template errors are in the visible page. */
export async function expectNoPhpOutput(page: Page) {
  const text = await page.locator('body').innerText();
  const match = text.match(PHP_OUTPUT);
  expect(match, `PHP output on the page ${page.url()}: ${match ? text.slice(Math.max(0, match.index! - 60), match.index! + 200) : ''}`).toBeNull();
}

async function login(page: Page, username: string, password: string) {
  await page.goto('/wp-login.php');
  await page.locator('#user_login').fill(username);
  await page.locator('#user_pass').fill(password);
  await page.locator('#wp-submit').click();
  await page.waitForURL(/wp-admin/);
}

type Fixtures = {
  /** A page for the WordPress administrator. */
  adminPage: Page;
  /** A page for a user with the "administer Elections" permission only. */
  managerPage: Page;
  /** A page for a logged in member with "view Elections". */
  memberPage: Page;
  /** Open a persona (by personal link) in its own browser context. */
  persona: (name: string) => Promise<Page>;
  /** Open a persona's page for a CiviCRM path and check it for PHP output. */
  personaGoto: (name: string, civiPath: string, params?: Record<string, string | number>) => Promise<Page>;
  guards: Guard[];
  /** Problems a test expects (for example the 403 of a refused page): push a pattern to allow it. */
  allowedProblems: RegExp[];
};

export const test = base.extend<Fixtures>({
  allowedProblems: async ({}, use) => {
    await use([]);
  },

  guards: [
    async ({ allowedProblems }, use) => {
      const guards: Guard[] = [];
      await use(guards);
      const problems = guards
        .flatMap((g) => g.problems.map((p) => `[${g.name}] ${p}`))
        .filter((p) => !allowedProblems.some((r) => r.test(p)));
      expect(problems, 'functional errors seen in the browser').toEqual([]);
    },
    { auto: true },
  ],

  adminPage: async ({ browser, guards }, use) => {
    const context = await browser.newContext();
    const page = await context.newPage();
    watch(page, 'admin', guards);
    await login(page, process.env.WP_ADMIN_USER || 'admin', process.env.WP_ADMIN_PASS || 'admin');
    await use(page);
    await context.close();
  },

  managerPage: async ({ browser, guards }, use) => {
    const context = await browser.newContext();
    const page = await context.newPage();
    watch(page, 'manager', guards);
    await login(page, users.manager.username, users.manager.password);
    await use(page);
    await context.close();
  },

  memberPage: async ({ browser, guards }, use) => {
    const context = await browser.newContext();
    const page = await context.newPage();
    watch(page, 'member', guards);
    await login(page, users.member.username, users.member.password);
    await use(page);
    await context.close();
  },

  persona: async ({ browser, guards }, use) => {
    const contexts: BrowserContext[] = [];
    await use(async (name: string) => {
      const context = await browser.newContext();
      contexts.push(context);
      const page = await context.newPage();
      watch(page, name, guards);
      return page;
    });
    await Promise.all(contexts.map((c) => c.close()));
  },

  personaGoto: async ({ persona }, use) => {
    const pages = new Map<string, Page>();
    await use(async (name, civiPath, params = {}) => {
      let page = pages.get(name);
      if (!page) {
        page = await persona(name);
        pages.set(name, page);
      }
      await page.goto(personaUrl(name, civiPath, params));
      await page.waitForLoadState('networkidle');
      await expectNoPhpOutput(page);
      return page;
    });
  },
});

export { expect };

/** Back office CiviCRM address: /wp-admin/admin.php?page=CiviCRM&q=<path>&... */
export function civiAdminUrl(civiPath: string, params: Record<string, string | number> = {}): string {
  const query = new URLSearchParams({ page: 'CiviCRM', q: civiPath });
  for (const [k, v] of Object.entries(params)) {
    query.set(k, String(v));
  }
  return `/wp-admin/admin.php?${query.toString()}`;
}
