<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * Write-ins: during voting a voter can name a person who is not on the ballot, where the position allows
 * it. The person is a nomination marked as a write-in. Votes for them are ordinary ballot rows on that
 * nomination, but only count if the person accepts before voting closes; accepting makes them a candidate
 * like any other (shown on the ballot to everyone who votes afterwards). Declining, or no answer, drops the
 * votes, and the ballot moves on to the voter's next choice.
 *
 * This is the only way anyone can be added to the ballot once voting has started.
 */
class CRM_Elections_Helper_WriteIn {

  /**
   * The most write-ins a voter can be given for each seat.
   */
  const MAX_PER_SEAT = 10;

  /**
   * How many write-in places a voter has in a position.
   *
   * @param array|object $position
   *
   * @return int
   */
  public static function places($position) {
    $position = (array) $position;
    $perSeat = min(self::MAX_PER_SEAT, max(0, (int) ($position['write_ins_per_seat'] ?? 0)));
    return $perSeat * max(1, (int) ($position['quantity'] ?? 1));
  }

  /**
   * Check the people a voter writes in and work out what each one is.
   *
   * @param CRM_Elections_BAO_Election $election
   * @param array $writeIns
   *   Each with position_id, contact_id and rank.
   *
   * @return array
   *   `error` (a message, or NULL) and `items`: each write-in with `kind` (new, pending or candidate) and
   *   the `nomination_id` when the person is already on record for the position.
   */
  public static function resolve($election, array $writeIns) {
    $items = [];
    $perPosition = [];
    $seen = [];
    foreach ($writeIns as $writeIn) {
      $positionId = $writeIn['position_id'] ?? NULL;
      $contactId = $writeIn['contact_id'] ?? NULL;
      $rank = $writeIn['rank'] ?? NULL;
      if (!CRM_Utils_Rule::positiveInteger($positionId) || !CRM_Utils_Rule::positiveInteger($contactId) || !CRM_Utils_Rule::positiveInteger($rank)) {
        return ['error' => 'A write-in needs a position, a person and a rank.', 'items' => []];
      }
      $position = CRM_Core_DAO::executeQuery('SELECT id, quantity, write_ins_per_seat FROM civicrm_election_position WHERE id = %1 AND election_id = %2 AND IFNULL(position_type, "election") <> "referendum"', [1 => [(int) $positionId, 'Integer'], 2 => [(int) $election->id, 'Integer']]);
      if (!$position->fetch()) {
        return ['error' => 'The selected position is not part of this election.', 'items' => []];
      }
      $places = self::places($position->toArray());
      if ($places < 1) {
        return ['error' => 'Write-ins are not allowed for this position.', 'items' => []];
      }
      $perPosition[$positionId] = ($perPosition[$positionId] ?? 0) + 1;
      if ($perPosition[$positionId] > $places) {
        return ['error' => 'There are more write-ins than this position allows.', 'items' => []];
      }
      if (isset($seen[$positionId][$contactId])) {
        return ['error' => 'A person can only be written in once for a position.', 'items' => []];
      }
      $seen[$positionId][$contactId] = TRUE;

      $existing = CRM_Core_DAO::executeQuery('SELECT id, is_eligible_candidate, has_accepted_nomination, has_rejected_nomination, is_write_in FROM civicrm_election_nomination WHERE election_position_id = %1 AND member_nominee = %2', [1 => [(int) $positionId, 'Integer'], 2 => [(int) $contactId, 'Integer']]);
      $item = ['position_id' => (int) $positionId, 'contact_id' => (int) $contactId, 'rank' => (int) $rank, 'nomination_id' => NULL, 'kind' => 'new'];
      if ($existing->fetch()) {
        if ($existing->has_rejected_nomination) {
          return ['error' => 'That person has declined or withdrawn for this position and cannot be written in.', 'items' => []];
        }
        if ($existing->is_eligible_candidate && $existing->has_accepted_nomination) {
          $item['kind'] = 'candidate';
        }
        elseif ($existing->is_write_in) {
          $item['kind'] = 'pending';
        }
        else {
          return ['error' => 'That person was nominated and has not accepted, so cannot be written in.', 'items' => []];
        }
        $item['nomination_id'] = (int) $existing->id;
      }
      elseif (!self::canBeWrittenIn($election, (int) $contactId)) {
        return ['error' => 'That person cannot be written in for this election.', 'items' => []];
      }
      $items[] = $item;
    }
    return ['error' => NULL, 'items' => $items];
  }

