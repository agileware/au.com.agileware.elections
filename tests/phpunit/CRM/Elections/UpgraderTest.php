<?php

use Civi\Test\HeadlessInterface;

/**
 * Upgrade steps change the schema (DDL), so this test is not transactional.
 *
 * @group headless
 */
class CRM_Elections_UpgraderTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface {

  const ELECTION_COLUMNS = ['show_photos', 'show_statements', 'show_nominators', 'name_format'];

  private function upgrader() {
    $upgrader = new CRM_Elections_Upgrader();
    $upgrader->init(['key' => 'au.com.agileware.elections']);
    return $upgrader;
  }

  /**
   * Whatever happens in a test, leave the shared test database with the full 2.0 schema.
   */
  public function tearDown(): void {
    $this->upgrader()->upgrade_20000();
    $this->upgrader()->upgrade_20010();
    $this->upgrader()->upgrade_20020();
    $this->upgrader()->upgrade_20030();
    $this->upgrader()->upgrade_20040();
    $this->upgrader()->upgrade_20050();
    $this->upgrader()->upgrade_20060();
    parent::tearDown();
  }

  public function setUpHeadless() {
    return \Civi\Test::headless()->installMe(__DIR__)->apply();
  }

  /**
   * An existing 1.x install has none of the 2.0 columns; upgrading must add
   * them with defaults that keep the old behaviour.
   */
  public function testUpgrade20000AddsColumnsWithBackwardsCompatibleDefaults() {
    try {
      $this->runUpgradeOnOldSchema();
    }
    finally {
      // Leave the schema complete for the tests that follow.
      $this->upgrader()->upgrade_20000();
    }
  }

