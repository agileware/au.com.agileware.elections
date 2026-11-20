# Playwright browser tests

Verified with: the suite run project by project on new WordPress, Drupal 11 and Joomla 5.4 sites built by the DDEV kit, CiviCRM 6.19.5, 2026-11-15. Results and known failures are in the notes below.

The Playwright suite drives Chromium against a real site. It tests the back end as organisers, the front end as people on personal links, a complete mock council election, accessibility, layout at phone width, and attacks. The same specs run on WordPress, Drupal and Joomla. This page covers how it is built, how to set up a site for it, and how to run it. `tests/playwright/README.md` only points here.

## Layout of `tests/playwright/`

| Path | What it holds |
|---|---|
| `playwright.config.ts` | Projects and their order, timeouts, retries, workers. |
| `fixtures/base.ts` | The test fixtures: `adminPage`, `managerPage`, `limitedPage`, `memberPage`, `persona`, `personaGoto`, and the checks every test gets (below). |
| `fixtures/cms.ts` | `CMS`, `CV`, and `frontUrl()` / `adminUrl()`: the address of a CiviCRM page on each CMS. |
| `fixtures/civi.ts` | Helpers that run `cv` for database state and stage changes: `civiApi4`, `moveToStage`, `advanceCouncil`, `reseedCouncil`, `civiSql`, `civiEval`. |
| `fixtures/personas.ts`, `council-ui.ts` | The persona links and the steps a person takes (`nominate`, `accept`, `withdraw`, `vote`). |
| `fixtures/data.ts` | Throwaway elections for back end specs, named per run so a re-run never meets old rows. |
| `fixtures/global-setup.ts` | Reseeds the council, logs the test users in once and saves their sessions. |
| `fixtures/setup-environment.sh` | First-time set-up of a WordPress site (roles, users, pages, mail, council seed). |
| `fixtures/setup-accounts-drupal.sh`, `setup-accounts-joomla.sh` | Test accounts on Drupal and Joomla. |
| `fixtures/seed-council.php`, `advance-council.php` | The council election and its stage steps ([seeding.md](seeding.md)). |
| `fixtures/theme-variants.sh` | Switches a site's default theme to a colour variation, for accessibility checks. |
| `fixtures/test-users.json` | Names, e-mail addresses and roles of the test accounts. It holds no passwords. |
| `fixtures/generate-passwords.php` | Called by the three setup scripts. Makes a random password for each account in `test-users.json` and saves them in `fixtures/.generated/test-passwords.json` (git-ignored, never committed). Existing passwords are kept, so running a script again changes nothing; delete the file and run the script again to make new ones. `global-setup.ts` reads the file and stops with a message that names the setup script if it is missing. |
| `specs/backend/` | Organisers: Manage Elections, the election form, positions (drag, edit in place, duplicate, delete, locking), candidates, overview, permissions, the page frame. |
| `specs/frontend/` | People on personal links: smoke, election page by stage, layout, accessibility. |
| `specs/council/` | A complete council election and variations on it: ranked choice, most votes, groups, write-ins, referendum, organiser-chosen candidates, results page. |
| `specs/security/` | `xss` (script in every field people can fill) and `tampering` (links, requests and forms that should be refused). |

## Projects and their order

The config has a `chromium` project (everything not named below), then projects that each move the one seeded election through its stages and put it back at the nominations stage, one after the other in the order listed: `layout`, `election-page`, `accessibility`, `security` (the `xss` spec), `tampering`, `council`, `plurality`, `groups`, `writein`, `referendum` and `results-display`. The projects are **not** chained with `dependencies` (they were until 2.1): the suite always runs with one worker, which runs the projects in this order, so no two of them touch the election at once, and a failure in one project never stops the later ones. Each of these specs reseeds the election before it starts, so a failed run leaves nothing for the next to trip over. Never raise the worker count for a full run; `PW_WORKERS=2` is safe only for the `chromium` project on its own, whose specs make their own elections.

## How it runs on each CMS

