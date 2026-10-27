import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  globalSetup: './fixtures/global-setup.ts',
  testDir: './specs',
  // Election stages are time ordered, so specs that move the election between stages
  // are written as serial describe blocks. Files can still run in parallel.
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: 1,
  // One worker in CI: several logins and checksum personas against a single WordPress
  // container at once is slow enough to hit the default timeout.
  workers: process.env.CI ? 1 : 2,
  timeout: process.env.CI ? 90000 : 60000,
  // SearchKit and Afform pages load their data after the page itself, so allow time for it.
  expect: { timeout: 15000 },
  reporter: process.env.CI ? [['html', { open: 'never' }], ['list']] : 'list',
  use: {
    baseURL: process.env.BASE_URL || 'http://localhost:8080',
    // The DDEV sites use certificates from the local mkcert authority.
    ignoreHTTPSErrors: true,
    // A step that cannot be done fails in seconds, not after the whole test timeout.
    actionTimeout: 20000,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      testIgnore: /council\/|security\/|frontend\/(layout|accessibility|screens)/,
      use: { ...devices['Desktop Chrome'] },
    },
    // The specs below move the one shared election through its stages (nominations, advertise, voting,
    // results) and put it back at the nominations stage when they finish, so each runs on its own,
    // one after the other, once everything else is done.
    {
      name: 'layout',
      testMatch: /frontend\/layout/,
      dependencies: ['chromium'],
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'accessibility',
      testMatch: /frontend\/accessibility/,
      dependencies: ['layout'],
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'security',
      testMatch: /security\/xss/,
      dependencies: ['accessibility'],
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'tampering',
      testMatch: /security\/tampering/,
      dependencies: ['security'],
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'council',
      testMatch: /council\//,
      dependencies: ['tampering'],
      use: { ...devices['Desktop Chrome'] },
    },
    {
      // Pictures of every page (SCREENS=1 only).
      name: 'screens',
      testMatch: /frontend\/screens/,
      dependencies: ['council'],
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
