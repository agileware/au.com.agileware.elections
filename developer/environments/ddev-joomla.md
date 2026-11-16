# DDEV test site: Joomla 5

Written from the existing setup, 2026-11-15. The kit builds a new site of this kind from nothing (`create-site.sh <cms>`); that path was followed on 2026-11-15 and is described in [`../ddev-kit/README.md`](../ddev-kit/README.md). The rest of this page describes the maintainers' existing site and was written from it.

The Joomla site (`elections2-joomla`) runs the Playwright suite on Joomla 5.4 with Joomla's default front end template. It is the most unusual of the three sites, because CiviCRM's command line tool cannot start Joomla itself.

## What the finished site looks like

| Item | Value |
|---|---|
| DDEV project type | `joomla`, docroot `.` (the project folder is the web root) |
| Address | `https://elections2-joomla.ddev.site` (inside the container: `http://localhost`) |
| Stack | PHP 8.3, MariaDB 11.8, nginx-fpm |
| Joomla | 5.4.10 (`libraries/src/Version.php`) |
| CiviCRM | 6.19.2, installed from the CiviCRM Joomla package (`administrator/components/com_civicrm/civicrm.zip` is the package, `civicrm/` the code) |
| Front end template | Cassiopeia, Joomla's default (`templates/cassiopeia`) |
| Joomla URLs | `sef` on, `sef_rewrite` off: addresses go through `/index.php` |

**Joomla 6 does not work with this CiviCRM.** Joomla 6.2 was tried first and dropped: the CiviCRM 6.19.2 Joomla installer and `cv` need classes that Joomla 6 removed (`Filesystem\File`) and a PSR logger that Joomla 6 does not ship. Joomla 5.4 is the supported pairing. Recheck when a later CiviCRM adds Joomla 6 support.

## Where things are

| What | Where |
|---|---|
| CiviCRM settings files | Two copies: `components/com_civicrm/civicrm.settings.php` (front end) and `administrator/components/com_civicrm/civicrm.settings.php` (back end) |
| Custom extensions directory | `CRM/extensions` in the web root, set in **both** settings files with `extensionsDir` (`/var/www/html/CRM/extensions`) and `extensionsURL` |
| Deployed extension | `CRM/extensions/au.com.agileware.elections` |
| CiviCRM command | `jcv`, from `/var/www/html` |
| Joomla command line | `php cli/joomla.php <command>` from `/var/www/html` |
| CiviCRM files | `media/civicrm` (`templates_c`, `ConfigAndLog`, `upload`, `custom`, `ext`) |
| Mail catcher | Mailpit: `http://elections2-joomla.ddev.site:8025` or `https://elections2-joomla.ddev.site:8026` |

The two settings files differ in one value, `CIVICRM_UF_BASEURL` (the front end file ends in `/`, the back end file in `/administrator/`). Both carry the custom directory lines; a change to one has to be made in the other. The files are mode 600; on a new site they come from the CiviCRM installer and are then edited.

## The `jcv` command

`cv` cannot boot Joomla. The site's `.ddev/homeadditions/bin/jcv` runs it without the CMS:

```bash
CIVICRM_UF=UnitTests exec php -d auto_prepend_file=$HOME/joomla-prepend.php $HOME/bin/cv "$@"
```

`CIVICRM_UF=UnitTests` makes CiviCRM boot headless against the site database. `joomla-prepend.php` (in `.ddev/homeadditions/`) makes Joomla's copy of `psr/log` available, because the CiviCRM package for Joomla leaves that library out and relies on the copy Joomla loads inside the web application. The plain `cv` phar is also in `homeadditions/bin/`; `jcv` calls it. 

Use `jcv` everywhere the other pages say `cv`, and set `CIVI_CV=jcv` when running Playwright (the specs run CiviCRM through `CIVI_CV`):

```bash
ddev exec 'cd /var/www/html && jcv ext:upgrade-db'
```

Because `jcv` runs outside Joomla, any cache it builds holds the wrong site address. For that reason `deploy-elections` on this site does not run `cv flush`; it empties the `civicrm_cache` table and the compiled templates and configuration, so the first web request rebuilds them with the real address:

```bash
ddev exec 'mysql -udb -pdb db -e "TRUNCATE civicrm_cache"; rm -rf /var/www/html/media/civicrm/templates_c/* /var/www/html/media/civicrm/ConfigAndLog/Config*'
```

(`db` and `db` are DDEV's standard database user, password and database name.) Do the same by hand after any `jcv` command that changes configuration.

## Mail

Joomla's mailer is `mail` (PHP's mail function), which DDEV routes to Mailpit. Not verified: the CiviCRM outgoing mail setting on this site (it is in the database).

## Accounts and permissions

`tests/playwright/fixtures/setup-accounts-joomla.sh` creates one account, a Super User, with the Joomla command line (`user:add`, or `user:reset-password` if it exists). Run it from the site root:

```bash
ddev exec 'bash CRM/extensions/au.com.agileware.elections/tests/playwright/fixtures/setup-accounts-joomla.sh'
```

The script reads the name and e-mail address from `fixtures/test-users.json` and makes a random password, kept in the git-ignored `fixtures/.generated/test-passwords.json` (see [Playwright](../testing/playwright.md)).

**There are no manager or participant sessions on Joomla.** CiviCRM permissions on Joomla are set through Joomla's access levels, not roles the script can create, so the global setup of the suite signs in only the administrator and six specs skip by design (election form, overview, permissions twice, smoke twice). Giving Joomla manager and participant sessions is planned for 2.2: see [`../roadmap/README.md`](../roadmap/README.md).

To let a Joomla group take part in elections, give it the CiviCRM permissions **view Elections** and **access AJAX API** under Users > Permissions, as [`../../docs/setup.md`](../../docs/setup.md) says for site owners.

## Front end pages the extension needs

| Page | Address |
|---|---|
| One election | `/index.php?option=com_civicrm&task=civicrm/elections/view&eid=<id>` |
| Election list | `/index.php?option=com_civicrm&task=civicrm/elections` |
| Back end | `/administrator/index.php?option=com_civicrm&task=civicrm/elections` |

Add a menu item of type CiviCRM if you want the screens in a menu. Not verified: whether this site has any menu items; the tests use the `index.php` addresses above (see `tests/playwright/fixtures/cms.ts`).

## Joomla gotchas

- **A refused page always returns 500**, whatever the CiviCRM version, so the smoke spec accepts 403 or 500.
- **Breadcrumbs take url and title pairs, not markup**, on Joomla and Standalone. Code that adds a breadcrumb must pass pairs.
- **Joomla 6 is not an option** for this CiviCRM (above).
- The deploy command runs `jcv ext:enable` and `jcv ext:upgrade-db`; if either fails, run it by hand and read the message.

## Build it from scratch

The script and files are described in [`../ddev-kit/README.md`](../ddev-kit/README.md). As an outline, from what is on the site:

1. `ddev config --project-type=joomla --docroot=.` with PHP 8.3, download Joomla 5.4 and install it.
2. Install the CiviCRM Joomla package through Joomla's extension installer; CiviCRM's own installer then creates its tables and the two `civicrm.settings.php` files.
3. Put `cv`, `jcv` and `joomla-prepend.php` in `.ddev/homeadditions/` (the first two in `bin/`).
4. Create `CRM/extensions` and add `extensionsDir` and `extensionsURL` to **both** settings files.
5. Add `deploy-elections` and the Chromium files from [browser-testing-setup.md](browser-testing-setup.md).
6. Deploy, run `setup-accounts-joomla.sh`, seed the election.

The order is inferred from the files on the site; it was not recorded when the site was built.
