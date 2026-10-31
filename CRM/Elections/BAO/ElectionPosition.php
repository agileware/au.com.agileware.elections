<?php
use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_BAO_ElectionPosition extends CRM_Elections_DAO_ElectionPosition {

  const TYPE_ELECTION = 'election';

  const TYPE_REFERENDUM = 'referendum';

  /**
   * The fields that change what a position is. They are frozen once the election has started.
   */
  const DEFINING_FIELDS = ['name', 'quantity', 'sortorder', 'description', 'election_id', 'write_ins_per_seat', 'position_type', 'pass_threshold', 'quorum_percent', 'resolution_text'];

  /**
   * @return string[]
   */
  public static function getTypes() {
    return [
      self::TYPE_ELECTION => E::ts('Election position'),
      self::TYPE_REFERENDUM => E::ts('Referendum question'),
    ];
  }

  /**
   * @param int $positionId
   *
   * @return bool
   */
  public static function isReferendum($positionId) {
    return CRM_Core_DAO::singleValueQuery('SELECT position_type FROM civicrm_election_position WHERE id = %1', [1 => [(int) $positionId, 'Integer']]) === self::TYPE_REFERENDUM;
  }

  /**
   * The choices on a referendum question, in ballot order.
   *
   * @param int $positionId
   *
   * @return array
   *   Option id => [id, kind, label].
   */
  public static function getOptions($positionId) {
    $dao = CRM_Core_DAO::executeQuery('SELECT id, kind, label FROM civicrm_election_option WHERE election_position_id = %1 ORDER BY weight, id', [1 => [(int) $positionId, 'Integer']]);
    $options = [];
    while ($dao->fetch()) {
      $options[(int) $dao->id] = ['id' => (int) $dao->id, 'kind' => $dao->kind, 'label' => $dao->label];
    }
    return $options;
  }

  /**
   * Save the wording of a referendum question's choices. Yes and No always exist; Abstain only when it has wording.
   *
   * @param int $positionId
   * @param array $labels
   *   yes, no and abstain wording. An empty value uses the default for Yes and No, and removes Abstain.
   */
  public static function saveOptions($positionId, array $labels = []) {
    $defaults = ['yes' => E::ts('Yes'), 'no' => E::ts('No'), 'abstain' => ''];
    $existing = [];
    foreach (self::getOptions($positionId) as $option) {
      $existing[$option['kind']] = $option;
    }
    $weight = 0;
    foreach (['yes', 'no', 'abstain'] as $kind) {
      $weight++;
      $label = trim((string) ($labels[$kind] ?? (isset($existing[$kind]) ? $existing[$kind]['label'] : $defaults[$kind])));
      if ($label === '' && $kind !== 'abstain') {
        $label = $defaults[$kind];
      }
      if ($label === '') {
        if (isset($existing[$kind])) {
          civicrm_api4('ElectionOption', 'delete', ['where' => [['id', '=', $existing[$kind]['id']]], 'checkPermissions' => FALSE]);
        }
        continue;
      }
      if (isset($existing[$kind])) {
        if ($existing[$kind]['label'] !== $label) {
          civicrm_api4('ElectionOption', 'update', ['values' => ['label' => $label], 'where' => [['id', '=', $existing[$kind]['id']]], 'checkPermissions' => FALSE]);
        }
      }
      else {
        civicrm_api4('ElectionOption', 'create', ['values' => ['election_position_id' => (int) $positionId, 'kind' => $kind, 'label' => $label, 'weight' => $weight], 'checkPermissions' => FALSE]);
      }
    }
  }

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
      throw new CRM_Elections_Exception(E::ts('Election position cannot be added/modified for deleted election.'));
    }
    if ($election->isNominationsStarted && $election->is_visible == 1) {
      throw new CRM_Elections_Exception(E::ts('This election is active, so its positions cannot be changed. Positions can be changed only before nominations start, or while the election is switched off.'));
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
    if ($field === 'write_ins_per_seat') {
      return (string) (int) $value;
    }
    if ($field === 'position_type') {
      return $value === self::TYPE_REFERENDUM ? self::TYPE_REFERENDUM : self::TYPE_ELECTION;
    }
    if ($field === 'pass_threshold') {
      return ($value === NULL || $value === '') ? '50' : (string) (float) $value;
    }
    if ($field === 'quorum_percent') {
      return ($value === NULL || $value === '' || (float) $value == 0) ? '' : (string) (float) $value;
    }
    return (string) $value;
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
      $current = CRM_Core_DAO::executeQuery('SELECT ' . implode(', ', self::DEFINING_FIELDS) . ' FROM civicrm_election_position WHERE id = %1', [1 => [(int) $item['id'], 'Positive']]);
      if (!$current->fetch()) {
        return;
      }
      $changes = FALSE;
      foreach (self::DEFINING_FIELDS as $field) {
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
      'position_type' => $source['position_type'] ?? self::TYPE_ELECTION,
      'pass_threshold' => $source['pass_threshold'] ?? 50,
      'quorum_percent' => $source['quorum_percent'] ?? NULL,
      'resolution_text' => $source['resolution_text'] ?? NULL,
      'description' => $source['description'] ?? NULL,
      'sortorder' => $last + 1,
    ]);
    if (($source['position_type'] ?? '') === self::TYPE_REFERENDUM) {
      $labels = [];
      foreach (self::getOptions($positionId) as $option) {
        $labels[$option['kind']] = $option['label'];
      }
      self::saveOptions((int) $copy['id'], $labels);
    }
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
