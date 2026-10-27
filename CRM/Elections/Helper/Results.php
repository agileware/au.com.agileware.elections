<?php
use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_Helper_Results {

  /**
   * TRUE only while the results of an election are being generated: results cannot be written at any
   * other time, see CRM_Elections_Helper_Guard.
   *
   * @var bool
   */
  public static $writing = FALSE;

  private function getPositions($electionId) {
    $positions = civicrm_api3('ElectionPosition', 'get', [
      'election_id' => $electionId,
      'options' => ['limit' => 0],
    ]);
    $positions = $positions['values'];

    return $positions;
  }

  private function getCandidateNominations($positionId) {
    $nominations = civicrm_api3('ElectionNomination', 'get', [
      'is_eligible_candidate' => 1,
      'has_accepted_nomination' => 1,
      'election_position_id' => $positionId,
      'options' => ['limit' => 0],
    ]);

    $nominations = $nominations['values'];

    return $nominations;
  }

  /**
   * The people allowed to vote when the results are generated, for the quorum of a referendum question.
   *
   * @var int
   */
  private $eligibleVoters = 0;

  private function generateResultsForPosition($position, $electionId) {
    $positionId = $position['id'];
    if (($position['position_type'] ?? '') === CRM_Elections_BAO_ElectionPosition::TYPE_REFERENDUM) {
      // A question has no candidates and no ranks: the result is whether it passed.
      $this->saveResultAndAssignRanks(CRM_Elections_Helper_Referendum::count($positionId, $this->eligibleVoters), [], $positionId, $electionId);
      return;
    }
    $nominations = $this->getCandidateNominations($positionId);

    $resultStatus = NULL;
    $candidateRanks = [];

    $seats = $position['quantity'];

    if (empty($nominations)) {
      $resultStatus = CRM_Elections_BAO_Election::$RESULTS_NO_NOMINATIONS;
    }
    else {
      $results = (CRM_Elections_VotingMethod::forElection($electionId) === CRM_Elections_VotingMethod::PLURALITY)
        ? $this->generateResultsByPlurality($nominations, $seats)
        : $this->generateResultsForNominations($nominations, $seats);

      $resultStatus = $results['status'];
      $candidateRanks = $results['candidateranks'];
    }

    $this->saveResultAndAssignRanks($resultStatus, $candidateRanks, $positionId, $electionId);
  }

  private function saveResultAndAssignRanks($status, $ranks, $positionId, $electionId) {
    foreach ($ranks as $nominationId => $rank) {
      civicrm_api3('ElectionResult', 'create', [
        'rank' => $rank,
        'election_position_id' => $positionId,
        'election_nomination_id' => $nominationId,
      ]);
    }

    civicrm_api3('ElectionPosition', 'create', [
      'id'            => $positionId,
      'election_id'   => $electionId,
      'result_status' => $status,
    ]);
  }

  private function getVotesByNominationIds($nominationIds) {
    $votes = civicrm_api3('ElectionVote', 'get', [
      'election_nomination_id' => ['IN' => $nominationIds],
      'options' => ['limit' => 0],
    ]);
    $votes = $votes['values'];

    return $votes;
  }

  private function countVotes(&$nominations, &$candidateVotes, &$candidateRanks) {
    foreach ($nominations as $nominationId => $nomination) {
      $candidateRanks[$nominationId] = 0;
    }

    $nominationIds = array_column($nominations, 'id');
    $votes = $this->compactRanks($this->getVotesByNominationIds($nominationIds));
    foreach ($votes as $vote) {
      $nominations[$vote['election_nomination_id']]['votes'][$vote['rank']]++;
      $nominations[$vote['election_nomination_id']]['preferences'][$vote['rank']][] = $vote['member_id'];
      if (!array_key_exists($vote['member_id'], $candidateVotes)) {
        $candidateVotes[$vote['member_id']] = [];
      }

      $candidateVotes[$vote['member_id']][$vote['rank']] = $vote['election_nomination_id'];
    }

    foreach ($candidateVotes as $index => $candidateVote) {
      ksort($candidateVotes[$index]);
    }
  }

  /**
   * Number each voter's choices 1, 2, 3 in the order they gave them, over the candidates who are on the count.
   *
   * A voter may have ranked someone who is not a candidate at the end (a person written in who did not accept,
   * or one who withdrew). Their ballot moves on to the next choice: the gap closes, so a first choice that is
   * not a candidate makes the second choice the first.
   *
   * @param array $votes
   *   Ballot rows, each with member_id, election_nomination_id and rank.
   *
   * @return array
   */
  private function compactRanks(array $votes) {
    $byMember = [];
    foreach ($votes as $key => $vote) {
      $byMember[$vote['member_id']][$key] = (int) $vote['rank'];
    }
    foreach ($byMember as $member => $rows) {
      asort($rows);
      $next = 1;
      foreach (array_keys($rows) as $key) {
        $votes[$key]['rank'] = $next++;
      }
    }
    return $votes;
  }

  private function generateVotesPlaceholder(&$nominations) {
    $maxVoteRank = count($nominations);
    foreach ($nominations as $index => $nomination) {
      $nominations[$index]['votes'] = [];
      $nominations[$index]['preferences'] = [];
      for ($j = 1; $j <= $maxVoteRank; $j++) {
        $nominations[$index]['votes'][$j] = 0;
        $nominations[$index]['preferences'][$j] = [];
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

  private function assignRanksIfClearMajority(&$nominations, &$candidateVotes, &$candidateRanks, $removedNominations = [], $seats = 1) {

    if ($seats >= count($nominations)) {
      $this->sortNominationsByVotes($nominations);
      $this->assignRanksToCandidates($nominations, $candidateRanks);

      return $this->returnElectionPositionResult(($seats > count($nominations)) ? CRM_Elections_BAO_Election::$RESULTS_MORE_SEATS : CRM_Elections_BAO_Election::$RESULTS_EQUAL_SEATS);
    }

    $nominationIds = implode(',', array_map('intval', array_column($nominations, 'id')));
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
    return [
      'status' => $resultCode,
    ];
  }

  private function executeIrv(&$nominations, &$candidateVotes, &$candidateRanks, $seats) {
    $firstRankVotes = array_column(array_column($nominations, 'votes'), 1);
    $leastVotes = min($firstRankVotes);
    $removedNominations = [];

    $shiftVotesOfCandidates = [];
    foreach ($nominations as $nominationId => $nomination) {
      if ($nomination['votes'][1] == $leastVotes) {
        if (isset($nomination['preferences'][1])) {
          $shiftVotesOfCandidates = array_merge($nomination['preferences'][1], $shiftVotesOfCandidates);
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

  /**
   * Count a position where every ballot row is a tick for a candidate: the most votes win.
   */
  private function generateResultsByPlurality($nominations, $seats) {
    $votes = array_fill_keys(array_column($nominations, 'id'), 0);
    foreach ($this->getVotesByNominationIds(array_keys($votes)) as $vote) {
      $votes[$vote['election_nomination_id']]++;
    }
    return CRM_Elections_VotingMethod::countPlurality($votes, $seats);
  }

  private function generateResultsForNominations($nominations, $seats) {
    $candidateVotes = [];
    $candidateRanks = [];
    $removedNominations = [];

    $this->generateVotesPlaceholder($nominations);
    $this->countVotes($nominations, $candidateVotes, $candidateRanks);
    $positionResults = $this->assignRanksIfClearMajority($nominations, $candidateVotes, $candidateRanks, $removedNominations, $seats);

    $positionResults += [
      'candidateranks' => $candidateRanks,
    ];

    return $positionResults;
  }

  public function generateResult($electionId, $anonymizeVotes) {
    $election = findElectionById($electionId);
    if ($election->has_results_generated) {
      // The results of an election are generated once and then stand.
      return;
    }
    if ($election->is_deleted || !$election->isVotingEnded) {
      throw new CRM_Core_Exception(E::ts('Results can only be generated once voting has ended.'));
    }

    $lock = Civi::lockManager()->acquire('data.elections.results.' . (int) $electionId, 30);
    if (!$lock->isAcquired()) {
      throw new CRM_Core_Exception(E::ts('The results of this election are already being generated.'));
    }
    try {
      // Another request may have finished while this one waited for the lock.
      if (CRM_Core_DAO::getFieldValue('CRM_Elections_DAO_Election', $electionId, 'has_results_generated')) {
        return;
      }
      self::$writing = TRUE;
      $transaction = new CRM_Core_Transaction();
      try {
        // Start from nothing, so a run that stopped half way cannot leave results from two runs.
        CRM_Core_DAO::executeQuery('DELETE r FROM civicrm_election_result r INNER JOIN civicrm_election_position p ON p.id = r.election_position_id WHERE p.election_id = %1', [1 => [(int) $electionId, 'Integer']]);

        $this->eligibleVoters = CRM_Elections_Helper_Eligibility::countContactsInGroups($election->allowed_groups);
        $positions = $this->getPositions($electionId);
        foreach ($positions as $position) {
          $this->generateResultsForPosition($position, $electionId);
        }
        $ballots = (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(DISTINCT member_id) FROM (
          SELECT v.member_id FROM civicrm_election_vote v INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id INNER JOIN civicrm_election_position p ON p.id = n.election_position_id WHERE p.election_id = %1
          UNION
          SELECT ov.member_id FROM civicrm_election_option_vote ov INNER JOIN civicrm_election_position p ON p.id = ov.election_position_id WHERE p.election_id = %1
        ) t', [1 => [(int) $electionId, 'Integer']]);

        civicrm_api3('Election', 'create', [
          'id' => $electionId,
          'has_results_generated' => 1,
        ]);
        CRM_Elections_Helper_WriteIn::logSummary($electionId);
        // The fingerprints of the ballots and the results, kept in the audit log, show any later change.
        CRM_Elections_Helper_Integrity::recordGeneration($electionId, $ballots);

        // Anonymise only once every position has been counted, as counting needs the voter ids.
        if ($anonymizeVotes == 1) {
          $this->removeVotes($electionId);
          CRM_Elections_Helper_Audit::log($electionId, 'votes_anonymised', []);
        }
        $transaction->commit();
      }
      catch (Exception $e) {
        $transaction->rollback();
        throw $e;
      }
      finally {
        self::$writing = FALSE;
      }
    }
    finally {
      $lock->release();
    }
  }

  /**
   * Anonymise the ballots of an election.
   *
   * The link to the voter is removed, and so is everything else that could be used to find it again:
   * the time each ballot was cast is replaced by the time voting closed, and the ballots are written
   * back in a random order, so neither the time nor the position in the table tells whose ballot is whose.
   *
   * @param int $electionId
   */
  public function removeVotes($electionId) {
    $params = [1 => [(int) $electionId, 'Integer']];
    $closed = CRM_Core_DAO::singleValueQuery('SELECT voting_end_date FROM civicrm_election WHERE id = %1', $params);
    $dao = CRM_Core_DAO::executeQuery('
      SELECT v.id, v.election_nomination_id, v.`rank`, v.member_id
      FROM civicrm_election_vote v
      INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id
      INNER JOIN civicrm_election_position p ON p.id = n.election_position_id
      WHERE p.election_id = %1
    ', $params);
    $ballots = [];
    $ids = [];
    while ($dao->fetch()) {
      $ids[] = (int) $dao->id;
      // Rows of one voter stay together; rows with no voter already are each their own.
      $ballots[$dao->member_id === NULL ? 'row' . $dao->id : 'member' . $dao->member_id][] = [(int) $dao->election_nomination_id, (int) $dao->rank];
    }
    if ($ids) {
      $order = array_keys($ballots);
      for ($i = count($order) - 1; $i > 0; $i--) {
        $j = random_int(0, $i);
        [$order[$i], $order[$j]] = [$order[$j], $order[$i]];
      }
      CRM_Core_DAO::executeQuery('DELETE FROM civicrm_election_vote WHERE id IN (' . implode(',', $ids) . ')');
      foreach ($order as $key) {
        $rows = $ballots[$key];
        shuffle($rows);
        foreach ($rows as [$nominationId, $rank]) {
          CRM_Core_DAO::executeQuery('INSERT INTO civicrm_election_vote (election_nomination_id, `rank`, member_id, created_at) VALUES (%1, %2, NULL, %3)', [
            1 => [$nominationId, 'Integer'],
            2 => [$rank, 'Integer'],
            3 => [$closed, 'String'],
          ]);
        }
      }
    }

    // The choices on referendum questions the same way: no voter, the time voting closed, a random order.
    $choices = CRM_Core_DAO::executeQuery('
      SELECT ov.id, ov.election_option_id, ov.election_position_id
      FROM civicrm_election_option_vote ov
      INNER JOIN civicrm_election_position p ON p.id = ov.election_position_id
      WHERE p.election_id = %1
    ', $params);
    $rows = [];
    $choiceIds = [];
    while ($choices->fetch()) {
      $choiceIds[] = (int) $choices->id;
      $rows[] = [(int) $choices->election_option_id, (int) $choices->election_position_id];
    }
    if ($choiceIds) {
      shuffle($rows);
      CRM_Core_DAO::executeQuery('DELETE FROM civicrm_election_option_vote WHERE id IN (' . implode(',', $choiceIds) . ')');
      foreach ($rows as [$optionId, $positionId]) {
        CRM_Core_DAO::executeQuery('INSERT INTO civicrm_election_option_vote (election_option_id, election_position_id, member_id, created_at) VALUES (%1, %2, NULL, %3)', [
          1 => [$optionId, 'Integer'],
          2 => [$positionId, 'Integer'],
          3 => [$closed, 'String'],
        ]);
      }
    }
  }

}