  private function runUpgradeOnOldSchema() {
    foreach (self::ELECTION_COLUMNS as $column) {
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_election DROP COLUMN `$column`");
    }
    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_election_nomination DROP COLUMN statement_url');
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_election (name, visibility_start_date, visibility_end_date, nomination_start_date, nomination_end_date, advertise_candidates_date, voting_start_date, voting_end_date, result_date) VALUES ('Existing', NOW(), NOW(), NOW(), NOW(), NOW(), NOW(), NOW(), NOW())");

    $this->assertTrue($this->upgrader()->upgrade_20000());

    $row = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_election WHERE name = 'Existing'");
    $row->fetch();
    $this->assertEquals(1, $row->show_photos, 'Photos stay visible');
    $this->assertSame('none', $row->show_statements, 'Statements stay hidden');
    $this->assertEquals(1, $row->show_nominators, 'Nominators stay visible');
    $this->assertNull($row->name_format, 'Name format follows the site setting');
    $this->assertTrue(CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_election_nomination', 'statement_url'));
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_election WHERE name = 'Existing'");
  }

  /**
   * Elections made before the voting method, separate groups, emails and write-ins keep behaving as they did:
   * ranked choice, the voting groups for every role, no emails, no write-ins.
   */
  public function testLaterStepsKeepExistingElectionsBehavingAsBefore() {
    try {
      foreach (['voting_method', 'nomination_groups', 'candidate_groups', 'send_emails'] as $column) {
        CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_election DROP COLUMN `$column`");
      }
      CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_election_position DROP COLUMN write_ins_per_seat');
      foreach (['is_write_in', 'write_in_notified_date', 'write_in_reminded_date'] as $column) {
        CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_election_nomination DROP COLUMN `$column`");
      }
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_election (name, allowed_groups, visibility_start_date, visibility_end_date, nomination_start_date, nomination_end_date, advertise_candidates_date, voting_start_date, voting_end_date, result_date) VALUES ('Existing later', '12', NOW(), NOW(), NOW(), NOW(), NOW(), NOW(), NOW(), NOW())");
      $electionId = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_election WHERE name = 'Existing later'");
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_election_position (name, quantity, sortorder, election_id) VALUES ('Chair', 1, 1, %1)", [1 => [(int) $electionId, 'Integer']]);

      foreach (['20030', '20040', '20050', '20060'] as $step) {
        $this->assertTrue($this->upgrader()->{'upgrade_' . $step}());
        $this->assertTrue($this->upgrader()->{'upgrade_' . $step}(), "Step $step can be repeated");
      }

      $election = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_election WHERE name = 'Existing later'");
      $election->fetch();
      $this->assertSame('irv', $election->voting_method);
      $this->assertNull($election->nomination_groups);
      $this->assertNull($election->candidate_groups);
      $this->assertEquals(0, $election->send_emails);
      $this->assertSame('12', (string) CRM_Elections_Helper_Eligibility::groupsFor(findElectionById($electionId), 'nominating'));
      $this->assertEquals(0, CRM_Core_DAO::singleValueQuery('SELECT write_ins_per_seat FROM civicrm_election_position WHERE election_id = %1', [1 => [(int) $electionId, 'Integer']]));
    }
    finally {
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_election WHERE name = 'Existing later'");
      foreach (['20030', '20040', '20050', '20060'] as $step) {
        $this->upgrader()->{'upgrade_' . $step}();
      }
    }
  }

  /**
   * Re-running a step, for example after a partial failure, must not error.
   */
  public function testUpgrade20000CanBeRepeated() {
    $this->assertTrue($this->upgrader()->upgrade_20000());
    $this->assertTrue($this->upgrader()->upgrade_20000());
    foreach (self::ELECTION_COLUMNS as $column) {
      $this->assertTrue(CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_election', $column));
    }
  }

  /**
   * Version 1.x allowed two rows for the same member and candidate and had no audit log table: upgrading
   * removes the duplicates, keeps the oldest row, adds the unique key and creates the table.
   */
  public function testUpgrade20010RemovesDuplicateVotesAndAddsTheKeyAndTheAuditTable() {
    try {
      // The foreign key to the contact needs an index of its own while the unique key is dropped.
      CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_election_vote ADD INDEX `tmp_member` (`member_id`)');
      CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_election_vote DROP INDEX `UI_election_vote_member_nomination`');
      CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS civicrm_election_audit');
      $election = CRM_Core_DAO::executeQuery("INSERT INTO civicrm_election (name, visibility_start_date, visibility_end_date, nomination_start_date, nomination_end_date, advertise_candidates_date, voting_start_date, voting_end_date, result_date) VALUES ('Dupes', NOW(), NOW(), NOW(), NOW(), NOW(), NOW(), NOW(), NOW())");
      $electionId = (int) CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_election WHERE name = "Dupes"');
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_election_position (election_id, name, quantity, sortorder) VALUES ($electionId, 'Chair', 1, 1)");
      $positionId = (int) CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_election_position WHERE election_id = $electionId");
      $contactA = (int) civicrm_api3('Contact', 'create', ['contact_type' => 'Individual', 'first_name' => 'Dupe', 'last_name' => 'A'])['id'];
      $contactB = (int) civicrm_api3('Contact', 'create', ['contact_type' => 'Individual', 'first_name' => 'Dupe', 'last_name' => 'B'])['id'];
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_election_nomination (election_position_id, member_nominee) VALUES ($positionId, $contactA)");
      $nominationId = (int) CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_election_nomination WHERE election_position_id = $positionId");
      foreach ([1, 1, 1] as $rank) {
        CRM_Core_DAO::executeQuery("INSERT INTO civicrm_election_vote (election_nomination_id, `rank`, member_id) VALUES ($nominationId, $rank, $contactB)");
      }
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_election_vote (election_nomination_id, `rank`, member_id) VALUES ($nominationId, 1, NULL), ($nominationId, 1, NULL)");
      $oldest = (int) CRM_Core_DAO::singleValueQuery("SELECT MIN(id) FROM civicrm_election_vote WHERE member_id = $contactB");

      $this->assertTrue($this->upgrader()->upgrade_20010());
      $this->assertTrue($this->upgrader()->upgrade_20010(), 'Can be repeated');

      $this->assertSame(1, (int) CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM civicrm_election_vote WHERE member_id = $contactB"));
      $this->assertSame($oldest, (int) CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_election_vote WHERE member_id = $contactB"));
      $this->assertSame(2, (int) CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM civicrm_election_vote WHERE member_id IS NULL AND election_nomination_id = $nominationId"), 'Anonymised ballots are not touched');
      $this->assertTrue(CRM_Core_BAO_SchemaHandler::checkIfIndexExists('civicrm_election_vote', 'UI_election_vote_member_nomination'));
      $this->assertSame('civicrm_election_audit', CRM_Core_DAO::singleValueQuery("SHOW TABLES LIKE 'civicrm_election_audit'"));
      $rule = CRM_Core_DAO::singleValueQuery("SELECT DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'civicrm_election_vote' AND REFERENCED_TABLE_NAME = 'civicrm_contact'");
      $this->assertSame('SET NULL', $rule, 'Deleting a contact no longer deletes their ballots');

      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_election_vote WHERE election_nomination_id = $nominationId");
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_election_nomination WHERE id = $nominationId");
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_election_position WHERE id = $positionId");
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_election WHERE id = $electionId");
      civicrm_api3('Contact', 'delete', ['id' => $contactA, 'skip_undelete' => 1]);
      civicrm_api3('Contact', 'delete', ['id' => $contactB, 'skip_undelete' => 1]);
    }
    finally {
      $this->upgrader()->upgrade_20010();
      if (CRM_Core_BAO_SchemaHandler::checkIfIndexExists('civicrm_election_vote', 'tmp_member')) {
        CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_election_vote DROP INDEX `tmp_member`');
      }
    }
  }

  /**
   * Version 1.x allowed a person to be nominated twice for a position and a nominator to count twice:
   * upgrading merges the duplicate nominations (keeping what hangs off them) and removes the extra
   * nominator rows, then adds the unique keys.
   */
  public function testUpgrade20020MergesDuplicateNominationsAndNominators() {
    try {
      // The foreign keys need an index of their own while the unique keys are dropped. (These helper
      // indexes are harmless and are left in the test database.)
      $this->ensureIndex('civicrm_election_nomination', 'tmp_position', 'election_position_id');
      $this->ensureIndex('civicrm_election_nomination', 'tmp_nominee', 'member_nominee');
      CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_election_nomination DROP INDEX `UI_election_nomination_position_nominee`');
      $this->ensureIndex('civicrm_election_nomination_seconder', 'tmp_nominator', 'member_nominator');
      $this->ensureIndex('civicrm_election_nomination_seconder', 'tmp_nomination', 'election_nomination_id');
      CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_election_nomination_seconder DROP INDEX `UI_election_nomination_seconder_nominator_nomination`');

      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_election (name, visibility_start_date, visibility_end_date, nomination_start_date, nomination_end_date, advertise_candidates_date, voting_start_date, voting_end_date, result_date) VALUES ('Dupes2', NOW(), NOW(), NOW(), NOW(), NOW(), NOW(), NOW(), NOW())");
      $electionId = (int) CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_election WHERE name = "Dupes2"');
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_election_position (election_id, name, quantity, sortorder) VALUES ($electionId, 'Chair', 1, 1)");
      $positionId = (int) CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_election_position WHERE election_id = $electionId");
      $nominee = (int) civicrm_api3('Contact', 'create', ['contact_type' => 'Individual', 'first_name' => 'Dup', 'last_name' => 'Nominee'])['id'];
      $nominator = (int) civicrm_api3('Contact', 'create', ['contact_type' => 'Individual', 'first_name' => 'Dup', 'last_name' => 'Nominator'])['id'];
      $other = (int) civicrm_api3('Contact', 'create', ['contact_type' => 'Individual', 'first_name' => 'Dup', 'last_name' => 'Other'])['id'];
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_election_nomination (election_position_id, member_nominee, is_eligible_candidate, has_accepted_nomination) VALUES ($positionId, $nominee, 0, 0), ($positionId, $nominee, 1, 1)");
      $ids = array_column(CRM_Core_DAO::executeQuery("SELECT id FROM civicrm_election_nomination WHERE election_position_id = $positionId ORDER BY id")->fetchAll(), 'id');
      [$keep, $extra] = array_map('intval', $ids);
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_election_nomination_seconder (member_nominator, election_nomination_id) VALUES ($nominator, $keep), ($nominator, $keep), ($nominator, $extra), ($other, $extra)");

      $this->assertTrue($this->upgrader()->upgrade_20020());
      $this->assertTrue($this->upgrader()->upgrade_20020(), 'Can be repeated');

      $this->assertSame(1, (int) CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM civicrm_election_nomination WHERE election_position_id = $positionId"));
      $merged = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_election_nomination WHERE id = $keep");
      $merged->fetch();
      $this->assertEquals(1, $merged->is_eligible_candidate, 'The merged nomination keeps what either had');
      $this->assertEquals(1, $merged->has_accepted_nomination);
      $this->assertSame(2, (int) CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM civicrm_election_nomination_seconder WHERE election_nomination_id = $keep"), 'One row for each nominator');
      $this->assertTrue(CRM_Core_BAO_SchemaHandler::checkIfIndexExists('civicrm_election_nomination', 'UI_election_nomination_position_nominee'));
      $this->assertTrue(CRM_Core_BAO_SchemaHandler::checkIfIndexExists('civicrm_election_nomination_seconder', 'UI_election_nomination_seconder_nominator_nomination'));

      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_election_nomination_seconder WHERE election_nomination_id = $keep");
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_election_nomination WHERE id = $keep");
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_election_position WHERE id = $positionId");
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_election WHERE id = $electionId");
      foreach ([$nominee, $nominator, $other] as $contactId) {
        civicrm_api3('Contact', 'delete', ['id' => $contactId, 'skip_undelete' => 1]);
      }
    }
    finally {
      $this->upgrader()->upgrade_20020();
    }
  }

  private function ensureIndex($table, $name, $column) {
    if (!CRM_Core_BAO_SchemaHandler::checkIfIndexExists($table, $name)) {
      CRM_Core_DAO::executeQuery("ALTER TABLE $table ADD INDEX `$name` (`$column`)");
    }
  }

}