| Variable | Meaning |
|---|---|
| `CMS` | `wordpress` (default), `drupal` or `joomla`. Chooses the address forms, the login page and the sessions. |
| `CIVI_CV` | The `cv` command for the site. `cv` everywhere except Joomla, where it is `jcv`. |
| `BASE_URL` | The site's address. Inside a DDEV container use `http://localhost`: the default (`http://localhost:8080`) refuses connections there. |
| `CIVI_EXEC_PREFIX` | A command put in front of every `cv` call, for running the tests outside the container (`ddev exec -s web -d <extension dir>`, or the `docker exec` line CI uses). Empty when the tests run inside the container. |
| `COMPONENT_DIR` | Where the extension is mounted inside the container; script paths are mapped to it when `CIVI_EXEC_PREFIX` is set (CI sets it). |
| `WP_ADMIN_USER`, `WP_ADMIN_PASS` | The WordPress administrator the suite logs in as (it defaults to the CI account). Set them to match your site's administrator. |
| `WP_CLEAN_URLS=0` | WordPress without pretty permalinks: use the `?page=CiviCRM&q=` address form. |
| `npm run screens` | Not a test: takes screenshots of every page (`tools/screens.spec.ts`, its own config `playwright.screens.config.ts`, pictures in `test-results/screens/<cms>/`). |
| `A11Y_WIDTHS`, `THEME_VARIANT` | Widths for the axe scan (default 1280) and the theme variation in use (see `theme-variants.sh`). |

Sessions saved by `global-setup.ts` under `fixtures/.generated/auth/<name>.json`:

| CMS | Sessions | Effect |
|---|---|---|
| WordPress | administrator, manager, limited organiser, member | All specs run. |
| Drupal | the same four (accounts from `setup-accounts-drupal.sh`) | All specs run. |
| Joomla | the same four (accounts and groups from `setup-accounts-joomla.sh`); the member signs in to the site, not the administrator | All specs run (since 2.2). |

Until 2.2 six specs skipped on Joomla for lack of a manager, limited or member session. The Joomla script now makes those accounts, with a Joomla group for each role ([../environments/ddev-joomla.md](../environments/ddev-joomla.md)). On Joomla a member may not use the administrator, so the specs that check a member is refused a back office page open it through the site with `memberAdminUrl()` (`fixtures/cms.ts`), where CiviCRM's own permission check refuses it. The front end persona specs use personal links, not logins ([personas.md](personas.md)).

