# History

> **Historical.** Written during version 2.1; may be out of date. Current guidance: [developer guide](../README.md).

What earlier versions of the extension planned, found and delivered. Pages here are frozen: they are not updated when the code changes, and the only edits allowed are fixes to links. For how things work today, use the guides and reference pages in the other sections of the [developer guide](../README.md).

## Contents

| Version | Released | Summary | Archive |
|---|---|---|---|
| 2.0 | 2026-11-01 (pull request 48) | [2.0/README.md](2.0/README.md): the rewrite of the extension for version 2, from the code review of 1.3.2 to the release | [2.0/archive/](2.0/archive/) |
| 2.1 | 2026-11-03 (pull request 49) | [2.1/README.md](2.1/README.md): one consistent user interface on every screen, and the fixes that found | [2.1/archive/](2.1/archive/) |
| 2.2 | 2026-11-22 (pull request 55) | [2.2/README.md](2.2/README.md): drag to rank, a permission for managing candidates, dark mode, new guide pictures, Drupal and Joomla on GitHub, and this developer guide | [2.2/archive/](2.2/archive/) |

The plan for the next version is in [`../roadmap/`](../roadmap/README.md) and moves here when it is released.

## How to read it

1. Start with the summary of the version you care about. It tells the story in one or two pages and links to the decisions and to the pages that now own each topic.
2. Open an archive file only when you need the detail: the reasoning behind a design, the findings of a review, or the wording of a release note.
3. Every archive file opens with a banner, and a line that says which version and dates it covers. Names, machine paths and personal account names were removed when the files were copied from the original working notes. Screenshots from the working notes were not kept.
4. The archive files refer to each other and to working notes by their old numbers (`11-todo.md`, `20-ui-standards.md`). Links to notes that were rewritten into living pages now point to those pages; links to notes that were not kept are shown as plain text with "not kept".
5. The word "member" appears in the archive because it was the wording before 2.0 and 2.1 replaced it with "people" in everything a person reads.

## What each archive file is

| File | Version | What it holds |
|---|---|---|
| `02-code-review.md` | 2.0 | Review of the 1.3.2 code and documentation: structure, gaps, technical observations |
| `03-issues-analysis.md` | 2.0 | The open GitHub issues, overlaps and duplicates, the proposed solution for each, and the questions that needed answers |
| `04-implementation-plan.md` | 2.0 | The phased plan for version 2 |
| `13-phase4-frontend-design.md` | 2.0 | The design of the front end redesign |
| `14-release-notes-and-issue-comments.md` | 2.0 | The 2.0.0 release notes and the comment written for each issue |
| `17-user-review-2026-10-31.md` | 2.0 | Ten review points from the maintainer, with proposals, decisions and what was built |
| `08-progress.md` (part 1) | 2.0 | Progress log up to 2026-10-31 |
| `19-user-review-2026-11-01.md` | 2.1 | Review of the election overview and the rules for back end edit forms |
| `21-ui-review.md` and `21-ui-review/` | 2.1 | The screen-by-screen review against the user interface standards |
| `23-readme-draft.md` | 2.1 | The draft of the new README opening |
| `08-progress.md` (part 2) | 2.1 | Progress log from 2026-11-01 to 2026-11-15 |
| `roadmap.md` | 2.2 | The living 2.2 roadmap page as it stood at the release |

The working notes that became living pages are not archived, because their content is now in the guide: the environment and test environment notes (see [`../environments/`](../environments/README.md)), the testing, Playwright and CI notes ([`../testing/`](../testing/README.md)), the schema and security notes ([`../architecture/`](../architecture/README.md)), the decisions log ([`../decisions/`](../decisions/README.md)), the user interface standards ([`../standards/`](../standards/README.md)), the Beekeepers review site notes, the STV review and the 2.2 handover ([`../roadmap/`](../roadmap/README.md)).
