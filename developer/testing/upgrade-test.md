# Upgrade test (1.3.2 to the current code)

Written from the existing setup, 2026-11-15; not yet followed from scratch.

The upgrade test proves that a site running the last 1.x release (1.3.2) can move to the code under test without losing or changing data. It is one of the three checks that gate a pull request. How upgrade steps are written is in [../architecture/upgrades.md](../architecture/upgrades.md).

## What it does

1. Installs release 1.3.2 of the extension (from the git tag) on a site.
2. Seeds it with `tests/upgrade/seed-v1.php`, run with `cv scr`: 12 people in a group and three elections, each with two positions (President, one seat; Council, two seats) written the way 1.3.2 wrote them:
   - a **finished** election (ballots cast, results generated),
   - one at the **voting** stage (four ballots in),
   - one taking **nominations** (one person nominated once of the two needed).
   The seed prints a `SNAPSHOT_JSON:` line: counts of elections, votes, nominations, seconders and results, plus the result and ballot rows.
3. Replaces the extension's files with the code under test and runs `cv ext:upgrade-db`, then `cv flush`.
4. Runs `tests/upgrade/verify-v2.php` with `cv scr`, which compares the site with the snapshot and exits non-zero if any check fails.

`verify-v2.php` checks, one `PASS` or `FAIL` line each:

| Group | Checks |
|---|---|
| Schema | Every new column and table exists, and the unique keys that enforce one ballot row per person and candidate and one nomination per person and position. |
| Old behaviour kept | Old elections are ranked choice, have no groups of their own for nominating or standing, send no emails, keep online nominating on, have no write-ins and are positions (not questions). |
| Nothing lost | The counts and the result and ballot rows equal the snapshot. |
| Old elections still described | The finished election lists its winners; its integrity record says "cannot be checked" rather than "failed"; the audit chain is sound; the voting election is in its voting stage. |
| Old elections still run | A new person votes in the old election and changes that vote (still one ballot); the nominations election takes a second nominator and the nominee becomes eligible; results are generated for the old voting election and get an integrity record. |
| Repeatable | Each upgrade step in a listed set can be run again. |

## Run it locally

Use a DDEV site with a `deploy-elections` command and a spare database state: the script saves the site database first and restores it at the end, so the site's data is as it was. The deployed extension folder is not: the script deletes it to install 1.3.2, so `tests/playwright/node_modules` and the generated test passwords (`fixtures/.generated/`) are gone afterwards. Before the next browser test run on that site, run `npm ci` and the setup script again ([playwright.md](playwright.md)). It needs the `1.3.2` tag in the extension repository.

```bash
tests/upgrade/upgrade-from-v1.sh <ddev project dir> <docroot> <cv command>
```

| Site | Arguments |
|---|---|
| WordPress | `../elections2 httpdocs cv` |
| Drupal | `../elections2-drupal web cv` |
| Joomla | `../elections2-joomla . jcv` |

The script finds the extension from its own path. It exports the database, uninstalls the current version, installs 1.3.2 from `git archive 1.3.2`, seeds, runs `ddev deploy-elections` to put the current code back, upgrades, verifies, and imports the saved database. The last lines say `restored` and the exit status is the verification's. If the restore fails the script prints `COULD NOT RESTORE: <path>`; if the script is interrupted, nothing is restored and the saved file `before.sql.gz` is left in a temporary folder: import it with `ddev import-db`.

Run it before a release on all three CMSs, and whenever you add an upgrade step.

## In CI

`.github/workflows/upgrade-tests.yml` calls the shared workflow `civicrm-extension-upgrade-tests.yml` with `FROM_REF: 1.3.2`. The workflow mounts the 1.3.2 checkout where the extension is installed and the commit under test beside it (read-only), then runs `tests/upgrade/ci-upgrade-test.sh` inside the container. That script does the same steps as the DDEV one (install old, seed, replace the files, `cv ext:upgrade-db`, verify), with no database backup because the container is thrown away. See [ci.md](ci.md).

## Add an upgrade step to the test

An upgrade step (`upgrade_2xxxx` in `CRM/Elections/Upgrader.php`) needs its own PHPUnit test in `UpgraderTest` ([phpunit.md](phpunit.md)). Add it to the upgrade test too, so that the whole path from 1.3.2 is proved:

1. **Schema check.** In `verify-v2.php`, add the new column or table to the schema lists at the top.
2. **Behaviour check.** If the step changes what an existing election does or looks like, add a `check()` that says what must be true for the seeded old elections afterwards (like the "old behaviour kept" group). If it should leave data untouched, the existing count checks already cover it.
3. **Repeatable.** Add the step number to the list near the end of `verify-v2.php` ("every upgrade step can be run again"). It runs every step up to `20130` today.
4. **Seed data.** Only if the step needs old data the seed does not hold, extend `seed-v1.php` using what 1.3.2's code could write (the seed runs on 1.3.2, so it may only use 1.3.2's API).
5. Run the test on WordPress, then the others.

Gotchas:

- `cv scr` runs a script inside a function, so a plain global is not shared across functions: the check count lives in a function (`check()`), and `cv scr` may not set `__DIR__` to the script's folder, so the scripts get their folder from `UPGRADE_DIR` or the arguments.
- The seed can only use 1.3.2 (the API names of that release), not the current one.
- The snapshot goes through a `SNAPSHOT_JSON:` output line, because the old and new code cannot be sure where files are written.