Other generated files: `fixtures/.generated/council.json` (every persona's personal link and the plan) and `council-personas.json`. They are git-ignored. `global-setup.ts` reseeds the council election each run, so a run starts at the nominations stage whatever the site was left at.

## Every test also fails on

The shared fixture watches every page a test opens, and fails the test on: an uncaught page error, a console error (except favicon and plain 404 noise), a failed election AJAX request (an HTTP error from any address containing "Election"), and PHP or template output on the page (`Warning:`, `Notice:`, `Deprecated:`, `Undefined array key`, Smarty errors). A test that expects such a response lists it: `allowedProblems.push(/403/)`. Use `personaGoto()` to get the PHP-output check for free.

## First-time set-up of a site

The site must have CiviCRM, the extension deployed and enabled, and (for WordPress) pretty permalinks and the CiviCRM base page. [../environments/README.md](../environments/README.md) and [../ddev-kit/README.md](../ddev-kit/README.md) build that. Chromium's system libraries and browser are kept outside the container: see [../environments/browser-testing-setup.md](../environments/browser-testing-setup.md).

In all cases, from the deployed extension (`tests/playwright`), once per site:

```bash
ddev exec 'cd /var/www/html/httpdocs/CRM/extensions/au.com.agileware.elections/tests/playwright && npm install && npx playwright install chromium'
```

If the browser download fails with `ENOTFOUND cdn.playwright.dev`, it is a passing DNS failure: run it again. `node_modules` is not copied by the deploy; `npm ci` after a rebuild brings it back.

| CMS | Then |
|---|---|
| WordPress | `bash fixtures/setup-environment.sh` from `tests/playwright`. It makes the roles and users in `test-users.json` (with generated passwords), the election pages from `docs/setup.md`, redirects outgoing mail to the database and seeds the council. Safe to run again. |
| Drupal | From the project root (`/var/www/html`): `bash <extension>/tests/playwright/fixtures/setup-accounts-drupal.sh` (needs `vendor/bin/drush`). The council seeds itself when the suite starts. |
| Joomla | From the site root: `bash <extension>/tests/playwright/fixtures/setup-accounts-joomla.sh`. The council seeds itself when the suite starts. |

Clean Drupal 11 and Joomla 5.4 sites built by the kit needed nothing more than their account script (checked 2026-11-15). Not verified: whether the mail redirect that the WordPress script sets matters on either.

**Review data (`tests/seed/`) and the suite can share a site since 2.2.** The referendum spec counts only its own election's answers, and the `xss` spec writes its test text only into the council election; before 2.2 the first failed on a site with seeded answers and the second overwrote the statements and descriptions of every election. The suite still creates and deletes its own elections, people and settings, so a separate site for review stays the tidier choice.

**Known failure on Drupal 11:** none. In Olivero's narrow column the three buttons above the candidates and positions lists do not fit on one row, so the Add button wraps to a row of its own; it stays at the right end, which `candidates.spec.ts` asserts on every CMS.

## Run

All commands run in the container, from `tests/playwright` of the deployed extension. Deploy first (`ddev deploy-elections`) and stop other DDEV sites.

WordPress:

```bash
BASE_URL=http://localhost npx playwright test --reporter=list
```

Drupal: `CMS=drupal BASE_URL=http://localhost npx playwright test`. Joomla: `CMS=joomla CIVI_CV=jcv BASE_URL=http://localhost npx playwright test`.

Run one project or one spec with `--project=<name>` or the spec's path. The projects share one election, so run one at a time (the default is one worker):

```bash
BASE_URL=http://localhost npx playwright test specs/backend/frame.spec.ts --reporter=list
```

```bash
CMS=drupal BASE_URL=http://localhost npx playwright test --project=council
```

A full run takes roughly 7 to 10 minutes on WordPress with one site running. Run it as a background job with a log, then read the log; never write a separate waiter that searches for the process by name (it matches itself and never ends):

```bash
ddev exec 'cd /var/www/html/httpdocs/CRM/extensions/au.com.agileware.elections/tests/playwright && BASE_URL=http://localhost npx playwright test --reporter=list' > "<scratch>/pw-wordpress.log" 2>&1 &
```

Check that it is alive with the log's modified time (`ls -l`) and read it with `grep -a`. A run that ends within seconds without a pass or fail line ran no tests: read the log. View the HTML report with `npm run report`.

## Failures and flakes

- Page loads timing out after 60 seconds on a loaded machine are not wrong results. Stop the other sites and rerun.
- A failure only on the first attempt right after a deploy (the cache was flushed) is a known flake; the config retries once. The same goes for the first SearchKit page load, which the global setup warms up.
- A run that ends early (browser closed, many "did not run") is an invalid result, not a set of failures: rerun it alone first.
- `Cannot find module '@playwright/test'` means `npm ci` is needed; `libnspr4.so: cannot open shared object file` means the image lacks Chromium's libraries ([../environments/browser-testing-setup.md](../environments/browser-testing-setup.md)).
- A login timeout in the global setup usually means another run is still using the site.
- One Drupal spec (`positions.spec.ts`) failed once and was not investigated.

## CI and local

In GitHub Actions the suite runs on WordPress only, with `CI` set: one worker, a 90 second test timeout, an HTML report, and `CIVI_EXEC_PREFIX` set to a `docker exec` line. The setup script is `fixtures/setup-environment.sh`. The theme in CI is the shared workflow's default (Astra); the local sites use each CMS's own default theme (see [ci.md](ci.md)). Locally the config uses two workers and a 60 second timeout. Drupal and Joomla are run on DDEV before each release.

## Add a spec

Put it in `specs/backend`, `frontend`, `council` or `security`. Import `test` and `expect` from `../../fixtures/base` so it gets the checks above. A spec that moves the council election through its stages must be its own project in `playwright.config.ts`, listed after the others (the single worker runs the projects in the order listed; do not add `dependencies`), and must reseed the council in `beforeAll` and `afterAll`. Test the front end through personas ([personas.md](personas.md)); use the CMS-specific address helpers, never a hard-coded path.
