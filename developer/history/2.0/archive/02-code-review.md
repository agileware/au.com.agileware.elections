> **Historical.** Written during version 2.0; may be out of date. Current guidance: [Code map](../../../architecture/code-map.md).

Covers: version 2.0, the code review of version 1.3.2, 2026-10-27. Copied from the project plan with names, paths and account names removed; screenshots under `plan/evidence/` are not kept (image not kept).

# 02 – Code review of v1.3.2

Reviewed: `README.md`, `docs/*.md`, `info.xml`, schema, `elections.php`, forms, pages, BAOs, helpers, API v3, templates, CSS, form-processor/action-provider integration.

## What the extension does

CiviCRM extension (key `au.com.agileware.elections`, ~6,000 lines PHP/Smarty/CSS) providing online elections:

`Election` → has many `ElectionPosition` → has many `ElectionNomination` (nominee contact) → has many `ElectionNominationSeconder` (nominators). `ElectionVote` stores one row per `(member, nomination, rank)`. `ElectionResult` stores the computed rank per nomination.

Election stages are gated by dates: Visible → Nominations → Advertise Candidates (nominee accepts/withdraws) → Voting → Results → Hidden. Participation is limited by CiviCRM Groups/Smart Groups. Optional checksum (`cid`/`cs`) access for non-logged-in users. A scheduled job (`Job/job.mgd.php`) calculates results using instant-runoff voting (IRV) and can anonymise votes.

## Structure

| Area | Files | Notes |
|---|---|---|
| Schema | `schema/*.entityType.php` (civix 25.10.2, `entity-types-php` mixin) | 6 tables: election, position, nomination, nomination_seconder, vote, result. Automatic schema upgrader (`CiviMix\Schema\Elections\AutomaticUpgrader`) **and** a legacy `CRM_Elections_Upgrader` with one step (`upgrade_10300`, adds `allow_checksum_access`). |
| Admin UI | `CRM/Elections/Page/*`, `Form/Create*`, `Form/Delete*`, `ElectionVisibility`, templates | Classic QuickForm/Smarty pages. Positions list already has drag-to-reorder (`ElectionPositions::reOrder`). Enable/disable via a confirm form (`ElectionVisibility`). |
| Participant UI | `Form/CreateElectionNomination`, `AcceptNomination`, `RejectNomination`, `VoteCandidates`, `Page/ViewElection`, `ElectionCandidate`, `ElectionSummary` | Server-rendered, minimal CSS (`css/electionforms.css`). |
| Results | `Helper/Results.php`, `BAO/ElectionResult.php` | IRV only. |
| Integration | `Civi/Elections/FormProcessor/NominationType.php`, `ActionProvider/*` | Form Processor / Action Provider hooks (vote/nominate from other forms). |
| Tokens | `CRM/Elections/Tokens.php` | Activity tokens. |
| CMS glue | `elections.php` | WordPress shortcode attribute amendments, `hideNonRequiredItemsOnPage`, checksum access. |
| Tests | `tests/phpunit` | PHPUnit scaffolding (`phpunit.xml.dist`); coverage to be assessed. |

## Issue and fix status (summary, kept up to date)

Every finding in this review, with where it stands on the `version-2` branch. **Fixed** = done and covered by a test; **Open** = planned, not built yet; **Decision** = waiting on an answer in [07-decisions-log.md](../../../decisions/README.md). Last updated 2026-10-27.

### A. Functional gaps (map to GitHub issues)
| # | Finding | Issue | Status | How / where |
|---|---|---|---|---|
| 1 | Candidate statement is only the nominee's `comments`; admins cannot see or edit it; not on the ballot | #43, #27, #3(3), #45 | **Fixed** | Statement plus optional link (`statement_url`), edit form (candidate until voting starts, admin until voting ends), admin Candidates screen with inline edit, statement shown on the ballot and candidate list (`show_statements`). Phase 2 and 3 |
| 2 | Photos always displayed | #44, #3(5) | **Fixed** | Per-election `show_photos` honoured on nominations, ballot, candidate page and results |
| 3 | Display name only, no nickname | #42 | **Fixed** | `name_format` per election plus site setting; same helper on ballot, results, nominations |
| 4 | Only IRV, no plurality | #3(1) | **Open** | Phase 5 (voting-method framework) |
| 5 | No write-in candidates | #3(4) | **Open, Decision D8** | Phase 5; D8 blocks it |
| 6 | One "Allowed Groups" setting for every stage | #4 | **Open, Decision D9** | Phase 6 |
| 7 | No referendum / question type | #5 | **Open, Decision D10** | Phase 7; D10 blocks it |
| 8 | Nominator / seconder details always shown | #28 | **Fixed** | `show_nominators`; the "Was nominated by" heading no longer prints once per nomination or when there are no nominators (fixed 2026-10-27). Admin can add candidates directly for offline nominations; a switch that turns the online nomination form off is not built (D7 partly) |
| 9 | Ballot wording ("Please number of the boxes"); strings not translatable | #46 | **Fixed** | Reworded; every user-facing string in templates and PHP wrapped in `{ts}` / `E::ts()` |

