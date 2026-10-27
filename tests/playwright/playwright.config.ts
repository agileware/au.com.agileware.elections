import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './specs',
  // Election stages are time ordered, so specs that move the election between stages
  // are written as serial describe blocks. Files can still run in parallel.
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  // One worker in CI: several logins and checksum personas against a single WordPress
  // container at once is slow enough to hit the default timeout.
  workers: process.env.CI ? 1 : undefined,
  timeout: process.env.CI ? 90000 : 60000,
  reporter: process.env.CI ? [['html', { open: 'never' }], ['list']] : 'list',
  use: {
    baseURL: process.env.BASE_URL || 'http://localhost:8080',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
