> **Historical.** Written during version 2.0; may be out of date. Current guidance: [Version 2.0 summary](../../../history/2.0/README.md).

Covers: version 2.0, the implementation plan, 2026-10-27 to 2026-10-28. Copied from the project plan with names, paths and account names removed; screenshots under `plan/evidence/` are not kept (image not kept).

# 04 – Implementation plan (version-2)

Order follows issue priority (highest first) while respecting dependencies: shared foundations first (settings/schema/helpers), then the small issue-driven features, then back-end/front-end UX, then the larger structural features. Every commit references its issue.

Version: `info.xml` → `2.0.0`, `develStage` `beta` until release. Minimum CiviCRM: 6.16 (decision D12).

> **Cross-phase task: Playwright tests.** Automated Playwright tests for the election front end and back end, including a complete mock Council Election (President, Secretary, Treasurer, Council Member 1/2/3; at least 30 voters), run through the Agileware `ci-workflows` Playwright workflow on every push and pull request. Plan: [10-playwright-tests.md](../../../testing/playwright.md). Recommended to land the harness and smoke specs before Phase 3 so every later feature ships with a spec.

## Phase 0 – Foundations
| # | Task | Notes |
|---|---|---|
| 0.1 | Capture baseline: run PHPUnit, smoke-test every page on Smarty 5, record errors | 02-code-review.md checklist |
| 0.2 | Unit tests pinning current IRV behaviour | Prevents regressions in Phase 5 |
| 0.3 | Move global helper functions into namespaced helper classes (keep wrappers) | Testability |
| 0.4 | Introduce `CRM_Elections_Helper_Candidate` (name, photo, statement) | Single source for #42/#43/#44/#45 |
| 0.5 | Introduce `CRM_Elections_Helper_Eligibility` (nominate/vote/candidate group checks) | Needed for #4 |
| 0.6 | Version bump to 2.0.0-dev; keep civix mixin versions current | |
| 0.7 | Template i18n pass: wrap all literal strings in `{ts}` | Needed for #46; helps all |

## Phase 1 – Quick wins
| Issue | Task |
|---|---|
| #46 | Fix ballot wording; generic "candidate/option" wording; copy review across all templates |
| #42 | Candidate name format option + helper; apply to ballot, candidate page, results, nominations, `NominationType` (Form Processor) |

## Phase 2 – Candidate statements and display options
Schema (upgrade step 20000+): election columns `show_photos`, `show_statements`, `show_nominators`, `name_format`; nomination column `statement_url` (see 05).
| Issue | Task |
|---|---|
| #43/#27/#3(3) | Statement entry on accept form (edit until voting starts), admin view/edit (interim: edit form linked from admin candidate list; full SearchKit in Phase 3) |
| #45 | Show statement/CV link on ballot (accessible dialog or details/summary), nominations block, results |
| #44/#3(5) | Honour `show_photos` in every template, incl. ballot, candidate page, nominations block, results |
| #28 | `show_nominators` option; improve seconding docs |
| #29 | Doc update; optional notification emails (stretch) |
| – | Election create/edit form gets a **Display options** section for the above |

