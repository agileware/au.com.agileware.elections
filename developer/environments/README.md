# Test environments

The extension is tested on DDEV sites, one for each CMS it supports, plus one site kept for reviewing elections by hand. This section describes those sites: what they are, how they sit next to the extension repository, how a code change reaches them, and how to build a new one.

## What is in this section

| Page | Kind | Use it to |
|---|---|---|
| [ddev-wordpress.md](ddev-wordpress.md) | Guide | Understand or rebuild the WordPress test site |
| [ddev-drupal.md](ddev-drupal.md) | Guide | Understand or rebuild the Drupal 11 test site |
| [ddev-joomla.md](ddev-joomla.md) | Guide | Understand or rebuild the Joomla 5 test site |
| [civicrm-versions.md](civicrm-versions.md) | Reference | Choose a CiviCRM version; see how 6.16 and 6.19 differ |
| [browser-testing-setup.md](browser-testing-setup.md) | Guide | Keep Chromium working in a DDEV site across stop and start |
| [beekeepers-review-site.md](beekeepers-review-site.md) | Guide | Use the manual review site with eight sample elections |

Scripts that build a site from scratch are in [`../ddev-kit/`](../ddev-kit/README.md). Seeding test data and running the suites are in [`../testing/`](../testing/README.md).

## Workspace layout

The extension repository and the DDEV sites sit side by side in one workspace folder:

```text
<workspace>/
├── au.com.agileware.elections/    the extension repository (a git clone)
├── elections2/                    DDEV site: WordPress, CiviCRM 6.16.5
├── elections2-drupal/             DDEV site: Drupal 11, CiviCRM 6.19.x
├── elections2-joomla/             DDEV site: Joomla 5.4, CiviCRM 6.19.x
└── election-test/                 DDEV site: WordPress, CiviCRM 6.19.4 (manual review)
```

The side-by-side layout is not a convention: the `deploy-elections` command of each site copies from `$DDEV_APPROOT/../au.com.agileware.elections/`, so the repository must be a sibling of the site folder and carry exactly that name. A site folder in another place needs its copy of the command edited.

The folder names of the sites are the DDEV project names; `elections2` is the one the other pages call "the WordPress site". Use whatever names you like for new sites, but keep the repository name.

## The four sites

| Site | CMS | CiviCRM | Docroot | Purpose |
|---|---|---|---|---|
| `elections2` | WordPress 7.1 | 6.16.5 (ESR build) | `httpdocs` | Main development site: PHPUnit, Playwright, upgrade test |
| `elections2-drupal` | Drupal 11.4 | 6.19.2 (Composer) | `web` | Playwright on Drupal |
| `elections2-joomla` | Joomla 5.4 | 6.19.2 (Joomla package) | `.` (the project folder) | Playwright on Joomla |
| `election-test` | WordPress 7.1 | 6.19.4 (stock release) | `httpdocs` | Manual review as an invented association |

All four use PHP 8.3, MariaDB 11.8 and nginx-fpm, set in each `.ddev/config.yaml`. Versions are those found on the sites on 2026-11-15; the pages for each CMS say where to look them up again.

The extension requires CiviCRM 6.16 or later and works up to 6.19.4: see [civicrm-versions.md](civicrm-versions.md) for why two versions are kept.

## The deploy loop

Every site has a host command, `.ddev/commands/host/deploy-elections`. Run it from the site folder after every code change:

```bash
cd <workspace>/elections2 && ddev deploy-elections
```

It does four things:

1. Copies the working copy of `../au.com.agileware.elections/` (whatever branch is checked out, committed or not) with `rsync -a --delete` into `<docroot>/CRM/extensions/au.com.agileware.elections/`. These paths are left alone, so anything installed there survives the copy: `.git`, `.github`, `node_modules`, `.generated`, `playwright-report` and `test-results`. This is why `npm install` in the deployed `tests/playwright` is done once, and why the personal links the setup writes to `.generated` are not wiped.
2. Enables the extension (`cv ext:enable au.com.agileware.elections`).
3. Runs the database upgrade (`cv ext:upgrade-db`).
4. Clears the CiviCRM cache (`cv flush`).

On Joomla the command uses `jcv` and, because `jcv` builds its caches outside Joomla with the wrong site address, empties the `civicrm_cache` table and the compiled templates instead of running `cv flush`. See [ddev-joomla.md](ddev-joomla.md).

`CRM/extensions` is the site's custom extensions directory, set in `civicrm.settings.php` (`extensionsDir` and `extensionsURL`). The CiviCRM package on the WordPress site also carries its own copy of the extension (version 1.3.2 in `wp-content/plugins/civicrm/civicrm/ext/`). Leave that copy alone: CiviCRM loads an extension from the custom directory in preference to the bundled one, so the deployed working copy wins. Check which copy is live:

```bash
ddev exec 'cd /var/www/html/httpdocs && cv ev "echo CRM_Extension_System::singleton()->getFullContainer()->getPath(\"au.com.agileware.elections\");"'
```

The answer must end in `CRM/extensions/au.com.agileware.elections`.

## Working rules for the sites

- **Start only the sites you need.** A full browser run fails from overload when several sites run at once. `ddev start <name>` starts one.
- **Stop what you started** with `ddev stop <name>`. Never use `ddev poweroff`: it also stops sites that someone else is using. Stopping keeps a site's data; `ddev start <name>` brings it back.
- **Never run two test jobs on one site**, and do not deploy while a job runs: the deploy replaces the files being tested.
- **The review site belongs to the maintainer.** Do not change, reset or stop `election-test` without asking. See [beekeepers-review-site.md](beekeepers-review-site.md).
- Commands that touch CiviCRM run inside the web container (`ddev exec '...'`), from the CMS docroot. Where the docroot is differs by CMS; each CMS page says where.
- Test accounts and personal links are generated by scripts on the site and are never committed.

## Building a new site

[`../ddev-kit/README.md`](../ddev-kit/README.md) describes the script and the files that build a site for each CMS. The CMS pages here describe what the finished site looks like, so you can check a new site against it.
