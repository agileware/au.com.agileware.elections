# Playwright tests

Automated browser tests for the **Elections** extension, covering the front end (how members
take part) and the back end (how administrators set elections up), including a complete mock
council election: President, Secretary, Treasurer, Council Member 1, 2 and 3, with at least 30
voters.

## How this fits together

- **CI**: `.github/workflows/frontend-tests.yml` calls the central reusable workflow
  `agileware/ci-workflows` → `playwright-tests.yml`. It builds a throwaway WordPress + CiviCRM in
  Docker, enables this extension, runs [`fixtures/setup-environment.sh`](fixtures/setup-environment.sh)
  and then this suite.
- **Setup**: `setup-environment.sh` creates the WordPress roles/users (`fixtures/test-users.json`),
  the election pages with the shortcodes from `docs/setup.md`, and seeds the council election with
  `fixtures/seed-council.php`. The seed uses the same persona fixture as the PHPUnit tests
  (`tests/phpunit/CRM/Elections/Test/CouncilFixture.php`) and writes a personal link
  (`cid` + `cs`) for each of the 41 personas to `fixtures/.generated/council.json`.
- **Front end tests** act as personas through their personal links (the election has *Allow non-logged
  in access*), each in its own browser context. **Back end tests** log in as the WordPress
  administrator, a manager (the permissions documented for an organiser in `docs/roles_and_permissions.md`), a limited organiser without View All Contacts (WordPress) and a member (`view Elections` only).
- **Every test** also fails on: uncaught page errors, console errors, failed election AJAX requests
  and PHP output on the page (`Warning:`, `Notice:`, `Deprecated:`, `Undefined array key`, Smarty
  errors). A test that expects an error (for example a refused page) lists it with
  `allowedProblems.push(/403/)`.
- Stages are moved with `civi.ts` helpers (`moveToStage`, `advanceCouncil`) that run
  `fixtures/advance-council.php` through `cv scr`, like the passing of time.

## Specs

- `specs/frontend/` – members acting as personas through their personal links.
- `specs/backend/` – administrators: Manage Elections (activate, deactivate, duplicate), positions (drag to reorder, edit in place, duplicate, delete, locked once started), candidates (edit statements, add a candidate, frozen once voting starts), the election overview, permissions.
- `specs/council/council-election.spec.ts` – a complete mock council election: six roles, 30 voters, 10 nominees, 6 candidates. The administrator checks the election on the back end; every nominator nominates and seconds, every nominee accepts or withdraws, 38 members vote (one changes their vote) and the results are checked against ballots worked out independently. It runs on its own after the other specs (project `council`) and seeds the election again when done.

## Personas

`V01`..`V30` voters, `N01`..`N10` nominees (`N01`..`N06` become candidates), `OUT` an outsider who
is not in the election's group. See `fixtures/personas.ts` (`personaUrl`) and the plan in
`CouncilFixture::PLAN`.

## Running locally with DDEV (inside the web container)

```bash
ddev exec 'cd /path/to/extension/tests/playwright && npm install && npx playwright install --with-deps chromium'
ddev exec 'cd /path/to/extension/tests/playwright && bash fixtures/setup-environment.sh'
ddev exec 'cd /path/to/extension/tests/playwright && BASE_URL=http://localhost npx playwright test'
```

### Keeping Chromium across `ddev stop` and `ddev start`

Chromium needs system libraries, and a DDEV container loses anything installed in it when the site is stopped. Two small
files in the DDEV project (`.ddev/`, outside this repository) keep them:

- `.ddev/web-build/Dockerfile.playwright` installs the libraries and fonts when the image is built. The package list is what
  `npx playwright install-deps --dry-run chromium` reports (after `sudo apt-get update`) for the container's Debian version:
  `RUN apt-get update && apt-get install -y --no-install-recommends <packages> && rm -rf /var/lib/apt/lists/*`.
- `.ddev/config.playwright.yaml` sets `web_environment: [PLAYWRIGHT_BROWSERS_PATH=/mnt/ddev-global-cache/ms-playwright]`, so the
  browser itself is downloaded once into DDEV's global cache and shared by every site on the machine.

Run `ddev restart` once to rebuild, then `npm install` and `npx playwright install chromium` in this directory. After that
`ddev stop` and `ddev start` need no reinstall.

`/path/to/extension` is where the extension is deployed on the site (for example
`/var/www/html/httpdocs/CRM/extensions/au.com.agileware.elections`). The tests run inside the
container, so `CIVI_EXEC_PREFIX` stays empty. Outside the container set
`CIVI_EXEC_PREFIX="ddev exec -s web -d <extension dir>"` and `BASE_URL` to the site.
View the report with `npm run report`.
