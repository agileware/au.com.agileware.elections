# Seeding test data

Written from the existing setup, 2026-11-15; not yet followed from scratch. The `tests/seed/` scripts were written for this page and have only had a syntax check.

There are two kinds of test data. They do different jobs and do not share code.

| | Council fixtures | Beekeepers seed |
|---|---|---|
| For | The automated suites | Reviewing by hand, demonstrations |
| Where | `tests/phpunit/CRM/Elections/Test/CouncilFixture.php`, `tests/playwright/fixtures/seed-council.php`, `advance-council.php` | `tests/seed/` (see its [README](../../tests/seed/README.md)) |
| Builds | One council election with 41 people ([personas.md](personas.md)) | 30 Beekeepers and eight elections, one of each kind, each at a stage that suits reviewing it |
| Stage control | `STEP=...` through `advance-council.php`, or the `moveToStage` helper | `move-election.php` |
| CMS | Any, with `cv scr` | Any, with `cv scr`; one optional WordPress-only script for logins |

## The council fixtures

`CouncilFixture` builds the personas, the election "Council Election" and its six positions, and can play nominations, responses and ballots as the people. PHPUnit uses it directly; Playwright reaches it through two `cv scr` scripts that use the same class, so both suites share one definition:

- `seed-council.php` removes the data of an earlier run, then builds "Playwright Council Election" at the nominations stage, with revoting on and personal links on. It writes `fixtures/.generated/council.json` (every persona's personal link, valid 30 days, plus the plan, the expected candidates and every ballot) and `council-personas.json` (for the step script). On WordPress it also puts the logged-in member user's contact in the election's group.
- `advance-council.php` takes `STEP`: `to-nominations`, `fill-nominations`, `to-advertise`, `fill-responses`, `to-voting`, `fill-ballots`, `to-results`, `generate`. The `to-` steps move the dates, the `fill-` steps do what the people not yet acted out would have done, and `generate` produces the results.

The Playwright helpers `reseedCouncil()`, `moveToStage()` and `advanceCouncil()` wrap these. To run by hand inside the container, from the docroot:

```bash
cv scr /var/www/html/httpdocs/CRM/extensions/au.com.agileware.elections/tests/playwright/fixtures/seed-council.php
```

```bash
STEP=to-voting cv scr /var/www/html/httpdocs/CRM/extensions/au.com.agileware.elections/tests/playwright/fixtures/advance-council.php
```

Other election kinds (most votes, write-ins, referendum, groups, organiser-chosen candidates) are built inside their own specs in `specs/council/`. `tests/upgrade/seed-v1.php` seeds the 1.3.2 elections for the [upgrade test](upgrade-test.md).

## The Beekeepers seed: one election of each kind on a new site

The eight elections, by number or key (set `ELECTIONS_SEED_ELECTIONS` to build some):

| # | Key | Kind | Left at |
|---|---|---|---|
| 1 | `committee` | Ranked choice, people nominate and second | Nominations |
| 2 | `honey-show` | Most votes win | Voting |
| 3 | `life-award` | Candidates chosen by the organisers | Candidates announced |
| 4 | `write-ins` | Write-ins | Voting |
| 5 | `referendum` | Yes or no questions only | Voting |
| 6 | `agm` | A position and a question on one ballot | Voting |
| 7 | `research-grants` | Separate nominating and candidate groups | Nominations |
| 8 | `results` | Finished, with a tie in one position | Results |

Steps on any CMS (the commands, docroot and `cv` name per CMS are in the seed README):

1. Have a site with CiviCRM and the extension enabled ([../environments/README.md](../environments/README.md), [../ddev-kit/README.md](../ddev-kit/README.md)), and the election pages or menu items set up as in [`docs/setup.md`](../../docs/setup.md). Mail from the seed goes nowhere unless the site's outgoing mail is set to a catcher (Mailpit on DDEV).
2. Deploy the extension, so `tests/seed/` is on the site, then run `seed.php`:

```bash
ddev exec 'cd /var/www/html/httpdocs && cv scr /var/www/html/httpdocs/CRM/extensions/au.com.agileware.elections/tests/seed/seed.php'
```

3. Optional, WordPress only: `wordpress-logins.php <credentials file>` makes four logins linked to Beekeepers. Drupal and Joomla test accounts for the browser tests come from `tests/playwright/fixtures/setup-accounts-*.sh`; for any CMS, review as people through personal links.
4. `links.php` writes personal links (below).

Run `seed.php` again at any time to rebuild: it removes what it made first. The review site of the maintainer was built from the same logic by WordPress-specific scripts: [../environments/beekeepers-review-site.md](../environments/beekeepers-review-site.md).

### Move an election to a stage

Elections stay where they are left for weeks. To see another stage, change the dates:

```bash
ddev exec 'cd /var/www/html/httpdocs && ELECTION=2 STAGE=results cv scr /var/www/html/httpdocs/CRM/extensions/au.com.agileware.elections/tests/seed/move-election.php'
```

`ELECTION` is an id or part of a name that matches one election. `STAGE` is `nominations`, `advertise`, `voting` or `results`. Moving back and forth is allowed, but data made at a later stage (ballots, results) stays: a moved-back election with results keeps them, which is not how a real election goes. Rebuild it with `ELECTIONS_SEED_ELECTIONS=<n>` for a clean one.

## Scheduled jobs: results and some emails

Results and the write-in emails are produced by CiviCRM scheduled jobs, not by a button. On a real site CiviCRM's cron runs them. On a test site, run them by hand after moving an election to the results stage or after a write-in. The three jobs are **Elections Results Job** (`Election.generateresults`), **Elections Write-in Reminders** (`Election.sendwriteinreminders`) and **Elections Write-in Closed Notices** (`Election.sendwriteinclosednotices`). Run all enabled jobs:

```bash
ddev exec 'cd /var/www/html/httpdocs && cv api3 Job.execute auth=0'
```

Or one of them:

```bash
ddev exec 'cd /var/www/html/httpdocs && cv api3 Election.generateresults'
```

On Joomla use `jcv`. Results are only counted once voting has closed and the results date has passed (the `results` stage). The other emails (to nominees, for example) are sent at the moment of the action, not by a job; read them in the mail catcher ([manual-browser-testing.md](manual-browser-testing.md)). Details of every email and job: [`docs/reference_emails_and_jobs.md`](../../docs/reference_emails_and_jobs.md).

## Personal links

A personal link lets someone act as a contact with no website login: the election page address with the contact's id (`cid`) and a checksum (`cs`) of it, valid for the time given when it was made. The election must allow people to take part without logging in (the seeds turn this on). Anyone who holds a link can act as that person, so links are generated, kept in git-ignored files and never committed or put in a pull request.

- Council personas: in `fixtures/.generated/council.json`, built by `seed-council.php`; the specs read them.
- Beekeepers: `links.php` writes `tests/seed/.generated/PERSONAL-LINKS.md`, or standard output with `ELECTIONS_SEED_LINKS_FILE=-`. The address form differs by CMS (`ELECTIONS_SEED_LINK_STYLE`; see [cms-and-civicrm-differences.md](cms-and-civicrm-differences.md)).
- For another page, swap `elections/view` in the address: `elections/nominations/create` to nominate, `elections/vote` to vote.

## The `cv scr` and `__DIR__` caveat

Scripts run with `cv scr` should not rely on `__DIR__` being their own folder: `tests/upgrade/seed-v1.php` passes its folder in for that reason. Current `cv` runs the script with `require`, which makes `__DIR__` right, but the `tests/seed/` scripts find their folder from `__DIR__`, the script path in `$argv[0]` or `ELECTIONS_SEED_DIR`, and load shared files from there. A shared file loaded with `require_once` can use its own `__DIR__` safely.
