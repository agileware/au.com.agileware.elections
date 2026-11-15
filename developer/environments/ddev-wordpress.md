# DDEV test site: WordPress

Written from the existing setup, 2026-11-15; not yet followed from scratch.

The WordPress site (`elections2`) is the main development site: PHPUnit, the upgrade test and the Playwright suite all run here. The review site `election-test` is built the same way on a newer CiviCRM; see [beekeepers-review-site.md](beekeepers-review-site.md).

## What the finished site looks like

| Item | Value |
|---|---|
| DDEV project type | `wordpress`, docroot `httpdocs` |
| Address | `https://elections2.ddev.site` (inside the container: `http://localhost`) |
| Stack | PHP 8.3, MariaDB 11.8, nginx-fpm, DDEV 1.25 with Mutagen |
| WordPress | 7.1.4 (found in `httpdocs/wp-includes/version.php`) |
| CiviCRM | 6.16.5, installed as a WordPress plugin from the Agileware ESR build, in `httpdocs/wp-content/plugins/civicrm/` |
| Front end theme | Twenty Twenty-Five (Astra is also installed; Not verified: which one is active) |
| Permalinks | `/%postname%/` (required for clean CiviCRM addresses) |
| Bundled extension | 1.3.2 in `wp-content/plugins/civicrm/civicrm/ext/`, left untouched |

The CiviCRM plugin is the Agileware "ESR" build of CiviCRM for WordPress, a Bitbucket archive of `agileware/civicrm-wordpress-esr` at commit `b06778e`. It stays on 6.16.x, which is why this site is the "older CiviCRM" site.

## Where things are

| What | Where |
|---|---|
| CiviCRM settings file | `httpdocs/wp-content/uploads/civicrm/civicrm.settings.php` |
| Custom extensions directory | `httpdocs/CRM/extensions`, set in the settings file with `extensionsDir` (`/var/www/html/httpdocs/CRM/extensions`) and `extensionsURL` |
| Deployed extension | `httpdocs/CRM/extensions/au.com.agileware.elections` |
| CiviCRM command | `cv`, from `/var/www/html/httpdocs` |
| WordPress command | `ddev wp <command>` (wp-cli is part of DDEV) |
| Mail catcher | Mailpit: `http://elections2.ddev.site:8025`, or `https://elections2.ddev.site:8026` |

The settings file picks its base address at run time: when the request host is `localhost` (the Playwright run inside the container) it uses `http://localhost`, otherwise `https://elections2.ddev.site`. This is why `BASE_URL=http://localhost` works from inside the container and the site also works from the browser on the host.

The two settings that make the custom directory work:

```php
$civicrm_setting['domain']['extensionsDir'] = '/var/www/html/httpdocs/CRM/extensions';
$civicrm_setting['domain']['extensionsURL'] = $elections_dev_base . '/CRM/extensions';
```

`$elections_dev_base` is defined at the top of the same file.

## Commands

`cv` has to be run with the docroot as the working directory:

```bash
ddev exec 'cd /var/www/html/httpdocs && cv api4 Election.get'
```

The `cv` and `phpunit` phars are in the site's `.ddev/homeadditions/bin/`, which DDEV copies into the container's home so both are on the `PATH` (`cv` v0.3.72, PHPUnit 9.6.38 at the time of writing). They survive `ddev stop` and `ddev start` because they are part of the project.

Useful commands:

| Task | Command |
|---|---|
| Flush the CiviCRM cache | `ddev exec 'cd /var/www/html/httpdocs && cv flush'` |
| Upgrade the extension database | `ddev exec 'cd /var/www/html/httpdocs && cv ext:upgrade-db'` |
| Run a PHP script inside CiviCRM | `ddev exec 'cd /var/www/html/httpdocs && cv scr <file>'` |
| List WordPress users | `ddev wp user list` |
| Reset a password | `ddev wp user update <login> --user_pass=<new>` |
| PHPUnit | see [`../testing/phpunit.md`](../testing/phpunit.md) |

## Mail

DDEV's Mailpit catches everything WordPress and CiviCRM send through PHP's mail function or SMTP port 1025, and delivers none of it. The Playwright setup script switches CiviCRM's outgoing mail to "redirect to database" (option 5) so tests do not depend on a mail server: `setup-environment.sh` runs `cv api4 Setting.set` for `mailing_backend`. The review site sends to Mailpit (SMTP to `localhost:1025`) so a reviewer can read the emails.

## Roles, accounts and pages the tests need

