# Version 2.2

> **Historical.** Written during version 2.2; may be out of date. Current guidance: [developer guide](../../README.md).

Released 2026-11-22 as pull request 55 (squash commit `f13b081`; tag `2.2.0` is the `Release 2.2.0` commit that **Cut release** made on the `release` branch). The work ran from 2026-11-15, when the developer guide was started, on a `version-2.2` branch of 62 commits cut from `master` on 2026-11-17. This is the story of that release; the detail is in [the archive](archive/).

## Goals

2.2 was planned as "new voting features" after 2.1.0. During the work the maintainer moved the largest items on (the Senate count to 2.3, the mobile voting app to 2.4) and kept 2.2 to:

- finish the ballot work left from 2.0: drag to rank, and a real dark mode and high contrast check;
- a permission for people who look after candidates but do not run elections;
- test every CMS on GitHub, not only WordPress, and finish the test clean-up;
- new pictures in the user guide, with a way to make them again;
- move the project's working notes into this developer guide, so anyone can pick the project up.

## What shipped

From the 2.2.0 pull request description:

**For administrators**

- **manage Election candidates**, a new permission: Election Candidates (choose an election), its Candidates list, Add Candidate and Edit Statement, and nothing else in the back end. It reads elections, positions and nominations but cannot change them.
- **Lists**: the Add buttons at the right end of the action bar, also when it wraps; on Joomla the table scrolls in its own box so the Add button stays in view; a disabled Add Position button keeps the bar's height; each list page checks its own permission.

**For people taking part**

- **Drag to rank** on a ranked ballot: a drag handle and Add to Ranking, Move Up, Move Down and Remove from Ranking buttons, ranked candidates first, each new rank read out to screen readers. The rank drop-downs stay and work without JavaScript; nothing new reaches the server.
- **Dark mode**: drop-down text, quiet buttons, badges and links readable on CiviCRM's dark theme.

**Fixed**: a list page opened without its permission returned an HTTP 500 ("Index 0 not found in api results") instead of a refusal.

**Documentation and housekeeping**: all 27 user guide pictures remade as PNG screenshots (9.7 MB of GIFs became 1.5 MB) by a tool and the **docs images** workflow; the known limits of the security model listed for developers and site operators; the browser tests on Drupal 11 and Joomla 5 in GitHub Actions (by hand and weekly); a pull request template with the user interface checklist; line coverage in CI; generated test passwords; the test clean-up; `Civi::router()` on CiviCRM 6.19; this developer guide, with roadmaps for 2.3 and 2.4.

No database steps.

## How it was done

1. **A developer guide first.** The working notes of 2.0 and 2.1 were rewritten into this guide (guides, reference pages, decisions, history), with a DDEV kit that builds a test site on any of the three CMSs and seed scripts for review data. It reached `master` on its own (pull request 51) before the 2.2 code.
2. **Test clean-up in two tiers.** Low-value tests were removed after checking that each assertion was covered elsewhere; the Playwright project chain went, so one failure no longer skips the rest; the layout and accessibility walks became one page walk.
3. **Each feature with its own tests on three CMSs.** Drag to rank and the new permission each got PHPUnit and Playwright tests, run on WordPress, Drupal and Joomla, on CiviCRM 6.16 and 6.19.
4. **CI for Drupal and Joomla.** A shared workflow in `agileware/ci-workflows` builds the sites with the DDEV kit on GitHub. Its first runs, and the first runs of the docs images workflow, were made from the branch with a temporary push trigger, removed in the next commit, because a workflow can be started by hand only from `master`.

## Key decisions

In [../../decisions/README.md](../../decisions/README.md):

- **D13**: the granular permission is manage Election candidates, for candidates only.
- **D14**: the Senate count (STV) moved to 2.3; approval voting is deferred until there is a requirement (it was an assistant's suggestion, not a request).
- **D19**: Drupal and Joomla run on GitHub by hand and weekly, not as a required check.
- **D30, D31, D32**: the shared developer guide; test passwords generated, never committed; the test suite clean-up.
- **U14**: the user interface standards stay in the developer guide, with a pull request template.
- The 2.2 branch was pushed as it went, with the release pull request opened only when 2.2 was ready.

## Upgrade steps added

None. The next free step is still `20140`. Current reference: [../../architecture/upgrades.md](../../architecture/upgrades.md).

## Test numbers at release

| Suite | Result |
|---|---|
| PHPUnit, WordPress (GitHub Actions) | 275 passed, with line coverage |
| PHPUnit, Drupal 11 kit site (CiviCRM 6.19.5) | 275 passed (the first PHPUnit run on Drupal) |
| PHPUnit, WordPress DDEV site (CiviCRM 6.16.5) | 274 passed |
| Playwright, WordPress | 108 passed on GitHub Actions, on the 6.16.5 DDEV site and on the 6.19.5 kit site |
| Playwright, Drupal 11 and Joomla 5.4.10 (GitHub Actions, CiviCRM 6.19.5) | 108 passed on each, no skips on any CMS |
| Upgrade from 1.3.2 | Passed |
| GitHub Actions on `master` after the merge | PHPUnit, upgrade, frontend tests and CodeQL all passed |

The Drupal and Joomla suites and the 6.16 upgrade check were not run again on the final commits, which changed only documentation, the guide pictures and the pictures tool.

## What was learned

- **Check that a test tool does what it says.** The theme variants script never applied a WordPress dark variation, so earlier dark mode checks had run on the light colours. Real dark runs found the contrast problems fixed in 2.2.
- **Generated files must reach the repository.** The guide pictures were first made into a test site's copy of the extension and lost when it was redeployed, while the guide already pointed at them. The docs images workflow now makes them on GitHub and opens a pull request.
- **Screenshots need the page to be still**: CiviCRM's pop-up messages sit outside the CiviCRM container; the WordPress block editor never goes "network idle"; WordPress shows its own update notices; a new site is on UTC. The tool now handles each.
- **CiviCRM 6.19 refuses some pages with HTTP 500**, not 403, and deprecates `CRM_Core_Menu::store()`. Both are handled and listed in the known allowances.
- **A workflow can be started by hand only once it is on `master`.** A temporary push trigger, removed in the next commit, is the way to run it from a branch.

## What moved to the next version

To 2.3: the Senate count (STV) and its questions S1 to S6, three small findings (38 px action bar buttons on a phone, the Joomla page width, one source for the election stage), and testing CiviCRM 6.16 on GitHub. To 2.4: the mobile voting app, now with a design draft. Approval voting is a potential future feature. See [../../roadmap/README.md](../../roadmap/README.md).

## Archive

All files are in [archive/](archive/):

- [roadmap.md](archive/roadmap.md): the living 2.2 roadmap page as it stood at the release (items, decisions, traps, start-up checklist).
