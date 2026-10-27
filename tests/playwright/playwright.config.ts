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
      testIgnore: /council\//,
      use: { ...devices['Desktop Chrome'] },
    },
    {
      // The council election moves the shared election through its stages, so it runs on its own,
      // after everything else, and puts the election back at the nominations stage when it is done.
      name: 'council',
      testMatch: /council\//,
      dependencies: ['chromium'],
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
