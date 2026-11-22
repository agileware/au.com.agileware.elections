# DDEV test site: Drupal 11

Written from the existing setup, 2026-11-15. The kit builds a new site of this kind from nothing (`create-site.sh <cms>`); that path was followed on 2026-11-15 and is described in [`../ddev-kit/README.md`](../ddev-kit/README.md). The rest of this page describes the maintainers' existing site and was written from it.

The Drupal site (`elections2-drupal`) runs the Playwright suite on Drupal 11 with Drupal's own default front end theme. PHPUnit is not run here (no test database): run it on the WordPress site and on a Drupal site built by the DDEV kit ([../testing/phpunit.md](../testing/phpunit.md)).

## What the finished site looks like

| Item | Value |
|---|---|
| DDEV project type | `drupal11`, docroot `web` (the Composer project root is the site folder) |
| Address | `https://elections2-drupal.ddev.site` (inside the container: `http://localhost`) |
| Stack | PHP 8.3, MariaDB 11.8, nginx-fpm; Node 24 with corepack enabled |
| Drupal | 11.4.11 (`composer.lock`) |
| CiviCRM | 6.19.2, installed by Composer: `civicrm/civicrm-core`, `civicrm/civicrm-drupal-8`, `civicrm/civicrm-packages`, `civicrm/civicrm-asset-plugin` |
| Drush | 13.8, in `vendor/bin/drush` |
| Front end theme | Olivero, Drupal's default (the active theme is not read from the database here) |
| CiviCRM module | `web/modules/contrib/civicrm` |
| CiviCRM assets | `web/libraries/civicrm`, put there by the asset plugin (`extra.civicrm-asset.path` in `composer.json`) |

`composer.json` asks for `~6.16` for the three CiviCRM packages, so a new `composer update` can move the site to a later 6.x. To pin a site to 6.16 for a comparison, give Composer a `~6.16.0` constraint instead. Not verified: that this resolves cleanly with Drupal 11.4.

## Where things are

