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
