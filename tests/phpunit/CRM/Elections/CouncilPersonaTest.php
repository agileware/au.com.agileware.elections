<?php

use CRM_Elections_Test_CouncilFixture as Council;

/**
 * Runs a council election end to end as personas: 30 voters, 10 nominees and
 * 6 candidates for President, Secretary, Treasurer and three Council Members.
 * Each stage is tested by people acting as themselves, never as an administrator.
 *
 * @group headless
 */
class CRM_Elections_CouncilPersonaTest extends api_v3_ElectionBaseTestCase {

  /**
   * @param callable $action
   * @param string|null $messageContains
   */
  private function assertRejected(callable $action, $messageContains = NULL) {
    try {
      $action();
    }
    catch (CRM_Core_Exception $e) {
      if ($messageContains !== NULL) {
        $this->assertStringContainsString($messageContains, $e->getMessage());
      }
      $this->assertTrue(TRUE);
      return;
    }
    $this->fail('The action should have been rejected.');
  }

  private function nominationField($fixture, $key, $field) {
    return civicrm_api3('ElectionNomination', 'getvalue', ['id' => $fixture->nominations[$key], 'return' => $field]);
  }

  private function countVotes($fixture, $persona = NULL) {
    $sql = 'SELECT COUNT(*) FROM civicrm_election_vote v
      INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id
      INNER JOIN civicrm_election_position p ON p.id = n.election_position_id
      WHERE p.election_id = %1';
    $params = [1 => [$fixture->electionId, 'Integer']];
    if ($persona) {
      $sql .= ' AND v.member_id = %2';
      $params[2] = [$fixture->id($persona), 'Integer'];
    }
    return (int) CRM_Core_DAO::singleValueQuery($sql, $params);
  }

  // ---------------------------------------------------------------- personas

  public function testThereAreEnoughPersonasForACouncilElection() {
    $fixture = Council::create();
    $voters = array_filter(array_keys($fixture->personas), fn($p) => $p[0] === 'V');
    $nominees = array_filter(array_keys($fixture->personas), fn($p) => $p[0] === 'N');
    $this->assertGreaterThanOrEqual(30, count($voters));
    $this->assertGreaterThanOrEqual(10, count($nominees));
    $this->assertSame(['President', 'Secretary', 'Treasurer', 'Council Member 1', 'Council Member 2', 'Council Member 3'], array_keys($fixture->positions));
    $this->assertCount(6, array_unique(array_merge(...array_values(Council::CANDIDATES))));
  }

  // ------------------------------------------------------------ nominations

  public function testNominationsBecomeEligibleOnlyWithEnoughNominators() {
    $fixture = Council::create()->playUntil(Council::STAGE_NOMINATIONS);
    $fixture->runNominations();
    foreach (Council::PLAN as [$position, $nominee, $nominators]) {
      $expected = count($nominators) >= 2 ? 1 : 0;
      $this->assertEquals($expected, $this->nominationField($fixture, "$position|$nominee", 'is_eligible_candidate'), "$nominee for $position");
      $count = civicrm_api3('ElectionNominationSeconder', 'getcount', ['election_nomination_id' => $fixture->nominations["$position|$nominee"]]);
      $this->assertSame(count($nominators), $count);
    }
  }

  public function testSecondNominationMakesAnUnsecondedNomineeEligible() {
    $fixture = Council::create();
    $fixture->runNominations();
    $key = 'President|N08';
    $this->assertEquals(0, $this->nominationField($fixture, $key, 'is_eligible_candidate'));

    $result = CRM_Elections_Helper_Process::nominate($fixture->electionId, $fixture->positions['President'], $fixture->id('N08'), $fixture->id('V12'));
    $this->assertTrue($result['is_eligible_candidate']);
    $this->assertEquals(1, $this->nominationField($fixture, $key, 'is_eligible_candidate'));
  }

