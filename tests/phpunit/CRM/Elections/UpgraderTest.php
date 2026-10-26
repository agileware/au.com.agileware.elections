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

}
