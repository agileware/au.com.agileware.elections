# Version 2.2: start here

Written from the existing setup, 2026-11-15; not yet followed from scratch. The facts about the repository and GitHub were checked on that date.

Read this page first when you start work on version 2.2. It says where things stand after 2.1.0, what 2.2 contains and in what order, what each item needs, what is still undecided, the traps earlier work fell into, and what to do in the first session. The detail lives in the pages it links to.

## Where things stand

| | |
|---|---|
| Released | **2.0.0** (2026-11-01, pull request 48) and **2.1.0** (2026-11-03, pull request 49). 2.1.0 is the latest GitHub release. The summaries are in [../../history/2.0/README.md](../../history/2.0/README.md) and [../../history/2.1/README.md](../../history/2.1/README.md). |
| `master` | Holds 2.1.0 (`info.xml` version 2.1.0, release date 2026-11-03), and nobody pushes to it: changes come by pull request with three passing checks and one review ([../../releases/branches-and-rules.md](../../releases/branches-and-rules.md)). |
| GitHub issues | None open. Of the 18 issues ever filed, the 11 that 2.0.0 answered were commented on and closed on 2026-11-01. |
| Requires | CiviCRM 6.16 or later. Tested on WordPress, Drupal 11 and Joomla 5. Smarty 5 templates. |
| Database steps | `20000` to `20130` are used. **The next free step is `20140`.** Any schema change needs a step in `CRM_Elections_Upgrader` and a test in `UpgraderTest` ([../../architecture/upgrades.md](../../architecture/upgrades.md)). |
| Tests at the 2.1 release | PHPUnit about 322 tests (run on one WordPress site). Playwright: WordPress 115, Drupal 117 and Joomla 110 passed, with some Joomla specs skipped because that CMS has no manager or limited session saved. In GitHub Actions the PHPUnit, upgrade-from-1.3.2 and Playwright (WordPress) checks pass on every pull request. See [../../testing/README.md](../../testing/README.md). |
| Developer guide | This guide itself was added after 2.1.0 (items 12 and 13 below). |

## The items, in recommended order

The order below is a recommendation. Ask the maintainer which to start with.

| # | Item | State and what it needs |
|---|---|---|
| 1 | **Senate count (STV): decisions S1 to S6** | Not answered. Read [stv.md](stv.md). The maintainer answers; a comment on the external pull request is then drafted for the maintainer to post. Nothing has been posted. |
| 2 | **Senate count (STV) built on version 2**, not merged as the external pull request stands | Six blocking problems are listed in [stv.md](stv.md). Needs a schema step from `20140`, its own attack tests, an independent check against a published count, a large-electorate overflow test, a security re-review, and a contributor credit. |
| 3 | **Drag to rank on the ballot** (the remainder of the 2.0 front end work) | Not started. The ballot ranks with numbers in drop-downs today. Touches the ballot, so it needs a security re-review ([../../architecture/security.md](../../architecture/security.md)). Keep the keyboard way (Move up and Move down) and the screen reader behaviour. |
| 4 | **Dark mode and high contrast re-check** | Not started; do it with item 3. Theme variants script: `tests/playwright/fixtures/theme-variants.sh` (see [../../testing/cms-and-civicrm-differences.md](../../testing/cms-and-civicrm-differences.md)). |
| 5 | **Mobile voting web app: discovery only** | A written plan, no code: API or mobile mode, ballot secrecy, how the personal link stays safe, offline behaviour, accessibility, and whether it works the same on the three CMSs. Building it is a later release. |
| 6 | **User interface standards: publish more widely?** | The standards are now in [../../standards/ui-standards.md](../../standards/ui-standards.md), which is public but left out of the release package. Open: whether to publish a version in `docs/` (shipped with the extension, listed in `mkdocs.yml`), and whether to add a pull request template in `.github` carrying the guide's checklist. `CONTRIBUTING.md` already points to the developer guide. The maintainer decides. |
| 7 | **A granular permission** between `administer Elections` and `view Elections` (for example `manage Election candidates`) | Deferred from 2.1. Default: build it only if an organisation needs it. If built: the permission, the Candidates routes split from the other admin routes, audit entries, tests on three CMSs, and `docs/roles_and_permissions.md`. Results visibility is already decided: keep the current behaviour. |
| 8 | **Drupal 11 and Joomla workflows on GitHub Actions** | Today `agileware/ci-workflows` runs WordPress only, so the other two CMSs are tested on DDEV before each release. Contribute workflow variants to that repository; DDEV in Actions is the recommended way. The theme input (`WP_THEME`) already exists. See [../../testing/ci.md](../../testing/ci.md). |
| 9 | **Re-record the user guide pictures and GIFs** in Google Chrome | The docs text already matches the 2.1 screens; these images still show older ones: `docs/images/admin_election`, `admin_edit`, `admin_add`, `admin_delete`, `user_nominate`, `user_vote`, `user_view_results`, and `docs/images/setup/election-listing-page.png` and `election-page.png`. The extension README still shows an old results GIF. |
| 10 | **Joomla manager and limited organiser sessions in the specs** | Some specs skip on Joomla because no manager or limited session exists there (the skip is in `tests/playwright/fixtures/base.ts` and `specs/backend/permissions.spec.ts`). Create the sessions from the Joomla account setup script `tests/playwright/fixtures/setup-accounts-joomla.sh`. |
| 11 | **Release 2.2** | Follow [../../releases/README.md](../../releases/README.md). |

