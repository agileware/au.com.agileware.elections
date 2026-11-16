> **Historical.** Written during version 2.0; may be out of date. Current guidance: [Version 2.0 summary](../../../history/2.0/README.md).

Covers: version 2.0, the progress log from the start of the project to 2026-10-31. Copied from the project plan with names, paths and account names removed; screenshots under `plan/evidence/` are not kept (image not kept).

# 08 – Progress log

## Phase 0 – Foundations (done, branch `version-2`)
| Task | Status | Notes |
|---|---|---|
| 0.1 Baseline PHPUnit + smoke test of pages on Smarty 5 | Done | Suite ported and green (81 tests). Front-end pages (view, vote, candidate, summary, results) smoke-tested over HTTP with checksum links, no PHP warnings left from the extension. Chrome (the test profile) checks done for the front end: election page, ballot (new wording, nickname format), candidate page, vote submission, summary/results. |
| 0.2 IRV pinning tests | Done | 18 existing result tests restored + new multi-position anonymisation test |
| 0.3 Move global helpers | Partly | Voter eligibility moved to `CRM_Elections_Helper_Eligibility`; wrappers kept. Remaining globals (`isElectionAdmin`, `findElectionById`, URL/checksum helpers) move in later phases as they are touched |
| 0.4 `CRM_Elections_Helper_Candidate` | Done | Name formatting now; statement/photo helpers added in Phase 2 |
| 0.5 `CRM_Elections_Helper_Eligibility` | Done | `canVote`, `canNominate`, `hasVoted`; Phase 6 adds nomination/candidate groups |
| 0.6 Version bump | Done | `2.0.0-dev`, `alpha` |
| 0.7 Template i18n | Done | All templates and PHP messages translatable, wording reviewed |
| (new) APIv4 entities | Done | Needed for SearchKit in Phase 3; admin-only |

## Phase 1 – Quick wins (done)
| Issue | Status | Commit subject |
|---|---|---|
| #46 wording | Done | Fix ballot wording: number the candidates (#46); Make all user-facing templates and messages translatable (#46) |
| #42 nickname | Done (global setting) | Add candidate name format setting to show nicknames on ballots, results and nominations (#42) |

Chrome verification (front end, checksum voter) found and fixed: an undefined `$form` warning on the election page, and a regression/latent bug where checksum-link voters were not recognised for group eligibility or "already voted" (now resolved through `CRM_Elections_Helper_Eligibility::getActingContactId()`). A real vote was submitted end-to-end.

Back-end checked in Chrome (admin session provided by the user): Elections list renders with the active election and Deactivate action; Elections Settings page shows Image Source and the new Candidate Name Format select. Phase 0–1 visual verification complete.

Not done / next: per-election name format override (Phase 2); visual check in Chrome; Phase 2 starts with the schema upgrade step (`upgrade_20000`).

## Phase 2 – Candidate statements and display options (done, branch `version-2`)
Upgrade step `upgrade_20000` adds, to `civicrm_election`: `show_photos` (default 1), `show_statements` (`none`/`link`/`inline`, default `none`), `show_nominators` (default 1), `name_format` (NULL = site setting); and to `civicrm_election_nomination`: `statement_url`. Columns are nullable and NULL is treated as the old behaviour, so API callers that post back NULLs do not switch features off. Existing elections keep their behaviour. The step is idempotent and is tested (columns dropped, upgraded, repeated).

| Issue | Delivered |
|---|---|
| #43 / #27 / #3(3) | Candidate statement = existing `comments` plus optional validated http(s) link. Accept form relabelled; new *Edit statement* form (`civicrm/elections/candidate/statement`): candidate until voting starts, administrators until voting ends. Statements are HTML-escaped everywhere (previously printed raw) |
| #45 | `show_statements` option: expandable *Read statement* (accessible `<details>`) or full text on the ballot and in the candidate list once voting starts |
| #44 / #3(5) | `show_photos` honoured on nominations, ballot, candidate page and results |
| #28 | `show_nominators` hides "Was nominated by" on the candidate page |
| #42 | Per-election `name_format` overrides the site setting |
| #29 / #3 / #27 | Documentation: how nominees accept (themselves, masquerade caveat), statements, display options |

