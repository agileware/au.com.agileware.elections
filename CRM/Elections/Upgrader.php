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
}
