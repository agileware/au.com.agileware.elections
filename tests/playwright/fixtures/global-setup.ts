import { chromium, type FullConfig, type Page } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
import users from './test-users.json';
import { adminUrl, CMS } from './cms';
import { civiApi4, reseedCouncil } from './civi';

/**
 * Logs each test user in once, one after the other, and saves the session.
 *
 * WordPress keeps a user's sessions in one meta value, so several browsers logging in as the
 * same user at the same moment can overwrite each other's session and be sent back to the
 * login page. Logging in once up front, then sharing the saved session, avoids that.
 */
export const AUTH_DIR = path.resolve(__dirname, '.generated', 'auth');

export const logins: Record<string, { username: string; password: string }> = {
  admin: { username: process.env.WP_ADMIN_USER || 'admin', password: process.env.WP_ADMIN_PASS || 'admin' },
  manager: { username: users.manager.username, password: users.manager.password },
  limited: { username: users.limited.username, password: users.limited.password },
  member: { username: users.member.username, password: users.member.password },
};

/** Drupal and Joomla use test accounts created by fixtures/setup-accounts-<cms>.sh. */
const testAdmin = { username: users.admin.username, password: users.admin.password };

const WARM_UP = ['civicrm/elections/manage', 'civicrm/elections/position-list', 'civicrm/elections/candidate-list', 'civicrm/elections/create', 'civicrm/elections/result-list', 'civicrm/elections/audit-log'];

async function warmUp(page: Page) {
  // The first visit to a SearchKit page after the caches were cleared is slow while CiviCRM
  // builds its templates; do it once here rather than inside the first test.
  for (const civiPath of WARM_UP) {
    await page.goto(adminUrl(civiPath, { reset: 1 }));
    await page.waitForLoadState('networkidle', { timeout: 120000 }).catch(() => {});
  }
}

export default async function globalSetup(config: FullConfig) {
  // Right after the caches were cleared, two workers that both need CiviCRM's metadata can try to write the
  // same cache row at the same moment. Build it once here, before any test runs.
  try {
    civiApi4('Contact.get', { select: ['id'], limit: 1 });
    civiApi4('ElectionVote.get', { select: ['id'], limit: 1 });
    // Start from the council election at the nominations stage, whatever an earlier run (or a person
    // looking around the site) left behind.
    reseedCouncil();
  }
  catch (e) {
    // Not fatal: the tests will say if CiviCRM is really not reachable.
  }
  const baseURL = config.projects[0].use.baseURL as string;
  fs.mkdirSync(AUTH_DIR, { recursive: true });
  const browser = await chromium.launch();

  // Which sessions each CMS gets. Joomla has no manager or member session: CiviCRM permissions are
  // set through Joomla's access levels there, so those specs are skipped on Joomla.
  const sessions: Array<[string, { username: string; password: string }]> =
    CMS === 'wordpress' ? Object.entries(logins)
      : CMS === 'drupal' ? [['admin', testAdmin], ['manager', logins.manager], ['member', logins.member]]
        : [['admin', testAdmin]];

  for (const [name, { username, password }] of sessions) {
    const context = await browser.newContext({ baseURL });
    const page = await context.newPage();
    if (CMS === 'wordpress') {
      await page.goto('/wp-login.php');
      await page.locator('#user_login').fill(username);
      await page.locator('#user_pass').fill(password);
      await page.locator('#wp-submit').click();
      await page.waitForURL(/wp-admin\//);
      await page.locator('#wpadminbar').waitFor();
    }
    else if (CMS === 'drupal') {
      await page.goto('/user/login');
      await page.locator('#edit-name').fill(username);
      await page.locator('#edit-pass').fill(password);
      await page.locator('#edit-submit').click();
      await page.waitForURL((u) => !u.pathname.startsWith('/user/login'));
    }
    else {
      await page.goto('/administrator/index.php');
      await page.locator('#mod-login-username').fill(username);
      await page.locator('#mod-login-password').fill(password);
      await page.locator('#btn-login-submit').click();
      await page.waitForURL(/administrator\/index\.php(?!.*login)/, { timeout: 30000 });
    }
    if (name === 'admin') {
      await warmUp(page);
    }
    await context.storageState({ path: path.join(AUTH_DIR, `${name}.json`) });
    await context.close();
  }
  await browser.close();
}
