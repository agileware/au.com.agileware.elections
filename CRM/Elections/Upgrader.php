<?php
use CRM_Elections_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Elections_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Version 1.3.0
   *
   * - Checksum support
   */
  public function upgrade_10300() {
    $this->ctx->log->info('Applying update 103000');
    CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_election` ADD COLUMN `allow_checksum_access` TINYINT DEFAULT 0");
    return TRUE;
  }

  /**
   * Version 2.0.0
   *
   * - Display options for each election (photos, statements, nominators, name format)
   * - Statement link for each nomination
   */
  public function upgrade_20000() {
    Civi::log()->info('Applying update 20000');
    $this->ensureColumn('civicrm_election', 'show_photos', 'TINYINT DEFAULT 1');
    $this->ensureColumn('civicrm_election', 'show_statements', "VARCHAR(16) DEFAULT 'none'");
    $this->ensureColumn('civicrm_election', 'show_nominators', 'TINYINT DEFAULT 1');
    $this->ensureColumn('civicrm_election', 'name_format', 'VARCHAR(32) NULL DEFAULT NULL');
    $this->ensureColumn('civicrm_election_nomination', 'statement_url', 'VARCHAR(2048) NULL DEFAULT NULL');
    return TRUE;
  }

  /**
   * Version 2.0.0 security hardening.
   *
   * - A member can have at most one row for a candidate in the votes table, so a ballot can never count a
   *   candidate twice (duplicates from earlier versions are removed first, keeping the oldest row).
   * - Deleting a contact no longer deletes the ballots they cast (the ballot loses its voter instead).
   * - The tamper-evident audit log table.
   */
  public function upgrade_20010() {
    Civi::log()->info('Applying update 20010');
    CRM_Core_DAO::executeQuery('
      DELETE v1 FROM civicrm_election_vote v1
      INNER JOIN civicrm_election_vote v2
        ON v1.member_id = v2.member_id AND v1.election_nomination_id = v2.election_nomination_id AND v1.id > v2.id
      WHERE v1.member_id IS NOT NULL
    ');
    if (!CRM_Core_BAO_SchemaHandler::checkIfIndexExists('civicrm_election_vote', 'UI_election_vote_member_nomination')) {
      CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_election_vote ADD UNIQUE INDEX `UI_election_vote_member_nomination` (`member_id`, `election_nomination_id`)');
    }

    $foreignKey = CRM_Core_DAO::singleValueQuery("
      SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'civicrm_election_vote' AND COLUMN_NAME = 'member_id' AND REFERENCED_TABLE_NAME = 'civicrm_contact'
      LIMIT 1
    ");
    $rule = $foreignKey ? CRM_Core_DAO::singleValueQuery("SELECT DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'civicrm_election_vote' AND CONSTRAINT_NAME = %1", [1 => [$foreignKey, 'String']]) : NULL;
    if ($foreignKey && $rule !== 'SET NULL') {
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_election_vote DROP FOREIGN KEY `$foreignKey`");
      CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_election_vote ADD CONSTRAINT `FK_civicrm_election_vote_member_id` FOREIGN KEY (`member_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE SET NULL');
    }

    CRM_Core_DAO::executeQuery('
      CREATE TABLE IF NOT EXISTS `civicrm_election_audit` (
        `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT \'Unique audit entry ID\',
        `election_id` int unsigned NULL COMMENT \'The election the entry is about\',
        `created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT \'When the change happened\',
        `contact_id` int unsigned NULL COMMENT \'The contact who made the change, if known\',
        `action` varchar(64) NULL COMMENT \'What happened\',
        `details` text NULL COMMENT \'JSON describing the change\',
        `prev_hash` varchar(64) NULL COMMENT \'Hash of the previous entry\',
        `entry_hash` varchar(64) NULL COMMENT \'Hash of this entry and the previous hash\',
        PRIMARY KEY (`id`),
        INDEX `index_election_id` (`election_id`)
      ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ');
    return TRUE;
  }

  /**
   * Add a column unless it already exists, so upgrade steps can safely be re-run.
   *
   * @param string $table
   * @param string $column
   * @param string $definition
   *   SQL column definition after the column name.
   */
  private function ensureColumn($table, $column, $definition) {
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists($table, $column)) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
  }

}
