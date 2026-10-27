<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * Referendum questions: a position that is a yes or no question instead of people standing for seats.
 *
 * Each question has a Yes and a No choice (and optionally Abstain), with the wording the election chose. A voter
 * picks one. Counting: Yes out of Yes plus No must beat the pass threshold (more than half unless the question
 * says otherwise), a tie fails, Abstain counts towards the quorum and not towards the percentage, and an
 * optional quorum is the share of the people allowed to vote who must take part.
 */
class CRM_Elections_Helper_Referendum {

  /**
   * Check the choices a voter made on referendum questions.
   *
   * @param CRM_Elections_BAO_Election $election
   * @param array $optionVotes
   *   Each with position_id and option_id.
   *
   * @return array
   *   `error` (a message, or NULL) and `items`: position_id and option_id for each choice.
   */
  public static function resolve($election, array $optionVotes) {
    $items = [];
    $seen = [];
    foreach ($optionVotes as $choice) {
      $positionId = $choice['position_id'] ?? NULL;
      $optionId = $choice['option_id'] ?? NULL;
      if (!CRM_Utils_Rule::positiveInteger($positionId) || !CRM_Utils_Rule::positiveInteger($optionId)) {
        return ['error' => 'A choice needs a question and an option.', 'items' => []];
      }
      $isQuestion = CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_position WHERE id = %1 AND election_id = %2 AND position_type = "referendum"', [1 => [(int) $positionId, 'Integer'], 2 => [(int) $election->id, 'Integer']]);
      if (!$isQuestion) {
        return ['error' => 'That is not a question of this election.', 'items' => []];
      }
      if (!CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_option WHERE id = %1 AND election_position_id = %2', [1 => [(int) $optionId, 'Integer'], 2 => [(int) $positionId, 'Integer']])) {
        return ['error' => 'That option does not belong to the question.', 'items' => []];
      }
      if (isset($seen[$positionId])) {
        return ['error' => 'Only one choice can be made on a question.', 'items' => []];
      }
      $seen[$positionId] = TRUE;
      $items[] = ['position_id' => (int) $positionId, 'option_id' => (int) $optionId];
    }
    return ['error' => NULL, 'items' => $items];
  }

  /**
   * Count one question.
   *
   * @param int $positionId
   * @param int $eligibleVoters
   *   The number of people allowed to vote, for the quorum.
   *
   * @return string
   *   passed, failed or noquorum.
   */
  public static function count($positionId, $eligibleVoters) {
    $position = CRM_Core_DAO::executeQuery('SELECT pass_threshold, quorum_percent FROM civicrm_election_position WHERE id = %1', [1 => [(int) $positionId, 'Integer']]);
    $position->fetch();
    $tally = ['yes' => 0, 'no' => 0, 'abstain' => 0];
    $dao = CRM_Core_DAO::executeQuery('
      SELECT o.kind, COUNT(*) AS total
      FROM civicrm_election_option_vote v
      INNER JOIN civicrm_election_option o ON o.id = v.election_option_id
      WHERE v.election_position_id = %1
      GROUP BY o.kind
    ', [1 => [(int) $positionId, 'Integer']]);
    while ($dao->fetch()) {
      $tally[$dao->kind] = (int) $dao->total;
    }
    return self::decide($tally, $position->pass_threshold === NULL ? 50 : (float) $position->pass_threshold, $position->quorum_percent === NULL ? NULL : (float) $position->quorum_percent, $eligibleVoters);
  }

  /**
   * The rule, apart from the database.
   *
   * @param array $tally
   *   yes, no and abstain counts.
   * @param float $threshold
   *   Percent of Yes out of Yes plus No. 50 means more than half; any other value means at least that share.
   * @param float|null $quorum
   *   Percent of the people allowed to vote who must have taken part, or NULL for no quorum.
   * @param int $eligibleVoters
   *
   * @return string
   *   passed, failed or noquorum.
   */
  public static function decide(array $tally, $threshold, $quorum, $eligibleVoters) {
    $taken = (int) $tally['yes'] + (int) $tally['no'] + (int) $tally['abstain'];
    if ($quorum !== NULL && $quorum > 0 && ($eligibleVoters < 1 || 100 * $taken < $quorum * $eligibleVoters)) {
      return CRM_Elections_BAO_Election::$RESULTS_NO_QUORUM;
    }
    $decided = (int) $tally['yes'] + (int) $tally['no'];
    if ($decided === 0) {
      return CRM_Elections_BAO_Election::$RESULTS_FAILED;
    }
    $yes = 100 * (int) $tally['yes'];
    $needed = $threshold * $decided;
    // Exactly half is not more than half. Other thresholds are "at least", and a share kept to two decimals
    // (66.67 for two thirds) must not fail a result that is exactly two thirds, so it allows for the rounding.
    $passes = ($threshold == 50) ? ($yes > $needed) : ($yes + 0.01 * $decided >= $needed);
    return $passes ? CRM_Elections_BAO_Election::$RESULTS_PASSED : CRM_Elections_BAO_Election::$RESULTS_FAILED;
  }

}
