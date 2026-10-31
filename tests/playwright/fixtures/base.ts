import { test as base, expect, type Browser, type BrowserContext, type Page } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
import { AUTH_DIR } from './global-setup';
import { personaUrl } from './personas';
import { adminUrl } from './cms';

/** PHP and template output that must never reach a visitor. */
const PHP_OUTPUT = /(?:Warning|Notice|Deprecated|Fatal error|Parse error)\s*:|Undefined (?:array key|variable|index|property)|Smarty|Syntax error in template|unknown modifier|unknown tag/i;

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

/** A page for a WordPress user, using the session saved by global-setup.ts. */
async function loggedInPage(browser: Browser, name: 'admin' | 'manager' | 'limited' | 'member') {
  const state = path.join(AUTH_DIR, `${name}.json`);
  if (!fs.existsSync(state)) {
    base.skip(true, `No saved ${name} session on this CMS (see fixtures/global-setup.ts)`);
  }
  const context = await browser.newContext({ storageState: path.join(AUTH_DIR, `${name}.json`) });
  return { context, page: await context.newPage() };
}

type Fixtures = {
  /** A page for the WordPress administrator. */
  adminPage: Page;
  /** A page for an organiser with the permissions documented in docs/roles_and_permissions.md. */
  managerPage: Page;
  /** A page for an organiser who lacks "view all contacts" (WordPress only). */
  limitedPage: Page;
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
    const { context, page } = await loggedInPage(browser, 'admin');
    watch(page, 'admin', guards);
    await use(page);
    await context.close();
  },

  managerPage: async ({ browser, guards }, use) => {
    const { context, page } = await loggedInPage(browser, 'manager');
    watch(page, 'manager', guards);
    await use(page);
    await context.close();
  },

  limitedPage: async ({ browser, guards }, use) => {
    const { context, page } = await loggedInPage(browser, 'limited');
    watch(page, 'limited', guards);
    await use(page);
    await context.close();
  },

  memberPage: async ({ browser, guards }, use) => {
    const { context, page } = await loggedInPage(browser, 'member');
    watch(page, 'member', guards);
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

/**
 * An administrator's page if this CMS has a saved administrator session, otherwise null, for specs
 * whose front end checks must run everywhere and whose back end checks are a bonus.
 */
export async function optionalAdminPage(browser: Browser): Promise<{ page: Page; close: () => Promise<void> } | null> {
  const state = path.join(AUTH_DIR, 'admin.json');
  if (!fs.existsSync(state)) {
    return null;
  }
  const context = await browser.newContext({ storageState: state });
  return { page: await context.newPage(), close: () => context.close() };
}

/** Back office CiviCRM address on the CMS under test (see adminUrl in cms.ts). */
export function civiAdminUrl(civiPath: string, params: Record<string, string | number> = {}): string {
  return adminUrl(civiPath, params);
}