### B. Technical observations
| Observation | Status | Notes |
|---|---|---|
| Global helper functions in `elections.php` | **Partly** | Eligibility moved to `CRM_Elections_Helper_Eligibility`, candidate naming to `Helper_Candidate`, with thin wrappers. `isElectionAdmin`, `findElectionById` and the URL/checksum helpers remain global. Low risk; moved as they are touched |
| Hand-rolled IRV, hard to follow; raw SQL with interpolated ids | **Partly** | 18 pinning tests (plus more) hold the current behaviour. The two interpolated SQL statements are fixed (anonymising is one parameterised UPDATE; the nomination id list is cast to integers, 2026-10-27); every other query is parameterised. Refactor of IRV into the voting-method framework is Phase 5 (**Open**) |
| Data access almost entirely APIv3, no APIv4 entities | **Fixed** | APIv4 entities for all six tables, administrator-only; SearchKit screens use them. `ElectionPosition` is a sortable entity (needed by CiviCRM 6.17+ for drag and drop) |
| Hand-built admin lists | **Fixed** | Manage Elections, Positions, Candidates and Results are SearchKit/Afform screens shipped as managed entities. `Elections.tpl` remains only as the public election list |
| Drag and drop via jQuery UI from a CDN | **Fixed** | Replaced by the SearchKit draggable table; the CDN script and reorder AJAX were removed (no CDN references remain) |
| Flat CSS with fixed widths, not responsive, no JS | **Fixed** | New fluid layout and theme-friendly buttons (task 09); small script for the election form tabs and date checks |
| Legacy Smarty modifiers on Smarty 5 | **Fixed** | Every front-end and admin page is exercised by Playwright with a "no PHP output" guard on WordPress, Drupal and Joomla; the CiviCRM log shows no deprecation or undefined-key lines from the extension |
| `info.xml` says 5.73, tested on 6.16 / 6.19 | **Fixed** (D12) | Minimum is now CiviCRM 6.16 in `info.xml` and the README; v2 is tested on 6.16.5 (WordPress) and 6.19.2 (Drupal, Joomla) |
| Photo setting is domain-wide | **By design** | Image source stays a site setting; whether to show photos is per election |
| Security and permissions (ballots must stay private, anonymisation preserved, new screens covered by the same rules) | **Fixed for what was added; full review Open** | New APIv4 entities and SearchKit/Afform screens require `administer Elections`; turnout shows counts only; a PHPUnit test checks a member cannot read ballots or other tables through APIv3/APIv4. A full security review and hardening is in the To Do list ([11-todo.md](../../2.2/archive/roadmap.md)) |

### C. Existing documentation
| Item | Status |
|---|---|
| Docs updated for each v2 feature | **Fixed** for Phases 1 to 3 and the setup guide (permalinks and base page, Drupal, Joomla). To do with each later feature |
| GIF walkthroughs re-recorded after the redesign | **Open** (Phase 8) |

### D. Baseline to capture before changing code
| Item | Status |
|---|---|
| PHPUnit suite running on DDEV | **Done**: ported from the removed `CiviCaseTestCase`; now 153+ tests |
| Smoke test of every page on Smarty 5 with logging | **Done**: Playwright guards plus log check (see B) |
| Tests pinning current IRV behaviour | **Done**: 18 existing result tests restored plus multi-position and anonymisation tests |

### E. Defects found and fixed during Phase 0
All ten are **Fixed**. The two that were left open in the table below when first written (the dead *Check Results* link and the CDN jQuery UI) are now closed: the link was removed (2026-10-27) and the CDN script was removed with the old positions table.

---

## Findings relevant to version 2

