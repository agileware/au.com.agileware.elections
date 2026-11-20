# DDEV test site kit

Verified with: DDEV v1.25.4, PHP 8.3, MariaDB 11.8, CiviCRM 6.19.5, extension 2.1.0; a new site for each CMS was built from nothing with `create-site.sh` on 2026-11-15 (WordPress, Drupal 11.4, Joomla 5.4.10).

The kit builds a DDEV test site for the Elections extension on WordPress, Drupal 11 or Joomla 5 with one command, using only public downloads. The site gets CiviCRM, a custom extensions folder, the extension deployed into it, `cv`, PHPUnit, the Playwright Chromium set-up and mail catching. It copies what the maintainers' existing test sites have, made generic. How those sites are described and used is in [`../environments/README.md`](../environments/README.md); how the suites run is in [`../testing/README.md`](../testing/README.md).

## Requirements

| Need | Detail |
|---|---|
| DDEV | 1.24.0 or newer (the script checks). The existing sites were built with 1.25.4. The `drupal11` project type needs 1.24 or later |
| Docker | Running before you start (Docker Desktop, Colima or another engine DDEV supports) |
| Host tools | `curl`, `rsync`, `openssl`, `sed`, `grep`, `sort`, bash 3.2 or newer (the macOS bash is enough) |
| Network | Needed throughout: Docker images, apt packages (the Chromium libraries), WordPress, Drupal, Joomla, CiviCRM, cv, PHPUnit, npm and the Chromium download |
| Disk | The existing sites are 410 to 670 MB of files each, plus Docker images and a database volume per site. Allow several GB per site on top of the shared images |
| Memory | Not measured. The maintainers run one test site at a time on a machine with 11 GB given to Docker; a full browser run failed from overload when six sites were running. Stop sites you are not using (`ddev stop <name>`, never `ddev poweroff`) |
| Repository | The extension repository checked out as `au.com.agileware.elections/` in the workspace folder. The upgrade test also needs its `1.3.2` tag |

## Layout

```text
<workspace>/
  au.com.agileware.elections/     the extension repository (this kit is in developer/ddev-kit/)
  <site-name>/                    created by the script: one DDEV project per CMS
```

## Usage

Run the script from the workspace folder, not from inside the repository:

```bash
cd <workspace>
au.com.agileware.elections/developer/ddev-kit/create-site.sh <wordpress|drupal|joomla> <site-name> [civicrm-version]
```

| CMS | Command | Site address | CiviCRM extensions folder |
|---|---|---|---|
| WordPress | `au.com.agileware.elections/developer/ddev-kit/create-site.sh wordpress elections-wp` | `https://elections-wp.ddev.site` | `httpdocs/CRM/extensions` |
| Drupal 11 | `au.com.agileware.elections/developer/ddev-kit/create-site.sh drupal elections-drupal` | `https://elections-drupal.ddev.site` | `web/CRM/extensions` |
| Joomla 5 | `au.com.agileware.elections/developer/ddev-kit/create-site.sh joomla elections-joomla` | `https://elections-joomla.ddev.site` | `CRM/extensions` |

The site name uses lower case letters, digits and hyphens, and must not already be a DDEV project. The script prints each step, and ends by printing the commands that create the test accounts and run the browser suite.

Environment variables, all optional:

| Variable | Effect | Default |
|---|---|---|
| `WP_VERSION` | WordPress release for `wp core download` | the latest release |
| `DRUPAL_CONSTRAINT` | Composer constraint for `drupal/recommended-project` | `^11` |
| `JOOMLA_VERSION` | Joomla 5 release downloaded from GitHub | `5.4.10` |
| `SKIP_PLAYWRIGHT=1` | Skip `npm install` and the Chromium download | not set |

## Choosing the CiviCRM version

The third argument is a full version, a series, or `latest`:

```bash
au.com.agileware.elections/developer/ddev-kit/create-site.sh wordpress elections-wp 6.19.5
```

```bash
au.com.agileware.elections/developer/ddev-kit/create-site.sh wordpress elections-wp-616 6.16
```

- `6.19.5` is used as given.
- `6.19` is resolved to the newest `6.19.x` on `https://latest.civicrm.org/stable.php?format=json`. This is the default series.
- `latest` is the newest stable release.

