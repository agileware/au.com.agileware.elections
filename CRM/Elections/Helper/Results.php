<?php
use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_Helper_Results {

  private function getPositions($electionId) {
    $positions = civicrm_api3("ElectionPosition", "get", array(
      'election_id' => $electionId,
      'options' => array('limit' => 0),
    ));
    $positions = $positions["values"];

    return $positions;
  }

  private function getCandidateNominations($positionId) {
    $nominations = civicrm_api3('ElectionNomination', 'get', [
      'is_eligible_candidate' => 1,
      'has_accepted_nomination' => 1,
      'election_position_id' => $positionId,
      'options' => array('limit' => 0),
    ]);

    $nominations = $nominations["values"];

    return $nominations;
  }

  private function generateResultsForPosition($position, $electionId) {
    $positionId = $position["id"];
    $nominations = $this->getCandidateNominations($positionId);

    $resultStatus = NULL;
    $candidateRanks = array();

    $seats = $position['quantity'];

    if (count($nominations) == 0) {
      $resultStatus = CRM_Elections_BAO_Election::$RESULTS_NO_NOMINATIONS;
    }
    else {
      $results = $this->generateResultsForNominations($nominations, $seats);

      $resultStatus = $results['status'];
      $candidateRanks = $results['candidateranks'];
    }

    $this->saveResultAndAssignRanks($resultStatus, $candidateRanks, $positionId, $electionId);
  }

  private function saveResultAndAssignRanks($status, $ranks, $positionId, $electionId) {
    foreach ($ranks as $nominationId => $rank) {
      civicrm_api3("ElectionResult", "create", array(
        'rank' => $rank,
        'election_position_id' => $positionId,
        'election_nomination_id' => $nominationId,
      ));
    }

    civicrm_api3("ElectionPosition", "create", array(
      'id'            => $positionId,
      'election_id'   => $electionId,
      'result_status' => $status,
    ));
  }

  private function getVotesByNominationIds($nominationIds) {
    $votes = civicrm_api3("ElectionVote", "get", array(
      'election_nomination_id' => array('IN' => $nominationIds),
      'options' => array('limit' => 0),
    ));
    $votes = $votes["values"];

    return $votes;
  }

  private function countVotes(&$nominations, &$candidateVotes, &$candidateRanks) {
    foreach ($nominations as $nominationId => $nomination) {
      $candidateRanks[$nominationId] = 0;
    }

    $nominationIds = array_column($nominations, "id");
    $votes = $this->getVotesByNominationIds($nominationIds);
    foreach ($votes as $vote) {
      $nominations[$vote["election_nomination_id"]]["votes"][$vote['rank']]++;
      $nominations[$vote["election_nomination_id"]]["preferences"][$vote['rank']][] = $vote['member_id'];
      if (!array_key_exists($vote['member_id'], $candidateVotes)) {
        $candidateVotes[$vote['member_id']] = array();
      }

      $candidateVotes[$vote['member_id']][$vote['rank']] = $vote["election_nomination_id"];
    }

    foreach ($candidateVotes as $index => $candidateVote) {
      ksort($candidateVotes[$index]);
    }
  }

  private function generateVotesPlaceholder(&$nominations) {
    $maxVoteRank = count($nominations);
    foreach ($nominations as $index => $nomination) {
      $nominations[$index]["votes"] = array();
      $nominations[$index]["preferences"] = array();
      for ($j = 1; $j <= $maxVoteRank; $j++) {
        $nominations[$index]["votes"][$j] = 0;
        $nominations[$index]["preferences"][$j] = array();
      }
    }
  }

  private function sortNominationsByVotes(&$nominations) {
    uasort($nominations, function($nominationA, $nominationB) {
      if ($nominationA['votes'][1] > $nominationB['votes'][1]) {
        return -1;
      }
      if ($nominationA['votes'][1] < $nominationB['votes'][1]) {
        return 1;
      }
      return 0;
    });
  }

  private function assignRankOneToAll($nominationIds, &$candidateRanks) {
    foreach ($nominationIds as $nominationId) {
      $candidateRanks[$nominationId] = 1;
    }
  }

  private function assignRanksToCandidates($nominations, &$candidateRanks) {
    $previousVotes = -1;
    $previousRank = 1;
    $rankToAssign = 0;

    foreach ($nominations as $nominationId => $nomination) {
      $votes = $nomination['votes'][1];

      if ($votes == $previousVotes) {
        $rankToAssign = $previousRank;
      }
      else {
        $rankToAssign++;

        $previousRank = $rankToAssign;
        $previousVotes = $votes;
      }

      $candidateRanks[$nominationId] = $rankToAssign;
    }
  }

  private function assignRanksIfClearMajority(&$nominations, &$candidateVotes, &$candidateRanks, $removedNominations = array(), $seats = 1) {

    if ($seats >= count($nominations)) {
      $this->sortNominationsByVotes($nominations);
      $this->assignRanksToCandidates($nominations, $candidateRanks);

      return $this->returnElectionPositionResult(($seats > count($nominations)) ? CRM_Elections_BAO_Election::$RESULTS_MORE_SEATS : CRM_Elections_BAO_Election::$RESULTS_EQUAL_SEATS);
    }

    $nominationIds = implode(",", array_column($nominations, "id"));
    $totalVotes = "SELECT COUNT(DISTINCT(member_id)) FROM civicrm_election_vote WHERE election_nomination_id IN ($nominationIds)";
    $totalVotes = CRM_Core_DAO::singleValueQuery($totalVotes);

    $majorityVotes = floor($totalVotes / 2);
    $this->sortNominationsByVotes($nominations);

    foreach ($nominations as $nomination) {
      if ($nomination['votes'][1] > $majorityVotes) {
        // We have clear majority => Nomination $nomination['id'] wins.;
        // Assign the ranks and return.

        $this->assignRanksToCandidates($nominations, $candidateRanks);
        return $this->returnElectionPositionResult(CRM_Elections_BAO_Election::$RESULTS_MAJORITY);
      }
      break;
    }

    if (count($nominations) == 2 && $seats == 1) {
      // If single seat position, and we're left with two candidates having similar votes,
      // Declare a tie.
      $this->assignRankOneToAll(array_keys($nominations), $candidateRanks);
      return $this->returnElectionPositionResult(CRM_Elections_BAO_Election::$RESULTS_NO_MAJORITY);
    }

    // We don't have clear majority in more then 2 candidates. Execute IRV.
    return $this->executeIrv($nominations, $candidateVotes, $candidateRanks, $seats);
  }

  private function returnElectionPositionResult($resultCode) {
    return array(
      'status' => $resultCode,
    );
  }

  private function executeIrv(&$nominations, &$candidateVotes, &$candidateRanks, $seats) {
    $firstRankVotes = array_column(array_column($nominations, "votes"), 1);
    $leastVotes = min($firstRankVotes);
    $removedNominations = array();

    $shiftVotesOfCandidates = array();
    foreach ($nominations as $nominationId => $nomination) {
      if ($nomination["votes"][1] == $leastVotes) {
        if (isset($nomination["preferences"][1])) {
          $shiftVotesOfCandidates = array_merge($nomination["preferences"][1], $shiftVotesOfCandidates);
        }
        unset($nominations[$nominationId]);
        $removedNominations[] = $nominationId;

        break; // Eliminate single candidate instead of multiple candidates to prevent several cases.
      }
    }

    foreach ($removedNominations as $removedNomination) {
      $candidateRanks[$removedNomination] = count($nominations) + 1;
    }

    $shiftVotesOfCandidates = array_unique($shiftVotesOfCandidates);

    // Remove votes.
    foreach ($shiftVotesOfCandidates as $shiftVotesOfCandidate) {
      foreach ($candidateVotes[$shiftVotesOfCandidate] as $rank => $nominationId) {
        $candidateVotes[$shiftVotesOfCandidate][$rank - 1] = $nominationId;
      }
      array_pop($candidateVotes[$shiftVotesOfCandidate]);
      array_pop($candidateVotes[$shiftVotesOfCandidate]);
    }

    //Reassign votes

    foreach ($shiftVotesOfCandidates as $shiftVotesOfCandidate) {
      foreach ($candidateVotes[$shiftVotesOfCandidate] as $rank => $nominationId) {
        if (isset($nominations[$nominationId])) {
          $nominations[$nominationId]['votes'][$rank]++;
          $nominations[$nominationId]['preferences'][$rank][] = $shiftVotesOfCandidate;
        }
      }
    }

    return $this->assignRanksIfClearMajority($nominations, $candidateVotes, $candidateRanks, $removedNominations, $seats);
  }

  private function generateResultsForNominations($nominations, $seats) {
    $candidateVotes = array();
    $candidateRanks = array();
    $removedNominations = array();

    $this->generateVotesPlaceholder($nominations);
    $this->countVotes($nominations, $candidateVotes, $candidateRanks);
    $positionResults = $this->assignRanksIfClearMajority($nominations, $candidateVotes, $candidateRanks, $removedNominations, $seats);

    $positionResults += array(
      'candidateranks' => $candidateRanks,
    );

    return $positionResults;
  }

  public function generateResult($electionId, $anonymizeVotes) {
    $positions = $this->getPositions($electionId);
    foreach ($positions as $position) {
      $this->generateResultsForPosition($position, $electionId);
      if ($anonymizeVotes == 1) {
        $this->removeVotes($electionId);
      }
    }

    civicrm_api3('Election', 'create', array(
      'id' => $electionId,
      'has_results_generated' => 1,
    ));
  }

  public function removeVotes($electionId) {
    $votes = civicrm_api3('ElectionVote', 'get', array(
      'election_nomination_id.election_position_id.election_id.id' => $electionId,
    ));
    $votes = $votes['values'];
    foreach ($votes as $vote) {
      $query = "UPDATE civicrm_election_vote SET member_id = NULL WHERE id = " . $vote['id'];
      CRM_Core_DAO::executeQuery($query);
    }
  }

}
