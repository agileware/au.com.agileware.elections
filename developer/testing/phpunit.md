# PHPUnit

Written from the existing setup, 2026-11-15; not yet followed from scratch.

The headless PHPUnit suite tests the extension's rules and code without a browser. It runs against a dedicated test database, never the site's own, on the WordPress DDEV site and in GitHub Actions. Where it runs is in [README.md](README.md).

## How it is set up

- The suite is in `tests/phpunit/` and is configured by `phpunit.xml.dist` in the extension root (bootstrap `tests/phpunit/bootstrap.php`, the `Civi\Test\CiviTestListener`).
- `bootstrap.php` boots CiviCRM's class loader through `cv` and registers the autoloaders for the test classes. `cv` must be able to find the site from the current folder, so run PHPUnit from inside the extension as deployed on the site.
- The tests are headless: `Civi\Test::headless()` installs the extension into a **test database** and, for most classes, wraps each test in a transaction that is rolled back (`TransactionalInterface`).
- The test database must already hold a full, working CiviCRM install, because the boot queries CiviCRM tables before any test runs. It is made by copying the site database. `CIVICRM_UF=UnitTests` tells CiviCRM to boot in test mode, where it reads the test database address from `cv`'s per-site configuration (`TEST_DB_DSN`).
- The database connects as the database root user, because the headless schema install needs the `SUPER` privilege. The test database exists only on a development machine or in a throwaway CI container.

On the DDEV WordPress site (`elections2`), `phpunit` (the 9.6 phar) and `cv` are in `.ddev/homeadditions/bin`, and `.ddev/homeadditions/.cv.json` holds `TEST_DB_DSN` for the database `civicrm_tests_dev`. [../environments/ddev-wordpress.md](../environments/ddev-wordpress.md) covers building the site. The Drupal and Joomla sites have neither a test database nor a safe configuration for one: the Joomla site's `cv` configuration points the test mode at the site's own database (that is how its `jcv` command works), so a headless run there would rebuild the site's CiviCRM tables. Do not run PHPUnit on Joomla or Drupal.

## Run it

Make the test database once, from the site database:

```bash
ddev exec 'mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS civicrm_tests_dev"'
```

```bash
ddev exec 'mysqldump -uroot -proot db | mysql -uroot -proot civicrm_tests_dev'
```

Deploy the extension (the tests are deployed with it), then run the suite from the deployed folder:

```bash
ddev deploy-elections
```

```bash
ddev exec 'cd /var/www/html/httpdocs/CRM/extensions/au.com.agileware.elections && CIVICRM_UF=UnitTests phpunit'
```

One class or one test: add the file path, or `--filter`:

```bash
ddev exec 'cd /var/www/html/httpdocs/CRM/extensions/au.com.agileware.elections && CIVICRM_UF=UnitTests phpunit tests/phpunit/CRM/Elections/WriteInTest.php'
```

Two warnings about `sql/test_data.mysql` files are harmless: CiviCRM's test harness reads them and the packaged release does not ship them. In GitHub Actions the shared workflow does the same steps with `phpunit9` and the database `civicrm_test` ([ci.md](ci.md)).

**After a schema change** the test database does not follow on its own. Either copy the site database again (the two commands above) or make sure the new upgrade step runs in `setUpHeadless()` (see "Gotchas"). CI builds the database new each time.

## What the tests cover

All classes are under `tests/phpunit/`. Test classes in `CRM/Elections/` are named `CRM_Elections_<Topic>Test`.

| Area | Classes |
|---|---|
| Stages, dates and status | `DateRulesTest`, `ScheduleTest`, `ElectionStatusTest`, `Helper/TimeZoneTest` |
| Who may do what | `Helper/EligibilityTest`, `SeparateGroupsTest`, `AdminRulesTest`, `AdminManagedTest`, `VisibilityTest` |
| Voting methods and counting | `VotingMethodTest`, `ReferendumTest`, `WriteInTest`, `BAO/ElectionResultTest` |
| A whole election as people | `CouncilPersonaTest` (all 41 personas at every stage; see [personas.md](personas.md)) |
| Display and options | `DisplayOptionsTest`, `ResultsDisplayTest`, `Helper/CandidateTest`, `FrameTest`, `ErrorPagesTest` |
| Emails | `MailerTest` |
| Security | `SecurityTest` (ballots only while voting is open, one ballot per person, results only once, rules locked once ballots exist, anonymising, the hash-chained audit log, address and picture checks) |
| Upgrades | `UpgraderTest` (each upgrade step, and that it can run twice) |
| Wording | `WordingTest` (no "member" or "membership" in anything a person reads; banned phrases) |
| The APIs | `api/v3/ElectionTest`, `ElectionPositionTest`, `ElectionNominationTest`, `ElectionNominationSeconderTest`, `ElectionVoteTest` |

## Add a test

1. Put the file in `tests/phpunit/CRM/Elections/` (or `tests/phpunit/api/v3/` for an API) and name the class `CRM_Elections_<Topic>Test` in a file `<Topic>Test.php`. The autoloader finds it by name.
2. Extend `api_v3_ElectionBaseTestCase` (in `tests/phpunit/api/v3/ElectionBaseTestCase.php`) and mark the class `@group headless`. It is headless and transactional, and has helpers: `createElection()`, `createElectionPosition()`, `createNomination()`, `makeCandidate()`, `individualCreate()`, `groupCreate()` and date helpers.
3. For a whole election, use the council fixture: `CRM_Elections_Test_CouncilFixture::create()` makes the personas and election, and `playUntil()` / `moveToStage()` take it through the stages ([seeding.md](seeding.md)).
4. Test the rule, not the screen: screens are for Playwright. Test the refusal as well as the success, and check the message a refusal gives (`CRM_Elections_Exception`, see [cms-and-civicrm-differences.md](cms-and-civicrm-differences.md)).
5. Run the one class first, then the whole suite.

Tests that read wording follow `WordingTest`: say "people", not "member".

## Gotchas

- **Shared default rows.** The test database is shared by every run. A test that changes or deletes rows CiviCRM ships (the default message templates, for example) corrupts later runs. Transactional tests are rolled back for you; a test that is not transactional, or that runs DDL, must restore what it changed in a `finally` block, as `UpgraderTest` does.
- **DDL is not rolled back.** `ALTER TABLE` ends the transaction. `UpgraderTest` is not transactional: it drops columns, runs the step, and in `tearDown()` and `finally` runs the upgrade steps again so the schema is whole.
- **Upgrade steps in `setUpHeadless()`.** `ElectionBaseTestCase::setUpHeadless()` and `UpgraderTest::tearDown()` each list the upgrade steps to re-run on the test database, because it may have been copied from an older site. Each lists steps by hand, so a new step has to be added to them (the lists today are not the same: check both when you add one).
- **A new column in a hand-written `SELECT`.** Several queries select columns by name to build objects. A column added to the schema must be added there too, or a test (or a screen) reports "Undefined property".
- **Stale test database after a schema change.** See "Run it".
- **Do not deploy during a run**, and do not run PHPUnit and Playwright on the same site at the same time: both use the site.
- **CiviCRM versions.** CI installs the newest stable CiviCRM, the DDEV site is on 6.16: run on both before a release.