Test on both 6.16 and 6.19 (see [`../environments/civicrm-versions.md`](../environments/civicrm-versions.md)), so build one site of each when you need both.

**How the existing 6.16.5 site differs from a public 6.16.x site.** The WordPress site `elections2` runs CiviCRM 6.16.5 from an ESR build that Agileware packages privately. That build adds an `agileware-patches` folder and bundles many Agileware extensions in `civicrm/ext`, including an old copy (1.3.2) of Elections, which the custom `CRM/extensions` folder overrides. The kit does not use that zip. A comparable public site is `create-site.sh wordpress <name> 6.16.5`, which downloads the public `civicrm-6.16.5-wordpress.zip` (the newest public 6.16 release today is 6.16.8: use `6.16`). It has CiviCRM core without the Agileware patches and bundled extensions; nothing in the Elections tests depends on them. The Drupal and Joomla sites are already public builds: Drupal from Composer and Joomla from the Joomla package, both CiviCRM 6.19.2 when built.

Joomla 6 does not work with the CiviCRM Joomla package and `cv` at 6.19.2 (removed classes and a missing PSR logger), so the kit uses Joomla 5.4.

## What the script does

1. **Checks.** DDEV version, Docker running, tools, network, the extension repository, that the name is free, and that an existing site folder is the same CMS. Resolves the CiviCRM version.
2. **Writes the project files** into `<site>/.ddev/` (table below), downloads `cv.phar` and `phpunit.phar` into `.ddev/homeadditions/bin/`, generates the administrator password into `.ddev/.site-credentials` (mode 600), and writes `.ddev/homeadditions/.cv.json`. All of this happens before the first start so the container picks it up.
3. **`ddev config`** with the CMS project type, PHP 8.3, MariaDB 11.8, Node.js 24 and nginx-fpm, then **`ddev start`**. The first start builds the web image with the Chromium libraries and takes a few minutes.
4. **Installs the CMS** with official public methods: WordPress with `wp core download` and `wp core install`, then pretty permalinks. Drupal with `composer create-project drupal/recommended-project:^11`, CiviCRM's Composer packages and settings, Drush, and `drush site:install standard` (Olivero). Joomla by downloading the full package from the Joomla GitHub releases, running `installation/joomla.php install` and removing `installation/`.
5. **Installs CiviCRM** from public sources: WordPress from the `download.civicrm.org` plugin zip with `cv core:install`, then activation, the CiviCRM base page and its `wpBasePage` setting. Drupal with `cv core:install` and `drush en civicrm`. Joomla from the `download.civicrm.org` Joomla package through Joomla's command line extension installer.
6. **Sets the custom extensions folder** (`extensionsDir` and `extensionsURL`) and makes the site answer on both its `ddev.site` address and `http://localhost` (the browser tests run inside the container), using `civicrm-dev-settings` and, on WordPress, `wp-config-localhost` (without it WordPress redirects `http://localhost` to its https address, whose certificate the container does not trust).
7. **Sends CiviCRM's outgoing mail to Mailpit** (SMTP `localhost:1025`).
8. **Creates the PHPUnit test database** `civicrm_tests_dev` as a copy of the site database (WordPress and Drupal; not Joomla).
9. **Deploys the extension** with `ddev deploy-elections`, which enables it, and checks that `cv ext:list` shows it.
10. **Installs the browser test packages**: `npm install` and `npx playwright install chromium` in the deployed `tests/playwright` folder.

Every finished step is recorded in `<site>/.ddev/.kit-steps`, so running the script again skips it and carries on after a failure. Delete a line from that file to repeat a step. Each step also checks real state where it can (for example, WordPress already installed).

## Files the kit copies

All under [`files/.ddev/`](files/.ddev/) and copied to `<site>/.ddev/`. None is a binary: the phars are downloaded.

