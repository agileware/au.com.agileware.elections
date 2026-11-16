# Back end screens: the frame, the lists and the shared CSS

How a back end screen gets its breadcrumb and Back buttons, how the SearchKit lists are built, and how to add a list or a form. The rules for what a screen must contain are the user interface standards: [../standards/ui-standards.md](../standards/ui-standards.md). This page is how the code delivers them.

## The two kinds of back end screen

| Kind | Examples | Built from |
|---|---|---|
| **List** | Manage Elections, Positions, Candidates, Results, Audit Log | An Afform (`ang/afsearch*.aff.*`) holding a SearchKit table that runs a SavedSearch and SearchDisplay from `managed/` |
| **Page or form** | Election Overview, Add or Edit Election, Add Position, Add Candidate, Results Page, Edit Statement; and the dialogs Duplicate, Delete, Activate | A `CRM_Elections_Page_*` or `Form_*` class and a Smarty template |

Every full page has a breadcrumb above the heading (`Manage Elections > election name > page`), Back and Back to Manage Elections. A popup has none: it has its action button and Cancel and closes itself.

## The frame: `CRM/Elections/Helper/Frame.php`

| Method | Does |
|---|---|
| `trail($electionId, $between, $page)` | Builds the list of `[label, address]` pairs; the last has no address. `overviewTrail()` for the overview |
| `setBreadcrumb($trail)` | Assigns Smarty `breadcrumb`, in the shape the CMS theme draws: markup for WordPress and Drupal, links for Joomla and Standalone (`breadcrumbMarkup()`, `breadcrumbLinks()`). Labels are escaped here |
| `assign($page, $trail, $fallbackBack)` and `assignToForm()` | Breadcrumb, `backUrl`, `managerUrl`; a form also keeps `back` in a hidden field so a form shown again after a mistake still goes back to where the person came from |
| `backUrl($fallback)` | The `back` request value, else the HTTP referrer, else the fallback; only addresses on this site (`Utils::localUrlOrNull()`), never the page itself |
| `addSubmitButtons($form)` | Submit, plus Cancel in a popup |
| `isPopup()` | True when the request has `snippet` |
| `applyToListPage()` | Called from `elections_civicrm_pageRun()` for Afform pages: sets the breadcrumb for the five election lists and Manage Elections, and loads `js/election-breadcrumb.js` when the election is not in the query |
| `listUrl()`, `withElection()`, `positionsUrl()`, `candidatesUrl()`, `overviewUrl()`, `managerUrl()` | Addresses |