Other fixes made on the way: accept/withdraw nomination forms identified the nominee only by login, so checksum-link nominees could not use them (and a missing contact could match any nomination); they now use the validated acting contact and deny when there is none.

Verified in Chrome (admin session): election form shows the new Display options; ballot shows *Read statement* toggles and hides photos when off; candidate page and admin *Edit statement* flow (invalid `javascript:` link rejected, valid link saved); upgrade run on the dev site against a 1.3.2-schema database.

Decisions applied (defaults from 07-decisions-log.md): D1 yes (reuse `comments` + `statement_url`), D2 plain text (escaped, line breaks kept), D3 new elections default to the expandable statement on the ballot, upgraded elections `none`, D4 hide photos everywhere in the election, D5 per-election override done.

Deferred / follow-ups:
- Display options are only editable while the election itself is editable (same rule as other settings). A quick inline edit belongs in the Phase 3 admin screens.
- Admin *candidates list* with inline statement editing is Phase 3.
- Notification emails (#29 stretch) not started.
- Hard-coded copy "Was nominated by" heading still shows when there are no nominators – tidy in Phase 4.

## Persona-based testing (front end, Chrome) – first full run
Approach recorded in 06-testing-plan.md. The WordPress admin was logged out of the front end for this run so personal-link personas apply.

**Automated** (`CouncilPersonaTest`, 41 personas: 30 voters, 10 nominees, 6 candidates, 1 outsider; six council roles): 33 tests across nominations, responses, voting and results – eligibility by nominators, outsider refusal, duplicate nominators, withdrawn nominees, wrong-persona accept, ballot validation, revote on/off, rejected revote keeps the old ballot, uncontested position, tie, anonymisation, nickname names in results, nominee search as a link persona. Suite: 134 tests, all passing.

**Chrome (front end, as personas):** Voter 01 nominated and Voter 02 seconded Nominee 01 (eligible); the outsider was refused the nomination form and ballot; Nominee 01 accepted with a statement, Nominee 03 withdrew from President, Nominee 08 (unseconded) and Voter 05 had no way to accept; Voters 03 and 10 voted; remaining personas were filled in by the fixture; results and summary viewed by a voter.

**Defects found by persona testing and fixed:**
| Defect | Fix |
|---|---|
| Nominee search failed ("Loading failed", 403) for people using a personal link: the AJAX route needs a permission anonymous users lack | Scoped grant of the two needed permissions, for the nominee search only, when the search page's `cid`/`cs` validate (`hook_civicrm_permission_check`); search lists nobody for people who may not nominate |
| An outsider with a valid link was shown the nomination form, "Need Second"/"Nominate" buttons and the ballot | Form and page gates now use the acting contact; buttons require `isAllowedToNominate` |
| Ballot API accepted votes for withdrawn/unaccepted nominees, other elections' nominees, duplicate or zero ranks, and deleted an earlier ballot before checking the new one | `ElectionVote.addvotes` validates first, then replaces inside a transaction |
| Nominating had no server side rule checks (nominator/nominee eligibility, nomination window, position belongs to election) – only the form hid things | `CRM_Elections_Helper_Process::nominate/accept/withdraw/vote` enforce them; forms call the service |
| Raw API error ("Expected one ElectionNomination but found 0") shown when opening someone else's nomination | Generic not-authorised message |
| Undefined array key warnings for positions without nominations | `|default:[]` |

**Observations / follow-ups:**
- Published results and the summary are visible to anyone holding any valid link, including the outsider. Decide whether results should be members only (decision D17).
- Nominee search only lists direct group members, not smart group members, although smart group members may vote (to fix with Phase 6 eligibility work).
- The search result label is the sort name ("01, Nominee"); show the formatted candidate name.
- A stale error banner ("You do not have permission to access this page") persisted in the session after the earlier failed search.
- The ballot lists the same person twice for different positions with identical labels; headings disambiguate, but the Phase 4 ballot should repeat the position on each row for screen readers.
- Dates on results pages show the previous day (site time zone display) – check time zone handling.
- **Button styling** (new task, Phase 4 table): text is off centre on WordPress; check WordPress, Drupal and Joomla default themes and inherit theme button styles.

## Phase 3 – Back-end design and UX (done in part, branch `version-2`)
| Feature | Status |
|---|---|
| **Manage Elections** (SearchKit + Afform, `civicrm/elections/manage`, now the Elections menu target) | Done: list with dates and position count, Activate/Deactivate (popup confirm), Positions, Candidates, Edit, Duplicate, Delete, Create Election |
| **Click to enable/disable** | Done as two-step Activate/Deactivate buttons (button, then confirm) plus inline editing of *Active*; both go through the same rule (an election needs a position before it can be activated), enforced in the data layer (`hook_civicrm_pre`) so APIv4 and inline edits obey it |
| **Duplicate election** | Done: `Election.duplicate` APIv4 action and popup form; copies settings, display options, groups and positions as an inactive election with dates shifted together; no nominations/votes |
| **Positions list** (SearchKit, `civicrm/elections/position-list`, old `civicrm/elections/positions` route redirects) | Done: drag to reorder (other positions shift so order stays unique), inline edit of name/seats/description, Add Position, Duplicate, Delete. Old jQuery UI CDN table and reorder AJAX removed. Positions are locked once an election starts (data layer rule) |
| **Duplicate position** | Done (popup form, copy goes to the end) |
| **Candidates screen** (SearchKit, `civicrm/elections/candidate-list`) | Done: every nomination with position, candidate, nominator count, eligible/accepted/withdrawn and statement/link editable in place; filters; **Add Candidate** form for elections where nominations happen offline (D7). Who is standing is frozen once voting starts; statements can be corrected until voting ends (data layer rule) |
| **Election overview** (`civicrm/elections/overview`) | Done: stage badge, next step in plain words, timeline with passed dates, counts (positions, nominations, eligible, standing, ballots), shortcuts |
| Election form tabs / date validation inline | Done: four tabs (Election, Dates, Participation, Display; keyboard operable, a tab with a problem is marked), dates checked in the browser as they change using the same rule table as the server (`Election::getDateRules()`), timeline preview of the stages with the running one highlighted, problems open the tab that has them |
| SearchKit admin displays for turnout/results | Done: Results screen (SearchKit, `civicrm/elections/result-list`, final ranking per position, linked from the overview once results are calculated) and a Turnout block on the overview (ballots out of people allowed to vote, per position candidates and ballots with a preference; counts only). Fixed on the way: the ballots count dropped to 0 once votes were anonymised, it now also uses the Vote activities |
| Permissions | Admin screens need `access CiviCRM` + `administer Elections`; no new permission added (D13 stays open) |

Fixed on the way: navigation entry permission format (500 on the menu bar), popup forms now close instead of loading another page, activate/deactivate form could not deactivate an election without positions and bypassed rules, `Election.duplicate` with positions that have no order.

Tests: PHPUnit 145 tests (administration rules, duplicate, overview counts and stages); Playwright 30 specs, including the complete mock council election with 38 voting members (see 10-playwright-tests.md).

## Front end on default themes (task 09) - first pass
- Drupal 11 (Olivero) and Joomla 5.4 (Cassiopeia) test sites running with the extension, personas seeded, deploy commands clear the CiviCRM cache (Joomla deploy no longer truncates `civicrm_menu`, which caused HTTP 500).
- Baseline and after screenshots (3 widths x 16 pages x 3 CMS) in `evidence/baseline` and `evidence/after`.
- Buttons are theme-coloured links and centred; layout is fluid; `layout.spec.ts` passes on WordPress, Drupal and Joomla. Details and remaining items in [09-frontend-cms-compatibility.md](../../../testing/cms-and-civicrm-differences.md).

## Security review and hardening (2026-10-27)
Full write-up in [12-security-review.md](../../../architecture/security.md). In short: script injection through names and text on nearly every page (130 problems on 15 pages before the fix, none after), ballots and nominators that could be counted twice, ballots and results that could be changed or regenerated after the fact, anonymised ballots that could be matched to voters, contacts whose deletion changed the election, and no way to see tampering are all fixed, each with a test. New: hash-chained audit log, ballot and result fingerprints with an integrity check on the election overview, an Audit log screen. Upgrade steps `20010` and `20020` (duplicates removed, unique keys, foreign key changed, audit table). PHPUnit: 177 tests; Playwright: the whole suite plus `security/xss` and `security/tampering` pass on WordPress, Drupal and Joomla.

## Voting features, Phase 5 to 7 and D7 (2026-10-27)
All built, tested and documented on `version-2`:
- **Plurality voting** (`voting_method`, upgrade 20030), tie display fix for both methods.
- **Separate nomination and candidate groups** (D9, upgrade 20040).
- **Emails** (D11 first piece, upgrade 20050): editable message templates, nominee invited when they can stand.
- **Write-ins** (D8, upgrade 20060): done as nomination records marked `is_write_in`, with ballot secrecy protections (security findings 17 to 19).
- **Referenda** (D10, upgrade 20070): yes or no questions with pass threshold, quorum, wording, results as passed / not passed / no quorum (security finding 20).
- **Online nominating switch** (D7, upgrade 20080).
- Also closed on the way: ballot rows can only be added by the ballot (finding 16), nobody can be added to the ballot once voting has started except by the write-in process (finding 17).

Tests at this point: PHPUnit 258 tests, 2670 assertions; Playwright on WordPress 69 passed, Drupal 69 passed, Joomla 65 passed (the skips are for sessions that do not exist on Joomla); **upgrade test from 1.3.2 on WordPress, Drupal and Joomla: all checks passed** (`tests/upgrade/upgrade-from-v1.sh`, seeds three elections with 1.3.2, upgrades, checks nothing changed and the old elections still run).

## Phase 8 status
| Task | State |
|---|---|
| Cross-CMS tests | Done (above) |
| Upgrade tests from 1.3.2 on all three CMS | Done |
| Release notes and a draft comment for each GitHub issue | Written in [14-release-notes-and-issue-comments.md](14-release-notes-and-issue-comments.md), **not posted** |
| Walkthrough pictures and GIFs for the user guides | **Not done**: the images in `docs/images` are from 1.x; re-recording them in Chrome (the test profile) is a manual pass over every screen |
| Version 2.0.0 (`info.xml` version and release date) | Done 2026-10-31 (2.0.0, stable). Merge to `master`, **Cut release** and the tag still wait for approval (outward facing) |
| Comment on and close the GitHub issues | **Waiting for the release**; `gh` is now installed and logged in |
| GitHub CI: PHPUnit, upgrade-test, browser tests and cut-release workflows | **Done and green on GitHub** (2026-10-31, [15](../../../testing/ci.md)); ci-workflows #10, #11, #12 merged. Drupal and Joomla workflows (D19) not started |
| Commits related to issues | Commit messages are short and descriptive; the issue to feature table in [03-issues-analysis.md](03-issues-analysis.md) is the link |


## 2026-10-28
- Write-ins: a person who never answered is emailed once when voting closes (opportunity to accept has passed, new message template and job **Elections Write-in Closed Notices**, upgrade step 20090); opening the accept or decline link after voting closed shows an explanation with a button to the election. PHPUnit 261 tests pass.
- Documentation restructured into chapters (introduction, set up, running an election, taking part, reference) with workflow diagrams for each kind of election (`docs/workflows.md`, `docs/election_types.md`, `docs/reference_*.md`) and an `mkdocs.yml` navigation.
- GitHub CI reviewed and prepared: see [15-github-ci.md](../../../testing/ci.md).

---
Cut here: the entries from "1 November 2026" onward (they include the 2.0.0 release day) are in [the version 2.1 archive](../../2.1/archive/08-progress.md).
