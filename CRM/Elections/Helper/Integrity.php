<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * Fingerprints of the ballots and the results of an election, taken when the results are generated and
 * written to the audit log, so that any later change to either is found.
 *
 * The fingerprint of the ballots does not need to know who voted: it is made from the sorted list of
 * "candidate:rank" rows, so it still works after the votes are anonymised.
 */
class CRM_Elections_Helper_Integrity {

  /**
   * Set while a voter replaces their own ballot (the old rows are deleted, new ones written).
   *
   * @var bool
   */
  public static $replacingBallot = FALSE;

  /**
   * @param int $electionId
   *
   * @return string
   */
  public static function votesHash($electionId) {
    $dao = CRM_Core_DAO::executeQuery('
      SELECT CONCAT(v.election_nomination_id, ":", v.rank) AS line
      FROM civicrm_election_vote v
      INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id
      INNER JOIN civicrm_election_position p ON p.id = n.election_position_id
      WHERE p.election_id = %1
    ', [1 => [(int) $electionId, 'Integer']]);
    $lines = [];
    while ($dao->fetch()) {
      $lines[] = $dao->line;
    }
    sort($lines, SORT_STRING);
    return hash('sha256', implode("\n", $lines));
  }

  /**
   * @param int $electionId
   *
   * @return string
   */
  public static function resultsHash($electionId) {
    $dao = CRM_Core_DAO::executeQuery('
      SELECT CONCAT("r:", r.election_position_id, ":", r.election_nomination_id, ":", r.rank) AS line
      FROM civicrm_election_result r
      INNER JOIN civicrm_election_position p ON p.id = r.election_position_id
      WHERE p.election_id = %1
      UNION ALL
      SELECT CONCAT("p:", p.id, ":", IFNULL(p.result_status, ""))
      FROM civicrm_election_position p
      WHERE p.election_id = %1
    ', [1 => [(int) $electionId, 'Integer']]);
    $lines = [];
    while ($dao->fetch()) {
      $lines[] = $dao->line;
    }
    sort($lines, SORT_STRING);
    return hash('sha256', implode("\n", $lines));
  }

  /**
   * Record the fingerprints of an election whose results were just generated.
   *
   * @param int $electionId
   * @param int $ballots
   *   Number of ballots counted.
   */
  public static function recordGeneration($electionId, $ballots) {
    CRM_Elections_Helper_Audit::log($electionId, 'results_generated', [
      'votes_hash' => self::votesHash($electionId),
      'results_hash' => self::resultsHash($electionId),
      'ballots' => (int) $ballots,
    ]);
  }

  /**
   * Compare the ballots and results now with what was recorded when the results were generated.
   *
   * @param int $electionId
   *
   * @return array
   *   status: not_generated, no_record, ok or mismatch; and a message in plain words.
   */
  public static function verify($electionId) {
    $generated = (int) CRM_Core_DAO::getFieldValue('CRM_Elections_DAO_Election', $electionId, 'has_results_generated');
    if (!$generated) {
      return ['status' => 'not_generated', 'message' => E::ts('Results have not been generated yet.')];
    }
    $chain = CRM_Elections_Helper_Audit::verify();
    if (!$chain['ok']) {
      return ['status' => 'mismatch', 'message' => E::ts('The audit log has been altered: entry %1 does not match the entry before it.', [1 => $chain['broken_at']])];
    }
    $recorded = CRM_Core_DAO::singleValueQuery('SELECT details FROM civicrm_election_audit WHERE election_id = %1 AND action = "results_generated" ORDER BY id DESC LIMIT 1', [1 => [(int) $electionId, 'Integer']]);
    if (!$recorded) {
      return ['status' => 'no_record', 'message' => E::ts('These results were generated before fingerprints were recorded, so they cannot be checked.')];
    }
    $recorded = json_decode($recorded, TRUE) ?: [];
    $votesOk = hash_equals((string) ($recorded['votes_hash'] ?? ''), self::votesHash($electionId));
    $resultsOk = hash_equals((string) ($recorded['results_hash'] ?? ''), self::resultsHash($electionId));
    if ($votesOk && $resultsOk) {
      return ['status' => 'ok', 'message' => E::ts('The ballots and results are exactly as they were when the results were generated.')];
    }
    $what = !$votesOk && !$resultsOk ? E::ts('The ballots and the results') : ($votesOk ? E::ts('The results') : E::ts('The ballots'));
    return ['status' => 'mismatch', 'message' => E::ts('%1 have changed since the results were generated.', [1 => $what])];
  }

}
