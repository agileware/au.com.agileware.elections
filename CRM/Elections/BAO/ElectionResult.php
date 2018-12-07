<?php
use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_BAO_ElectionResult extends CRM_Elections_DAO_ElectionResult {
  public static function getResultsAndSummary($electionId, $completeSummary = FALSE) {
    $positions = civicrm_api3("ElectionPosition", "get", array(
      'election_id' => $electionId,
      'options' => array('limit' => 0, 'sort' => 'sortorder ASC'),
    ));
    $positions = $positions["values"];
    self::setCandidatesAndResultsForEachPosition($positions, $completeSummary);
    self::updateCandidateProfilePictures($positions, TRUE, $completeSummary);
    return $positions;
  }

  public static function updateCandidateProfilePictures(&$positions, $hasCandidates = TRUE, $hasNominations = FALSE) {
    $candidateIds = array();
    foreach ($positions as $position) {
      if ($hasCandidates && isset($position['candidates'])) {
        $candidateIds = array_merge($candidateIds, array_column($position['candidates'], "member_nominee"));
      }
      if ($hasNominations && isset($position['nominations'])) {
        $candidateIds = array_merge($candidateIds, array_column($position['nominations'], "member_nominee"));
      }
    }

    $candidateIds = array_unique($candidateIds);
    $profilePictures = CRM_Elections_Helper_Utils::getCMSProfilePictures($candidateIds);

    foreach ($positions as $positionIndex => $position) {
      if ($hasCandidates && isset($position['candidates'])) {
        foreach ($position['candidates'] as $index => $candidate) {
          $positions[$positionIndex]['candidates'][$index]['member_nominee.image_URL'] = $profilePictures[$candidate['member_nominee']];
        }
      }

      if ($hasNominations && isset($position['nominations'])) {
        foreach ($position['nominations'] as $index => $nomination) {
          $positions[$positionIndex]['nominations'][$index]['member_nominee.image_URL'] = $profilePictures[$nomination['member_nominee']];
        }
      }
    }
  }

  private static function setCandidatesAndResultsForEachPosition(&$positions, $completeSummary) {
    foreach ($positions as $positionId => $position) {
      if ($completeSummary) {
        $positions[$positionId]['nominations'] = self::getAllNominations($positionId);
      }
      $positions[$positionId]['candidates'] = self::getCandidateNominations($positionId);
      $positions[$positionId]['results']    = self::getResultsForPosition($positionId);
      $positions[$positionId]['ranks']      = self::getRanksForPosition($positions[$positionId]['results'], $positions[$positionId]['candidates'], $position['quantity']);
    }
  }

  private static function getRanksForPosition($results, &$candidates, $positionQty) {
    $ranks = array();
    foreach ($results as $result) {
      if (!array_key_exists($result['rank'], $ranks)) {
        $ranks[$result['rank']] = array();
      }

      if ($result['rank'] <= $positionQty) {
        $candidates[$result['election_nomination_id']]['is_winner'] = TRUE;
      }
      $ranks[$result['rank']][] = $result['election_nomination_id'];
    }
    uksort($ranks, function ($rankA, $rankB) {
      if ($rankA == $rankB) {
        return 0;
      }
      if ($rankA < $rankB) {
        return -1;
      }
      else {
        return 1;
      }
    });
    return $ranks;
  }

  private static function getResultsForPosition($positionId) {
    $results = civicrm_api3("ElectionResult", "get", array(
      'election_position_id' => $positionId,
    ));
    return $results['values'];
  }

  private static function getAllNominations($positionId) {
    $nominations = civicrm_api3('ElectionNomination', 'get', [
      'election_position_id' => $positionId,
      'options' => array('limit' => 0),
      'return' => array('comments', 'member_nominee', 'member_nominee.display_name', 'member_nominee.image_URL', 'election_position_id'),
    ]);

    $nominations = $nominations["values"];
    return $nominations;
  }

  private static function getCandidateNominations($positionId) {
    $nominations = civicrm_api3('ElectionNomination', 'get', [
      'is_eligible_candidate' => 1,
      'has_accepted_nomination' => 1,
      'election_position_id' => $positionId,
      'options' => array('limit' => 0),
      'return' => array('comments', 'member_nominee', 'member_nominee.display_name', 'member_nominee.image_URL', 'election_position_id'),
    ]);

    $nominations = $nominations["values"];
    return $nominations;
  }

}
