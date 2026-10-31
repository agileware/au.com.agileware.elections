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
      testIgnore: /council\/|security\/|frontend\/(layout|election-page|accessibility|screens)/,
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
      name: 'election-page',
      testMatch: /frontend\/election-page/,
      dependencies: ['layout'],
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'accessibility',
      testMatch: /frontend\/accessibility/,
      dependencies: ['election-page'],
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
      testMatch: /council\/council-election/,
      dependencies: ['tampering'],
      use: { ...devices['Desktop Chrome'] },
    },
    {
      // The same council counted by most votes. Its own project, as both use the one election.
      name: 'plurality',
      testMatch: /council\/plurality/,
      dependencies: ['council'],
      use: { ...devices['Desktop Chrome'] },
    },
    {
      // Separate groups for nominating and standing, on the same election again.
      name: 'groups',
      testMatch: /council\/groups/,
      dependencies: ['plurality'],
      use: { ...devices['Desktop Chrome'] },
    },
    {
      // Write-ins during voting, on the same election again.
      name: 'writein',
      testMatch: /council\/writein/,
      dependencies: ['groups'],
      use: { ...devices['Desktop Chrome'] },
    },
    {
      // A referendum question on the same ballot, on the same election again.
      name: 'referendum',
      testMatch: /council\/referendum/,
      dependencies: ['writein'],
      use: { ...devices['Desktop Chrome'] },
    },
    {
      // An election whose organisers choose the candidates.
      name: 'admin-managed',
      testMatch: /council\/admin-managed/,
      dependencies: ['referendum'],
      use: { ...devices['Desktop Chrome'] },
    },
    {
      // The look of the results page: logo, header, footer and the celebration, on the same election again.
      name: 'results-display',
      testMatch: /council\/results-display/,
      dependencies: ['admin-managed'],
      use: { ...devices['Desktop Chrome'] },
    },
    {
      // Pictures of every page (SCREENS=1 only).
      name: 'screens',
      testMatch: /frontend\/screens/,
      dependencies: ['results-display'],
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
