# The Beekeepers review site

Written from the existing setup, 2026-11-15; not yet followed from scratch.

`election-test` is a DDEV WordPress site for reviewing the extension by hand as an invented organisation, the **Beekeepers Association of Australia**. Everything on it is made up: the organisation, its 30 people (all with addresses on the reserved domain `beekeepers.example.org`) and its elections. It runs the stock CiviCRM 6.19.4 rather than the ESR build, so it is also the place to look at the extension on the newer CiviCRM.

**It belongs to the maintainer.** It is left running, and its data is what the maintainer reviews. Do not change, reset, re-provision, redeploy or stop it without asking.

## What the site is

| Item | Value |
|---|---|
| DDEV project type | `wordpress`, docroot `httpdocs`; PHP 8.3, MariaDB 11.8 |
| Address | `https://election-test.ddev.site` |
| WordPress | 7.1.4; permalinks `/%postname%/`; site timezone Australia/Sydney; the election list is the home page |
| CiviCRM | 6.19.4, extensions directory `httpdocs/CRM/extensions`, set in `civicrm.settings.php` as on the other sites ([ddev-wordpress.md](ddev-wordpress.md)) |
| Elections | Deployed from the extension working copy with `ddev deploy-elections` (2.1.0 at the time of writing) |
| Mail | Everything the site sends goes to Mailpit at `https://election-test.ddev.site:8026` (CiviCRM sends by SMTP to `localhost:1025`); nothing is delivered |
| Playwright | Not set up: the site has no Chromium files |

CiviCRM has the base page `civicrm` and the pages `election` and `current-elections` made from the shortcodes in the user guide ([`../../docs/setup.md`](../../docs/setup.md)). Australia is the default country and AUD the currency.

## People, groups and logins

- **30 people**, a contact subtype *Beekeeper* of Individual, with Australian addresses.
- **Groups.** *Beekeepers* is everyone and is the electorate. *Executive Committee* (5 people) and *Committee Candidates* are smaller groups used by the separate-groups election (and by the write-in election for who may be written in).
- **Four WordPress logins**, each linked to a person: one `elections_manager` (runs the elections, with `administer_elections` and `view_all_contacts`) and three `elections_member` (take part while signed in). There is also the WordPress administrator.
- **Passwords are generated.** The seed script makes a random password for each login and writes them to a local file (`CREDENTIALS.md` in the site folder, mode 600). The file is never committed or shared; read it on the machine, or reset a login with `ddev wp user update <login> --user_pass=<new>`.
- **Personal links** (the `cid` and `cs` address that lets a person act without signing in) are written to `PERSONAL-LINKS.md` in the site folder by `ddev election-links`, also local only and valid for 90 days.

The scripts that make the people, groups and elections are in the repository in `tests/seed/` and run on any CMS: see [`../testing/seeding.md`](../testing/seeding.md). This page describes only what the review site adds on top: the logins, the pages, the site settings and the commands below.

## The eight elections

Each is left at the stage that suits reviewing it, with windows of weeks so it stays put. The site's own `README.md` lists who is nominated or has voted, by name.

| ID | Election | Kind | Stage | What to try |
|---|---|---|---|---|
| 1 | Beekeeper Committee 2027 Election | Ranked choice; people nominate and second. Six positions (President, Secretary, Treasurer, three Committee Members) | Nominations open | Nominate and second people; one nominee already has two nominators and two others each need a second. Move to `advertise` to accept or withdraw |
| 2 | Honey Show Stewards 2027 (Most Votes Win) | Most votes win: tick up to the number of seats | Voting open | Vote as a person who has not (12 of 30 have); move to `results` and run the jobs |
| 3 | Life Membership Award Panel 2027 (Candidates Chosen by the Organisers) | Online nominating off; organisers add candidates | Candidates announced | One candidate has accepted, three have been asked (their emails are in Mailpit) |
| 4 | Regional Delegates 2027 (Write-ins Allowed) | Ranked choice with write-ins; people may change their vote | Voting open | Vote and write someone in; two people are already written in and invited |
| 5 | Constitution Amendments 2027 (Referendum) | Three yes or no questions with thresholds and a quorum | Voting open | Answer the questions; 10 people have |
| 6 | Annual General Meeting 2027 (Positions and Questions) | One position (Auditor) and one question on one ballot | Voting open | Vote for the Auditor and answer the question |
| 7 | Research Grants Committee 2027 (Separate Nominating and Candidate Groups) | Only the Executive Committee nominates; only Committee Candidates may stand | Nominations open | Try as an executive and as someone who is not |
| 8 | Beekeeper Committee 2026 (Results) | Ranked choice, finished | Results published | Read the results; the Treasurer result is a tie on purpose |

