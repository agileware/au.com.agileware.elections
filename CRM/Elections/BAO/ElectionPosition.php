<?php
use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_BAO_ElectionPosition extends CRM_Elections_DAO_ElectionPosition {

  /**
   * Positions can only be added, changed, reordered or deleted before the election starts
   * (or while it is inactive). Applies to every write path, including SearchKit inline edits
   * and drag and drop reordering.
   *
   * @param int $electionId
   *
   * @throws CRM_Core_Exception
   */
  public static function assertCanChange($electionId) {
    $election = findElectionById($electionId, FALSE);
    if ($election->is_deleted) {
      throw new CRM_Core_Exception(E::ts('Election position cannot be added/modified for deleted election.'));
    }
    if ($election->isNominationsStarted && $election->is_visible == 1) {
      throw new CRM_Core_Exception(E::ts('Election position cannot be added/modified once election has been started.'));
    }
  }

  /**
   * A position's value as it is compared to see whether it changed. An empty number of write-ins is 0.
   *
   * @param string $field
   * @param mixed $value
   *
   * @return string
   */
  public static function normalise($field, $value) {
    return $field === 'write_ins_per_seat' ? (string) (int) $value : (string) $value;
  }

  /**
   * The same rule for a position written through APIv4, checked before CiviCRM moves any other position.
   *
   * @param array $item
   *   The values being written, with the position's id when it already exists.
   *
   * @throws CRM_Core_Exception
   */
  public static function assertItemCanChange(array $item) {
    $electionId = $item['election_id'] ?? NULL;
    if (!empty($item['id'])) {
      $current = CRM_Core_DAO::executeQuery('SELECT name, quantity, sortorder, description, election_id, write_ins_per_seat FROM civicrm_election_position WHERE id = %1', [1 => [(int) $item['id'], 'Positive']]);
      if (!$current->fetch()) {
        return;
      }
      $changes = FALSE;
      foreach (['name', 'quantity', 'sortorder', 'description', 'election_id', 'write_ins_per_seat'] as $field) {
        if (array_key_exists($field, $item) && self::normalise($field, $item[$field]) !== self::normalise($field, $current->$field)) {
          $changes = TRUE;
        }
      }
      if (!$changes) {
        return;
      }
      $electionId = $electionId ?: $current->election_id;
    }
    if ($electionId) {
      self::assertCanChange($electionId);
    }
  }

  /**
   * Copy a position to the end of its election's list.
   *
   * @param int $positionId
   * @param string|null $name
   *
   * @return array
   * @throws CRM_Core_Exception
   */
  public static function duplicate($positionId, $name = NULL) {
    $source = civicrm_api3('ElectionPosition', 'getsingle', ['id' => $positionId]);
    $last = (int) CRM_Core_DAO::singleValueQuery('SELECT MAX(sortorder) FROM civicrm_election_position WHERE election_id = %1', [1 => [$source['election_id'], 'Positive']]);
    $copy = civicrm_api3('ElectionPosition', 'create', [
      'election_id' => $source['election_id'],
      'name' => $name ?: E::ts('Copy of %1', [1 => $source['name']]),
      'quantity' => $source['quantity'] ?? 1,
      'write_ins_per_seat' => $source['write_ins_per_seat'] ?? 0,
      'description' => $source['description'] ?? NULL,
      'sortorder' => $last + 1,
    ]);
    return civicrm_api3('ElectionPosition', 'getsingle', ['id' => $copy['id']]);
  }

  public static function findWithNominationsByElectionId($electionId) {
    $nominations = civicrm_api3('ElectionNomination', 'get', [
      'election_position_id.election_id'  => $electionId,
      'return' => array_merge(['member_nominee.image_URL', 'election_position_id', 'comments', 'statement_url', 'is_eligible_candidate', 'id', 'has_rejected_nomination', 'rejection_comments', 'has_accepted_nomination', 'member_nominee'], CRM_Elections_Helper_Candidate::NAME_RETURN_FIELDS),
      'options' => ['limit' => 0],
    ]);

    $nominations = elections_shuffle_assoc($nominations['values']);
    CRM_Elections_Helper_Candidate::applyNameFormatToAll($nominations, CRM_Elections_Helper_Candidate::getNameFormatForElection($electionId));

    $nominationIds = array_column($nominations, 'id');

    $nominationSeconders = [];
    if (!empty($nominationIds)) {
      $nominationSeconders = civicrm_api3('ElectionNominationSeconder', 'get', [
        'election_nomination_id' => [
          'IN' => $nominationIds,
        ],
        'options' => [
          'limit' => 0,
        ],
        'return' => ['id', 'description', 'member_nominator.display_name', 'member_nominator', 'election_nomination_id'],
      ]);
      $nominationSeconders = $nominationSeconders['values'];
    }

    foreach ($nominationSeconders as $nominationSeconder) {
      if (isset($nominations[$nominationSeconder['election_nomination_id']])) {
        if (!isset($nominations[$nominationSeconder['election_nomination_id']]['seconders'])) {
          $nominations[$nominationSeconder['election_nomination_id']]['seconders'] = [];
        }
        $nominations[$nominationSeconder['election_nomination_id']]['seconders'][] = $nominationSeconder;
      }
    }

    $electionPositions = civicrm_api3('ElectionPosition', 'get', [
      'election_id' => $electionId,
	  'options' => ['limit' => 0],
    ]);

    $electionPositions = $electionPositions['values'];

    foreach ($nominations as $nomination) {
      if (isset($electionPositions[$nomination['election_position_id']])) {
        if (!isset($electionPositions[$nomination['election_position_id']]['nominations'])) {
          $electionPositions[$nomination['election_position_id']]['nominations'] = [];
        }

        $electionPositions[$nomination['election_position_id']]['nominations'][] = $nomination;
      }
    }

    self::sortPositionsByOrder($electionPositions);

    return $electionPositions;
  }

  private static function sortPositionsByOrder(&$electionPositions) {
    uasort($electionPositions, function ($positionA, $positionB) {
      if ($positionA['sortorder'] == $positionB['sortorder']) {
        return 0;
      }
      if ($positionA['sortorder'] < $positionB['sortorder']) {
        return -1;
      }
      return 1;
    });
  }

}