Templates: `templates/CRM/Elections/Frame/BackButtons.tpl` (Back, Back to Manage Elections; included in a form's footer when `!$isPopup`) and `BackToElection.tpl` (front end). The settings page (`civicrm/admin/setting/elections`) is CiviCRM's generic form, framed by `Helper/SettingsFrame.php`, which replaces CiviCRM's Save and Cancel with Submit and the Back buttons where it can.

### Why a list's address carries the election twice

The lists read the election from the address **after the `#`** (`#/?eid=12`), because Afform routes are Angular routes. A browser never sends the `#` part to the server and leaves it out of the referrer, so a Back link to a list built from the referrer would lose the election. Therefore:

- `Frame::listUrl()` puts the election in the query (`?reset=1&eid=12`) **and** after the `#`.
- `Frame::withElection()` rewrites any referrer that is one of the lists (`ELECTION_LISTS`) so Back keeps the election.
- The server can use the query value for the breadcrumb, and `ang/electionsLists.js` copies a query-only `eid` into the `#` before the list starts.
- A list opened with no election shows a notice with a link to Manage Elections instead of the rows of every election.

## The lists

| Route | Afform | SavedSearch and display | Entity |
|---|---|---|---|
| `civicrm/elections/manage` | `afsearchManageElections` | `ManageElections` / `ManageElections_Table_1` | `Election` |
| `civicrm/elections/position-list` | `afsearchElectionPositions` | `ElectionPositions` / `ElectionPositions_Table_1` (drag and edit in place) | `ElectionPosition` |
| `civicrm/elections/position-list-active` | `afsearchElectionPositionsLocked` | same search, display `ElectionPositions_Locked` (read only) | `ElectionPosition` |
| `civicrm/elections/candidate-list` | `afsearchElectionCandidates` | `ElectionCandidates` / `ElectionCandidates_Table_1` | `ElectionNomination` |
| `civicrm/elections/result-list` | `afsearchElectionResults` | `ElectionResults` / `ElectionResults_Table_1` | `ElectionResult` |
| `civicrm/elections/audit-log` | `afsearchElectionAudit` | `ElectionAudit` / `ElectionAudit_Table_1` | `ElectionAudit` |

- **Managed entities** (`managed/SavedSearch_*.mgd.php`) use `cleanup => unused` and `update => unmodified`: a site's own edits to a display are kept, so a changed default reaches a site only if it never edited the display. Clear the CiviCRM cache after deploying.
- **Permissions** on an Afform are `access CiviCRM` AND `administer Elections`; the APIv4 entities also declare `administer Elections`.
- **The Positions screen is two displays** (editable and read only). `civicrm/elections/positions` (a Smarty page) redirects to the right one: `Page_ElectionPositions` chooses the read only list when `canEdit()` is not true. Drag and drop and inline edits are APIv4 writes: `Civi/Api4/ElectionPosition.php` uses `SortableEntity` and guarded actions (`Action/ElectionPosition/{Create,Update,Save}` with the `GuardsStartedElections` trait) so a refused move does not shuffle the other positions first.
- **Row actions** are SearchKit button links to Smarty forms (`civicrm/elections/positions/create?...`); popup targets are `crm-popup`.
- **Angular module `electionsLists`** (`ang/electionsLists.ang.php`, `ang/electionsLists.js`) is loaded by the attribute `election-list-table="<key>"` on the table. It adds the table caption, labels each cell for the card layout on a phone, names each row button ("Edit Position A"), points Back at the page the person came from, shows the time zone line above lists with dates, and gives the Positions list move up and move down buttons. The server settings it reads (`CRM.electionsLists`: the time zone) come from `CRM_Elections_Helper_ListPage::angularSettings()`. The page keys it knows are `manage`, `positions`, `locked`, `candidates`, `results`, `audit`.

### Computed columns (APIv4 spec providers)

Classes in `Civi/Api4/Service/Spec/Provider/` are found by CiviCRM in an extension without registration. Each adds read-only `Extra` fields to APIv4 `get`, with an SQL renderer, so SearchKit can show, sort and filter them:

| Provider | Fields | Used by |
|---|---|---|
| `ElectionSpecProvider` | `stage`, `is_locked`, `lock_reason` | Manage Elections (stage column, locked rows, which buttons show) |
| `ElectionNominationSpecProvider` | `nomination_status` (nominee, candidate, declined, withdrawn) | Candidates |
| `ElectionAuditSpecProvider` | `action_label`, `details_text`, `changed_by_name` | Audit Log |

A provider must implement `applies($entity, $action)` and `modifySpec()`. The SQL renderer receives the field's own SQL name and derives sibling columns with a regex on it (`column()`); follow the existing providers. The SQL must stay equal to the PHP rule it mirrors (see the stage note in [data-model.md](data-model.md)).

## Shared CSS and the scoping rule

One stylesheet, `css/electionforms.css`, serves the front end and the back end. `hook_civicrm_coreResourceList` adds it to **every** CiviCRM page, so a rule written for an Elections screen would restyle every other CiviCRM screen on the site. Therefore:

- **Front end** classes are prefixed `election-` (`election-btn` for buttons, `election-back-to-election`, and so on) and are only used in extension templates.
- **Back end button styles are scoped.** Submit, Back and Back to Manage Elections are given one size by rules whose selector starts `.crm-container .crm-submit-buttons:has(.election-back)`: they apply only to a form footer that contains the Back button. The action rows of the lists and the overview use `.election-action-bar`. Never style `.crm-submit-buttons` or `.crm-button` on their own.
- The quiet (outlined) button is `election-action-quiet`, on both `a.button` (Smarty pages) and `a.btn` (SearchKit); the primary action of a list sits at the end of the action bar (`election-action-end`).
- Three button families exist and must not be mixed: CiviCRM's `button` on Smarty back end pages, `af-button btn` in Afforms, `election-btn` on the front end.
- Colours follow the active CMS theme where it publishes them, with fallbacks; do not hard-code a brand colour (the front end offers `--election-brand-bg`).

## Adding a list

1. **Computed values:** add a spec provider (or a field to one) if a column is not a plain column; keep its SQL equal to the PHP rule.
2. **Saved search and display:** a new `managed/SavedSearch_<Name>.mgd.php` with the SavedSearch and the SearchDisplay (`type => table`, `noResultsText`, a `description` that says what the list is, columns with `label`, buttons). Copy `SavedSearch_ElectionCandidates.mgd.php`.
3. **Afform:** `ang/afsearch<Name>.aff.php` (`type => search`, `server_route`, the two permissions with operator AND, `search_displays`) and `.aff.html`: an `election-action-bar election-list-actions` with Back (`election-back`) and Back to Manage Elections (`election-back-manage`), then `<crm-search-display-table election-list-table="<key>" search-name=... display-name=... filters="{election_id: routeParams.eid}">`.
4. **Frame:** add the route to `Frame::listScreens()` and its path segment to `Frame::ELECTION_LISTS`; add the key to `pages` in `ang/electionsLists.js` (and any form it links to, to `formRoutes` there).
5. **Link to it** from the overview template, with `Frame::listUrl()`.
6. **Words, order of columns and buttons:** follow `../standards/ui-standards.md`.
7. **Tests:** `FrameTest`, and a Playwright case in `tests/playwright/specs/backend/` on the model of `frame.spec.ts` and `candidates.spec.ts`.
8. Deploy and clear the cache; confirm the list on CiviCRM 6.16 and 6.19.

## Adding a form screen

1. Route in `xml/Menu/elections.xml` (`administer Elections` for the back end).
2. A class extending `CRM_Elections_Form_Base`. In `buildQuickForm()`: `hideNonRequiredItemsOnPage($this)`, `throwUnauthorizedMessageIfRequired($this)`, read `eid` with `retrieveElectionIdFromUrl()` or `CRM_Utils_Request::retrieve()`, then `Frame::assignToForm($this, Frame::trail(...), $fallbackBackUrl)` and `Frame::addSubmitButtons($this)`.
3. A template in `templates/CRM/Elections/Form/` ending its footer with `{if !$isPopup}{include file="CRM/Elections/Frame/BackButtons.tpl"}{/if}`. A small dialog (Duplicate, Delete, Activate) instead has its own action button and Cancel, and no Back.
4. In `postProcess()` do the work through `Process`, a BAO method or the API; on a refusal call `stayOnForm($message, $title)` (the form comes back with the person's input); on success set a status message and call `$this->finish($this->startedFrom($fallback))`, which does nothing in a popup.
5. Button text and order: Submit first, then Back, then Back to Manage Elections.
6. Add the row or button to the list that opens it (`managed/` display), and a Playwright case. If the form can be reached from a list, add its route to `formRoutes` in `ang/electionsLists.js` so Back skips it.
