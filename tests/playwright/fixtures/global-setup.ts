import { chromium, type FullConfig } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
import users from './test-users.json';
import { CMS } from './cms';

/**
 * Logs each WordPress user in once, one after the other, and saves the session.
 *
 * WordPress keeps a user's sessions in one meta value, so several browsers logging in as the
 * same user at the same moment can overwrite each other's session and be sent back to the
 * login page. Logging in once up front, then sharing the saved session, avoids that.
 */
export const AUTH_DIR = path.resolve(__dirname, '.generated', 'auth');

export const logins: Record<string, { username: string; password: string }> = {
  admin: { username: process.env.WP_ADMIN_USER || 'admin', password: process.env.WP_ADMIN_PASS || 'admin' },
  manager: { username: users.manager.username, password: users.manager.password },
  member: { username: users.member.username, password: users.member.password },
};

export default async function globalSetup(config: FullConfig) {
  // Back end logins below are for WordPress; the other CMS sites are covered by the front end specs.
  if (CMS !== 'wordpress') {
    return;
  }
  const baseURL = config.projects[0].use.baseURL as string;
  fs.mkdirSync(AUTH_DIR, { recursive: true });
  const browser = await chromium.launch();
  for (const [name, { username, password }] of Object.entries(logins)) {
    const context = await browser.newContext({ baseURL });
    const page = await context.newPage();
    await page.goto('/wp-login.php');
    await page.locator('#user_login').fill(username);
    await page.locator('#user_pass').fill(password);
    await page.locator('#wp-submit').click();
    await page.waitForURL(/wp-admin\//);
    await page.locator('#wpadminbar').waitFor();
    if (name === 'admin') {
      // The first visit to a SearchKit page after the caches were cleared is slow while CiviCRM
      // builds its templates; do it once here rather than inside the first test.
      for (const url of ['/wp-admin/admin.php?page=CiviCRM&q=civicrm%2Felections%2Fmanage&reset=1', '/wp-admin/admin.php?page=CiviCRM&q=civicrm%2Felections%2Fposition-list&reset=1', '/wp-admin/admin.php?page=CiviCRM&q=civicrm%2Felections%2Fcandidate-list&reset=1']) {
        await page.goto(url);
        await page.waitForLoadState('networkidle', { timeout: 120000 }).catch(() => {});
      }
    }
    await context.storageState({ path: path.join(AUTH_DIR, `${name}.json`) });
    await context.close();
  }
  await browser.close();
}