## Phase 3 – Back-end design & UX
Goal: configure and manage elections with fewer clicks, using SearchKit/Afform where possible.
| Task | Detail |
|---|---|
| Elections admin list | SearchKit saved search + table display of `Election` (name, stage badge, dates, positions/nominees/votes counts via joins/`COUNT`, active toggle); replaces `Elections.tpl` admin view |
| **Click to enable/disable** | Inline toggle (SearchKit "toggle" column / `is_visible`/active action calling APIv4 `Election.update`, no page reload) with the existing "must have a position" rule enforced server-side |
| **Duplicate election** | Row action "Duplicate": copies election settings, positions (and referendum options), shifts dates by a user-chosen offset, creates inactive. New APIv4 action `Election.duplicate` + button/dialog |
| **Duplicate position** | Same for positions |
| **Drag/drop** | Position ordering via SearchKit draggable display (`sortorder`), keep legacy `reOrder` for fallbacks; drag/drop for referendum options |
| Positions list | SearchKit display with inline edit (name, seats, description) |
| **Candidates screen** (#3(2)/#43) | SearchKit list of nominations per election/position: nominee, nominators, eligible/accepted/withdrawn status, statement (inline editable), actions: add candidate directly (admin-managed nominations, D7), mark eligible/accepted, withdraw, remove |
| Vote / results | SearchKit admin displays: turnout per position (counts only), results table; ballots never exposed when anonymised |
| Election edit form | Group fields in tabs/sections (Dates, Participation, Voting, Display); date validation with helpful inline errors; stage timeline preview |
| Dashboard | Election summary page: stage timeline, counts, next action ("Add a position", "Activate") |
| Permissions | New granular permissions? (`administer Elections`, `view Elections`, plus `manage Election candidates`) – D13 |
| Managed entities | Ship SavedSearch/SearchDisplay/Afform as `managed/*.mgd.php` + `ang/` (mixins: `mgd-php`, `ang-php`/Afform scan) with upgrade-safe `cleanup => unused` |

## Phase 4 – Front-end design & UX (entire workflow)
Principles: responsive, accessible (WCAG 2.1 AA), theme-friendly (CSS custom properties, inherit CMS fonts/colours), progressive enhancement, no hard-coded widths.
| Area | Improvements |
|---|---|
| Election listing | Card layout with stage badge ("Nominations open – closes in 3 days"), countdowns, clear call-to-action per stage |
| Election page | Stage timeline (stepper) showing current stage and dates in the viewer's timezone; per-user next-action panel (you are eligible to vote / you have been nominated / you have voted) |
| Nomination | Autocomplete nominee picker, position cards, clear confirmation, "nominated by" summary, error messages beside fields |
| Accept/withdraw | Single "Your nominations" panel with statement editor, status chips, accept/withdraw buttons with confirmation |
| Candidate page | Statement, photo (if enabled), positions, nominators (if enabled) |
| **Voting** | Preferential: drag-to-rank list **and** accessible numeric/select fallback; live validation (no duplicate ranks, gaps); review-and-confirm step; "vote saved" receipt page with timestamp; works on mobile without drag. Plurality: checkbox limit counter. Referendum: radio buttons |
| Results | Per-position cards, winner badges, round-by-round IRV breakdown (collapsible), pass/fail for referenda, print-friendly CSS |
| Shell/layout | Same templates for WordPress, Drupal and Joomla; no CMS-specific markup. Replace `electionforms.css` with a scoped BEM stylesheet using CSS variables; support dark/high-contrast themes |
| Emails | HTML message templates for nominations/voting reminders (stretch) |
| Accessibility | Keyboard-only operation, ARIA live regions for validation, focus management in dialogs, colour contrast checks (axe) |
| **Front end display on default themes (expanded from button styling)** | Full scope, themes (WordPress Twenty Twenty-Five, Drupal 11 Olivero, Joomla 6 Cassiopeia), acceptance criteria and approach are in [09-frontend-cms-compatibility.md](../../../testing/cms-and-civicrm-differences.md): the display of the entire election process on the front end (every stage and step, list to results) must display and work correctly with each CMS's default theme and no developer customisation. Starting point: check the default button styling on WordPress, Drupal and Joomla. On the WordPress dev site (Twenty Twenty-Five) the election buttons (Vote Now, Submit Nomination, Accept/Withdraw, Need Second, Nominate, Back, Edit statement) have off-centre text: the extension sets `election-action-button`/`input type=button` styles that fight the theme and CiviCRM. Default themes in all three CMSs style buttons correctly, so the aim is to **inherit the theme's button styles** (use native `<button>`/`<a class="button">` markup with minimal, scoped CSS using CSS variables) and verify on each CMS default theme (WordPress Twenty Twenty-Five, Drupal 11 Olivero, Joomla Cassiopeia). Screenshots of before/after kept in `plan/evidence/` |

Deliverable: updated screenshots/GIFs recorded in Chrome (the test profile).

## Phase 5 – Voting methods & write-ins (#3)

### 5a design (plurality voting), 2026-10-27 — **built 2026-10-27**
- **Setting:** `voting_method` for each election, `irv` (ranked choice, today's behaviour, the default for every existing election) or `plurality` (most votes win). Chosen on the Participation tab, locked once ballots exist, copied by Duplicate, written to the audit log.
- **Plurality ballot:** for each position the voter ticks up to *seats* candidates (a single-seat role is pick one). Ticks are stored as ballots with rank 1, so the table, the one-ballot-per-member rule, anonymisation, fingerprints and the review step all work unchanged.
- **Server rules:** for plurality every rank must be 1 and the number of ticks for a position cannot exceed its seats; for ranked choice the existing rules stand. Both are checked in the one place that saves a ballot.
- **Counting:** the most votes win; candidates with the same number of votes share a rank; if the tie runs across the last seat the position is marked as a tie and every tied candidate is listed (it is never settled by arbitrary choice). Ranked choice counting is untouched (its tests pin the behaviour).
- **Fixing on the way:** the results page showed only one of the candidates in a ranked-choice tie as if they had won; a tie now lists every tied candidate with the Tied badge.
- **Built as designed.** `voting_method` (upgrade_20030, nullable, empty = ranked choice), `CRM_Elections_VotingMethod` (options, per-position ballot rules, instructions, plurality count), tick-box ballot with live checks and review, Participation setting locked once ballots exist and audited, Duplicate copies it, new result status `mostvotes`. Ties now show every tied candidate with a Tied badge for both methods (`is_tied` as well as `is_winner`). Tests: `VotingMethodTest` (PHPUnit) and `council/plurality.spec.ts` (all three CMS).
- **Code:** a small `CRM_Elections_VotingMethod` layer (key, label, ballot validation, instruction text, counting) with the two methods; ranked choice delegates to the existing code.

| Task | Detail |
|---|---|
| Voting-method framework | `CRM_Elections_VotingMethod_Interface` with implementations: `IRV` (refactor of existing), `Plurality` (top N by first-choice/approval count), optional `Approval`; per-election `voting_method` (default `irv` for upgrades) |
| Ballot adaption | Ballot form and validation delegate to the method (rank vs select-up-to-N) |
| Results | Method returns ranked results + round data; stored in `election_result` (+ `votes` count/round JSON column) |
| Write-ins (D8 decided: contact from a group, notified immediately, must accept before voting closes, then a normal candidate on the ballot) | `write_ins_per_seat` (0 to 10, 0 = off) per position, so up to seats x that many write-in places on a ballot, added one at a time with an "Add another write-in" button; `election_write_in` table (position, contact, status `pending`/`accepted`/`declined`, notified date); ballot UI with a names-only search limited to the voting groups, one row per seat, and a warning that the vote counts only if the person accepts; first write-in creates the record and sends the invitation (personal link) and a reminder before close; accepting creates an eligible, accepted nomination marked as from a write-in, re-points the write-in votes to it and adds it to the ballot; declining or no answer by close drops the votes (ballot skips to the next preference); the rule that who is standing is frozen once voting starts gets a controlled exception for this path; all steps in the audit log; admin screen lists write-in records with status and counts only |
| Tie handling | Explicit tie status; admin tie-break note |

**Write-ins (5b) built 2026-10-27**, with the change from a separate table to nomination records described in [05-schema-and-upgrades.md](../../../architecture/upgrades.md): `write_ins_per_seat` on positions (form, SearchKit inline edit, Duplicate, 0 to 10 validated on every write), ballot places with a names-only search and *Add another write-in*, the invitation and one reminder (scheduled job *Elections Write-in Reminders*), accept or decline until voting closes, ballot renumbering when a choice is dropped, overview list and warning, audit summary line, and the secrecy protections (security review findings 17 to 19). The email infrastructure (D11) is in as editable message templates. Tests: `WriteInTest`, `MailerTest` (PHPUnit), `council/writein.spec.ts` (all three CMS).
| Tests | Unit tests for each method incl. ties, multi-seat, write-ins |

## Phase 6 – Phase-specific eligibility (#4) (D9 decided: yes to `nomination_groups` and `candidate_groups`)
`nomination_groups`, `candidate_groups`, (voting) `allowed_groups`; helper in Phase 0.5; election form UI with explanation text; docs; tests for each combination and for upgrade (unchanged behaviour).

**Built 2026-10-27.** `nomination_groups` and `candidate_groups` (upgrade_20040, both empty on upgrade so behaviour is unchanged). `Eligibility::groupsFor/canNominate/canBeNominated` apply them at every action: the nomination form and seconding (nomination groups); being nominated, accepting, administrator Add Candidate, and the nominee picker (candidate groups); voting stays on `allowed_groups`. Election form fields with help text, overview panel *Who can take part*, locked once ballots exist, audited, copied by Duplicate. Tests: `SeparateGroupsTest` (PHPUnit) and `council/groups.spec.ts`.

## Phase 7 – Referenda (#5) (D10 decided: new options table, thresholds, abstain, optional quorum)
Position type `referendum`; options table and admin UI (drag/drop options, duplicate); ballot, results (counts, % , pass/fail with threshold), anonymisation, SearchKit; works with Phase 5 framework (`SingleChoice` method).

**Built 2026-10-27**, with the differences recorded in [05-schema-and-upgrades.md](../../../architecture/upgrades.md): position type `referendum`, fixed Yes/No/(Abstain) options with renamable wording in `civicrm_election_option`, answers in `civicrm_election_option_vote`, the position form switches to the question fields, SearchKit list shows the type, radio buttons on the ballot with the review step and receipt, passed / not passed / no quorum results with the resolution (no counts, D17), anonymising, fingerprint, turnout, duplicate and merge. Tests: `ReferendumTest` (PHPUnit); `council/referendum.spec.ts` and the question test in `backend/positions.spec.ts` (all three CMS).

## Phase 8 – Cross-CMS testing, docs, release
| Task | Detail |
|---|---|
| WordPress | Full manual + automated run on `elections2` (shortcodes `civicrm component="elections"`/`electioninfo`) |
| Drupal 11 | New DDEV project, CiviCRM via Composer, run workflow; verify permissions and menu |
| Joomla | New DDEV project, CiviCRM package, run workflow; verify frontend routes |
| Upgrade tests | Install v1.3.2 with sample data → upgrade to v2 → verify unchanged behaviour and data |
| Docs | Update `README.md`, `docs/`, new screenshots/GIFs, upgrade notes, release notes |
| Release | Version 2.0.0, tag, `info.xml` release date; comment on and close each GitHub issue referencing commits |

## Timeline/ordering rationale
Phases 0–2 deliver the reporter's upgrade blockers (#42–#46) early. Phase 3 precedes 4 because the admin Candidates screen is the home for statements/candidate editing. Phases 5–7 add data-model-heavy features after the UI scaffolding and tests exist.