Done since 2.1.0. Items 12 and 13 reached `master` with the developer guide pull request (#51, 2026-11-17); items 14 to 22 are on the branch `version-2.2` (not pushed):

| # | Item | Where |
|---|---|---|
| 12 | **Reorganise the project notes into this developer guide** (guides, reference pages, decisions, history of 2.0 and 2.1, this roadmap) | [`../../README.md`](../../README.md) |
| 13 | **Test data scripts in the repository**: one election of each kind at a chosen stage on any CMS, with the logins as a separate optional step | `tests/seed/`, described in [../../testing/seeding.md](../../testing/seeding.md) |
| 14 | **Test account passwords generated at setup, not committed** (D31) | `tests/playwright/fixtures/generate-passwords.php`, [../../testing/playwright.md](../../testing/playwright.md) |
| 15 | **Playwright project chain removed**: one worker runs the projects in order, so one failure no longer skips the rest (D32) | `tests/playwright/playwright.config.ts` |
| 16 | **Tier 1 test clean-up**: 29 PHPUnit and 7 browser tests removed, `admin-managed` folded into `groups.spec.ts`, the screenshot walk made a tool (`npm run screens`) (D32) | [../../decisions/platform-testing-and-release.md](../../decisions/platform-testing-and-release.md) |
| 17 | **Referendum spec counts only its own election's answers**, so the suite passes on a site that holds seeded review data | `tests/playwright/specs/council/referendum.spec.ts` |
| 18 | **`xss` spec writes its test text only into the council election**, not into every election on the site | `tests/playwright/specs/security/xss.spec.ts` |
| 19 | **Add buttons at the right end of the list action bar**, as the standards say. CiviCRM's own button rule (`#bootstrap-theme .btn`) had cancelled the margin on every CMS; where the row is too narrow (Drupal's Olivero) the Add button wraps to its own row and stays at the right end. Checked by screenshots on the three CMSs at 1280, 1024, 768 and 375 px (2026-11-20), which found two more: on Joomla the list table widened the page past the window (the admin sidebar stays open down to 768 px), pushing the Add button off screen, so the table now scrolls in its own box; and the disabled Add Position on a locked list was shorter than the other buttons. Both fixed | `css/electionforms.css`, `tests/playwright/specs/backend/candidates.spec.ts`, `positions.spec.ts` |
| 20 | **`xss` spec proves its text reached the pages**: it fails if the test text was not shown as plain text on the election page, ballot, candidate page, candidates list and results | `tests/playwright/specs/security/xss.spec.ts` |
| 21 | **Upgrade tests cover steps 20110 and 20130**, and the upgrade-from-1.3.2 check covers `has_withdrawn_nomination` and re-runs steps 20120 and 20130 | `tests/phpunit/CRM/Elections/UpgraderTest.php`, `tests/upgrade/verify-v2.php` |
| 22 | **Out-of-date notes trimmed**: `tests/playwright/README.md` points to the guide; `CONTRIBUTING.md` no longer explains creating the `release` branch | |
| 23 | **Tier 2 test clean-up and a coverage report in CI**: 22 PHPUnit tests removed and one cut to its fallback line, after moving three assertions to stronger neighbours; 8 browser tests removed, one replaced by a refusal-message test, one cut to a single variant; the unused "no link" branch of `throwAccessDeniedException()` removed with its test. PHPUnit in CI measures line coverage with pcov and shows the totals on the job page (measured on `elections2` with Xdebug: 50.98 percent of lines before the removals, 50.89 after; the lines lost are the overview breadcrumb, which the browser tests open, and a one-line date guard). The `COVERAGE` input is on a local `ci-workflows` branch, `phpunit-coverage`, not pushed: that change must be merged before this workflow change, or the PHPUnit check fails at the start with an unknown input | `phpunit.xml.dist`, `.github/workflows/phpunit-tests.yml`, `agileware/ci-workflows` (`COVERAGE` input), [../../testing/phpunit.md](../../testing/phpunit.md) |

