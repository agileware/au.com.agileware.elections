# Playwright tests

Browser tests for the **Elections** extension: the back end as organisers, the front end as people on personal links, a complete mock council election, accessibility, layout at phone width, and attacks. The same specs run on WordPress, Drupal and Joomla.

How the suite is built, how to set up a site for it and how to run it are in the developer guide. This page only points there, so the two never disagree:

- [`developer/testing/playwright.md`](../../developer/testing/playwright.md): the layout of this folder, the projects and their order, the settings for each CMS, first-time set-up, running, failures and flakes, adding a spec.
- [`developer/testing/personas.md`](../../developer/testing/personas.md): the personas (`V01` to `V30`, `N01` to `N10`, `OUT`) and how the tests act as them.
- [`developer/testing/seeding.md`](../../developer/testing/seeding.md): the council election the suite seeds, and the review data in `tests/seed/`.
- [`developer/environments/browser-testing-setup.md`](../../developer/environments/browser-testing-setup.md): keeping Chromium across `ddev stop` and `ddev start`.
- [`developer/testing/ci.md`](../../developer/testing/ci.md): how the suite runs in GitHub Actions.

In short, inside the web container of a DDEV site, from this folder as deployed on the site:

```bash
npm install && npx playwright install chromium
```

```bash
bash fixtures/setup-environment.sh
```

```bash
BASE_URL=http://localhost npx playwright test
```

The second command is for WordPress; Drupal and Joomla have their own account scripts, and a site built by the DDEV kit needs its own administrator passed in (see the guide).