  public function testANominatorCannotCountTwice() {
    $fixture = Council::create();
    $fixture->runNominations();
    $again = CRM_Elections_Helper_Process::nominate($fixture->electionId, $fixture->positions['President'], $fixture->id('N08'), $fixture->id('V01'));
    $this->assertTrue($again['already_nominated']);
    $this->assertFalse($again['is_eligible_candidate']);
    $this->assertSame(1, civicrm_api3('ElectionNominationSeconder', 'getcount', ['election_nomination_id' => $fixture->nominations['President|N08']]));
  }

  public function testSomeoneWhoIsNotAMemberCannotTakePartInNominations() {
    $fixture = Council::create();
    $position = $fixture->positions['President'];
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::nominate($fixture->electionId, $position, $fixture->id('N01'), $fixture->id('OUT')), 'not allowed to nominate');
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::nominate($fixture->electionId, $position, $fixture->id('OUT'), $fixture->id('V01')), 'can take part');
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::nominate($fixture->electionId, $position, $fixture->id('N01'), NULL));
  }

  public function testNominationsCloseWithTheNominationPeriod() {
    $fixture = Council::create();
    $fixture->moveToStage(Council::STAGE_ADVERTISE);
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::nominate($fixture->electionId, $fixture->positions['President'], $fixture->id('N01'), $fixture->id('V01')), 'not open');
  }

  public function testAPositionOfAnotherElectionCannotBeUsed() {
    $fixture = Council::create();
    $other = Council::create();
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::nominate($fixture->electionId, $other->positions['President'], $fixture->id('N01'), $fixture->id('V01')), 'not part of this election');
  }

  public function testAWithdrawnNomineeCannotBeNominatedAgain() {
    $fixture = Council::create();
    $fixture->runNominations();
    CRM_Elections_Helper_Process::withdraw($fixture->nominations['Treasurer|N07'], $fixture->id('N07'), 'No thanks');
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::nominate($fixture->electionId, $fixture->positions['Treasurer'], $fixture->id('N07'), $fixture->id('V30')), 'withdrawn');
  }

  // ---------------------------------------------------------- acceptances

  public function testNomineesRespondAndSixCandidatesStand() {
    $fixture = Council::create()->playUntil(Council::STAGE_ADVERTISE);
    $all = [];
    foreach (Council::CANDIDATES as $position => $expected) {
      sort($expected);
      $this->assertSame($expected, $fixture->standingCandidates($position), $position);
      $all = array_merge($all, $expected);
    }
    $this->assertCount(6, array_unique($all), 'Six people stand in total');
  }

  public function testCandidatesStatementsAreSaved() {
    $fixture = Council::create()->playUntil(Council::STAGE_ADVERTISE);
    $nomination = civicrm_api3('ElectionNomination', 'getsingle', [
      'id' => $fixture->nominations['President|N01'],
      'return' => ['comments', 'statement_url', 'has_accepted_nomination'],
    ]);
    $this->assertSame('Statement of N01 for President', $nomination['comments']);
    $this->assertSame('https://example.org/N01', $nomination['statement_url']);
  }

  public function testAcceptingThenWithdrawingMeansNotStanding() {
    $fixture = Council::create()->playUntil(Council::STAGE_ADVERTISE);
    $this->assertNotContains('N10', $fixture->standingCandidates('Council Member 3'));
    $this->assertEquals(1, $this->nominationField($fixture, 'Council Member 3|N10', 'has_rejected_nomination'));
  }

  public function testOnlyTheNomineeCanAccept() {
    $fixture = Council::create();
    $fixture->runNominations();
    $fixture->moveToStage(Council::STAGE_ADVERTISE);
    $nomination = $fixture->nominations['President|N01'];
    // A nominator, another nominee and an outsider cannot accept for N01.
    foreach (['V01', 'N02', 'OUT'] as $persona) {
      $this->assertRejected(fn() => CRM_Elections_Helper_Process::accept($nomination, $fixture->id($persona), 'Not mine'), 'not authorized');
    }
    $this->assertEquals(0, $this->nominationField($fixture, 'President|N01', 'has_accepted_nomination'));
    CRM_Elections_Helper_Process::accept($nomination, $fixture->id('N01'));
    $this->assertEquals(1, $this->nominationField($fixture, 'President|N01', 'has_accepted_nomination'));
  }

  public function testANomineeWithoutEnoughNominatorsCannotAccept() {
    $fixture = Council::create();
    $fixture->runNominations();
    $fixture->moveToStage(Council::STAGE_ADVERTISE);
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::accept($fixture->nominations['President|N08'], $fixture->id('N08')), 'enough nominations');
  }

  public function testCannotAcceptTwiceOrAfterWithdrawing() {
    $fixture = Council::create()->playUntil(Council::STAGE_ADVERTISE);
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::accept($fixture->nominations['President|N01'], $fixture->id('N01')), 'already have accepted');
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::accept($fixture->nominations['President|N03'], $fixture->id('N03')), 'rejected');
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::withdraw($fixture->nominations['President|N03'], $fixture->id('N03')), 'withdrawn');
  }

  public function testAcceptingNeedsAWebAddressForTheStatementLink() {
    $fixture = Council::create();
    $fixture->runNominations();
    $fixture->moveToStage(Council::STAGE_ADVERTISE);
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::accept($fixture->nominations['President|N01'], $fixture->id('N01'), 'Hi', 'javascript:alert(1)'), 'http');
  }

  public function testNomineesCannotRespondOnceVotingHasStarted() {
    $fixture = Council::create()->playUntil(Council::STAGE_ADVERTISE);
    $fixture->moveToStage(Council::STAGE_VOTING);
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::withdraw($fixture->nominations['President|N01'], $fixture->id('N01')), 'voting');
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::accept($fixture->nominations['Secretary|N04'], $fixture->id('N04')), 'voting');
  }

  // ----------------------------------------------------------------- voting

  public function testEveryMemberMayVoteAndAnOutsiderMayNot() {
    $fixture = Council::create()->playUntil(Council::STAGE_ADVERTISE);
    $fixture->moveToStage(Council::STAGE_VOTING);
    foreach ($fixture->members() as $persona => $contactId) {
      $this->assertTrue(CRM_Elections_Helper_Eligibility::canVote($fixture->electionId, $contactId), $persona);
    }
    $this->assertFalse(CRM_Elections_Helper_Eligibility::canVote($fixture->electionId, $fixture->id('OUT')));
    $ballot = $fixture->ranksForApi(['President' => ['N01' => 1]]);
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::vote($fixture->electionId, $fixture->id('OUT'), $ballot), 'not allowed to vote');
    $this->assertSame(0, $this->countVotes($fixture));
  }

  public function testThirtyEightMembersVoteAndTwoAbstain() {
    $fixture = Council::create()->playUntil(Council::STAGE_VOTING);
    $voted = CRM_Core_DAO::singleValueQuery('
      SELECT COUNT(DISTINCT v.member_id) FROM civicrm_election_vote v
      INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id
      INNER JOIN civicrm_election_position p ON p.id = n.election_position_id WHERE p.election_id = %1', [1 => [$fixture->electionId, 'Integer']]);
    $this->assertEquals(38, $voted);
    $this->assertTrue(CRM_Elections_Helper_Eligibility::hasVoted($fixture->electionId, $fixture->id('V01')));
    $this->assertFalse(CRM_Elections_Helper_Eligibility::hasVoted($fixture->electionId, $fixture->id('V29')));
    $this->assertFalse(CRM_Elections_Helper_Eligibility::hasVoted($fixture->electionId, $fixture->id('OUT')));
  }

  public function testOnlyStandingCandidatesCanBeVotedFor() {
    $fixture = Council::create()->playUntil(Council::STAGE_ADVERTISE);
    $fixture->moveToStage(Council::STAGE_VOTING);
    $voter = $fixture->id('V29');
    foreach (['Treasurer|N07' => 'withdrew', 'President|N08' => 'was never eligible', 'Secretary|N04' => 'never accepted', 'Council Member 3|N10' => 'withdrew after accepting'] as $key => $why) {
      $this->assertRejected(fn() => CRM_Elections_Helper_Process::vote($fixture->electionId, $voter, [$fixture->nominations[$key] => 1]), 'only vote for candidates');
    }
    $this->assertSame(0, $this->countVotes($fixture, 'V29'));
  }

  public function testACandidateOfAnotherElectionCannotBeVotedFor() {
    $fixture = Council::create()->playUntil(Council::STAGE_ADVERTISE);
    $other = Council::create()->playUntil(Council::STAGE_ADVERTISE);
    $fixture->moveToStage(Council::STAGE_VOTING);
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::vote($fixture->electionId, $fixture->id('V29'), [$other->nominations['President|N01'] => 1]), 'only vote for candidates');
  }

  public function testBadBallotsAreRejected() {
    $fixture = Council::create()->playUntil(Council::STAGE_ADVERTISE);
    $fixture->moveToStage(Council::STAGE_VOTING);
    $voter = $fixture->id('V29');
    $n01 = $fixture->nominations['President|N01'];
    $n02 = $fixture->nominations['President|N02'];
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::vote($fixture->electionId, $voter, []), 'votes');
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::vote($fixture->electionId, $voter, [$n01 => 1, $n02 => 1]), 'different');
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::vote($fixture->electionId, $voter, [$n01 => 0]), 'positive');
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::vote($fixture->electionId, $voter, [$n01 => 2]), 'rank 1');
    $this->assertSame(0, $this->countVotes($fixture, 'V29'));
  }

  public function testVotingOnlyWhileVotingIsOpen() {
    $fixture = Council::create()->playUntil(Council::STAGE_ADVERTISE);
    $ballot = $fixture->ranksForApi(['President' => ['N01' => 1]]);
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::vote($fixture->electionId, $fixture->id('V29'), $ballot), 'before voting');
    $fixture->moveToStage(Council::STAGE_RESULTS);
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::vote($fixture->electionId, $fixture->id('V29'), $ballot), 'after voting');
  }

  public function testAMemberCanChangeTheirVoteWhenRevotingIsAllowed() {
    $fixture = Council::create(['allow_revote' => 1])->playUntil(Council::STAGE_VOTING);
    $before = $this->countVotes($fixture, 'V01');
    $this->assertGreaterThan(1, $before);

    CRM_Elections_Helper_Process::vote($fixture->electionId, $fixture->id('V01'), $fixture->ranksForApi(['President' => ['N02' => 1]]));
    $this->assertSame(1, $this->countVotes($fixture, 'V01'), 'The new ballot replaces the old one');
    $this->assertEquals(1, civicrm_api3('ElectionVote', 'getvalue', ['member_id' => $fixture->id('V01'), 'return' => 'rank']));
  }

  public function testAMemberCannotVoteTwiceWhenRevotingIsOff() {
    $fixture = Council::create(['allow_revote' => 0])->playUntil(Council::STAGE_VOTING);
    $before = $this->countVotes($fixture, 'V01');
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::vote($fixture->electionId, $fixture->id('V01'), $fixture->ranksForApi(['President' => ['N02' => 1]])), 'already voted');
    $this->assertSame($before, $this->countVotes($fixture, 'V01'));
  }

  public function testARejectedRevoteKeepsTheEarlierBallot() {
    $fixture = Council::create(['allow_revote' => 1])->playUntil(Council::STAGE_VOTING);
    $before = $this->countVotes($fixture, 'V01');
    $this->assertRejected(fn() => CRM_Elections_Helper_Process::vote($fixture->electionId, $fixture->id('V01'), [$fixture->nominations['Treasurer|N07'] => 1]));
    $this->assertSame($before, $this->countVotes($fixture, 'V01'), 'A bad ballot must not cost someone their vote');
  }

  // ---------------------------------------------------------------- results

  /**
   * First preference count per candidate persona, worked out independently of the extension.
   */
  private function firstPreferences($fixture, $position) {
    $counts = array_fill_keys(Council::CANDIDATES[$position], 0);
    foreach ($fixture->ballots() as $ballot) {
      foreach ($ballot[$position] ?? [] as $candidate => $rank) {
        if ($rank == 1) {
          $counts[$candidate]++;
        }
      }
    }
    return $counts;
  }

  private function ranksForPosition($fixture, $position) {
    $byContact = array_flip($fixture->personas);
    $ranks = [];
    $results = civicrm_api3('ElectionResult', 'get', ['election_position_id' => $fixture->positions[$position], 'options' => ['limit' => 0]]);
    foreach ($results['values'] as $result) {
      $nominee = civicrm_api3('ElectionNomination', 'getvalue', ['id' => $result['election_nomination_id'], 'return' => 'member_nominee']);
      $ranks[$byContact[$nominee]] = (int) $result['rank'];
    }
    ksort($ranks);
    return $ranks;
  }

  private function positionStatus($fixture, $position) {
    return civicrm_api3('ElectionPosition', 'getvalue', ['id' => $fixture->positions[$position], 'return' => 'result_status']);
  }

  public function testResultsFollowTheBallots() {
    $fixture = Council::create()->playUntil(Council::STAGE_RESULTS);

    foreach (['President', 'Council Member 1', 'Council Member 2', 'Council Member 3'] as $position) {
      $counts = $this->firstPreferences($fixture, $position);
      arsort($counts);
      $winner = array_key_first($counts);
      $this->assertGreaterThan(array_sum($counts) / 2, $counts[$winner], "$position has a clear majority by design");
      $ranks = $this->ranksForPosition($fixture, $position);
      $this->assertSame(1, $ranks[$winner], "$winner wins $position");
      $this->assertSame(CRM_Elections_BAO_Election::$RESULTS_MAJORITY, $this->positionStatus($fixture, $position), $position);
    }
  }

  public function testTheUncontestedPositionIsWon() {
    $fixture = Council::create()->playUntil(Council::STAGE_RESULTS);
    $this->assertSame(['N03' => 1], $this->ranksForPosition($fixture, 'Secretary'));
  }

  public function testATiedPositionIsReportedAsATie() {
    $fixture = Council::create()->playUntil(Council::STAGE_RESULTS);
    $counts = $this->firstPreferences($fixture, 'Treasurer');
    $this->assertSame($counts['N04'], $counts['N05'], 'The Treasurer ballots are a tie by design');
    $this->assertSame(['N04' => 1, 'N05' => 1], $this->ranksForPosition($fixture, 'Treasurer'));
    $this->assertSame(CRM_Elections_BAO_Election::$RESULTS_NO_MAJORITY, $this->positionStatus($fixture, 'Treasurer'));
  }

  public function testAnonymisedElectionKeepsNoVoterIdentities() {
    $fixture = Council::create(['anonymize_votes' => 1])->playUntil(Council::STAGE_RESULTS);
    $this->assertGreaterThan(0, $this->countVotes($fixture));
    $named = CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_vote WHERE member_id IS NOT NULL');
    $this->assertEquals(0, $named);
    $this->assertSame(1, $this->ranksForPosition($fixture, 'President')['N01'], 'Results are still calculated for every position');
    $this->assertSame(1, $this->ranksForPosition($fixture, 'Council Member 3')['N05']);
  }

  public function testOpenElectionKeepsVoterIdentities() {
    $fixture = Council::create(['anonymize_votes' => 0])->playUntil(Council::STAGE_RESULTS);
    $this->assertSame(38, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(DISTINCT member_id) FROM civicrm_election_vote'));
  }

  public function testPublishedResultsNameWinnersWithNicknames() {
    $fixture = Council::create()->playUntil(Council::STAGE_RESULTS);
    Civi::settings()->set('elections_name_format', CRM_Elections_Helper_Candidate::NAME_FIRST_NICK_LAST);
    try {
      $positions = CRM_Elections_BAO_ElectionResult::getResultsAndSummary($fixture->electionId);
      $names = [];
      foreach ($positions as $position) {
        foreach ($position['candidates'] as $candidate) {
          $names[] = $candidate['member_nominee.display_name'];
        }
      }
      $this->assertContains('Nominee "Butch" 01', $names);
      $this->assertContains('Nominee "Mo" 03', $names);
      $this->assertContains('Nominee 02', $names);
    }
    finally {
      Civi::settings()->revert('elections_name_format');
    }
  }

}
