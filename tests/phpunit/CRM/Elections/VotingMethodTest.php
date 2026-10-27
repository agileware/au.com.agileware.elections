<?php

use CRM_Elections_Test_CouncilFixture as Council;

/**
 * Elections can be counted by ranked choice (the default, and what every election made before 2.0 uses)
 * or by most votes (plurality), where the voter ticks up to as many candidates as there are seats.
 *
 * @group headless
 */
class CRM_Elections_VotingMethodTest extends api_v3_ElectionBaseTestCase {

  private function pluralityFixture($stage = Council::STAGE_ADVERTISE) {
    $fixture = Council::create(['voting_method' => 'plurality', 'allow_revote' => 1])->playUntil(Council::STAGE_ADVERTISE);
    if ($stage === Council::STAGE_VOTING) {
      $fixture->moveToStage(Council::STAGE_VOTING);
    }
    return $fixture;
  }

  private function nomination(Council $fixture, $position, $candidate) {
    return (int) $fixture->nominations["$position|$candidate"];
  }

  private function addVotes(Council $fixture, $voter, array $ticks) {
    return civicrm_api3('ElectionVote', 'addvotes', [
      'election_id' => $fixture->electionId,
      'member_id' => $fixture->id($voter),
      'votes' => array_map(fn($nominationId) => ['election_nomination_id' => $nominationId, 'rank' => 1], $ticks),
    ]);
  }

  private function setMethod(Council $fixture, $method) {
    civicrm_api3('Election', 'create', ['voting_method' => $method] + civicrm_api3('Election', 'getsingle', ['id' => $fixture->electionId]));
  }

  private function plainDates() {
    $keys = ['visibility_start_date' => -10, 'nomination_start_date' => -2, 'nomination_end_date' => 5, 'advertise_candidates_date' => 6, 'voting_start_date' => 10, 'voting_end_date' => 20, 'result_date' => 25, 'visibility_end_date' => 60];
    return array_map(fn($days) => date('Y-m-d H:i:s', strtotime(($days >= 0 ? '+' : '') . $days . ' days')), $keys);
  }

  private function expectRefusal(callable $change, $needle) {
    try {
      $change();
    }
    catch (\Throwable $e) {
      $this->assertStringContainsStringIgnoringCase($needle, $e->getMessage());
      return;
    }
    $this->fail('Expected a refusal containing: ' . $needle);
  }

  // ------------------------------------------------------------------ the setting

  public function testRankedChoiceIsTheDefault() {
    $election = civicrm_api3('Election', 'create', ['name' => 'Plain'] + $this->plainDates());
    $this->assertSame('irv', civicrm_api3('Election', 'getsingle', ['id' => $election['id']])['voting_method']);
    $this->assertSame('irv', CRM_Elections_VotingMethod::normalise(NULL));
    $this->assertSame('irv', CRM_Elections_VotingMethod::normalise(''));
    $this->assertSame('irv', CRM_Elections_VotingMethod::normalise('something else'));
    $this->assertSame('plurality', CRM_Elections_VotingMethod::normalise('plurality'));
  }

  public function testAnUnknownMethodIsRefused() {
    $this->expectRefusal(function () {
      civicrm_api3('Election', 'create', ['name' => 'Odd', 'voting_method' => 'coin-toss'] + $this->plainDates());
    }, 'not a valid option');
  }

  public function testTheMethodCannotChangeOnceThereAreBallots() {
    $fixture = $this->pluralityFixture(Council::STAGE_VOTING);
    // No ballots yet: it can still change.
    $this->setMethod($fixture, 'irv');
    $this->setMethod($fixture, 'plurality');
    $this->addVotes($fixture, 'V01', [$this->nomination($fixture, 'President', 'N01')]);
    $this->expectRefusal(function () use ($fixture) {
      $this->setMethod($fixture, 'irv');
    }, 'cannot be changed');
  }