### Functional gaps (map to issues)
1. **Nomination `comments` is the only "candidate statement"**. It is entered by the nominee when accepting, not visible/editable by admins (issues #43, #27, #3), and not shown on the ballot (#45). `VoteCandidates::findElectionCandidates()` only returns `id, member_nominee, display_name, image_URL, election_position_id`.
2. **Photos are always displayed** (`ElectionCandidate.tpl`, `VoteCandidates.tpl`, `ElectionResults.tpl`, `nominations.tpl`); only the *source* is configurable (global setting `elections_image_source`) (#44).
3. **Display name is `member_nominee.display_name` only** (`NominationType.php::getOptions`, templates) – no nickname support (#42).
4. **Only IRV** in `Helper/Results.php` (#3: plurality / "straight up" elections).
5. **No write-in candidates** (#3).
6. **A single "Allowed Groups" setting** is used for all stages (#4).
7. **No referendum/question type** – a position always means "elect N candidates" (#5).
8. **Nominator/seconder information is always rendered** (`nominations.tpl`, `ElectionCandidate.tpl`) (#28 follow-up).
9. Wording: `VoteCandidates.tpl` "Please number of the boxes…" (#46). Many strings in templates are not wrapped in `{ts}`, so they cannot be translated.

### Technical observations
- `elections.php` holds many global (non-namespaced) helper functions (`isElectionAdmin`, `findElectionById`, `isLoggedInMemberAllowedToVote`, …). Works, but hampers testing; v2 should move them into `CRM_Elections_Helper_*`/`Civi\Elections\*` classes progressively, keeping thin global wrappers only where still referenced from templates/hooks.
- `Helper/Results.php`: IRV implementation is hand-rolled; multi-seat behaviour (`seats > 1`) and the vote-shifting code (rank re-indexing with two `array_pop` calls) are hard to follow. 18 result tests already exist (`tests/phpunit/CRM/BAO/ElectionResultTest.php`) and now run again; treat them as the pinning suite before any refactor (Phase 5). Raw SQL interpolates ids that originate from the API (`IN ($nominationIds)`); convert to parameterised/APIv4.
- Data access is almost entirely **APIv3** (`civicrm_api3`). v2 admin features (SearchKit) require APIv4 entities – v1.3.2 did **not** register any (`cv api4 Election.get` failed). APIv4 entity classes were added in Phase 0 (`Civi/Api4/*.php`), restricted to `administer Elections`; foreign keys (nomination → contact, vote → nomination) resolve. Field metadata (`pseudoconstant`, `input_type`) still needs review in Phase 3.
- Admin lists (`Elections.tpl`, `ElectionPositionsTable.tpl`) are hand-built tables. SearchKit (core `search_kit`/`afform`) can replace them with displays that support inline editing, toggles and links; shipped as managed `SavedSearch`/`SearchDisplay` (`managed/*.mgd.php`) and Afforms.
- Drag/drop already exists for positions (jQuery UI sortable posting to `reOrder`). SearchKit table displays support drag-sorting through a `weight`/`sortorder` column (`draggable` setting), so this can be replaced consistently.
- CSS is a single flat file with fixed-width blocks; the front end is not responsive and does not follow CMS themes well. No JS beyond inline snippets.
- Templates use legacy Smarty modifiers (`|@count`, direct `{$form.x.html}`); the dev site runs Smarty 5, so every template must be exercised for deprecation notices/errors during Phase 0.
- `info.xml` compatibility was `5.73`; v2 now requires CiviCRM 6.16 (D12).
- The nominee photo URL comes from the contact/CMS user; the setting is domain-wide, not per election.
- Security/permissions: access checks rely on custom helpers plus `view Elections`/`administer Elections`; API permission map is in `elections_civicrm_alterAPIPermissions`. Any new API/Afform/SearchKit display must be covered by the same permission rules (voters must never read other voters' ballots; anonymisation must be preserved).

## Existing documentation
`README.md` and `docs/` (admin/user guides with GIF walkthroughs, `index.md` workflow diagrams, `setup.md`). Docs must be updated for every v2 feature; GIF screenshots will be re-recorded in Chrome (the test profile) after the UI redesign.

## Baseline to capture before changing code (Phase 0)
- [x] Run existing PHPUnit suite on the DDEV site (see 01-environment.md). The suite had been broken (depended on removed `CiviCaseTestCase`); ported, now 62 tests pass.
- [x] Smoke test each page/form on Smarty 5 with error logging enabled; log deprecations. Done through the Playwright suite and a log check on all three sites.
- [x] Add unit tests that pin current IRV behaviour (single seat, multi seat, tie, no nominations). The 18 existing result tests plus new ones.

## Defects found and fixed during Phase 0 (v2 branch)
| Defect | Fix |
|---|---|
| The whole PHPUnit suite could not run (base class depended on core's removed `CiviCaseTestCase`; `setUp()` signatures incompatible with PHPUnit 9) | Base test case ported to `Civi\Test` headless; 61 tests pass |
| **Anonymising votes ran inside the per-position loop**, wiping voter ids before later positions were counted – elections with more than one position and *Anonymise Votes* produced wrong/failed results | Anonymise once after all positions are counted; parameterised single UPDATE; regression test added |
| Smart-group check in `isLoggedInMemberAllowedToVote` used the logged-in contact instead of the contact passed in (TypeError on null) | Uses the supplied contact; returns FALSE without a contact |
| `hasLoggedInUserAlreadyVoted()` with no contact queried all votes | Guarded |
| PHP 8.3 deprecations: `DateTime::createFromFormat(null)` in `BAO/Election.php` | Guarded |
| Candidate/nominations screens hid nominees with fewer than a **hard-coded 2** nominators, ignoring the election's *Number of Required Nominations* | Visibility now based on `is_eligible_candidate` |
| Undefined array key warnings (`seconders`, `is_winner`) with Smarty 5 | `|default` in templates |
| `Check Results` button links to `civicrm/elections/results`, a route that does not exist (never shown because results use the view page) | **Fixed 2026-10-27**: the button was removed |
| Positions admin table loads jQuery UI from `code.jquery.com` CDN | **Fixed (Phase 3)**: SearchKit draggable table, no CDN |
| ~100 user-facing strings in PHP/templates not translatable | Wrapped in `E::ts()`/`{ts}` |
