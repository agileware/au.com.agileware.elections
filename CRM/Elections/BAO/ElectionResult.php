<?php
use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_BAO_ElectionResult extends CRM_Elections_DAO_ElectionResult {
  public static function getResultsAndSummary($electionId, $completeSummary = FALSE) {
    $positions = civicrm_api3('ElectionPosition', 'get', [
      'election_id' => $electionId,
      'options' => ['limit' => 0, 'sort' => 'sortorder ASC'],
    ]);
    $positions = $positions['values'];
    self::setCandidatesAndResultsForEachPosition($positions, $completeSummary);
    self::updateCandidateProfilePictures($positions, TRUE, $completeSummary);
    return $positions;
  }

  public static function updateCandidateProfilePictures(&$positions, $hasCandidates = TRUE, $hasNominations = FALSE) {
    $candidateIds = [];
    foreach ($positions as $position) {
      if ($hasCandidates && isset($position['candidates'])) {
        $candidateIds = array_merge($candidateIds, array_column($position['candidates'], 'member_nominee'));
      }
      if ($hasNominations && isset($position['nominations'])) {
        $candidateIds = array_merge($candidateIds, array_column($position['nominations'], 'member_nominee'));
      }
    }

    $candidateIds = array_unique($candidateIds);
    $profilePictures = CRM_Elections_Helper_Utils::getCandidatePictures($candidateIds);

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

  /**
   * Group the results by rank, best first, and mark who won.
   *
   * Candidates on the same rank share it. A rank that fits in the seats still open wins; one that runs
   * across the last seat is a tie, and every candidate in it is marked `is_tied` (nobody is chosen over
   * the others).
   */
  private static function getRanksForPosition($results, &$candidates, $positionQty) {
    $ranks = [];
    foreach ($results as $result) {
      $ranks[$result['rank']][] = $result['election_nomination_id'];
    }
    ksort($ranks);
    $filled = 0;
    foreach ($ranks as $members) {
      if ($filled >= $positionQty) {
        break;
      }
      $tied = ($filled + count($members) > $positionQty);
      foreach ($members as $nominationId) {
        $candidates[$nominationId][$tied ? 'is_tied' : 'is_winner'] = TRUE;
      }
      $filled += count($members);
    }
    return $ranks;
  }

  private static function getResultsForPosition($positionId) {
    $results = civicrm_api3('ElectionResult', 'get', [
      'election_position_id' => $positionId,
	  'options' => ['limit' => 0],
    ]);
    return $results['values'];
  }

  private static function getAllNominations($positionId) {
    $nominations = civicrm_api3('ElectionNomination', 'get', [
      'election_position_id' => $positionId,
      'options' => ['limit' => 0],
      'return' => array_merge(['comments', 'statement_url', 'member_nominee', 'member_nominee.image_URL', 'election_position_id'], CRM_Elections_Helper_Candidate::NAME_RETURN_FIELDS),
    ]);

    $nominations = $nominations['values'];
    CRM_Elections_Helper_Candidate::applyNameFormatToAll($nominations, CRM_Elections_Helper_Candidate::getNameFormatForPosition($positionId));
    return $nominations;
  }

  private static function getCandidateNominations($positionId) {
    $nominations = civicrm_api3('ElectionNomination', 'get', [
      'is_eligible_candidate' => 1,
      'has_accepted_nomination' => 1,
      'election_position_id' => $positionId,
      'options' => ['limit' => 0],
      'return' => array_merge(['comments', 'statement_url', 'member_nominee', 'member_nominee.image_URL', 'election_position_id'], CRM_Elections_Helper_Candidate::NAME_RETURN_FIELDS),
    ]);

    $nominations = $nominations['values'];
    CRM_Elections_Helper_Candidate::applyNameFormatToAll($nominations, CRM_Elections_Helper_Candidate::getNameFormatForPosition($positionId));
    return $nominations;
  }

}
