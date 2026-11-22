# Version 2.1

> **Historical.** Written during version 2.1; may be out of date. Current guidance: [developer guide](../../README.md).

Released 2026-11-03 as pull request 49 (squash commit `9a5c869`; tag `2.1.0` is the `Release 2.1.0` commit that **Cut release** made on the `release` branch). The work ran from 2026-11-01, the day 2.0.0 was released, on a `version-2.1` branch of 17 commits. This is the story of that release; the detail is in [the archive](archive/).

## Goals

2.0 added a great deal. 2.1 made the whole extension look and behave as one product, and fixed what that exposed:

- write down one standard for every screen (words, page frame, buttons, labels, messages, emails, accessibility), get it approved, and review every screen against it;
- build every change from that review, including the behaviour problems it found;
- act on the maintainer's review of the election overview and back end forms;
- secure and stabilise: a security policy, rules protecting `master`, and a way to keep the browser test tools through DDEV rebuilds.

## What shipped

From the 2.1.0 release and pull request description:

**For administrators**

- **One frame on every back end page**: a breadcrumb that starts at Manage Elections, edit forms that end with **Submit**, **Back** and **Back to Manage Elections**, and buttons of one size. Back keeps the election you were working on.
- **Election Overview**: the action buttons in one row under the heading (Edit Election first); the audit log with a heading, readable labels, who made each change, and the whole fingerprint with a Copy button.
- **Lists** (Manage Elections, Positions, Candidates, Results, Audit Log): actions in the action bar; row actions Edit, Duplicate, Activate or Deactivate and Delete; a running election shows "Locked" instead of Edit and Delete; stage words (Inactive, Not started, Nominations open and so on); positions can be reordered by keyboard; a candidate Status (Nominee, Candidate, Declined or Withdrawn); captions and a card layout on a phone.
- **Election form** tabs (Details, Dates, Participation, Display, Emails, Privacy), each date named and explained, labels in sentence case, dates in CiviCRM's configured format with the time zone stated.
- **Dialogs and messages**: Withdraw Election became **Deactivate**; every dialog says what will happen; messages are short sentences with colours of their own in every CiviCRM theme.

**For people taking part**

- The election list opens on a personal link; times are shown with one "Times are shown in ..." line.
- A nominee **Accepts** or **Declines**; **Withdraw** is for someone who already accepted, and the two are stored apart.
- The ballot's **Review Your Vote** step has **Go Back and Change** and **Submit Vote**; every sub page has a Back to the election link.
- Access denied, not found and voting closed pages say what is wrong and what to do, with the right status; about 30 developer-worded messages became plain sentences. The four emails say what the person is asked to do.

**Fixed**: editing an election switched it off; seats and required nominations could be saved as 0; a PHP warning on the election list; the audit log always said "System" under Who; a server error on every new back end page on Joomla and a deprecation message on Drupal.

**Documentation and housekeeping**: the user documentation describes the new screens; the README opening was rewritten; `SECURITY.md` was added; the three test workflows run on pushes to `master` and on every pull request; `info.xml` is 2.1.0.

## How it was done

