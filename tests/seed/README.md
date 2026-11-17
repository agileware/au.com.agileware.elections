# Test data for reviewing elections

Scripts that fill a CiviCRM site with the invented **Beekeepers Association of Australia**: 30 Beekeeper contacts in three groups and eight elections, one of each kind, each left at the stage that suits reviewing it. They are for manual review and for demonstrations. The automated tests use their own data (the council fixtures in `tests/playwright/fixtures/`).

The folder is left out of the released extension (`tests` is not in the release package). How the scripts fit with the other test data is in [`developer/testing/seeding.md`](../../developer/testing/seeding.md).

Written for `cv scr` and CiviCRM only, so the same scripts run on WordPress, Drupal and Joomla. The one script that needs a CMS is `wordpress-logins.php`. Run on a clean site of each CMS on 2026-11-15 (CiviCRM 6.19.5): `seed.php` (twice), `links.php` and `move-election.php` on WordPress, Drupal and Joomla; `wordpress-logins.php` on WordPress.

## Run them

Run each script with `cv scr` from the site's docroot, with the path of the script as deployed on the site (the extension is deployed to `CRM/extensions/au.com.agileware.elections`). Under DDEV:

```bash
ddev exec 'cd /var/www/html/httpdocs && cv scr /var/www/html/httpdocs/CRM/extensions/au.com.agileware.elections/tests/seed/seed.php'
```

The docroot and command differ by site:

| Site | Docroot in the container | Command |
|---|---|---|
| WordPress | `/var/www/html/httpdocs` | `cv` |
| Drupal | `/var/www/html/web` | `cv` |
| Joomla | `/var/www/html` | `jcv` (a `cv` that runs CiviCRM without Joomla) |

Set options in front of the command, inside the quotes: `ddev exec 'cd /var/www/html/httpdocs && ELECTIONS_SEED_ELECTIONS=2,5 cv scr ...'`.

Start with `seed.php`. Then, as needed, `links.php`, `move-election.php` and (WordPress only) `wordpress-logins.php`.

## The scripts

| Script | What it does |
|---|---|
| `seed.php` | Removes what an earlier run made, then builds the organisation, 30 Beekeepers, 3 groups and the elections. Safe to run again. |
| `stages.php` | The dates of each stage (nominations, advertise, voting, results) and `move_to_stage()`. Loaded by the others; not run by itself. |
| `move-election.php` | Moves one election to a stage by changing its dates, as time passing would. |
| `links.php` | Writes a personal link for every Beekeeper in every seeded election. |
| `wordpress-logins.php` | WordPress only. Makes the roles and four logins linked to Beekeepers; writes generated passwords to a local file. |
| `common.php` | Helpers: the options, the folder for generated files, the record of what was seeded. Loaded by the others. |

`cv scr` may not set `__DIR__` to the folder of the script it runs (see `tests/upgrade/seed-v1.php`). Each script therefore finds its folder from `__DIR__`, then from the script path in `$argv[0]`, then from `ELECTIONS_SEED_DIR`, and loads the shared files from there. Set `ELECTIONS_SEED_DIR` only if a script says it cannot find its folder.

### seed.php

What it creates:

- The organisation: the domain contact is renamed "Beekeepers Association of Australia", with the domain name, the default from address and an `info@` e-mail address on the seed domain.
- The contact subtype **Beekeeper**, and 30 Beekeepers (numbered 1 to 30 in the script) with an e-mail address, a mobile number and an Australian address.
- Groups **Beekeepers** (all 30; may vote), **Executive Committee** (5; may nominate for the Research Grants Committee) and **Committee Candidates** (14; may stand where a candidate group is set).
- The elections, in this order:

| Number | Key | Kind | Left at |
|---|---|---|---|
| 1 | `committee` | Ranked choice; people nominate and second | Nominations (some nominations made, some needing a second) |
| 2 | `honey-show` | Most votes win | Voting (12 ballots in) |
| 3 | `life-award` | Candidates chosen by the organisers; no nomination form | Candidates announced (one has accepted) |
| 4 | `write-ins` | Voters can write in a person | Voting (two write-ins made) |
| 5 | `referendum` | Yes or no questions only | Voting (10 ballots in) |
| 6 | `agm` | A position and a question on one ballot | Voting (6 ballots in) |
| 7 | `research-grants` | Separate nominating and candidate groups | Nominations |
| 8 | `results` | Ranked choice, finished, with a tie in one position | Results (results generated) |

It prints the id and name of each election, and records the contact, group and election ids in `.generated/seeded.json` (ids only: no passwords or links).

Rerunning removes the eight elections it names (by name), every contact of the subtype Beekeeper and the three groups, then builds again. Contacts linked to a website login are unlinked from it first (the login itself is a CMS matter). If no other election is left, election numbering starts again from 1.

Options (environment variables):

