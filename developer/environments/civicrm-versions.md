# CiviCRM versions

The extension supports CiviCRM 6.16 and later, and is tested up to 6.19.4. This page says which test site runs which version, how to pick one for a new site, and where the two versions behave differently.

## What the extension declares

| Where | Says |
|---|---|
| `info.xml`, `<compatibility>` | `6.16` |
| `README.md`, Prerequisites | 6.16 or greater, verified compatible up to 6.19.4; version 1.x of the extension is for older CiviCRM |
| `info.xml`, `<requires>` | The extensions `org.civicrm.afform` and `org.civicrm.search_kit` |

Version 2 raised the minimum from 5.73 to 6.16 (a maintainer decision taken while version 2 was built, tested first on 6.16.5). The code and templates are written for Smarty 5.

## Which site runs which version

| Site | CiviCRM | How it got there |
|---|---|---|
| `elections2` (WordPress) | 6.16.5 | The Agileware ESR plugin build; stays on 6.16.x |
| `elections2-drupal` | 6.19.2 | Composer (`~6.16` constraint, locked at 6.19.2) |
| `elections2-joomla` | 6.19.2 | The CiviCRM Joomla package |
| `election-test` (WordPress) | 6.19.4 | The stock WordPress release |
| GitHub Actions | The newest stable at the time of the run | The shared workflows build a WordPress image and install CiviCRM into it; see [`../testing/ci.md`](../testing/ci.md) |

All versions come from `civicrm-version.php` or `composer.lock` in the site folders, read on 2026-11-15.

The sites were not moved to 6.16.6 or 6.19.3. That was considered for a security fix and dropped: the extension has no `/ajax/` routes, which the fix affects (see the table below), and moving 6.16.5 to 6.16.6 would not have closed the gap between 6.16 and 6.19 anyway.

## Choosing a version for a new site

- **Test on both lines.** A change that passes on 6.16.5 alone has not passed: every difference in the table below was first met on 6.19 after the work had passed on 6.16.5. Run the checks for a change on at least one 6.19 site as well (any of the three, for the browser suite).
- A new WordPress site for the main suite: use 6.16, so PHPUnit runs on the supported minimum. [`../ddev-kit/README.md`](../ddev-kit/README.md) lists the versions the build script installs.
- A new Drupal or Joomla site: use the newest 6.19 the CMS package supports. Joomla needs Joomla 5.4 for CiviCRM 6.19.2: see [ddev-joomla.md](ddev-joomla.md).
- Before a release, run the browser suite on each CMS and PHPUnit on the WordPress site, and look at the review site on 6.19.
- Do not mix up the sites' roles: PHPUnit runs only on the WordPress site (6.16), so it does not exercise 6.19 code paths. The Playwright suite on Drupal and Joomla and in CI is what covers 6.19.

## Differences between 6.16 and 6.19

| Behaviour | 6.16 | 6.19 | What the extension does |
|---|---|---|---|
| Message of an exception raised inside an APIv4 AJAX call (position drag and drop, edit in place) | Shown to the person on the development site (cause not confirmed: `CRM/Api4/Page/AJAX.php` has the same `show_detailed_error` and `view debug output` check in 6.16.5 and 6.19.4, so the permissions of the person may explain the difference) | Hidden behind "Sorry an error occurred (Error ID ...)" unless the exception carries `show_detailed_error` | `CRM_Elections_Exception` sets it. Every refusal meant for a person uses it, never a bare exception. `AdminRulesTest::testRefusalsCarryTheirReasonToAjaxCallers` checks it |
| Whether a WordPress page counts as a front end page | Any page | Only if the route has `is_public`; otherwise forms post to `wp-admin` and anonymous voters are sent to the login | `is_public` is set on every route in `xml/Menu/elections.xml` that people open (ten routes: list, view, nominate, accept, reject, vote, candidate, candidate statement, results logo, summary) |
| HTTP status of a refused page | 403 | 500 on WordPress and Drupal (suspected cause, not confirmed: the unhandled exception handler sets 500 after the 403; the call is in both versions) (Joomla returns 500 on both versions) | The smoke spec accepts 403 or 500 (`specs/frontend/smoke.spec.ts`) |
| Drag and drop of SearchKit rows | Not reported as a problem | Refused unless the entity says how weights are grouped | `ElectionPosition` declares `@groupWeightsBy election_id` |
| Routes containing `/ajax/` (CIVI-SA-2026-49) | Not guarded in 6.16.5; guarded from 6.16.6 | Guarded against cross-site requests from 6.19.3 (each handler needs its own named route) | Nothing to change: the extension has no `/ajax/` routes |
| Colour of error and warning messages in the back end | Not reported as a problem | None of their own (a refusal looked like a success message) | `css/electionforms.css` styles `.messages.crm-error` itself. Not verified: the cause is from the earlier working notes; only the CSS rule was checked |

Not verified: the 403 column for 6.16 on each CMS was recorded in the earlier notes and is not re-run here; the smoke spec's own comment gives the 6.19 behaviour.

The 6.19 sites here are 6.19.2 (Drupal, Joomla) and 6.19.4 (review site, CI): the cross-site guard is on the second pair only.

## Cache trap on both versions

Adding or removing a mixin in `info.xml` leaves cached lists in the `civicrm_cache` table (`mixinScan` and `mixinBoot`), and `cv flush` can then crash. On a test site, empty the cache table first, then deploy:

```bash
ddev exec 'mysql -udb -pdb db -e "TRUNCATE civicrm_cache"'
```

Then run `ddev deploy-elections`. (The Joomla site's deploy command already truncates this table every time.) Not verified: the exact cache group names; the earlier notes say only "empty those rows".

## Record new differences

When a new difference turns up, add a row here in the same pull request as the fix, with the version it appeared in. That is how the table above grew.
