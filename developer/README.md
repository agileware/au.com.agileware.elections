# Developer guide

For people and AI assistants who work on the Elections extension (`au.com.agileware.elections`): how it is built, how to set up test sites on WordPress, Drupal and Joomla, how to seed them with elections, how to run the browser and PHP tests, and what earlier versions decided and why. The aim is that you can follow it without redoing work that has been done.

This folder is **not part of the released extension**: the Cut release workflow leaves out `developer/` and `AGENTS.md`. The user guide for people who run elections is in [`docs/`](../docs/index.md).

## How this folder is organised

Every page is one of three kinds:

| Kind | What it is | Rule |
|---|---|---|
| **Guide** | Steps to do something (set up a site, run a suite, cut a release) | Current. Starts with a "Verified with" line: the versions and the date it was last followed. |
| **Reference** | Facts and decisions that stay true (data model, standards, decisions) | Current. Changes with the code. |
| **History** | What earlier versions planned, found and delivered | Frozen. Dated, may be out of date, never edited except for links. |

When a guide and the code disagree, trust the code and fix the guide in the same pull request.

## Contents

Sections are written in this order; the status says what exists today.

| Section | Contents | Status |
|---|---|---|
| `environments/` | DDEV sites for WordPress, Drupal and Joomla; CiviCRM 6.16 and 6.19; the workspace layout; the Beekeepers review site | To write |
| `ddev-kit/` | The files to copy into a DDEV site, and a script that builds a test site for each CMS | To write |
| `testing/` | PHPUnit, Playwright on each CMS, the upgrade test, seeding test data, personas, CI, known CMS differences | To write |
| `architecture/` | Code map, data model and upgrade steps, voting methods, security model | To write |
| `standards/` | User interface standards, wording rules, commit and pull request conventions | To write |
| `decisions/` | One short entry for each decision: what, why, when | To write |
| `releases/` | How to cut a release, branch rules, what ships and what does not | To write |
| `roadmap/` | The plan for the next version | To write |
| `history/` | Summaries and archived plans of 2.0 and 2.1 | To write |

Test data scripts are code, so they live in `tests/seed/` (also left out of releases) and are described in `testing/`.

## Ground rules

The short list for anyone changing the extension is in [`AGENTS.md`](../AGENTS.md) at the top of the repository. [`CONTRIBUTING.md`](../CONTRIBUTING.md) covers tests, templates, wording and releases.

## Writing and changing pages here

- Write steps in the order a newcomer would do them. No progress diary, no "waiting for" notes, no questions that have been answered.
- Keep names of people, accounts and machine paths out. Credentials, passwords and personal links are never committed: document how to generate them.
- Put the "Verified with" line at the top of every guide and update it when you follow the guide again.
- Link rather than copy: one fact lives in one page.
