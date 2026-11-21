# Differences between CMSs and CiviCRM versions

This reference page lists the known behavioural differences between WordPress, Drupal and Joomla, and between CiviCRM 6.16 and 6.19, that affect the tests or the code. Add a row when you find a new one, with the version it appeared in, so it is not found again. The versions of each site and how to get them are in [../environments/civicrm-versions.md](../environments/civicrm-versions.md).

The rule that follows: **a pass on one CMS or one CiviCRM version is not a pass.** The extension supports CiviCRM 6.16 and later. The main DDEV site is on 6.16 (an ESR build); the Drupal and Joomla sites, the maintainer's review site and CI are on 6.19.

## CiviCRM 6.16 and 6.19

| Behaviour | 6.16 | 6.19 | What the extension and tests do |
|---|---|---|---|
| The message of an exception thrown in an APIv4 AJAX call (for example drag and drop on positions) | Shown to the person on the development site (cause not confirmed: the check in `CRM/Api4/Page/AJAX.php` is the same in 6.16.5 and 6.19.4, so the permissions of the person may explain it) | Hidden behind "Sorry an error occurred (Error ID ...)" unless the exception sets `show_detailed_error` | `CRM_Elections_Exception` sets it. Every refusal the extension raises uses it, so the person reads the reason. Never throw a bare exception for something an AJAX call can refuse; check the message on both versions. |
| Whether a WordPress page counts as a front end page | Any page | Only if the route's menu item has `is_public`; otherwise forms post to `wp-admin` and anonymous voters are sent to the login | `is_public` is set on the front end routes in `xml/Menu/elections.xml`. It changes which address is made, not who is allowed in: CiviCRM checks the route's permission first. |
| HTTP status of a refused page | 403 | The unhandled exception handler can set 500 after the 403 | `frontend/smoke.spec.ts` accepts 403 or 500 on the pages where it matters, and the refusal's text is checked as well. `electionsSetHttpStatus()` in `elections.php` sends the status on each CMS. |
| Routes containing `/ajax/` (a CiviCRM security fix in 6.19.3 and 6.16.6) | Not guarded in 6.16.5 | Guarded against CSRF; each handler needs its own named route | The extension has no `/ajax/` routes. Nothing to do; do not add one. |
| SearchKit templates | Read for the lists | Not read when the lists were built | The user interface standards say to check the lists on 6.19 again ([../standards/ui-standards.md](../standards/ui-standards.md)). |
| Adding or removing a mixin (for example `scan-classes`) | Leaves cached lists in `civicrm_cache` (`mixinScan`, `mixinBoot`); `cv flush` can then crash | The same | Not verified again here: empty those rows before flushing if `cv flush` fails after a mixin change. |

## WordPress, Drupal and Joomla

| Area | WordPress | Drupal 11 | Joomla 5.4 |
|---|---|---|---|
| Address of a front end page (`tests/playwright/fixtures/cms.ts`) | `/civicrm/elections/view/?eid=1` through the CiviCRM base page; `?page=CiviCRM&q=...` without pretty permalinks (`WP_CLEAN_URLS=0`) | `/civicrm/elections/view?eid=1` | `/index.php?option=com_civicrm&task=civicrm/elections/view&eid=1` |
| Address of a back office page | `/wp-admin/admin.php?page=CiviCRM&q=...` | the same path as the front end | `/administrator/index.php?option=com_civicrm&task=...` |
| Set-up the site needs | Pretty permalinks, the CiviCRM base page, and pages with the shortcodes ([`docs/setup.md`](../../docs/setup.md)) | Menu routes: no pages | Component routing: no pages |
| `cv` command | `cv` | `cv` | `jcv`: it runs CiviCRM without Joomla, in test mode against the site database (so PHPUnit must never run there, see [phpunit.md](phpunit.md)) |
| CiviCRM's `CIVICRM_UF` value | `WordPress` | `Drupal8` | `Joomla` (but `UnitTests` under `jcv`) |
| Base URL in the CiviCRM settings | The site | The site | The site plus `/administrator/` (the seed's `links.php` removes it) |
| Test sessions | Administrator, manager, limited organiser, member | The same four | Administrator only; six back end specs skip ([playwright.md](playwright.md)) |
| Front end and back end sessions | One | One | Separate: an administrator is not signed in on the front end, so anything an administrator loads from a back office page must use the back office address (the logo preview on the results page does) |
| Heading of a back office page | CiviCRM's `h2` | The theme's `h1` | The theme's `h1` |
| Breadcrumb | CiviCRM's own, current page not a link | The same (the theme draws one of its own above, so specs scope to `.crm-container .breadcrumb`) | The page template links every step, so the current page is a link to `#` with `aria-current="page"` inside it; `Frame::breadcrumbLinks()` builds it. A recorded exception to the interface standard ([../standards/ui-standards.md](../standards/ui-standards.md)). Standalone behaves like Joomla. |
| Status code of a refused page | `status_header()` | Set on the response by an event listener | Set on the application |
| Profile pictures of candidates | WordPress avatar | The user's picture | None |
| Login address for "log in first" | The `elections_get_login_page_url` filter | CiviCRM's login URL | CiviCRM's login URL (not customised) |
| Permissions | Capabilities with names in the munged form (`administer_elections`) on roles ([`docs/roles_and_permissions.md`](../../docs/roles_and_permissions.md)) | Permission names as written (`administer Elections`) on roles | Set through Joomla access levels, which is why the suite has no manager session there |
| PHP notices | No known problem | No known problem | Joomla shows deprecation notices on the page, so the PHP-output check catches old CiviCRM method names |
| Admin theme | No known problem | Tall: scroll table rows into view before computing drag positions in a spec | No known problem |
| Default front end theme | Twenty Twenty-Five (CI uses Astra) | Olivero | Cassiopeia |

Two more that apply everywhere:

- **A CMS session wins over a personal link.** An administrator signed in to the site in the same browser acts as the administrator whatever link they open. Sign the administrator out before looking at the front end as a voter ([manual-browser-testing.md](manual-browser-testing.md)). Seen on WordPress; not separately checked on the other two.
- **Themes set button heights**, so the back end action buttons (Submit, Back) have one fixed height in `css/electionforms.css`, scoped with `:has(.election-back)`. Do not widen that rule: it once reached the front end ballot, where buttons must stay at least 36 px high at phone width (the page walk checks).

## How these show up when you work

- A spec that passes on WordPress and fails on Joomla or Drupal is usually one of the rows above (an address form, a heading level, a session, a scroll position) before it is a bug. Use `frontUrl()` and `adminUrl()`; never hard-code a path.
- A spec that passes on 6.16 and fails on 6.19, or the other way, is usually one of the first table. Check the message, the page type and the status code.
- New or changed code that touches a screen: run the specs on all three CMSs, and on both CiviCRM versions ([README.md](README.md)).
