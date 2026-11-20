# Continuous integration

This reference page describes the GitHub workflows of the extension and the shared workflows they call. Where each suite runs is summarised in [README.md](README.md).

## The workflows in `.github/workflows`

| File | Name | Runs on | What it does |
|---|---|---|---|
| `phpunit-tests.yml` | PHPUnit tests | Push to `master`, every pull request, manual | Calls the shared `civicrm-phpunit-tests.yml`. Job name `phpunit`. |
| `upgrade-tests.yml` | upgrade tests | Push to `master`, every pull request, manual | Calls `civicrm-extension-upgrade-tests.yml` with `FROM_REF: 1.3.2`. Job name `upgrade`. |
| `frontend-tests.yml` | frontend tests | Push to `master`, every pull request, manual | Calls `playwright-tests.yml` with `SETUP_SCRIPT: tests/playwright/fixtures/setup-environment.sh`. Job name `playwright`. |
| `cut-release.yml` | Cut release | Manual (`VERSION` input) | Calls `civicrm-extension-cut-release.yml`. Excludes `.github tests .gitignore CONTRIBUTING.md developer AGENTS.md` from the release. See [../releases/README.md](../releases/README.md). |
| `init-release.yml` | Init release | Manual | One-time creation of the `release` branch and caller files. Not used any more: the shared workflow refuses once `cut-release.yml` exists. |
| `civix-upgrade.yml` | civix upgrade | Manual | Runs `civix upgrade` and opens a pull request. |

All of them call the shared repository `agileware/ci-workflows` at `@main`, and the test workflows need the repository secrets `DOCKERHUB_USER` and `DOCKERHUB_TOKEN` (the container image is not public). Pull requests from forks do not receive secrets, so those jobs cannot run for them.

## What the shared workflows do

The three test workflows build a throwaway WordPress and CiviCRM in Docker: a MariaDB service and the container `agilewareprojects/wordpress:pipeline-latest`, with the repository mounted where the extension lives (`wp-content/uploads/civicrm/ext/<name>`). They install WordPress (administrator `admin`), `cv`, and CiviCRM (`cv core:install`), raise PHP's memory limit to 512M, and then:

- **`civicrm-phpunit-tests.yml`**: installs `pdo_mysql`, clones the new site's database into `civicrm_test` as the root user, writes its address into `cv`'s per-site config (`TEST_DB_DSN`), ensures `phpunit9` is present, and runs `phpunit9 --do-not-cache-result --log-junit` as `www-data` from the extension folder with `PWD` set. It uploads the JUnit file, and on failure the Docker logs. An input `PHPUNIT_ARGS` can narrow the run. With `COVERAGE: true` (Elections sets it) it installs pcov, adds the coverage reports to the run, writes the line, method and class totals to the job summary, and uploads the text, Clover and HTML reports as the `phpunit-coverage` artifact. It reports only: no minimum fails the check.
- **`civicrm-extension-upgrade-tests.yml`**: mounts the `FROM_REF` checkout where the extension is installed and the commit under test read-only beside it, then runs the repository's `UPGRADE_SCRIPT` (`tests/upgrade/ci-upgrade-test.sh`) as `www-data`, given `EXTENSION_KEY`, `EXTENSION_DIR` and `NEW_VERSION_DIR`. The script must exit non-zero on failure ([upgrade-test.md](upgrade-test.md)).
- **`playwright-tests.yml`**: also installs and activates a theme (`WP_THEME`, default Astra), sets permalinks to `/%postname%/`, creates the CiviCRM base page and flushes rewrites, enables the extension, installs Node and Playwright (`npm ci`, browsers), opens the checkout's permissions to the container user, runs `SETUP_SCRIPT` inside the container, opens permissions again for the runner, and runs `npx playwright test` with `BASE_URL`, `WP_ADMIN_USER`, `WP_ADMIN_PASS` and `CIVI_EXEC_PREFIX` (a `docker exec` line). It uploads the HTML report, and on failure the traces and Docker logs. Elections passes no `WP_THEME`, so CI runs on Astra; the suite also passes on each CMS's own default theme on DDEV.
- **`civicrm-extension-cut-release.yml`**: checks that `info.xml`'s version equals the input, standardises the docs structure and links, rebuilds the `release` branch from the source commit without the excluded paths, tags, publishes a GitHub Release with notes since the previous tag, and asks docs.civicrm.org to rebuild.

The pipeline image installs the newest stable CiviCRM, so CI also covers a CiviCRM newer than the main DDEV site (6.16). Which version that is at any time: Not verified here; read the run's log.

## The three required checks

`master` takes changes only through a pull request with one review and these checks passing: `phpunit / phpunit`, `upgrade / upgrade` and `playwright / playwright` (the caller's job name, a slash, the shared workflow's job name). See [../standards/git-and-pull-requests.md](../standards/git-and-pull-requests.md) and [../releases/branches-and-rules.md](../releases/branches-and-rules.md).

## What a pull request runs

PHPUnit (the whole suite), the upgrade test from 1.3.2, and the whole Playwright suite on WordPress with `CI` set: one worker, a 90 second timeout, one retry. A run takes several minutes; the browser suite takes the longest.

A re-run of an earlier run does not pick up changes merged to `ci-workflows`: GitHub keeps the commit of the shared workflow that run used. Start a new run (push, or `gh workflow run <file> --ref <branch>`) to test a change to the shared workflows.

## What is not in CI yet

- **Drupal and Joomla browser tests.** The shared workflows build WordPress only. The Playwright suite and the upgrade test run on the Drupal and Joomla DDEV sites before each release. Adding Drupal and Joomla workflows to `ci-workflows` is planned work ([../roadmap/2.2/README.md](../roadmap/2.2/README.md)).
- **CiviCRM 6.16.** CI runs the newest stable CiviCRM only; 6.16 is covered on the DDEV WordPress site.
- **Joomla sessions** for the back end specs that need a manager or member ([playwright.md](playwright.md)).
- **Theme variations and screenshots** (`THEME_VARIANT`, `npm run screens`): run by hand.
