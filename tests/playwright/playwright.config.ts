import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  globalSetup: './fixtures/global-setup.ts',
  testDir: './specs',
  // Election stages are time ordered, so specs that move the election between stages
  // are written as serial describe blocks. Files can still run in parallel.
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: 1,
  // One worker, always. The projects below that move the one seeded election between stages must never run
  // at the same time as each other or as the `chromium` project, and with one worker Playwright runs the
  // projects one after the other in the order they are listed here. The projects have no `dependencies`, so a
  // failure in one never stops the later ones from running. (Several logins and checksum personas against one
  // container at once is also slow enough to hit the default timeout.) Set PW_WORKERS=2 only to run the
  // `chromium` project on its own, whose specs make their own elections.
  workers: Number(process.env.PW_WORKERS) || 1,
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
      testIgnore: /council\/|security\/|frontend\/(layout|election-page|accessibility)/,
      use: { ...devices['Desktop Chrome'] },
    },
    // The specs below move the one shared election through its stages (nominations, advertise, voting,
    // results) and put it back at the nominations stage when they finish, so each runs on its own, one after
    // the other, once everything else is done. They run in this order because there is one worker; they are not
    // chained with `dependencies`, which would skip every later project after one failure. Each spec reseeds the
    // election before it starts, so a failure leaves nothing behind for the next one to trip over.
    {
      name: 'layout',
      testMatch: /frontend\/layout/,
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'election-page',
      testMatch: /frontend\/election-page/,
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'accessibility',
      testMatch: /frontend\/accessibility/,
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'security',
      testMatch: /security\/xss/,
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'tampering',
      testMatch: /security\/tampering/,
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'council',
      testMatch: /council\/council-election/,
      use: { ...devices['Desktop Chrome'] },
    },
    {
      // The same council counted by most votes. Its own project, as both use the one election.
      name: 'plurality',
      testMatch: /council\/plurality/,
      use: { ...devices['Desktop Chrome'] },
    },
    {
      // Separate groups for nominating and standing, on the same election again.
      name: 'groups',
      testMatch: /council\/groups/,
      use: { ...devices['Desktop Chrome'] },
    },
    {
      // Write-ins during voting, on the same election again.
      name: 'writein',
      testMatch: /council\/writein/,
      use: { ...devices['Desktop Chrome'] },
    },
    {
      // A referendum question on the same ballot, on the same election again.
      name: 'referendum',
      testMatch: /council\/referendum/,
      use: { ...devices['Desktop Chrome'] },
    },
    {
      // The look of the results page: logo, header, footer and the celebration, on the same election again.
      name: 'results-display',
      testMatch: /council\/results-display/,
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
