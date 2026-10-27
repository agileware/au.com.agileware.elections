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

}