IDs are numbered from 1 because the seed script resets the counter when it clears its own elections.

## Commands

Run each in the site folder, `<workspace>/election-test`. They are DDEV host commands in `.ddev/commands/host/`.

| Command | What it does |
|---|---|
| `ddev deploy-elections` | The standard deploy: copies the working copy of the extension into `httpdocs/CRM/extensions`, enables it, upgrades the database, flushes the cache ([README.md](README.md#the-deploy-loop)) |
| `ddev move-election <id or part of the name> <stage>` | Moves one election to `nominations`, `advertise`, `voting` or `results` by rewriting its dates, as time passing would. It refuses a name that matches more than one election |
| `ddev election-jobs` | Runs CiviCRM's scheduled jobs now (`cv api3 Job.execute auth=0`): results, write-in reminders and notices |
| `ddev election-links` | Writes `PERSONAL-LINKS.md` again |
| `ddev provision-election-test` | Resets the whole site to its first state: see below |

### How the stages are set

`move-election` and the seed script share one table of day offsets from today for the eight dates of an election. Opening dates are set to 09:00 and closing dates to 17:00.

| Stage | Visible from | Nominations open | Nominations close | Candidates announced | Voting opens | Voting closes | Results | Visible until |
|---|---|---|---|---|---|---|---|---|
| `nominations` | -5 | -3 | +21 | +22 | +28 | +35 | +36 | +90 |
| `advertise` | -20 | -18 | -8 | -7 | +14 | +21 | +22 | +90 |
| `voting` | -30 | -25 | -16 | -15 | -3 | +25 | +26 | +90 |
| `results` | -60 | -55 | -45 | -44 | -35 | -14 | -13 | +90 |

Moving to `results` only changes dates. Run `ddev election-jobs` afterwards so the results are calculated, as the scheduled job would.

## How the data is rebuilt

`ddev provision-election-test` resets the Beekeepers, logins and elections (it takes a few minutes). It runs these steps in order:

1. `ddev deploy-elections`.
2. `provision/01-site.sh` (inside the container): site name and timezone, pretty permalinks, the CiviCRM base page and the `wpBasePage` setting, Australian defaults, outgoing mail to Mailpit, the `election` and `current-elections` pages, the election list as the home page, and the two roles. Safe to run again.
3. Empties Mailpit through its API, so the inbox shows only the emails of this run.
4. `provision/02-seed.php` with `cv scr`: removes what an earlier run made (the eight elections by name, the three groups, the Beekeepers and their logins), then creates the organisation, the people, groups, logins and eight elections with their nominations, responses and votes.
5. `provision/03-links.php`: writes the personal links file.

The site's own copies are in `election-test/provision/`; the repository versions in `tests/seed/` split the same work into a CiviCRM-only part (people, groups, elections, links, stage moves) and a WordPress-only part (logins), described in [`../testing/seeding.md`](../testing/seeding.md). Use the repository scripts for a new site; the review site keeps running from its own copies. A reset deletes anything the maintainer added to the invented elections, so ask first.

Before a risky change (a new extension build, a reset) take a DDEV snapshot of the site, for example `ddev snapshot --name before-change`, and restore it with `ddev snapshot restore before-change`. A snapshot was used that way before the 2.1 build was deployed.

## Using the site for a review

1. Check its state with `ddev list`. The maintainer normally leaves it running; if it is stopped or paused, ask before starting it.
2. Deploy the build under review only with the maintainer's agreement.
3. Look at the front end as people, not only as the administrator: open a personal link in a window with no administrator session (a WordPress administrator session overrides a personal link), then repeat signed in as a participant login.
4. Move an election to another stage with `move-election` to see the other screens; put it back when done, so the next reviewer finds it where the table above says.
5. Read the emails in Mailpit.

Not verified: the snapshot step was not run for this page; it comes from the working notes.