| File | Purpose | Differences between the existing sites, and the choice |
|---|---|---|
| `web-build/Dockerfile.playwright` | The system packages and fonts Chromium needs, installed when the image is built (Debian 13), so they survive `ddev stop` | Identical on the three sites. `election-test` has none |
| `config.playwright.yaml` | `PLAYWRIGHT_BROWSERS_PATH=/mnt/ddev-global-cache/ms-playwright`: the browser lives in DDEV's global cache, shared by all sites | Identical on the three sites |
| `commands/host/deploy-elections` | Copies the repository folder next to the site into `<docroot>/CRM/extensions/au.com.agileware.elections`, enables it, upgrades the database, clears the CiviCRM cache | The three sites had three copies that differed in docroot (`httpdocs`, `web`, `.`) and in the Joomla ending. One command now reads `DDEV_DOCROOT` and `DDEV_PROJECT_TYPE`. Joomla keeps its own ending: `jcv` and clearing the cache by hand. `ELECTIONS_SRC` overrides the source folder |
| `homeadditions/bin/cv` | Runs `~/bin/cv.phar` | The existing sites put the phar itself in `bin/`; a wrapper keeps the binary out of version control |
| `homeadditions/bin/jcv`, `homeadditions/joomla-prepend.php` | `cv` for Joomla, which `cv` cannot boot: runs CiviCRM in UnitTests mode with Joomla's `psr/log` | The existing wrapper hard-coded the home folder of the maintainer's user. It now uses `$HOME`. Joomla sites only |
| `homeadditions/bin/phpunit` | Runs `~/bin/phpunit.phar` (9.6) with `CIVICRM_UF=UnitTests` | Only WordPress had it in use. It now refuses to run on Joomla (see Known limits) |
| `homeadditions/bin/wp-config-localhost` | WordPress only: makes `wp-config.php` answer on `http://localhost` inside the container (`WP_HOME` and `WP_SITEURL`), and removes DDEV's `#ddev-generated` signature so a restart does not overwrite the file (it still includes `wp-config-ddev.php` for the database settings) | The `elections2` site has the same five lines at the top of `wp-config.php` |
| `homeadditions/bin/civicrm-dev-settings` | Edits `civicrm.settings.php`: the `$elections_dev_base` address helper, the extensions folder and its URL | The existing sites were edited by hand, in different places. This reproduces the same result |
| `homeadditions/bin/.gitignore` | Keeps `*.phar` out of version control | New |

