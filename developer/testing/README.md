# Testing

This section says what the Elections test suites are, where each one runs, and which to run when. It is a reference page: the steps live in the pages it links to.

The extension has three automated suites, one set of test data for people reviewing by hand, and a checklist for reviewing screens as real people. Everything here is left out of the released extension.

## The suites

| Suite | Where it lives | What it is for | Page |
|---|---|---|---|
| **PHPUnit** (headless) | `tests/phpunit/` | Rules and code without a browser: dates and stages, who may nominate or vote, the voting methods, write-ins, referendums, the upgrade steps, security rules, wording, the emails. Fast (a minute or two). | [phpunit.md](phpunit.md) |
| **Upgrade test** | `tests/upgrade/` | Installs release 1.3.2, fills it with elections at three stages, upgrades to the code under test, and checks that nothing was lost or changed and the old elections still run. | [upgrade-test.md](upgrade-test.md) |
| **Playwright** (browser) | `tests/playwright/` | The screens, in Chromium, on WordPress, Drupal and Joomla: the back end as organisers, the front end as people on personal links, a complete mock council election, accessibility, layout at phone width and attacks. | [playwright.md](playwright.md) |

Test data is described in [seeding.md](seeding.md): the council fixtures the suites use, and the Beekeepers seed in `tests/seed/` for reviewing by hand. The people the tests act as are in [personas.md](personas.md). Known differences between CMSs and CiviCRM versions that the tests and code have to allow for are in [cms-and-civicrm-differences.md](cms-and-civicrm-differences.md). The GitHub workflows are in [ci.md](ci.md). Reviewing a screen yourself is in [manual-browser-testing.md](manual-browser-testing.md).

## Which suite runs where

CiviCRM versions: the main WordPress site runs 6.16 (an ESR build), the others 6.19. See [../environments/civicrm-versions.md](../environments/civicrm-versions.md).

| Site | PHPUnit | Upgrade test | Playwright | Notes |
|---|---|---|---|---|
| WordPress, CiviCRM 6.16 (DDEV site `elections2`) | Yes | Yes | Yes, all projects | The main development site. |
| WordPress, CiviCRM 6.19 (the Beekeepers review site) | Not set up | Not run there: it swaps the review data out for the length of the run | Possible; ask the maintainer first | The maintainer's review site: reviewed by hand, never reset without asking. |
| Drupal 11, CiviCRM 6.19 (DDEV site `elections2-drupal`) | No (no test database; use the kit site) | Yes | Yes (`CMS=drupal`), administrator, manager, limited and voter sessions | |
| Drupal 11, CiviCRM 6.19 (kit site `kit-test-drupal`) | Yes (since 2.2): the kit makes the test database | Not run | Yes (`CMS=drupal`) | Built by [../ddev-kit/](../ddev-kit/README.md). Runs PHPUnit on 6.19. |
| Joomla 5.4, CiviCRM 6.19 (DDEV site `elections2-joomla`) | No: never run it there | Yes | Yes (`CMS=joomla`, `CIVI_CV=jcv`), all four sessions | All specs run (since 2.2). |
| GitHub Actions (WordPress in Docker, the newest stable CiviCRM the pipeline image installs) | Yes | Yes | Yes | The only suites that gate a pull request. Drupal and Joomla run the browser suite by hand and weekly ([ci.md](ci.md)), not as a pull request check. |

The DDEV sites are described in [../environments/README.md](../environments/README.md).

## Which to run when

| You changed | Run before asking for review |
|---|---|
| A rule, the voting code, an API or a BAO | PHPUnit on WordPress (6.16); before a release also on the Drupal kit site (6.19). |
| The database (a new column or table) | PHPUnit and the upgrade test, and the new upgrade step's own test ([upgrade-test.md](upgrade-test.md)). |
| A screen, template, style or script | PHPUnit, then the whole Playwright suite on WordPress, and the affected specs on Drupal and Joomla. Then look at the screen as a person ([manual-browser-testing.md](manual-browser-testing.md)). |
| Wording | PHPUnit (`WordingTest` fails on "member" in anything a person reads). |
| Anything on a screen or in an AJAX call | Also check CiviCRM 6.19 and 6.16: a pass on one is not a pass. |
| Before a release | Everything above on all three CMSs, the upgrade test on all three, and CI green. |

Three rules apply to every run:

- Never run two test jobs on one site, and do not deploy while one runs: a deploy replaces the files being tested.
- Stop the DDEV sites you do not need. A full browser run fails from overload when several sites are running.
- Run long jobs in the background with a log file, and read the log; do not poll in a loop.