| What | Where |
|---|---|
| CiviCRM settings file | `web/sites/default/civicrm.settings.php` |
| Custom extensions directory | `web/CRM/extensions`, set in the settings file with `extensionsDir` (`/var/www/html/web/CRM/extensions`) and `extensionsURL` |
| Deployed extension | `web/CRM/extensions/au.com.agileware.elections` |
| CiviCRM command | `cv`, from `/var/www/html/web` |
| Drupal command | `vendor/bin/drush` from `/var/www/html` (the project root), for example `ddev exec vendor/bin/drush status` |
| CiviCRM files | `web/sites/default/files/civicrm` (templates, uploads, logs) |
| Configuration sync folder | `web/sites/default/files/sync` (DDEV's default in `settings.ddev.php`) |
| Mail catcher | Mailpit: `http://elections2-drupal.ddev.site:8025` or `https://elections2-drupal.ddev.site:8026` |

The settings file sets its base address from the request host in the same way as on WordPress (`http://localhost` inside the container, the `ddev.site` address otherwise) and carries these lines:

```php
$civicrm_setting['domain']['extensionsDir'] = '/var/www/html/web/CRM/extensions';
$civicrm_setting['domain']['extensionsURL'] = $elections_dev_base . '/CRM/extensions';
```

`cv` finds the site from the working directory, so always run it from `web`:

```bash
ddev exec 'cd /var/www/html/web && cv flush'
```

The `cv` and `phpunit` phars are in `.ddev/homeadditions/bin/`, as on the other sites.

## Mail

Drupal sends through PHP's mail function, which DDEV routes to Mailpit; nothing is delivered. Not verified: the CiviCRM outgoing mail setting on this site, which is stored in the database. The Playwright specs that send mail run on the WordPress site's setup script, which redirects CiviCRM's mail to the database; set the same with `cv api4 Setting.set '{"values":{"mailing_backend":{"outBound_option":5}}}'` if a Drupal run needs it.

## Roles, accounts and permissions the tests need

`tests/playwright/fixtures/setup-accounts-drupal.sh` makes the roles and accounts with Drush. It reads the user names and e-mail addresses from `fixtures/test-users.json`, makes random passwords (kept in the git-ignored `fixtures/.generated/test-passwords.json`), and is safe to run again. Run it from the project root, where `vendor/bin/drush` exists:

```bash
ddev exec 'bash /var/www/html/web/CRM/extensions/au.com.agileware.elections/tests/playwright/fixtures/setup-accounts-drupal.sh'
```

| Role | Permissions |
|---|---|
| `elections_manager` | access CiviCRM, administer Elections, view Elections, access AJAX API, view all contacts |
| `elections_limited` | the same without view all contacts |
| `elections_member` | view Elections, access AJAX API, access uploaded files, profile view, profile edit |

Accounts: `admin` (role `administrator`), `manager`, `limited` and `member` (in the three roles above). Permission names in Drupal are CiviCRM's own labels, with their spaces and capital letters (`administer Elections`). The script uses `drush role:perm:add`.

The council election and the extension's own tests that need it are seeded separately: see [`../testing/seeding.md`](../testing/seeding.md). `setup-environment.sh` is WordPress-only (it uses wp-cli); on Drupal run the seed script with `cv` from `web` (the same script the specs use to reseed):

```bash
ddev exec 'cd /var/www/html/web && cv scr CRM/extensions/au.com.agileware.elections/tests/playwright/fixtures/seed-council.php'
```

## Front end pages the extension needs

Drupal needs no pages, shortcodes or menu items: CiviCRM registers its routes with Drupal's router, so the election screens are at their CiviCRM paths.

| Page | Address |
|---|---|
| One election | `/civicrm/elections/view?eid=<id>` |
| Election list | `/civicrm/elections` |
| Nominate, accept, vote | `/civicrm/elections/nominations/create`, `/civicrm/elections/nominations/accept`, `/civicrm/elections/vote` |
| Back end | The same `/civicrm/elections` paths, for a person with `administer Elections` |

Give the roles that take part the CiviCRM permissions `view Elections` and `access AJAX API` in People > Permissions, plus the profile and uploaded files permissions. [`../../docs/setup.md`](../../docs/setup.md) lists them for site owners.

## Drupal gotchas

- **Olivero sets button heights.** The extension's back end action buttons carry a fixed height to look right under Drupal's theme; front end buttons are checked at a minimum of 36 px by the page walk. Check any new button on Drupal.
- **Run Drush from the project root, `cv` from `web`.** They look for different things.
- **A refused page returns 403 or 500**: Drupal from CiviCRM 6.19, so the smoke spec accepts either. See [civicrm-versions.md](civicrm-versions.md).
- The deploy command runs `cv flush` before and after enabling the extension; one failure on the first run after a flush is a known flake and the retry passes.
- Olivero is removed from Drupal 12 core. Recheck the default theme when Drupal 12 is targeted.

## Build it from scratch

The script and files are described in [`../ddev-kit/README.md`](../ddev-kit/README.md). As an outline, from what is on the site:

1. `ddev config --project-type=drupal11 --docroot=web --php-version=8.3`, then create the project with Composer (`composer create-project drupal/recommended-project`) and add `civicrm/civicrm-core`, `civicrm/civicrm-drupal-8`, `civicrm/civicrm-packages` and `civicrm/civicrm-asset-plugin`, with their plugins allowed in `composer.json`.
2. Install Drupal (`ddev drush site:install` with the standard profile; the standard profile makes Olivero the front end theme).
3. Install CiviCRM into the Drupal database with `cv core:install` and enable the `civicrm` module.
4. Create `web/CRM/extensions` and add the `extensionsDir` and `extensionsURL` lines to `web/sites/default/civicrm.settings.php`.
5. Put the `cv` and `phpunit` phars in `.ddev/homeadditions/bin/`, add `deploy-elections`, add the Chromium files from [browser-testing-setup.md](browser-testing-setup.md).
6. Deploy, run `setup-accounts-drupal.sh`, seed the election.

The step order is inferred from the files on the site; it was not recorded when the site was built.
