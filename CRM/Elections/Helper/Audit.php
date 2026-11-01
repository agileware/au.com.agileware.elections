<?php

/**
 * A tamper-evident log of what was changed in an election, by whom and when.
 *
 * Each entry stores a hash of its own content and of the entry before it, so changing, removing or
 * inserting an entry in the middle of the log breaks the chain and is found by verify(). Writing the
 * fingerprint of the latest entry (see getHeadHash()) down elsewhere also exposes entries cut off
 * the end. Ballots are never recorded here: no voter, no choices.
 */
class CRM_Elections_Helper_Audit {

  const NO_PREVIOUS = '0000000000000000000000000000000000000000000000000000000000000000';

  /**
   * Add an entry.
   *
   * @param int|null $electionId
   * @param string $action
   *   Short name, for example election_changed, results_generated.
   * @param array $details
   *   Anything that helps to understand the change. Must never contain how someone voted.
   * @param int|null $contactId
   *   Defaults to the logged in contact (none for the scheduled job and checksum links). FALSE records no contact at all.
   */
  public static function log($electionId, $action, array $details = [], $contactId = NULL) {
    if ($contactId === NULL) {
      $contactId = CRM_Core_Session::getLoggedInContactID() ?: NULL;
    }
    elseif ($contactId === FALSE) {
      // Deliberately no contact: a record that must not say who acted (for example a voter writing someone in).
      $contactId = NULL;
    }
    $lockName = 'elections_audit_chain';
    CRM_Core_DAO::singleValueQuery('SELECT GET_LOCK(%1, 10)', [1 => [self::lockKey($lockName), 'String']]);
    try {
      $previous = CRM_Core_DAO::singleValueQuery('SELECT entry_hash FROM civicrm_election_audit ORDER BY id DESC LIMIT 1') ?: self::NO_PREVIOUS;
      $created = date('Y-m-d H:i:s');
      $json = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      $hash = self::hashEntry($previous, $electionId, $created, $contactId, $action, $json);
      // NULL cannot be passed as a typed parameter, so it is written into the statement.
      $electionSql = $electionId === NULL ? 'NULL' : (string) (int) $electionId;
      $contactSql = $contactId === NULL ? 'NULL' : (string) (int) $contactId;
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_election_audit (election_id, created_date, contact_id, action, details, prev_hash, entry_hash) VALUES ($electionSql, %1, $contactSql, %2, %3, %4, %5)", [
        1 => [$created, 'String'],
        2 => [$action, 'String'],
        3 => [$json, 'String'],
        4 => [$previous, 'String'],
        5 => [$hash, 'String'],
      ]);
    }
    finally {
      CRM_Core_DAO::singleValueQuery('SELECT RELEASE_LOCK(%1)', [1 => [self::lockKey($lockName), 'String']]);
    }
  }

  private static function lockKey($name) {
    // Lock names are shared by every database on the server: make it specific to this one.
    return $name . '_' . substr(md5((string) CRM_Core_DAO::singleValueQuery('SELECT DATABASE()')), 0, 12);
  }

  private static function hashEntry($previous, $electionId, $created, $contactId, $action, $json) {
    return hash('sha256', implode("\n", [$previous, (string) $electionId, $created, (string) $contactId, $action, $json]));
  }

  /**
   * Check the whole chain.
   *
   * @return array
   *   ok (bool), count (int) and broken_at (the id of the first entry that does not fit, or NULL).
   */
  public static function verify() {
    $dao = CRM_Core_DAO::executeQuery('SELECT * FROM civicrm_election_audit ORDER BY id ASC');
    $previous = self::NO_PREVIOUS;
    $count = 0;
    while ($dao->fetch()) {
      $count++;
      $expected = self::hashEntry($previous, $dao->election_id, $dao->created_date, $dao->contact_id, $dao->action, $dao->details);
      if ($dao->prev_hash !== $previous || $dao->entry_hash !== $expected) {
        return ['ok' => FALSE, 'count' => $count, 'broken_at' => (int) $dao->id];
      }
      $previous = $dao->entry_hash;
    }
    return ['ok' => TRUE, 'count' => $count, 'broken_at' => NULL];
  }

  /**
   * The hash of the latest entry: note it down outside the database to detect entries removed from the end.
   *
   * @return string
   */
  public static function getHeadHash() {
    return CRM_Core_DAO::singleValueQuery('SELECT entry_hash FROM civicrm_election_audit ORDER BY id DESC LIMIT 1') ?: self::NO_PREVIOUS;
  }

  /**
   * The latest entries about one election, newest first.
   *
   * @param int $electionId
   * @param int $limit
   *
   * @return array[]
   */
  public static function entries($electionId, $limit = 50) {
    $dao = CRM_Core_DAO::executeQuery('SELECT a.id, a.created_date, a.contact_id, a.action, a.details, c.display_name FROM civicrm_election_audit a LEFT JOIN civicrm_contact c ON c.id = a.contact_id WHERE a.election_id = %1 ORDER BY a.id DESC LIMIT ' . (int) $limit, [1 => [(int) $electionId, 'Integer']]);
    $rows = [];
    while ($dao->fetch()) {
      $rows[] = [
        'id' => (int) $dao->id,
        'date' => $dao->created_date,
        'who' => $dao->display_name ?: '',
        'action' => $dao->action,
        'details' => json_decode((string) $dao->details, TRUE) ?: [],
      ];
    }
    return $rows;
  }

}