## Small items found while writing and checking these guides

Not yet in the table above; each is a small change. Ask the maintainer whether to include them in 2.2.

| Item | Detail |
|---|---|
| **Action bar buttons are 38 px high at phone width** | `css/electionforms.css` asks for at least 44 px on a phone or touch screen (`.election-list-actions .btn` in the `max-width: 768px`, `pointer: coarse` rule), but the fixed-height rule for every action bar button (`height: 2.5em !important`) wins, so they are 38 px on all three CMSs. The UI standards say at least 36 px, which they meet; decide whether the 44 px touch size is wanted and, if so, let the touch rule win. Found 2026-11-20, not changed. |
| **Manage Elections still a little wider than the window on Joomla below about 900 px** | With the table fix the page is at most about 140 px too wide (the Add button stays in view); something else on Manage Elections, probably the filter row, has a minimum width. Not looked into. |
| **Rest of the test clean-up** | Not decided. One retry for the council and security files; merging the `layout`, `accessibility` and `election-page` page walks; trimming the council spec to about five nominators and six voters; tightening weak assertions (for example the `callAPIFailure()` calls with no message). Tier 2 and the coverage report are done (row 23). Remove a test only when a neighbour fails for the same regression. |
| **Dark mode claim** | The user guide says dark mode is supported; check it against the CSS and the theme variants script before 2.2 says so again. |
| **Election stage has three implementations** | Checked 2026-11-17 and confirmed. The stage is worked out in `CRM_Elections_BAO_Election::getStage()` (overview and stepper), `ElectionSpecProvider::renderStage()` (an SQL `CASE` for the Stage column of Manage Elections) and `CRM_Elections_Helper_ElectionStatus::card()` (the cards on the front end list); `ElectionStatus::nextAction()` walks the same ladder a fourth time, and `CRM_Elections_Page_Elections::modifyElectionValues()` works out the card's date flags a second time. They agree today: same order of checks, same boundaries, same clock, same rule for results, and the same words on screen. `AdminRulesTest::testTheThreeWaysOfWorkingOutTheStageAgree` now holds them together in ten states. They differ in three ways that do not show: the keys (`advertise` and `closed` against `candidates`, `nominations_closed` and `voting_closed`), a `deleted` stage only in `getStage()`, and `nextAction()` checking nominations open before candidates advertised (it matters only if the advertise date is before nominations close, which the date rules refuse). Still open: one source for the stage (a refactor that touches the front end election page and needs the three-CMS run). A new stage or rule, for example from the STV work, must be made in all of these places and added to the test. |

## Decisions still open

The full list and the decisions already made are in [../../decisions/README.md](../../decisions/README.md). Open for 2.2:

| Decision | State |
|---|---|
| Additional voting methods (STV, approval voting) | Open. The STV questions S1 to S6 are in [stv.md](stv.md). Approval voting has no request behind it; the recommendation is to drop it, not yet confirmed. |
| Granular permission (item 7) | Deferred to 2.2; default build only if needed. |
| Publishing the standards more widely (item 6) | Open. |
| Branch name for 2.2 and whether to push as work goes | Ask at the start. The 2.1 rule (kept local until finished, one release pull request) was for 2.1 only. Suggested name: `version-2.2`, cut from `origin/master`. |

## Rules that matter

The short list for anyone changing the extension is [`../../../AGENTS.md`](../../../AGENTS.md). The ones that are easiest to forget:

- The maintainer decides, and merges. Bring a recommendation and the options, one decision at a time. Anything outward-facing (pushing, pull requests, running **Cut release**, GitHub settings, comments on issues or other repositories) waits for the go-ahead.
- Commits are short and descriptive, name the issue where there is one, and carry no assistant attribution. A pull request description lists features and changes only.
- Everyone who takes part is "people", never "member" or "membership" in anything a person reads; `WordingTest` fails the build on it. See [../../standards/wording.md](../../standards/wording.md).
- Test on CiviCRM 6.16 and 6.19. They behave differently ([../../testing/cms-and-civicrm-differences.md](../../testing/cms-and-civicrm-differences.md)).
- Test screens as the people who use them (personas on personal links), on all three CMSs for anything that changes a screen. See [../../testing/personas.md](../../testing/personas.md).
- Never commit passwords, personal links or tokens. Test sessions are created by setup scripts; nobody types a password into a test.
- Browser testing uses Google Chrome with the test profile, never a built-in browser.
- Run long test jobs in the background with a log file and check the log; never run two test jobs on one site, and do not deploy while one runs. Stop the DDEV sites you started when you finish, and only those (`ddev stop <name>`, never `ddev poweroff`). See [../../environments/README.md](../../environments/README.md).

## Traps from earlier versions

- **The referrer drops the `#` part.** SearchKit lists read the election from `#/?eid=N`, which the browser does not send. `Frame::withElection()` puts `eid` in the query and the fragment. Any new link from a list to a form must carry the election in the query too.
- **CMS differences.** Joomla and Standalone breadcrumbs take url and title pairs, not markup. Drupal's theme sets button heights (hence the fixed height on back end action buttons). The WordPress session wins over a personal link in the same browser, so sign the administrator out before looking at the front end as a voter.
- **Smarty 5.** Every `.tpl` must be Smarty 5 compatible.
- **The PHPUnit database.** Not verified here (it comes from the test site's configuration, see [../../testing/phpunit.md](../../testing/phpunit.md)): tests run against a separate test database seeded once from the site database. A test that deletes shared default rows (for example the default message templates) corrupts later runs: restore what you remove.
- **A new column must be added to every hand-written `SELECT`** that feeds an object. `has_withdrawn_nomination` was missed once in `ElectionNomination::assertCanChange`, causing "Undefined property".
- **CSS scoping.** The 2.1 button rule once also caught the front end ballot (35px high at 375px, failing `layout.spec`). Buttons must be at least 36px high. Do not widen the `.crm-container .crm-submit-buttons:has(.election-back)` scope in `css/electionforms.css`.
- **Flaky runs.** A failure only on the first run right after a deploy (the cache flush) passes on retry. One Drupal failure in `positions.spec.ts` once happened and was not investigated.
- **Do not leave the notes uncommitted.** Edits to these pages are part of the pull request like any other change.

Behaviours that are decided and easy to break: a running election is **locked** (rules for who votes and how votes count cannot change once ballots exist, even when inactive); **Declined** and **Withdrawn** are stored apart; dates follow CiviCRM's configured format; results show winners, not counts, and ties are shown as ties; one ballot per person is enforced by the database. See [../../architecture/README.md](../../architecture/README.md).

## Start-up checklist for the first 2.2 session

1. Read this page, [stv.md](stv.md), [../../decisions/README.md](../../decisions/README.md), [../../standards/ui-standards.md](../../standards/ui-standards.md) and [../../environments/README.md](../../environments/README.md).
2. In the extension repository run `git fetch --all --tags`. Confirm `origin/master` is the 2.1.0 merge (or later) and the tag `2.1.0` exists. Ask the maintainer for the 2.2 branch name and whether it is pushed as you go.
3. Leave `info.xml` at 2.1.0 until the release; set the new version and date in the release pull request only.
4. Ask which item to start with. Make a task list and work one item at a time, with tests and a deploy to the relevant test sites.
5. Start only the DDEV sites you need and stop them afterwards.
6. After each item: PHPUnit, the Playwright suite on WordPress and (for anything that touches screens) on Drupal and Joomla, a check on CiviCRM 6.19, and the notes updated (this page, the decisions).
7. Release when the maintainer says: [../../releases/README.md](../../releases/README.md).
