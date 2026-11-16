# Decisions

One short entry for each decision made while building Elections 2.0 and 2.1: what was decided, why, when, and where it shows in the code or documentation. This is reference material: it records decisions that still hold. Why the plan reached a decision is in [`../history/`](../history/README.md) and the current rules that follow from the decisions are in [`../standards/`](../standards/README.md).

| Page | Covers |
|---|---|
| [`voting-and-elections.md`](voting-and-elections.md) | Candidates, statements, groups, write-ins, referendum questions, locking, dates, results page, behaviour fixes, the open voting method questions |
| [`interface-and-wording.md`](interface-and-wording.md) | "People, not members", the glossary, button and label rules, breadcrumbs, dates, the screen review |
| [`platform-testing-and-release.md`](platform-testing-and-release.md) | Supported CiviCRM and CMS versions, test environments, branch rules, releases, the developer guide |

## How the IDs work

The plan's IDs are kept, so the archived plan still matches. Renumbering is not allowed. Where two plan documents used the same ID for different things, the IDs are prefixed:

| Prefix | Source | Date |
|---|---|---|
| `D1` to `D32` | The plan's decisions log | October to 17 November 2026 |
| `R2.0-1` to `R2.0-10` | The review of 31 October 2026 (version 2.0). Also logged as `D20` to `D29`, which stay the main ID (`R2.0-1` is `D20` and so on) | 2026-10-31 |
| `R2.1-1` to `R2.1-20` | The screen review against the interface standards (version 2.1) | 2026-11-01 |
| `U1` to `U17` | The interface standard decisions | 2026-11-01 |
| `F1` to `F7` | Choices made while building from the maintainer's feedback on the election overview | 2026-11-01 |
| `SD1` to `SD8` | The eight small decisions made after the 2.1 build | 2026-11-02 |
| `S1` to `S6` | Questions about the Senate count (STV) pull request, still open | |
| `G2`, `G5`, `PLAT1`, `ENV1`, `REL1` to `REL3` | Platform and release decisions that were not numbered in the plan, or are from the CI review | 2026-10-28 to 2026-11-03 |

## Index

Status is **Decided** (and in force), **Applied** (decided on a default and built), **Deferred**, **Open**, or **Replaced**.

### Voting and elections ([`voting-and-elections.md`](voting-and-elections.md))

| ID | Title | Decision in one line | Status |
|---|---|---|---|
| D1 | Statement field | Reuse `comments` and add an optional `statement_url` | Applied |
| D2 | Statement format | Plain text, escaped, line breaks kept | Applied |
| D3 | Statement display default | New elections show "Read statement"; upgraded elections show none | Applied |
| D4 | Photo scope | Hiding photos hides them everywhere | Applied |
| D5 | Name format | Three formats, site default plus per-election override | Applied |
| D7 | Admin-managed nominations | A setting turns online nominating off | Decided |
| D8 | Write-ins | A voter picks a person from a group; the person must accept before voting closes | Decided |
| D9 | Separate groups | Optional nominating and candidate groups; empty means the voting groups | Decided |
| D10 | Referendum questions | A separate option table; thresholds, quorum, results show Passed, Not passed or No quorum | Decided |
| D11 | Nomination emails | Message templates and a per-election switch | Applied |
| D17 | Results visibility | Keep the current access; results show winners, never counts | Decided |
| D21 (R2.0-2) | Active elections are locked | No Edit, Delete or Deactivate on the row; a confirmed Deactivate asks for the name | Decided |
| D22 (R2.0-3) | Read-only positions | A separate page for an active election and a clear message on refused writes | Decided |
| D24 (R2.0-5) | Nomination email template | Per-election drop-down; "Election - " plus a verb for every template title | Decided |
| D25 (R2.0-6) | Date calculator | One server-side function with fixed rules and four cycles | Decided |
| D27 (R2.0-8) | Celebration effects | Five effects, each visit for N days after results, default off | Decided |
| D28 (R2.0-9) | Results page logo and text | Per-election plain text and logo, editable at any time | Decided |
| D29 (R2.0-10) | Organiser permissions | Document them and show a notice; no group-title API | Decided |
| R2.1-1 | Edit keeps the Active state | Saving the form no longer deactivates an election or changes its creator | Decided |
| R2.1-2 | Declined and Withdrawn | Stored apart; older refusals show as Declined | Decided |
| R2.1-3 | "Your vote" block | Kept, shown to the voter alone and never when votes are anonymised | Decided |
| R2.1-20 | Election list on a personal link | Add the access callback to the list route | Decided |
| SD7 | Rules stay locked | Who votes and how votes count stay locked once ballots exist, even when inactive | Decided |
| SD8 | STV upgrade steps | Start at `20140` | Decided |
| D13 | Granular permission | A permission between `administer` and `view` | **Deferred to 2.2** |
| D14 | More voting methods | Approval and STV later; drop approval | **Open** |
| S1 | STV ties | Report ties; do not draw automatically | **Open** |
| S2 | STV setting | Per position or per election | **Open** |
| S3 | STV formal ballot | Optional preferential | **Open** |
| S4 | STV timing | 2.2 | **Open** |
| S5 | Speaking to the fork | Whether to comment on or contact the pull request's author | **Open** |
| S6 | Running the STV engine | Run its tests in an empty directory outside the project | **Open** |

### Interface and wording ([`interface-and-wording.md`](interface-and-wording.md))

