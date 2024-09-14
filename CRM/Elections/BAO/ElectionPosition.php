<?php
use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_BAO_ElectionPosition extends CRM_Elections_DAO_ElectionPosition {

  public static function findWithNominationsByElectionId($electionId) {
    $nominations = civicrm_api3('ElectionNomination', 'get', [
      'election_position_id.election_id'  => $electionId,
      'return' => ['member_nominee.display_name', 'member_nominee.image_URL', 'election_position_id', 'comments', 'is_eligible_candidate', 'id', 'has_rejected_nomination', 'rejection_comments', 'has_accepted_nomination', 'member_nominee'],
      'options' => ['limit' => 0],
    ]);

    $nominations = elections_shuffle_assoc($nominations['values']);

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