  /**
   * The explanation shown to a person who tries to answer a write-in nomination after voting closed.
   *
   * @param CRM_Elections_BAO_Election $election
   * @param string|null $positionName
   *
   * @return string
   */
  public static function closedMessage($election, $positionName = NULL) {
    $closed = CRM_Utils_Date::customFormat($election->voting_end_date);
    if ($positionName) {
      return E::ts('Voting closed on %1, so the opportunity to answer this nomination has passed. You were written in for %2 in the election "%3", and write-in nominations can only be accepted or declined before voting closes. You are not on the ballot and no votes written in for you are counted. Nothing more is needed from you.', [1 => $closed, 2 => $positionName, 3 => $election->name]);
    }
    return E::ts('Voting closed on %1, so the opportunity to answer this nomination has passed. Write-in nominations can only be accepted or declined before voting closes.', [1 => $closed]);
  }

  /**
   * Write one entry in the audit log saying how many people were written in and how each ended. Counts
   * only: it never says who wrote whom in.
   *
   * @param int $electionId
   */
  public static function logSummary($electionId) {
    $rows = CRM_Core_DAO::executeQuery('
      SELECT n.has_accepted_nomination AS accepted, n.has_rejected_nomination AS declined
      FROM civicrm_election_nomination n
      INNER JOIN civicrm_election_position p ON p.id = n.election_position_id
      WHERE p.election_id = %1 AND n.is_write_in = 1
    ', [1 => [(int) $electionId, 'Integer']]);
    $summary = ['written_in' => 0, 'accepted' => 0, 'declined' => 0, 'unanswered' => 0];
    while ($rows->fetch()) {
      $summary['written_in']++;
      $summary[$rows->accepted ? 'accepted' : ($rows->declined ? 'declined' : 'unanswered')]++;
    }
    if ($summary['written_in']) {
      CRM_Elections_Helper_Audit::log((int) $electionId, 'write_ins_summary', $summary);
    }
  }

  /**
   * Can this person stand in the election: a living, undeleted contact in the candidate groups.
   *
   * @param CRM_Elections_BAO_Election $election
   * @param int $contactId
   *
   * @return bool
   */
  public static function canBeWrittenIn($election, $contactId) {
    $contact = CRM_Core_DAO::executeQuery('SELECT is_deleted, is_deceased FROM civicrm_contact WHERE id = %1', [1 => [$contactId, 'Integer']]);
    if (!$contact->fetch() || $contact->is_deleted || $contact->is_deceased) {
      return FALSE;
    }
    return CRM_Elections_Helper_Eligibility::canBeNominated($election->id, $contactId);
  }

  /**
   * Record a person as written in for a position (once, whoever writes them in first). Call inside the
   * ballot's transaction, with the invitation sent afterwards by invite().
   *
   * @param CRM_Elections_BAO_Election $election
   * @param int $positionId
   * @param int $contactId
   *
   * @return int
   *   The nomination id.
   */
  public static function record($election, $positionId, $contactId) {
    $lock = Civi::lockManager()->acquire('data.elections.nominate.' . (int) $positionId . '.' . (int) $contactId, 10);
    if (!$lock->isAcquired()) {
      throw new CRM_Elections_Exception(E::ts('This person is being written in. Please wait a moment and try again.'));
    }
    try {
      $existing = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_election_nomination WHERE election_position_id = %1 AND member_nominee = %2', [1 => [(int) $positionId, 'Integer'], 2 => [(int) $contactId, 'Integer']]);
      if ($existing) {
        return (int) $existing;
      }
      CRM_Elections_Helper_Guard::$writeIn = TRUE;
      try {
        // APIv4: the nomination window of APIv3 is for nominations; a write-in happens during voting.
        $id = (int) civicrm_api4('ElectionNomination', 'create', [
          'values' => [
            'election_position_id' => (int) $positionId,
            'member_nominee' => (int) $contactId,
            'is_eligible_candidate' => TRUE,
            'has_accepted_nomination' => FALSE,
            'has_rejected_nomination' => FALSE,
            'is_write_in' => TRUE,
          ],
          'checkPermissions' => FALSE,
        ])->first()['id'];
        // Nothing on the record says when it was made: the time would line up with the time the voter's ballot was cast.
        $opened = CRM_Core_DAO::singleValueQuery('SELECT voting_start_date FROM civicrm_election WHERE id = %1', [1 => [(int) $election->id, 'Integer']]);
        CRM_Core_DAO::executeQuery('UPDATE civicrm_election_nomination SET created_at = %2, updated_at = %2, write_in_notified_date = %3 WHERE id = %1', [
          1 => [$id, 'Integer'],
          2 => [$opened, 'String'],
          3 => [date('Y-m-d 00:00:00'), 'String'],
        ]);
      }
      finally {
        CRM_Elections_Helper_Guard::$writeIn = FALSE;
      }
      return $id;
    }
    finally {
      $lock->release();
    }
  }

}
