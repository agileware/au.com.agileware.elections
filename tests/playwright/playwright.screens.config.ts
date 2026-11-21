import { defineConfig, devices } from '@playwright/test';

/**
 * Configuration for the screenshot tools in `tools/`: `screens.spec.ts` (`npm run screens`) and `docs-images.spec.ts`
 * (`npm run docs-images`, the user guide's pictures). It is separate from
 * `playwright.config.ts` so that the test suite never runs it. Pictures go to `test-results/screens/<cms>/`.
 */
export default defineConfig({
  globalSetup: './fixtures/global-setup.ts',
  testDir: './tools',
  fullyParallel: false,
  workers: 1,
  retries: 0,
  timeout: 20 * 60 * 1000,
  reporter: 'list',
  use: {
    baseURL: process.env.BASE_URL || 'http://localhost:8080',
    ignoreHTTPSErrors: true,
    actionTimeout: 20000,
  },
  projects: [{ name: 'screens', use: { ...devices['Desktop Chrome'] } }],
});