| Variable | Default | Meaning |
|---|---|---|
| `ELECTIONS_SEED_DOMAIN` | `beekeepers.example.org` | E-mail domain of the Beekeepers and the organisation. Use a domain whose mail goes nowhere or into a mail catcher. |
| `ELECTIONS_SEED_ELECTIONS` | `all` | Which elections to build: `all`, or a comma separated list of numbers or keys, for example `2,5` or `referendum,results`. All eight are removed first whichever are built. |
| `ELECTIONS_SEED_STATE_FILE` | `.generated/seeded.json` in this folder | Where to record the ids. |

The seed does not set up the site itself. A new site also needs, for e-mail to reach a mail catcher, an outgoing mail setting (on the DDEV sites Mailpit takes everything the site sends), and the election pages (see `docs/setup.md`) for people to reach the front end. Set the Australian country and currency defaults if the addresses should list as Australian. The maintainer's review site does this with a WordPress script that is not in this repository.

### move-election.php

```bash
ddev exec 'cd /var/www/html/httpdocs && ELECTION=3 STAGE=voting cv scr /var/www/html/httpdocs/CRM/extensions/au.com.agileware.elections/tests/seed/move-election.php'
```

`ELECTION` is an election id or part of its name (it must match exactly one election). `STAGE` is `nominations`, `advertise`, `voting` or `results`. The election and stage can instead be given as the first and second arguments after the script path. The dates of each stage are in `stages.php`; they are wide windows (weeks) so an election stays at its stage.

Moving to `results` changes dates only. The results are produced by the scheduled jobs: run `cv api3 Job.execute auth=0` (see `developer/testing/seeding.md`).

### links.php

Writes `.generated/PERSONAL-LINKS.md` in this folder (owner-only permissions, git-ignored): one table for each election, with a personal link for each Beekeeper. A personal link works without a website login for 90 days. Anyone who holds a link can act as that person, so never commit or share the file.

| Variable | Default | Meaning |
|---|---|---|
| `ELECTIONS_SEED_LINKS_FILE` | `.generated/PERSONAL-LINKS.md` | Where to write; `-` writes to standard output. |
| `ELECTIONS_SEED_LINKS_DAYS` | `90` | How long the links stay valid. |
| `ELECTIONS_SEED_LINK_STYLE` | `auto` | `wordpress`, `drupal`, `joomla` (the address forms of `tests/playwright/fixtures/cms.ts`) or `civicrm`. `auto` follows the CMS. On Joomla set `joomla`: `jcv` runs CiviCRM without Joomla, so the script cannot tell the CMS. |
| `ELECTIONS_SEED_BASE_URL` | CiviCRM's base URL | The address of the site's front end. |
| `ELECTIONS_SEED_STATE_FILE` | `.generated/seeded.json` | The record `seed.php` made. |
| `ELECTION` | all seeded elections | Only this election (an id or part of its name). |

### wordpress-logins.php (WordPress only)

```bash
ddev exec 'cd /var/www/html/httpdocs && cv scr /var/www/html/httpdocs/CRM/extensions/au.com.agileware.elections/tests/seed/wordpress-logins.php /var/www/html/CREDENTIALS.md'
```

Makes two WordPress roles (Elections Manager, Elections Participant) and four logins, each linked to a Beekeeper: `bee.manager` (Beekeeper 10, runs the elections) and `bee.ellie`, `bee.marcus` and `bee.priya` (Beekeepers 1, 2 and 3, who take part while logged in). Passwords are generated. They are written only to the credentials file, never printed. Run after `seed.php`.

The credentials file is the first argument, else `ELECTIONS_SEED_CREDENTIALS_FILE`, else `.generated/CREDENTIALS.md` in this folder. Keep it out of git: put it in a git-ignored folder or outside the repository. An existing file is kept, and only its table of Beekeeper logins is replaced.

**Drupal and Joomla** test accounts for the browser tests come from `tests/playwright/fixtures/setup-accounts-drupal.sh` and `setup-accounts-joomla.sh` (they are not linked to Beekeepers). For a review as a person on any CMS, use personal links from `links.php`.

## Generated files

Everything the scripts generate goes under `tests/seed/.generated/` (git-ignored) unless a variable points elsewhere: `seeded.json`, `PERSONAL-LINKS.md` and `CREDENTIALS.md`. The DDEV `deploy-elections` commands keep `.generated` folders when they copy the extension to a site.

Invented content: the seed text calls the people who take part "people", "voters" or "Beekeepers", as the wording standard asks. Three uses of the word are left on purpose, because they are the invented Association's own subject matter and not a description of who takes part: role titles such as "Committee Member 1", the "Life Membership Award Panel", and the two referendum questions (the membership fee and voting by proxy). These scripts are not covered by the wording test, which scans the extension's screens, docs and code only.
