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
| 1 | ~~Senate count (STV): decisions S1 to S6~~ | **Moved to 2.3** on 2026-11-21 by the maintainer ([../2.3/README.md](../2.3/README.md)). |
| 2 | ~~Senate count (STV) built on version 2~~ | **Moved to 2.3** on 2026-11-21 ([../2.3/stv.md](../2.3/stv.md)). |
| 3 | **Drag to rank on the ballot** (the remainder of the 2.0 front end work) | **Done** 2026-11-21. With JavaScript, a ranked ballot gets a drag handle (mouse, pen or finger, by pointer events) and Add to Ranking, Move Up, Move Down and Remove from Ranking for each candidate; ranked candidates are listed first, then a "Not ranked yet" line; a live region says each new rank. The rank drop-downs stay the record and the way to vote without JavaScript; write-in ranks are left alone; nothing new reaches the server (security re-review in [../../architecture/security.md](../../architecture/security.md), "The ballot page writes only the rank fields"). `js/election-ballot.js`, `election-page.spec.ts`. |
| 4 | **Dark mode and high contrast re-check** | **Done** 2026-11-21. Found that `theme-variants.sh` never applied a WordPress variation (each run added an untagged styles record that WordPress ignored), so earlier dark checks ran on the light colours: fixed, and the script now also switches CiviCRM's own (Riverlea) dark mode (`civicrm dark`). Real dark runs found the Select2 text forced to `#333` (contrast 1.04 on Riverlea dark) and quiet buttons, badges and links drawn in the theme colour on CiviCRM's background (1.56); fixed with Riverlea's variables (`--crm-input-color`, `--crm-danger-ink-color`, and `--election-ink` from `--crm-ink`). The accessibility spec takes `FORCED_COLORS=active` for high contrast. See [../../standards/ui-standards.md](../../standards/ui-standards.md), section 10. Checked on all three CMSs (2026-11-21), page walk axe and keyboard at 375 and 1280 px, no findings in any: WordPress (CiviCRM dark and light on Twenty Twenty-Five default, Midnight and Evening; forced colours), Drupal 11 (CiviCRM dark on Olivero default and gold, light on gold), Joomla 5.4 (CiviCRM dark on Cassiopeia default and alternative, light on alternative); CiviCRM's dark mode was confirmed to apply on Drupal and Joomla (the CiviCRM area #363636 with white text). |
| 5 | ~~Mobile voting web app: discovery only~~ | **Moved to 2.4** on 2026-11-21 by the maintainer ([../2.4/README.md](../2.4/README.md)). |
| 6 | **User interface standards: publish more widely?** | **Done** 2026-11-21 (U14): a pull request template (`.github/pull_request_template.md`) with the checklist of the standards, section 14; the standards stay in the developer guide, not `docs/`. |
| 7 | **A granular permission** between `administer Elections` and `view Elections` (for example `manage Election candidates`) | **Done** 2026-11-21 (D13): **manage Election candidates**: Election Candidates (choose an election), its Candidates list, Add Candidate and Edit Statement; APIv4 read only; nothing else in the back end. `AdminRulesTest`, `candidates-manager.spec.ts`, `docs/roles_and_permissions.md`, test accounts on three CMSs. |
| 8 | **Drupal 11 and Joomla workflows on GitHub Actions** | **Done** 2026-11-21 (D19): `ddev-playwright-tests.yml` in `ci-workflows` (agileware/ci-workflows#17, merged) and `.github/workflows/frontend-tests-drupal-joomla.yml`, by hand and every Monday. First run from `version-2.2` (a temporary push trigger, removed straight after; run 44861354748): Drupal 11 and Joomla 5.4.10, both CiviCRM 6.19.5, 108 passed each (about 18 and 13 minutes). It can be started by hand only once the file is on `master`. |
| 9 | **Re-record the user guide pictures and GIFs** in Google Chrome | **Done** 2026-11-21: all 27 pictures of the guide remade as PNG screenshots (the maintainer's choice; the GIFs are gone, 9.7 MB to 1.5 MB) on WordPress with CiviCRM 6.19.5 (`kit-test-wp`), by a tool that can make them again (`tests/playwright/tools/docs-images.spec.ts`, `npm run docs-images`). They show the 2.2 screens, including drag to rank. The alt texts now describe each picture. The set-up script puts the short-codes in a Custom HTML block, as `docs/setup.md` says. `docs/images/setup/example-permissions.png` was left as it is. |
| 10 | **Joomla manager and limited organiser sessions in the specs** | **Done** 2026-11-21. `setup-accounts-joomla.sh` makes the manager, limited organiser and voter accounts, and `fixtures/joomla-test-groups.php` a Joomla group for each with its CiviCRM permissions; the global setup signs all four in on Joomla (the voter to the site); the voter's refusal tests open the back office pages through the site (`memberAdminUrl()`); the skip in `permissions.spec.ts` is gone. No spec skips on Joomla now. See [../../environments/ddev-joomla.md](../../environments/ddev-joomla.md). |
| 24 | **Rest of the test clean-up** (all test work is done in 2.2, the maintainer's decision of 2026-11-21) | **Done** 2026-11-21, the three parts chosen: one retry for the council and security files (each reseeds in `beforeAll`, so a retry starts clean); the layout and accessibility walks merged into `page-walk.spec.ts` (one council progression, all problems reported at the end, `WALK_CHECKS` to run one check); the 16 message-less `callAPIFailure()` calls in `tests/phpunit/api/v3` now name the message (each checked to be the refusal the test means). Not chosen: trimming the council spec. |
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
| 23 | **Tier 2 test clean-up and a coverage report in CI**: 22 PHPUnit tests removed and one cut to its fallback line, after moving three assertions to stronger neighbours; 8 browser tests removed, one replaced by a refusal-message test, one cut to a single variant; the unused "no link" branch of `throwAccessDeniedException()` removed with its test. PHPUnit in CI measures line coverage with pcov and shows the totals on the job page (measured on `elections2` with Xdebug: 50.98 percent of lines before the removals, 50.89 after; the lines lost are the overview breadcrumb, which the browser tests open, and a one-line date guard). The `COVERAGE` input was added to `ci-workflows` in agileware/ci-workflows#14 (merged 2026-11-21); it has not yet run in Actions | `phpunit.xml.dist`, `.github/workflows/phpunit-tests.yml`, `agileware/ci-workflows` (`COVERAGE` input), [../../testing/phpunit.md](../../testing/phpunit.md) |

## Small items found while writing and checking these guides

Moved to 2.3 by the maintainer on 2026-11-21: the 38 px action bar buttons, the Joomla page width and the three stage implementations ([../2.3/README.md](../2.3/README.md), items 3 to 5). The dark mode claim is now part of item 4, and the rest of the test clean-up is item 24, because all the test work is done in 2.2.

## Decisions still open

The full list and the decisions already made are in [../../decisions/README.md](../../decisions/README.md). Open for 2.2:

| Decision | State |
|---|---|
| Additional voting methods (STV, approval voting) | STV moved to 2.3 on 2026-11-21 (S4); its other questions are in [../2.3/stv.md](../2.3/stv.md). Approval voting: the recommendation is to drop it, not yet confirmed. |
| Public `security.md` | Whether it includes the residual-risk notes. Open. |
| PHPUnit on Drupal | Whether to run it through the DDEV kit. Open. |

Decided during 2.2, kept here for reference: the granular permission (item 7) is **manage Election candidates** (D13, 2026-11-21); publishing the standards (item 6) is a pull request template, the standards stay in the developer guide (U14, 2026-11-21); the branch is `version-2.2`, cut from `origin/master`, pushed as a branch with the release pull request opened only when 2.2 is ready (2026-11-21).

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
- **CSS scoping.** The 2.1 button rule once also caught the front end ballot (35px high at 375px, failing the layout check, now in `page-walk.spec.ts`). Buttons must be at least 36px high. Do not widen the `.crm-container .crm-submit-buttons:has(.election-back)` scope in `css/electionforms.css`.
- **Flaky runs.** A failure only on the first run right after a deploy (the cache flush) passes on retry. One Drupal failure in `positions.spec.ts` once happened and was not investigated.
- **Do not leave the notes uncommitted.** Edits to these pages are part of the pull request like any other change.

Behaviours that are decided and easy to break: a running election is **locked** (rules for who votes and how votes count cannot change once ballots exist, even when inactive); **Declined** and **Withdrawn** are stored apart; dates follow CiviCRM's configured format; results show winners, not counts, and ties are shown as ties; one ballot per person is enforced by the database. See [../../architecture/README.md](../../architecture/README.md).

## Start-up checklist for the first 2.2 session

1. Read this page, [../../decisions/README.md](../../decisions/README.md), [../../standards/ui-standards.md](../../standards/ui-standards.md) and [../../environments/README.md](../../environments/README.md).
2. In the extension repository run `git fetch --all --tags`. Confirm `origin/master` is the 2.1.0 merge (or later) and the tag `2.1.0` exists. Ask the maintainer for the 2.2 branch name and whether it is pushed as you go.
3. Leave `info.xml` at 2.1.0 until the release; set the new version and date in the release pull request only.
4. Ask which item to start with. Make a task list and work one item at a time, with tests and a deploy to the relevant test sites.
5. Start only the DDEV sites you need and stop them afterwards.
6. After each item: PHPUnit, the Playwright suite on WordPress and (for anything that touches screens) on Drupal and Joomla, a check on CiviCRM 6.19, and the notes updated (this page, the decisions).
7. Release when the maintainer says: [../../releases/README.md](../../releases/README.md).