Generated for each site, not stored in `files/`: `homeadditions/.cv.json` (WordPress and Drupal point `TEST_DB_DSN` at `civicrm_tests_dev` with DDEV's own local root login; Joomla points it at the site database because `jcv` needs it), `.ddev/.site-credentials`, and a `.gitignore` in the site folder that lists the credentials, the steps file and the phars (DDEV rewrites its own `.ddev/.gitignore`).

## Confirm the site works

From the site folder:

```bash
ddev describe
```

```bash
ddev exec 'cd /var/www/html/httpdocs && cv ext:list -L | grep au.com.agileware.elections'
```

Use `/var/www/html/web` for Drupal, and for Joomla `ddev exec 'cd /var/www/html && jcv ext:list -L | grep au.com.agileware.elections'`. The line should show the extension as installed. Then:

```bash
ddev exec 'cd /var/www/html/httpdocs && cv ev "echo CRM_Extension_System::singleton()->getFullContainer()->getPath(\"au.com.agileware.elections\");"'
```

It must print the `CRM/extensions` path, not `civicrm/ext`. Open the site address and sign in as `admin` with the password in `.ddev/.site-credentials`. In CiviCRM open Elections, Manage Elections. Send a test message and read it with `ddev launch -m` (Mailpit, `https://<site-name>.ddev.site:8026`).

PHPUnit (WordPress):

```bash
ddev exec 'cd /var/www/html/httpdocs/CRM/extensions/au.com.agileware.elections && phpunit'
```

Browser tests: use the commands the script prints at the end. In short, WordPress runs `bash fixtures/setup-environment.sh` from `tests/playwright`, which creates the roles, test users, election pages and the council election. Drupal and Joomla run `fixtures/setup-accounts-drupal.sh` or `setup-accounts-joomla.sh` from the project root; the suite seeds the council election when it starts. Then run `npx playwright test` with `BASE_URL=http://localhost`, plus `CMS=drupal`, or `CMS=joomla CIVI_CV=jcv`. On WordPress the suite logs in as `admin` with the password `admin` unless `WP_ADMIN_USER` and `WP_ADMIN_PASS` are set, so pass the generated ones as the script's closing text shows: it reads the password from `.ddev/.site-credentials` inside the container, so the password is never on a command line (DDEV prints the whole command when a test run fails, and a password in it would be printed too). After changing any file in `files/.ddev/homeadditions`, run `ddev restart` so the container picks it up. Details: [`../testing/playwright.md`](../testing/playwright.md) and [`../testing/phpunit.md`](../testing/phpunit.md).

## Passwords

The script never prints a password. The administrator password is random (24 letters and digits plus a short tail), written once to `<site>/.ddev/.site-credentials` with mode 600, and the file is git-ignored. It is passed to `wp`, `drush` or the Joomla installer on a command line for a moment, which other users on the same machine could see in the process list. The database logins are DDEV's fixed local defaults (`db` and `root`). The test accounts the fixture scripts create (`elections_manager` and others) use fixed passwords from `tests/playwright/fixtures/test-users.json`, which is committed: they are for local test sites only, so do not expose a kit site outside your machine. On Joomla the project folder is the web root, so the credentials file sits inside it (in `.ddev/`); do not copy that folder to a public server. Not verified: that DDEV's web server refuses requests for `.ddev/`.

## Delete a site

```bash
cd <workspace>/<site-name> && ddev delete --omit-snapshot --yes
```

```bash
cd <workspace> && rm -rf <site-name>
```

Stop a site you are not using with `ddev stop <site-name>`; its data is kept and `ddev start <site-name>` brings it back.

## Known limits

- **Joomla and PHPUnit.** `~/.cv.json` points `jcv` at the site database, and the headless tests rebuild the database they are given, so the `phpunit` wrapper refuses on Joomla. Run PHPUnit on WordPress (the maintainers' only PHPUnit site).
- **Joomla 6** is not supported (see above). **Drupal 12** is not covered: the constraint is `^11`.
- **The script is for one site per run**, on macOS or Linux with DDEV. It does not set up Windows.
- **No Agileware ESR patches** are included (see the version section).
- **Not a real mail server.** Nothing the site sends leaves the machine.
- **Latest tools.** `cv.phar` is the current release and PHPUnit is the newest 9.6.x, so a rebuild months later may differ from the maintainers' sites (cv 0.3.72 and a PHPUnit 9.6 phar were in use).
- **Browser tests need time.** A full run takes about 8 minutes on WordPress. Never run two test jobs on one site, and do not deploy while one runs.

## What was verified, and what was not

On 2026-11-15 the script was run from nothing for each CMS (CiviCRM 6.19.5, extension 2.1.0, one site at a time). Each build finished, `cv ext:list` (`jcv` on Joomla) showed the extension installed, and the browser tests ran on it:

| CMS | Build | Browser tests (`CMS=... npx playwright test`) | Seed scripts |
|---|---|---|---|
| WordPress | Passed after fixes to the settings helper and the `localhost` address (see below) | 21 passed in the first subset run, 1 flaky | Passed, including a second run, `move-election` and `links` |
| Drupal 11 | Passed after turning off Composer's insecure-package block (see below) | 11 of 13 projects pass; two failures, see [`../testing/playwright.md`](../testing/playwright.md) | Passed, including a second run |
| Joomla 5.4 | Passed first time; CiviCRM installs through the Joomla command line | All 13 projects pass (a few specs are skipped by design) | Passed; `ELECTIONS_SEED_LINK_STYLE=joomla` links open |

Not verified:

- CiviCRM 6.16.x on Drupal 11 and Joomla 5.4, and CiviCRM 6.20 on any CMS.
- PHPUnit on Drupal and Joomla (the maintainers do not run it there).
- Mail reaching Mailpit through `localhost:1025` on Drupal and Joomla.
- `shellcheck` on the scripts (not available); `bash -n` passes.
- A second build of the same site name after `ddev delete` (done by hand for each rebuild during the check).

## Known limits found when the kit was first run

- **Drupal: Composer's security blocking is switched off.** Every PhpSpreadsheet 5.x release that CiviCRM 6.19.5 allows has published security advisories, so `composer require civicrm/civicrm-core` fails with Composer's default settings (`audit.block-insecure`). The script sets `audit.block-insecure false` in the test site's `composer.json` and prints a warning. This is acceptable for a throwaway local test site only. Never copy it to a real Drupal site: update CiviCRM to a release that requires a fixed PhpSpreadsheet instead.