| ID | Title | Decision in one line | Status |
|---|---|---|---|
| D6 | Wording pass | Review and translate every string | Applied |
| D20 (R2.0-1) | Manage Elections filters | Help under each filter; date ranges for voting close and results; a Stage filter | Decided |
| D23 (R2.0-4), U2 | People, not members | "People"; database and API names keep `member_` | Decided |
| D26 (R2.0-7) | Example name | Robert "Rob" Irwin | Decided |
| U1 | Capitalisation | Title Case for actions and screens, sentence case for labels and text | Decided |
| U3 | Decline and Withdraw | Decline before accepting, Withdraw after; the route keeps `reject` | Decided |
| U4 | Deactivate | Replaces Withdraw Election, with a typed-name confirmation when running | Decided |
| U5 | Active and Inactive | The words administrators see | Decided |
| U6 | Submit | One word on every edit form | Decided |
| U7 | Back | Returns to where the person came from, else the breadcrumb parent | Decided |
| U8 | Breadcrumb | Manage Elections, election, page, on every full back end page | Decided |
| U9 | Button families | `button` on the back end, `af-button btn` in SearchKit, `election-btn` on the front end | Decided |
| U10 | Dates | Always CiviCRM's configured format; time zone once per page | Decided |
| U11 | No ellipsis | No "..." in text | Decided |
| U12 | Empty states | A reason and a next step on every list | Decided |
| U13 | Icons | One fixed icon per action | Decided |
| U14 | Standards in `docs/` | Whether to publish the standards with the extension | **Open (2.2)** |
| U15 | Button table | Placement, order and labels as the standard's tables | Decided |
| U16 | Breadcrumb table | A trail for every page | Decided |
| U17 | Field labels | Date labels name the event; unique labels on each form | Decided |
| F1, F3, F4 | Back, breadcrumb, Submit | Recorded while building; the same as U7, U8, U6 | Replaced by U7, U8, U6 |
| F2 | Which screens get the frame | Full pages; popups keep their own buttons | Applied |
| F5 | Edit Election when run | Disabled with the reason, not hidden | Applied |
| F6, F7 | Fingerprint and audit labels | Whole fingerprint with Copy; readable action labels | Applied |
| SD1 | Locked | In the glossary | Decided |
| SD2 | Delete dialog sentence | Says what is lost | Decided |
| SD3, SD4 | "CMS" label, template variables | Both stay | Decided |
| SD5 | Labels added | Second Nomination, Add Another Write-in, View Election Summary | Decided |
| SD6 | Joomla breadcrumb | The current page is a link on Joomla | Decided (exception) |
| R2.1-4 to R2.1-19 | Screen review decisions | Overview heading, Candidates and Results lists, locked rows, Settings footer, Edit Statement footers, no admin tools on the front end, links, "please", celebration, dialog names, error pages, SearchKit limits, time zone line | Decided |

### Platform, testing and release ([`platform-testing-and-release.md`](platform-testing-and-release.md))

| ID | Title | Decision in one line | Status |
|---|---|---|---|
| D12 | Minimum CiviCRM | 6.16 or later | Decided |
| PLAT1 | Three CMSs | WordPress, Drupal 11 and Joomla 5, same markup | Decided |
| D15 | Project directory name | Use the existing workspace folder | Done |
| D16 | Plan as its own repository | Every change committed | Replaced by D30 |
| D18 | Playwright location | Inside the DDEV web container | Decided |
| D19 | Drupal and Joomla in CI | Add workflow variants to `ci-workflows` | Decided, not built (2.2) |
| ENV1 | Chromium in DDEV | Libraries in the image, browser in the global cache | Decided |
| G2 | CI theme | Astra, the shared workflow default | Decided |
| G5 | Documentation navigation | The extension's own `mkdocs.yml` | Decided |
| REL1 | Supported versions | 2.x only; no 1.3.4 | Decided |
| REL2 | `master` ruleset | Pull request, one approval, three checks | Decided |
| REL3 | Cutting a release | Cut release workflow builds `release`; development files stay out | Decided |
| D30 | Developer guide | Move plans, environments and tests into `developer/` | Decided |
| D31 | Test account passwords | Generated at setup, never committed | Decided |
| D32 | Test suite clean-up | No Playwright project chain; low-value tests removed; screenshot walk is a tool | Decided |

## Open decisions

These are asked of the maintainer; do not assume an answer.

| ID | Question | Recommended or default |
|---|---|---|
| D13 | A granular permission such as `manage Election candidates` | Build only if an organisation needs it (deferred to 2.2) |
| D14 | Voting methods beyond ranked choice and most votes | STV in 2.2; drop approval voting (unconfirmed) |
| S1 | Ties in an STV count | Report the tie |
| S2 | Per position or per election setting for STV | Per position if mixing is wanted |
| S3 | What counts as a formal STV ballot | Optional preferential |
| S4 | When to build STV | 2.2 |
| S5 | Whether to comment on or contact the author of the STV pull request | Not yet decided; nothing has been posted |
| S6 | Whether to run the STV engine's tests and stress checks | Yes, in an empty directory outside the project |
| U14 | Whether the standards also go into `docs/` and a pull request template | Decide in 2.2 |
| (none) | Test data scripts in the repository (`tests/seed/`) | Being added with this guide; confirm |
| (none) | The 2.2 branch name and whether it is pushed as it goes | Branch `version-2.2` made 2026-11-17; push rule still to confirm |

## How to add a decision

1. Pick the page by topic. Add one entry with **Decision**, **Why**, **Date** and **In the code** (class, file or document that shows it). Keep it to a few lines.
2. Give it an ID. Use the next free `D` number for a decision on how the extension behaves or is run (the last is `D32`). Use a topic prefix as above for decisions from a review. Never reuse or renumber an ID.
3. Add a row to the index above with its status. Move it out of **Open decisions** when it is answered, and record the date of the answer.
4. Say "the maintainer decided" and give the date. Do not name people.
5. If a new decision replaces an old one, mark the old one **Replaced by** the new ID and keep it.
6. Update the code, [`../standards/`](../standards/README.md) or the tests in the same pull request, and link them from the entry.
