# Version 2.0

> **Historical.** Written during version 2.0; may be out of date. Current guidance: [developer guide](../../README.md).

Released 2026-11-01 as pull request 48 (squash commit `5a314e7`, tag `2.0.0`). Work ran from 2026-10-27 to 2026-11-01 on a `version-2` branch of 95 commits. This is the story of that release; the detail is in [the archive](archive/).

## Goals

Version 1.3.2 worked, but a site upgrading from 1.2.6 and several open GitHub issues showed what was missing. The goals were:

- answer every open issue (eleven at the start: candidate statements, photos, nicknames, wording, seconding, accepting nominations, separate nominating groups, referenda, custom fields, accepting or rejecting a nomination);
- redesign the front end for everyone who takes part and the back end for organisers, with SearchKit lists, drag and drop, duplicate and click to enable;
- make the extension trustworthy enough to run real elections: a security review of every entry point;
- work on WordPress, Drupal 11 and Joomla, with the tests to prove it, on GitHub as well as on a laptop;
- upgrade existing 1.x installs without losing anything.

It began with a review of the existing code and docs ([code review](archive/02-code-review.md)) and an [analysis of each issue](archive/03-issues-analysis.md) that grouped overlapping ones, then a [phased plan](archive/04-implementation-plan.md).

## What shipped

The GitHub release notes and the pull request description are the sources for this list; the [release notes draft](archive/14-release-notes-and-issue-comments.md) has the per-issue comments.

**For voters and candidates**

- A new election page with a stage stepper and one clear "what you can do now" action; election cards on the list.
- A redesigned ballot: a review step before the vote is saved, works on a phone, dark mode and theme colours.
- Candidate statements (with an optional link), photos that can be switched off, a choice of name format (nicknames).
- Counting by **ranked choice** or **most votes win**.
- **Write-ins**: a voter names a person from the candidate group; the person is told, must accept before voting closes, and then appears on the ballot.
- **Referendum questions**: yes or no on the same ballot, with a pass threshold and optional quorum.
- Ties are shown as ties, with no candidate chosen over another.

**For administrators**

- Manage Elections, Positions, Candidates, Results and Audit Log screens on SearchKit, with drag and drop ordering, duplicate and inline editing.
- A tabbed election form that checks dates as you type; an overview with stage, timeline, turnout (counts only), integrity and recent changes.
- Separate groups for who may nominate, who may stand and who may vote; online nominating can be turned off for elections where organisers choose the candidates.
- Optional emails from editable message templates; a results page with a logo, header, footer and a celebration effect.

**Trust and security.** A review with threat model, 15 fixes and attack tests on all three CMSs: every output escaped; one ballot per person and one nominator count per person enforced by the database; ballots only added by the ballot and only while voting is open; results generated once under a lock; a hash-chained audit log; ballot and result fingerprints with an integrity check; anonymising that removes the voter, the time and the order; rules that decide who votes locked once people have voted. The current description is in [../../architecture/security.md](../../architecture/security.md).

## Key decisions

The decisions are written up, with their reasons, in:

- [../../decisions/voting-and-elections.md](../../decisions/voting-and-elections.md): statements stored on the nomination with a link field; display options per election; a new options table for referenda, with pass threshold and quorum; write-ins as nominations marked as such, accepted by the person before voting closes; separate nominating and candidate groups; CiviCRM 6.16 as the minimum; results show winners and how each was decided, not vote counts.
- [../../decisions/interface-and-wording.md](../../decisions/interface-and-wording.md): "people" instead of "members"; the review of 31 October (Manage Elections filters, read-only positions on a running election, date rules, celebration effects, results page logo and text, permissions documented).
- [../../decisions/platform-testing-and-release.md](../../decisions/platform-testing-and-release.md): Playwright runs inside the DDEV web container; WordPress, Drupal and Joomla workflows to follow in `ci-workflows`.

## Upgrade steps added

Steps `20000` to `20110` in `CRM_Elections_Upgrader`:

| Steps | What they do |
|---|---|
| 20000 | Display options for each election and a statement link for each nomination |
| 20010, 20020 | Remove duplicate ballot and nominator rows (keeping the oldest), add unique keys, change the contact foreign key so a deleted contact no longer deletes ballots, create the audit log |
| 20030 | Counting method for each election (ranked choice or most votes win) |
| 20040 | Separate nominating and candidate groups |
| 20050 | Emails about nominations (off for existing elections) |
| 20060 | Write-ins |
| 20070 | Referendum questions (options tables, threshold, quorum, wording) |
| 20080 | Online nominating switch (on for existing elections) |
| 20090 | Notice to write-ins who never answered |
| 20100 | Choice of nomination email template; message templates titled "Election - ..." |
| 20110 | Results page header and footer, logo, celebration effect |

Existing elections behave as before: ranked choice, the voting groups for every role, no emails, online nominating on, no write-ins, no questions. The upgrade was tested from 1.3.2 to this release on WordPress, Drupal and Joomla with elections in each stage. The current reference is [../../architecture/upgrades.md](../../architecture/upgrades.md) and the test is described in [../../testing/upgrade-test.md](../../testing/upgrade-test.md).

## Test numbers at release

| Suite | Result |
|---|---|
| PHPUnit (WordPress site) | 297 tests, 3329 assertions, passing |
| Playwright on WordPress | 90 passed, 1 skipped by design (the screenshot walk, which needs `SCREENS=1`) |
| Playwright on Drupal and Joomla | Run locally on DDEV for the back end, results display and council election suites (Drupal back end 37 passed; Joomla main project 33 passed with 6 skipped because that CMS had no manager or limited session) |
| Upgrade from 1.3.2 | Passed on WordPress, Drupal and Joomla |
| GitHub Actions on the release commit | PHPUnit, upgrade and frontend workflows all green |

## What was learned

- **A pass on one CiviCRM version is not a pass.** The development site ran 6.16.5 and the review site, Drupal, Joomla and GitHub ran 6.19, which hides messages of AJAX errors unless the exception is a `CRM_Elections_Exception`, needs `is_public` on front end routes on WordPress, and answers refused pages with 500. These were each found by surprise on 6.19 after passing on 6.16. See [../../testing/cms-and-civicrm-differences.md](../../testing/cms-and-civicrm-differences.md).
- **Long test runs need care.** A hung wait cost about nine hours, and failures that looked like bugs came from running six DDEV sites at once on a small Docker VM. Run the job in the background, check the log, run one job per site, keep other sites stopped.
- **Moving CI to GitHub found real bugs**: a missing lock file, checkout permissions in the container, a 6.19 front end check that sent anonymous voters to the WordPress login, and a missing test data placeholder. Fixes went into the shared `ci-workflows` repository. See [../../testing/ci.md](../../testing/ci.md).
- **The security review paid for itself**: script injection through names on nearly every page, ballots counted twice, results editable afterwards, anonymised ballots that could be matched to voters. Each has a test.
- **Write-ins and referenda are better as new records with their own rules** than as special cases of existing rows, because the ballot, counting, anonymising and fingerprint queries all join votes to nominations.

## What moved to the next version

- A user interface standards guide and a review of every screen against it (became 2.1).
- Re-recording the user guide pictures, the Drupal and Joomla workflows on GitHub, drag to rank on the ballot, a dark mode re-check, the Senate count, a granular permission and the Joomla manager sessions (these moved on to 2.2: see [../../roadmap/2.2/README.md](../../roadmap/2.2/README.md)).
- Version 1.x is no longer supported and there was no 1.3.4: only 2.x gets fixes.

After the release the maintainer commented on and closed all eleven issues, and the `version-2` branch was deleted.

## Archive

All files are in [archive/](archive/):

- [02-code-review.md](archive/02-code-review.md), [03-issues-analysis.md](archive/03-issues-analysis.md), [04-implementation-plan.md](archive/04-implementation-plan.md)
- [13-phase4-frontend-design.md](archive/13-phase4-frontend-design.md)
- [14-release-notes-and-issue-comments.md](archive/14-release-notes-and-issue-comments.md)
- [17-user-review-2026-10-31.md](archive/17-user-review-2026-10-31.md)
- [08-progress.md](archive/08-progress.md) (up to 2026-10-31; the entries from 1 November on, which include the release day, are in the [2.1 archive](../2.1/archive/08-progress.md))