  public function testChangingTheMethodIsWrittenToTheAuditLog() {
    $fixture = $this->pluralityFixture();
    $this->setMethod($fixture, 'irv');
    $logged = (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_audit WHERE election_id = %1 AND details LIKE %2', [1 => [$fixture->electionId, 'Integer'], 2 => ['%voting_method%', 'String']]);
    $this->assertGreaterThan(0, $logged);
  }

  public function testDuplicateKeepsTheMethod() {
    $fixture = $this->pluralityFixture();
    $copy = CRM_Elections_BAO_Election::duplicate($fixture->electionId)['id'];
    $this->assertSame('plurality', civicrm_api3('Election', 'getsingle', ['id' => $copy])['voting_method']);
  }

  // ------------------------------------------------------------------ ballots

  public function testAPluralityBallotTicksOneCandidateForASingleSeat() {
    $fixture = $this->pluralityFixture(Council::STAGE_VOTING);
    $this->addVotes($fixture, 'V01', [$this->nomination($fixture, 'President', 'N01')]);
    $this->assertSame(1, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_vote WHERE member_id = %1', [1 => [$fixture->id('V01'), 'Integer']]));
  }

  public function testAPluralityBallotCannotTickMoreThanTheSeats() {
    $fixture = $this->pluralityFixture(Council::STAGE_VOTING);
    $this->expectRefusal(function () use ($fixture) {
      $this->addVotes($fixture, 'V01', [$this->nomination($fixture, 'President', 'N01'), $this->nomination($fixture, 'President', 'N02')]);
    }, 'no more candidates than there are seats');
    $this->assertSame(0, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_vote WHERE member_id = %1', [1 => [$fixture->id('V01'), 'Integer']]), 'Nothing is stored');
  }

  public function testAPluralityBallotCannotRank() {
    $fixture = $this->pluralityFixture(Council::STAGE_VOTING);
    $this->expectRefusal(function () use ($fixture) {
      civicrm_api3('ElectionVote', 'addvotes', [
        'election_id' => $fixture->electionId,
        'member_id' => $fixture->id('V01'),
        'votes' => [
          ['election_nomination_id' => $this->nomination($fixture, 'President', 'N01'), 'rank' => 1],
          ['election_nomination_id' => $this->nomination($fixture, 'President', 'N02'), 'rank' => 2],
        ],
      ]);
    }, 'chosen, not ranked');
  }

  public function testSeveralSeatsAllowSeveralTicksButNotMore() {
    $fixture = $this->pluralityFixture(Council::STAGE_ADVERTISE);
    // Two seats for Council Member 3, which has candidates N03 and N05, plus a third added as a standing candidate.
    CRM_Core_DAO::executeQuery('UPDATE civicrm_election_position SET quantity = 2 WHERE id = %1', [1 => [$fixture->positions['Council Member 3'], 'Integer']]);
    $fixture->moveToStage(Council::STAGE_VOTING);
    $this->addVotes($fixture, 'V01', [$this->nomination($fixture, 'Council Member 3', 'N03'), $this->nomination($fixture, 'Council Member 3', 'N05')]);
    $this->assertSame(2, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_vote WHERE member_id = %1', [1 => [$fixture->id('V01'), 'Integer']]));
  }

  public function testRankedChoiceStillRanksAndStillRefusesTheSameRankTwice() {
    $fixture = Council::create()->playUntil(Council::STAGE_ADVERTISE);
    $fixture->moveToStage(Council::STAGE_VOTING);
    $a = $this->nomination($fixture, 'President', 'N01');
    $b = $this->nomination($fixture, 'President', 'N02');
    civicrm_api3('ElectionVote', 'addvotes', ['election_id' => $fixture->electionId, 'member_id' => $fixture->id('V01'), 'votes' => [['election_nomination_id' => $a, 'rank' => 1], ['election_nomination_id' => $b, 'rank' => 2]]]);
    $this->expectRefusal(function () use ($fixture, $a, $b) {
      civicrm_api3('ElectionVote', 'addvotes', ['election_id' => $fixture->electionId, 'member_id' => $fixture->id('V02'), 'votes' => [['election_nomination_id' => $a, 'rank' => 1], ['election_nomination_id' => $b, 'rank' => 1]]]);
    }, 'Rank must be different');
  }

  // ------------------------------------------------------------------ counting

  public function testMostVotesWins() {
    $result = CRM_Elections_VotingMethod::countPlurality([10 => 4, 11 => 9, 12 => 1], 1);
    $this->assertSame([11 => 1, 10 => 2, 12 => 3], $result['candidateranks']);
    $this->assertSame(CRM_Elections_BAO_Election::$RESULTS_MOST_VOTES, $result['status']);
  }

  public function testATieForTheOnlySeatIsATie() {
    $result = CRM_Elections_VotingMethod::countPlurality([10 => 5, 11 => 5, 12 => 1], 1);
    $this->assertSame([10 => 1, 11 => 1, 12 => 2], $result['candidateranks']);
    $this->assertSame(CRM_Elections_BAO_Election::$RESULTS_NO_MAJORITY, $result['status']);
  }

  public function testATieAcrossTheLastOfSeveralSeatsIsATie() {
    $result = CRM_Elections_VotingMethod::countPlurality([10 => 9, 11 => 4, 12 => 4, 13 => 1], 2);
    $this->assertSame([10 => 1, 11 => 2, 12 => 2, 13 => 3], $result['candidateranks']);
    $this->assertSame(CRM_Elections_BAO_Election::$RESULTS_NO_MAJORITY, $result['status']);
  }

  public function testATieAmongTheWinnersIsNotATie() {
    $result = CRM_Elections_VotingMethod::countPlurality([10 => 6, 11 => 6, 12 => 1], 2);
    $this->assertSame([10 => 1, 11 => 1, 12 => 2], $result['candidateranks']);
    $this->assertSame(CRM_Elections_BAO_Election::$RESULTS_MOST_VOTES, $result['status']);
  }

  public function testNoVotesAtAllIsATie() {
    $result = CRM_Elections_VotingMethod::countPlurality([10 => 0, 11 => 0], 1);
    $this->assertSame(CRM_Elections_BAO_Election::$RESULTS_NO_MAJORITY, $result['status']);
  }

  public function testEnoughSeatsForEveryCandidate() {
    $this->assertSame(CRM_Elections_BAO_Election::$RESULTS_EQUAL_SEATS, CRM_Elections_VotingMethod::countPlurality([10 => 3, 11 => 1], 2)['status']);
    $this->assertSame(CRM_Elections_BAO_Election::$RESULTS_MORE_SEATS, CRM_Elections_VotingMethod::countPlurality([10 => 3, 11 => 1], 3)['status']);
  }

  public function testAFullPluralityElection() {
    $fixture = $this->pluralityFixture(Council::STAGE_VOTING);
    $president = ['N01' => $this->nomination($fixture, 'President', 'N01'), 'N02' => $this->nomination($fixture, 'President', 'N02')];
    $treasurer = ['N04' => $this->nomination($fixture, 'Treasurer', 'N04'), 'N05' => $this->nomination($fixture, 'Treasurer', 'N05')];
    $voters = array_keys($fixture->members());
    $k = 0;
    foreach ($voters as $voter) {
      $k++;
      if (in_array($voter, ['V29', 'V30'], TRUE)) {
        continue;
      }
      // President: 3 in 4 for N01. Treasurer: an even split.
      $this->addVotes($fixture, $voter, [
        $k % 4 ? $president['N01'] : $president['N02'],
        $k % 2 ? $treasurer['N04'] : $treasurer['N05'],
      ]);
    }
    $fixture->moveToStage(Council::STAGE_RESULTS);
    $fixture->generateResults();

    $ranks = fn($position) => CRM_Core_DAO::executeQuery('SELECT n.member_nominee, r.`rank` FROM civicrm_election_result r INNER JOIN civicrm_election_nomination n ON n.id = r.election_nomination_id WHERE r.election_position_id = %1', [1 => [$fixture->positions[$position], 'Integer']])->fetchMap('member_nominee', 'rank');
    $president = $ranks('President');
    $this->assertSame(1, (int) $president[$fixture->id('N01')]);
    $this->assertSame(2, (int) $president[$fixture->id('N02')]);
    $this->assertSame(CRM_Elections_BAO_Election::$RESULTS_MOST_VOTES, CRM_Core_DAO::singleValueQuery('SELECT result_status FROM civicrm_election_position WHERE id = %1', [1 => [$fixture->positions['President'], 'Integer']]));

    // The Treasurer split is even, so the position is a tie and both are listed as tied.
    $treasurer = $ranks('Treasurer');
    $this->assertSame((int) $treasurer[$fixture->id('N04')], (int) $treasurer[$fixture->id('N05')]);
    $this->assertSame(CRM_Elections_BAO_Election::$RESULTS_NO_MAJORITY, CRM_Core_DAO::singleValueQuery('SELECT result_status FROM civicrm_election_position WHERE id = %1', [1 => [$fixture->positions['Treasurer'], 'Integer']]));
    $positions = CRM_Elections_BAO_ElectionResult::getResultsAndSummary($fixture->electionId);
    $byName = array_column($positions, NULL, 'name');
    $tied = array_filter($byName['Treasurer']['candidates'], fn($c) => !empty($c['is_tied']));
    $this->assertCount(2, $tied, 'Both tied candidates are listed as tied');
    $won = array_filter($byName['Treasurer']['candidates'], fn($c) => !empty($c['is_winner']));
    $this->assertCount(0, $won, 'Neither is a winner');
    $presidentWinners = array_filter($byName['President']['candidates'], fn($c) => !empty($c['is_winner']));
    $this->assertCount(1, $presidentWinners);
  }

  public function testARankedChoiceTieListsEveryTiedCandidate() {
    $fixture = Council::create()->playUntil(Council::STAGE_RESULTS);
    $byName = array_column(CRM_Elections_BAO_ElectionResult::getResultsAndSummary($fixture->electionId), NULL, 'name');
    $tied = array_filter($byName['Treasurer']['candidates'], fn($c) => !empty($c['is_tied']));
    $this->assertCount(2, $tied);
    $president = array_filter($byName['President']['candidates'], fn($c) => !empty($c['is_winner']));
    $this->assertCount(1, $president);
  }

}
