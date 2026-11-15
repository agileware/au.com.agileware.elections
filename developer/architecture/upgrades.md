# Upgrades

How installed sites move from one version of the extension to the next: the upgrader, every step so far, and the rules for adding one. A change to the database without a step leaves every existing site broken, so this is part of the definition of done.

## How it works

- `info.xml` names the upgrader `CiviMix\Schema\Elections\AutomaticUpgrader` (from `mixin/lib/civimix-schema`). On **install** it creates all the tables from `schema/*.entityType.php`, and CiviCRM records the highest `upgrade_NNNNN` number as the site's revision, so a new site runs no steps.
- On **upgrade** the same upgrader hands over to `CRM_Elections_Upgrader` (`CRM/Elections/Upgrader.php`, extending `CRM_Extension_Upgrader_Base`). Every public method named `upgrade_NNNNN` is a revision. CiviCRM runs the revisions above the site's stored revision, in numeric order, when someone runs `cv ext:upgrade-db` or accepts the upgrade prompt in the browser.
- The automatic upgrader does **not** change existing tables to match the schema file. Every new column, index, foreign key or table needs a step.
- Do not override the revision methods of the base class (`getRevisions()`, `getCurrentRevision()`, `setCurrentRevision()`, `enqueuePendingRevisions()`, `hasPendingRevisions()`, `onUpgrade()`, `appendTask()`) in `CRM_Elections_Upgrader`; the automatic upgrader refuses an upgrader that does.
- Downgrades are not supported.

## Numbering

| Range | Meaning |
|---|---|
| `10300` | The last 1.x step (checksum access) |
| `20000` to `20130` | Version 2.0 (`20000` to `20110`) and 2.1 (`20120`, `20130`) |
| **`20140`** | **The next free step** |

Numbers only have to increase. Use steps of ten, one step for each feature, so a fix can be slipped in between. The number is the order of building, not of the release.

## Every step

| Step | Version | What it does |
|---|---|---|
| `10300` | 1.3.0 | Adds `civicrm_election.allow_checksum_access` (plain `ALTER`, not guarded) |
| `20000` | 2.0 | Display options on `civicrm_election` (`show_photos`, `show_statements`, `show_nominators`, `name_format`) and `civicrm_election_nomination.statement_url` |
| `20010` | 2.0 | Removes duplicate ballot rows (keeps the oldest), adds the unique key `UI_election_vote_member_nomination`, makes `civicrm_election_vote.member_id` `ON DELETE SET NULL`, creates `civicrm_election_audit` |
| `20020` | 2.0 | Merges duplicate nominations and nominator rows (keeps the oldest and moves what hangs off the others), adds `UI_election_nomination_position_nominee` and `UI_election_nomination_seconder_nominator_nomination` |
| `20030` | 2.0 | `voting_method` (default `irv`, so every existing election stays ranked choice) |
| `20040` | 2.0 | `nomination_groups`, `candidate_groups` (empty means the voting groups) |
| `20050` | 2.0 | `send_emails` (off for existing elections) |
| `20060` | 2.0 | Write-ins: `write_ins_per_seat` on positions; `is_write_in`, `write_in_notified_date`, `write_in_reminded_date` on nominations |
| `20070` | 2.0 | Referenda: `position_type`, `pass_threshold`, `quorum_percent`, `resolution_text`; creates `civicrm_election_option` and `civicrm_election_option_vote` |
| `20080` | 2.0 | `allow_online_nominations` (on for existing elections) |
| `20090` | 2.0 | `write_in_closed_notified_date` |
| `20100` | 2.0 | `nomination_message_template_id` with its foreign key; retitles the four message templates, only where the site still has the original title |
| `20110` | 2.0 | Results page: header text, footer text, logo (with foreign key to `civicrm_file`), logo alt text, celebration settings |
| `20120` | 2.1 | Replaces email wording: always in the reserved original of each message template, in the editable copy only when it still equals the original (a site's edits are kept) |
| `20130` | 2.1 | `has_withdrawn_nomination` (declined versus withdrawn; earlier refusals show as declined) |

## Rules for adding a step

1. **Same commit as the schema.** Change `schema/<Entity>.entityType.php`, the DAO property annotation, and add `upgrade_NNNNN()` together.
2. **Idempotent.** A step may run twice (a failure part way, a re-run). Use the private `ensureColumn($table, $column, $definition)`, `CRM_Core_BAO_SchemaHandler::checkIfIndexExists()` and `checkIfFieldExists()`, `CREATE TABLE IF NOT EXISTS`, and a check before adding a foreign key.
3. **Defaults that keep old behaviour.** Existing elections must behave as before: choose the default that equals the 1.x or previous behaviour, and make new columns nullable where code treats `NULL` as that default (see `assignDisplayOptions()` and `VotingMethod::normalise()`).
4. **Clean data before adding a key.** If a unique key could fail on existing rows, remove or merge the duplicates first (as `20010` and `20020` do).
5. **Do not overwrite what a site changed.** Message templates, settings and anything an administrator can edit are updated only while they still match the original (see `20100` and `20120`).
6. **Return `TRUE`** and log with `Civi::log()->info('Applying update NNNNN')`.
7. **Raw SQL with `CRM_Core_DAO::executeQuery`**, parameters passed as `%1`, never concatenated user input.
8. **Write the tests** (next section) and call the new step from the two lists that bring a copied test database up to date.

## Tests for a step

| Where | What to add |
|---|---|
| `tests/phpunit/CRM/Elections/UpgraderTest.php` | A test that drops the new column or table, runs the step on the old shape, asserts the defaults and that existing rows are unchanged, and runs the step a second time to prove it can be repeated. Add the step to `tearDown()`, which restores the full schema for the next test (the test class is not transactional) |
| `tests/phpunit/api/v3/ElectionBaseTestCase.php` | Add the call to `setUpHeadless()`, so a test database copied from an older site has the column |
| `tests/upgrade/verify-v2.php` | Add the new column or table to the schema checks, and a check of the preserved behaviour. `tests/upgrade/seed-v1.php` builds the elections in every stage on 1.3.2 |

The upgrade test (`tests/upgrade/ci-upgrade-test.sh` in GitHub Actions, `tests/upgrade/upgrade-from-v1.sh` on a DDEV site) installs release `1.3.2`, seeds elections in every stage, replaces the code with the commit under test, runs `cv ext:upgrade-db` and runs `verify-v2.php`. How to run it: [../testing/upgrade-test.md](../testing/upgrade-test.md).

## How a 1.3.x site upgrades

The 1.3.x site has revision `10300`. Installing 2.x over it and running `cv ext:upgrade-db` (or the extension upgrade prompt in CiviCRM) runs `20000` to the last step in order. Every step keeps the old behaviour: all existing elections stay ranked choice, show nominators and photos, send no emails, allow online nominations and have no write-ins or questions. Managed entities (SavedSearches, SearchDisplays, message templates, jobs) are reconciled by CiviCRM, so a site gets the new lists and jobs. Not verified: the exact moment this happens during `ext:upgrade-db`; clear the cache afterwards.

To try it by hand, take a database export first (`ddev export-db`), run `cv ext:upgrade-db`, then `cv flush`. After deploying new code to a site, always clear the CiviCRM cache.