`tests/playwright/fixtures/setup-environment.sh` creates everything from the command line and is safe to run again. Run it once on a new site, from the deployed copy of the extension:

```bash
ddev exec 'cd /var/www/html/httpdocs/CRM/extensions/au.com.agileware.elections/tests/playwright && bash fixtures/setup-environment.sh'
```

It makes:

| Item | Detail |
|---|---|
| Role `elections_manager` | Runs elections: `access_civicrm`, `administer_elections`, `view_elections`, `access_ajax_api`, `view_all_contacts` |
| Role `elections_limited` | An organiser without View All Contacts: the same but no `view_all_contacts` |
| Role `elections_member` | Takes part while signed in: `view_elections`, `access_ajax_api`, `access_uploaded_files`, `profile_view`, `profile_edit` |
| Users | One user for each role, plus the WordPress administrator `admin`; names, e-mail addresses and the test passwords are in `tests/playwright/fixtures/test-users.json` and are for local test sites only |
| Page `election` | Shortcode `[civicrm component="electioninfo" hijack="0"]` |
| Page `elections` | Shortcode `[civicrm component="elections" hijack="0" action="visible" viewpageid="<election page id>"]` |
| Council election | Seeded with `fixtures/seed-council.php`: see [`../testing/seeding.md`](../testing/seeding.md) |

Capability names are the "munged" forms that CiviCRM's WordPress permission check looks for: lower case, runs of non-letters replaced by an underscore (`administer_elections`, not `administer Elections`).

## WordPress gotchas

- **Pretty permalinks and the CiviCRM base page are required.** Without them, election pages are reached as `/?page=CiviCRM&q=civicrm/elections/view` and WordPress shows them inside the blog template. The tests use `/civicrm/elections/view/?eid=<id>`. `WP_CLEAN_URLS=0` selects the old form for a site without pretty permalinks. The user guide says the same in [`../../docs/setup.md`](../../docs/setup.md).
- **A signed-in session beats a personal link.** In one browser, a WordPress administrator session overrides the `cid` and `cs` of a personal link. Sign out before looking at the front end as a voter.
- **CiviCRM front end pages need `is_public` on WordPress.** From CiviCRM 6.19 a page is a front end page only if its route is public; the extension sets it on every route people use. See [civicrm-versions.md](civicrm-versions.md).
- On the front end, WordPress can print `Attempt to read property "ID" on null` from CiviCRM's `WordPress.php` when no CiviCRM base page exists. It is not an extension error: create the base page.
- DDEV's nginx handles the rewrites, so wp-cli's warning about `.htaccess` after `wp rewrite structure ... --hard` can be ignored.

## Front end pages the extension needs

| Page | Address on this site |
|---|---|
| Election list | `/elections/` (the page with the listing shortcode) |
| One election | `/civicrm/elections/view/?eid=<id>` through the CiviCRM base page |
| Nominate, accept, vote | `/civicrm/elections/nominations/create/`, `/civicrm/elections/nominations/accept/`, `/civicrm/elections/vote/` (all with `eid`) |
| Back end | `/wp-admin/admin.php?page=CiviCRM&q=civicrm/elections&reset=1` |

The same routes, with personal links (`cid` and `cs` added), are what the persona tests use. See [`../testing/personas.md`](../testing/personas.md).

## Build it from scratch

[`../ddev-kit/README.md`](../ddev-kit/README.md) has the script and files that build this site. As an outline, from what the existing site contains:

1. `ddev config --project-type=wordpress --docroot=httpdocs --php-version=8.3` and `ddev start`.
2. Download WordPress and install it with wp-cli (`wp core download`, `wp config create`, `wp core install`).
3. Unpack the CiviCRM plugin into `httpdocs/wp-content/plugins/civicrm` and activate it.
4. Install CiviCRM's database with `cv core:install` (wp-cli's own CiviCRM installer needs a zip).
5. Put the `cv` phar (and `phpunit`) in `.ddev/homeadditions/bin/`.
6. Create `httpdocs/CRM/extensions` and add the two `extensionsDir` and `extensionsURL` settings to `civicrm.settings.php`.
7. Set permalinks, check the CiviCRM base page exists and `wpBasePage` names it.
8. Add `.ddev/commands/host/deploy-elections`, then the Chromium files from [browser-testing-setup.md](browser-testing-setup.md).
9. Deploy, then run `setup-environment.sh`.

Steps 1 to 6 are from the project's earlier notes and the files on the site, not a recorded transcript.
