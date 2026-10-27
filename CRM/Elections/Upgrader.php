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
   * Version 2.0.0 security hardening, part 2.
   *
   * - One nomination for each person and position, and one nominator row for each nominator and
   *   nomination, so that a repeated request can never count as a second nominator. Duplicates from
   *   earlier versions are merged first (keeping the oldest row and what hangs off the others).
   */
  public function upgrade_20020() {
    Civi::log()->info('Applying update 20020');
    $duplicates = CRM_Core_DAO::executeQuery('
      SELECT election_position_id, member_nominee, MIN(id) AS keep_id
      FROM civicrm_election_nomination
      GROUP BY election_position_id, member_nominee
      HAVING COUNT(*) > 1
    ');
    while ($duplicates->fetch()) {
      $keep = (int) $duplicates->keep_id;
      $others = CRM_Core_DAO::executeQuery('SELECT id FROM civicrm_election_nomination WHERE election_position_id = %1 AND member_nominee = %2 AND id <> %3', [
        1 => [(int) $duplicates->election_position_id, 'Integer'],
        2 => [(int) $duplicates->member_nominee, 'Integer'],
        3 => [$keep, 'Integer'],
      ])->fetchAll();
      foreach (array_column($others, 'id') as $other) {
        $other = (int) $other;
        CRM_Core_DAO::executeQuery("UPDATE IGNORE civicrm_election_nomination_seconder SET election_nomination_id = $keep WHERE election_nomination_id = $other");
        CRM_Core_DAO::executeQuery("UPDATE IGNORE civicrm_election_vote SET election_nomination_id = $keep WHERE election_nomination_id = $other");
        CRM_Core_DAO::executeQuery("UPDATE civicrm_election_nomination k INNER JOIN civicrm_election_nomination o ON o.id = $other SET k.is_eligible_candidate = GREATEST(IFNULL(k.is_eligible_candidate, 0), IFNULL(o.is_eligible_candidate, 0)), k.has_accepted_nomination = GREATEST(IFNULL(k.has_accepted_nomination, 0), IFNULL(o.has_accepted_nomination, 0)), k.has_rejected_nomination = GREATEST(IFNULL(k.has_rejected_nomination, 0), IFNULL(o.has_rejected_nomination, 0)) WHERE k.id = $keep");
        CRM_Core_DAO::executeQuery("DELETE FROM civicrm_election_nomination WHERE id = $other");
      }
    }
    CRM_Core_DAO::executeQuery('
      DELETE s1 FROM civicrm_election_nomination_seconder s1
      INNER JOIN civicrm_election_nomination_seconder s2
        ON s1.member_nominator = s2.member_nominator AND s1.election_nomination_id = s2.election_nomination_id AND s1.id > s2.id
    ');
    if (!CRM_Core_BAO_SchemaHandler::checkIfIndexExists('civicrm_election_nomination', 'UI_election_nomination_position_nominee')) {
      CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_election_nomination ADD UNIQUE INDEX `UI_election_nomination_position_nominee` (`election_position_id`, `member_nominee`)');
    }
    if (!CRM_Core_BAO_SchemaHandler::checkIfIndexExists('civicrm_election_nomination_seconder', 'UI_election_nomination_seconder_nominator_nomination')) {
      CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_election_nomination_seconder ADD UNIQUE INDEX `UI_election_nomination_seconder_nominator_nomination` (`member_nominator`, `election_nomination_id`)');
    }
    return TRUE;
  }

  /**
   * Version 2.0.0: how each election is counted (ranked choice, which every existing election uses, or most votes win).
   */
  public function upgrade_20030() {
    Civi::log()->info('Applying update 20030');
    // Nullable like the other election settings: an update that leaves it out stores NULL, which means ranked choice.
    $this->ensureColumn('civicrm_election', 'voting_method', "VARCHAR(32) NULL DEFAULT 'irv'");
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_election MODIFY COLUMN voting_method VARCHAR(32) NULL DEFAULT 'irv'");
    return TRUE;
  }

  /**
   * Version 2.0.0: separate groups for who may nominate and second, and who may stand. Empty means the
   * voting groups, so every existing election behaves as before.
   */
  public function upgrade_20040() {
    Civi::log()->info('Applying update 20040');
    $this->ensureColumn('civicrm_election', 'nomination_groups', 'TEXT NULL DEFAULT NULL');
    $this->ensureColumn('civicrm_election', 'candidate_groups', 'TEXT NULL DEFAULT NULL');
    return TRUE;
  }

  /**
   * Version 2.0.0: emails about nominations, switched on for each election (off for every existing election).
   */
  public function upgrade_20050() {
    Civi::log()->info('Applying update 20050');
    $this->ensureColumn('civicrm_election', 'send_emails', 'TINYINT NULL DEFAULT 0');
    return TRUE;
  }

  /**
   * Version 2.0.0: write-ins. A position can allow voters to write people in (off for every existing
   * position); a person written in is a nomination marked as a write-in until they accept.
   */
  public function upgrade_20060() {
    Civi::log()->info('Applying update 20060');
    $this->ensureColumn('civicrm_election_position', 'write_ins_per_seat', 'INT UNSIGNED NULL DEFAULT 0');
    $this->ensureColumn('civicrm_election_nomination', 'is_write_in', 'TINYINT NULL DEFAULT 0');
    $this->ensureColumn('civicrm_election_nomination', 'write_in_notified_date', 'DATETIME NULL DEFAULT NULL');
    $this->ensureColumn('civicrm_election_nomination', 'write_in_reminded_date', 'DATETIME NULL DEFAULT NULL');
    return TRUE;
  }

  /**
   * Version 2.0.0: referenda. A position can be a yes or no question (off for every existing position) with its
   * own options and ballot rows, counted by a pass threshold and an optional quorum.
   */
  public function upgrade_20070() {
    Civi::log()->info('Applying update 20070');
    $this->ensureColumn('civicrm_election_position', 'position_type', "VARCHAR(16) NULL DEFAULT 'election'");
    $this->ensureColumn('civicrm_election_position', 'pass_threshold', 'DECIMAL(5,2) NULL DEFAULT 50');
    $this->ensureColumn('civicrm_election_position', 'quorum_percent', 'DECIMAL(5,2) NULL DEFAULT NULL');
    $this->ensureColumn('civicrm_election_position', 'resolution_text', 'TEXT NULL');
    CRM_Core_DAO::executeQuery('
      CREATE TABLE IF NOT EXISTS `civicrm_election_option` (
        `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT \'Unique ElectionOption ID\',
        `election_position_id` int unsigned NULL COMMENT \'FK to the referendum question this is a choice of.\',
        `kind` varchar(16) NULL COMMENT \'yes, no or abstain.\',
        `label` varchar(255) NULL COMMENT \'The wording shown to voters.\',
        `weight` int NULL DEFAULT 0 COMMENT \'Order on the ballot.\',
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT \'Date on which the option was created.\',
        PRIMARY KEY (`id`),
        UNIQUE INDEX `UI_election_option_position_kind` (`election_position_id`, `kind`),
        CONSTRAINT `FK_civicrm_election_option_election_position_id` FOREIGN KEY (`election_position_id`) REFERENCES `civicrm_election_position`(`id`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ');
    CRM_Core_DAO::executeQuery('
      CREATE TABLE IF NOT EXISTS `civicrm_election_option_vote` (
        `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT \'Unique ElectionOptionVote ID\',
        `election_option_id` int unsigned NULL COMMENT \'FK to the option chosen.\',
        `election_position_id` int unsigned NULL COMMENT \'FK to the question answered.\',
        `member_id` int unsigned NULL COMMENT \'FK to Contact who made this choice.\',
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT \'Date on which the choice was made.\',
        PRIMARY KEY (`id`),
        UNIQUE INDEX `UI_election_option_vote_member_position` (`member_id`, `election_position_id`),
        CONSTRAINT `FK_civicrm_election_option_vote_election_option_id` FOREIGN KEY (`election_option_id`) REFERENCES `civicrm_election_option`(`id`) ON DELETE CASCADE,
        CONSTRAINT `FK_civicrm_election_option_vote_election_position_id` FOREIGN KEY (`election_position_id`) REFERENCES `civicrm_election_position`(`id`) ON DELETE CASCADE,
        CONSTRAINT `FK_civicrm_election_option_vote_member_id` FOREIGN KEY (`member_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE SET NULL
      ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ');
    return TRUE;
  }

  /**
   * Version 2.0.0: an election can turn online nominating off, for organisers who choose the candidates themselves
   * (on for every existing election).
   */
  public function upgrade_20080() {
    Civi::log()->info('Applying update 20080');
    $this->ensureColumn('civicrm_election', 'allow_online_nominations', 'TINYINT NULL DEFAULT 1');
    return TRUE;
  }

  /**
   * Version 2.0.0: a person written in who never answered is told when voting closes.
   */
  public function upgrade_20090() {
    Civi::log()->info('Applying update 20090');
    $this->ensureColumn('civicrm_election_nomination', 'write_in_closed_notified_date', 'DATETIME NULL DEFAULT NULL');
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