1. **Standards first.** The user interface standards guide was drafted from a read of the extension's 627 interface strings, routes, SearchKit searches and templates, then approved decision by decision (17 decisions). It is now [../../standards/ui-standards.md](../../standards/ui-standards.md).
2. **Review by reading**: three reviewers checked every screen against the guide (about 350 findings and a 56-row text scan, in [archive/21-ui-review.md](archive/21-ui-review.md) and its folder), then the back end and front end were looked at in Chrome as an administrator and as a voter on a personal link. That found problems the reading missed (the audit log's Who column, the Candidates status words, missing message colours on CiviCRM 6.19).
3. **Twenty decisions** (R1 to R20) were approved together, then built in an order that kept each change testable.
4. **Maintainer review of the review site** after deployment found two more bugs (button sizes, and Back from a form losing the election), fixed before release.

## Key decisions

In [../../decisions/interface-and-wording.md](../../decisions/interface-and-wording.md):

- the primary button on every edit form is **Submit**, never Save;
- Accept and Decline for nominees, Withdraw only after accepting; Deactivate in place of Withdraw Election; "Locked" is a word of the interface;
- dates and times use the format configured in CiviCRM, with the site time zone stated;
- an inactive election that has ballots keeps its voting rules locked: the finer rule already protects the vote, so editing is not refused outright;
- editing an election must not deactivate it (a behaviour fix);
- Joomla's breadcrumb (the current page is a link) is a recorded exception.

In [../../decisions/platform-testing-and-release.md](../../decisions/platform-testing-and-release.md): dropping the CiviCRM 6.16.6 update of the test sites; no 2.0.1 and no 1.3.4, so every fix went into 2.1; the shared developer guide came later (see the roadmap).

## Upgrade steps added

| Step | What it does |
|---|---|
| 20120 | Rewords the four emails. The reserved original is always replaced; the editable copy only if the site never changed it. A template a site has edited is left as it is. |
| 20130 | Adds `has_withdrawn_nomination`. Nominations refused before the upgrade cannot be told apart, so they show as Declined. |

Current reference: [../../architecture/upgrades.md](../../architecture/upgrades.md). The next free step after 2.1 is `20140`.

## Test numbers at release

| Suite | Result |
|---|---|
| PHPUnit (WordPress site) | 321 tests at the 1 November build, 323 after the 3 November fixes, passing |
| Playwright, WordPress | 115 passed at the full run of 1 November (2 flaky, passing on retry), 120 passed after the 3 November fixes |
| Playwright, Drupal 11 | 117 passed at the full run of 1 November; the frame, overview and manage specs rerun after 3 November |
| Playwright, Joomla 5 | 110 passed with 8 skipped (six by design, because that CMS had no manager or limited session; one needed the session of an ordinary person; one is the screenshots spec); the frame specs rerun after 3 November |
| Upgrade from 1.3.2 | Passed |
| GitHub Actions on `master` after the merge | PHPUnit, upgrade and frontend workflows all passed |

## What was learned

- **A standard needs a live check.** The reading review gave about 350 findings, and looking at the screens in Chrome still found things the reading could not.
- **The referrer drops the `#` part**, where SearchKit lists keep the election. Any link from a list to a form must carry the election in the query as well. See [../../roadmap/2.2/README.md](../2.2/archive/roadmap.md) (traps).
- **CSS scope matters.** A rule that sized the back end buttons once also caught the front end ballot and failed the layout spec; the selector is now scoped to forms with a Back button.
- **CiviCRM 6.19 gave error and warning messages no colours of their own**, so a refusal looked like a success message; the extension now styles them.
- **DDEV rebuilds lost the browser tools.** The fix is a build file that installs the Chromium libraries and a shared browser cache; it is now part of [../../environments/README.md](../../environments/README.md).
- **Real defects came out of the review**: zero seats, a PHP warning on a list, a server error on Joomla and a deprecation on Drupal.

## What moved to the next version

The Drupal and Joomla GitHub workflows, re-recording the user guide pictures and the Joomla manager sessions were moved to 2.2 on 2026-11-03; the granular permission was deferred; and the voting features left over from 2.0 (Senate count, drag to rank, dark mode re-check, a mobile app discovery) were already planned there. See [../../roadmap/2.2/README.md](../2.2/archive/roadmap.md).

## Archive

All files are in [archive/](archive/):

- [19-user-review-2026-11-01.md](archive/19-user-review-2026-11-01.md)
- [21-ui-review.md](archive/21-ui-review.md) and its folder [21-ui-review/](archive/21-ui-review/): [backend-forms.md](archive/21-ui-review/backend-forms.md), [frontend-emails.md](archive/21-ui-review/frontend-emails.md), [searchkit.md](archive/21-ui-review/searchkit.md)
- [23-readme-draft.md](archive/23-readme-draft.md)
- [08-progress.md](archive/08-progress.md) (from 2026-11-01, including the 2.0.0 release day, to 2026-11-15)
