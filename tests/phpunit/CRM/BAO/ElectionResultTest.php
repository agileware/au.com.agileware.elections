<?php

use CRM_Elections_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class CRM_BAO_ElectionResultTest extends api_v3_ElectionBaseTestCase {

  private $election;
  private $electionPosition;

  public function setUp() {
    parent::setUp();
    $this->election = $this->createElection(array(
      'is_visible' => 1,
      'is_deleted' => 0,
    ));
  }

  /**
   * Test that a correct result status is set of election position for no nominations.
   */
  public function testNoNominationsForPosition() {
    $this->electionPosition = $this->createElectionPosition($this->election);
    $this->election = $this->generateElectionResults($this->election);
    $this->assertElectionPositionStatus(CRM_Elections_BAO_Election::$RESULTS_NO_NOMINATIONS);
  }

  /**
   * Test that a correct result status is set of election position (Multiple Seats) for no nominations.
   */
  public function testNoNominationsForPositionWithMultipleSeats() {
    $this->electionPosition = $this->createElectionPosition($this->election, array(
      'quantity' => 2,
    ));
    $this->election = $this->generateElectionResults($this->election);
    $this->assertElectionPositionStatus(CRM_Elections_BAO_Election::$RESULTS_NO_NOMINATIONS);
  }

  /**
   * Test that a correct result status is set of election position (Multiple Seats) for no candidates.
   */
  public function testNoCandidatesForPositionWithMultipleSeats() {
    $this->electionPosition = $this->createElectionPosition($this->election, array(
      'quantity' => 2,
    ));
    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $this->election);
    $this->createNominationForResults($this->electionPosition['id']);
    $this->election = $this->generateElectionResults($this->election);
    $this->assertElectionPositionStatus(CRM_Elections_BAO_Election::$RESULTS_NO_NOMINATIONS);
  }

  /**
   * Test that a correct result status is set of election position (Multiple Seats) for single candidate.
   */
  public function testSingleCandidateForPositionWithMultipleSeats() {
    $this->electionPosition = $this->createElectionPosition($this->election, array(
      'quantity' => 2,
    ));
    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $this->election);
    $members = $this->createNominationForResults($this->electionPosition['id'], 1, 1);
    $this->election = $this->generateElectionResults($this->election);
    $this->assertElectionPositionStatus(CRM_Elections_BAO_Election::$RESULTS_MORE_SEATS);
    $candidateOne = $members['candidates'][0];
    $this->assertRank($candidateOne['id'], $this->electionPosition['id'], 1);
  }

  /**
   * Test that a correct result status is set of election position (Multiple Seats) for equal candidates.
   */
  public function testEqualCandidatesForPositionWithMultipleSeats() {
    $this->electionPosition = $this->createElectionPosition($this->election, array(
      'quantity' => 2,
    ));
    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $this->election);
    $members = $this->createNominationForResults($this->electionPosition['id'], 2, 2);
    $this->election = $this->generateElectionResults($this->election);
    $this->assertElectionPositionStatus(CRM_Elections_BAO_Election::$RESULTS_EQUAL_SEATS);
    foreach ($members['candidates'] as $candidate) {
      $this->assertRank($candidate['id'], $this->electionPosition['id'], 1);
    }
  }

  /**
   * Test that a correct result status is set of election position for no candidates.
   */
  public function testNoCandidatesForPosition() {
    $this->electionPosition = $this->createElectionPosition($this->election);
    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $this->election);
    $this->createNominationForResults($this->electionPosition['id']);
    $this->election = $this->generateElectionResults($this->election);
    $this->assertElectionPositionStatus(CRM_Elections_BAO_Election::$RESULTS_NO_NOMINATIONS);
  }

  /**
   * Test that a correct result status is set of election position for single candidate.
   */
  public function testSingleCandidateForPosition() {
    $this->electionPosition = $this->createElectionPosition($this->election);
    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $this->election);
    $members = $this->createNominationForResults($this->electionPosition['id'], 1, 1);
    $this->election = $this->generateElectionResults($this->election);
    $this->assertElectionPositionStatus(CRM_Elections_BAO_Election::$RESULTS_EQUAL_SEATS);
    $candidateOne = $members['candidates'][0];
    $this->assertRank($candidateOne['id'], $this->electionPosition['id'], 1);
  }

  /**
   * Test that a correct result status is set of election position when there is majority between two candidates.
   */
  public function testMajorityBetweenTwoCandidatesForPosition() {
    $this->electionPosition = $this->createElectionPosition($this->election);
    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $this->election);
    $members = $this->createNominationForResults($this->electionPosition['id'], 2, 2);
    $winnerCandidate = $members['candidates'][0];
    $secondCandidate = $members['candidates'][1];
    $voters = $this->getVoters(5);

    $memberVotes = $voters['memberVotes'];
    $voters = $voters['voters'];
    $this->addMembersInElectionGroup($voters, $this->election);

    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_VOTING_IN_PROGRESS, $this->election);

    for ($i = 0; $i < 3; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $secondCandidate['id']);
    }
    for ($i = 3; $i < 5; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $secondCandidate['id']);
    }

    $this->addMemberVotes($memberVotes);

    $this->election = $this->generateElectionResults($this->election);
    $this->assertElectionPositionStatus(CRM_Elections_BAO_Election::$RESULTS_MAJORITY);

    $this->assertRank($winnerCandidate['id'], $this->electionPosition['id'], 1);
    $this->assertRank($secondCandidate['id'], $this->electionPosition['id'], 2);
  }

  /**
   * Test that a correct result status is set of election position when there is tie between two candidates.
   */
  public function testTieBetweenTwoCandidatesForPosition() {
    $this->electionPosition = $this->createElectionPosition($this->election);
    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $this->election);
    $members = $this->createNominationForResults($this->electionPosition['id'], 2, 2);
    $winnerCandidate = $members['candidates'][0];
    $secondCandidate = $members['candidates'][1];
    $voters = $this->getVoters(6);

    $memberVotes = $voters['memberVotes'];
    $voters = $voters['voters'];
    $this->addMembersInElectionGroup($voters, $this->election);

    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_VOTING_IN_PROGRESS, $this->election);
    for ($i = 0; $i < 3; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $secondCandidate['id']);
    }
    for ($i = 3; $i < 6; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $secondCandidate['id']);
    }
    $this->addMemberVotes($memberVotes);
    $this->election = $this->generateElectionResults($this->election);
    $this->assertElectionPositionStatus(CRM_Elections_BAO_Election::$RESULTS_NO_MAJORITY);

    $this->assertRank($winnerCandidate['id'], $this->electionPosition['id'], 1);
    $this->assertRank($secondCandidate['id'], $this->electionPosition['id'], 1);
  }

  /**
   * Test that a correct result status is set of election position when there is majority between three candidates.
   */
  public function testMajorityBetweenThreeCandidatesForPosition() {
    $this->electionPosition = $this->createElectionPosition($this->election);
    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $this->election);
    $members = $this->createNominationForResults($this->electionPosition['id'], 3, 3);
    $winnerCandidate = $members['candidates'][0];
    $secondCandidate = $members['candidates'][1];
    $thirdCandidate  = $members['candidates'][2];

    $voters = $this->getVoters(10);

    $memberVotes = $voters['memberVotes'];
    $voters = $voters['voters'];
    $this->addMembersInElectionGroup($voters, $this->election);

    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_VOTING_IN_PROGRESS, $this->election);
    for ($i = 0; $i < 6; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $secondCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(3, $thirdCandidate['id']);
    }
    for ($i = 6; $i < 10; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $secondCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(3, $thirdCandidate['id']);
    }
    $this->addMemberVotes($memberVotes);
    $this->election = $this->generateElectionResults($this->election);
    $this->assertElectionPositionStatus(CRM_Elections_BAO_Election::$RESULTS_MAJORITY);

    $this->assertRank($winnerCandidate['id'], $this->electionPosition['id'], 1);
    $this->assertRank($secondCandidate['id'], $this->electionPosition['id'], 2);
    $this->assertRank($thirdCandidate['id'], $this->electionPosition['id'], 3);
  }

  /**
   * Test that a correct result status is set of election position when there is a majority after one elimination between three candidates.
   */
  public function testMajorityAfterEliminationBetweenThreeCandidatesForPosition() {
    $this->electionPosition = $this->createElectionPosition($this->election);
    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $this->election);
    $members = $this->createNominationForResults($this->electionPosition['id'], 3, 3);
    $winnerCandidate = $members['candidates'][0];
    $secondCandidate = $members['candidates'][1];
    $thirdCandidate  = $members['candidates'][2];

    $voters = $this->getVoters(10);

    $memberVotes = $voters['memberVotes'];
    $voters = $voters['voters'];
    $this->addMembersInElectionGroup($voters, $this->election);

    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_VOTING_IN_PROGRESS, $this->election);
    for ($i = 0; $i < 5; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $secondCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(3, $thirdCandidate['id']);
    }
    for ($i = 5; $i < 7; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $secondCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(3, $thirdCandidate['id']);
    }
    for ($i = 7; $i < 10; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(3, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $secondCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $thirdCandidate['id']);
    }
    $this->addMemberVotes($memberVotes);
    $this->election = $this->generateElectionResults($this->election);
    $this->assertElectionPositionStatus(CRM_Elections_BAO_Election::$RESULTS_MAJORITY);

    $this->assertRank($winnerCandidate['id'], $this->electionPosition['id'], 1);
    $this->assertRank($secondCandidate['id'], $this->electionPosition['id'], 3);
    $this->assertRank($thirdCandidate['id'], $this->electionPosition['id'], 2);
  }

  /**
   * Test that a correct result status is set of election position when there is tie between three candidates after one elimination.
   */
  public function testTieAfterEliminationBetweenThreeCandidatesForPosition() {
    $this->electionPosition = $this->createElectionPosition($this->election);
    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $this->election);
    $members = $this->createNominationForResults($this->electionPosition['id'], 3, 3);
    $winnerCandidate = $members['candidates'][0];
    $secondCandidate = $members['candidates'][1];
    $thirdCandidate  = $members['candidates'][2];

    $voters = $this->getVoters(10);

    $memberVotes = $voters['memberVotes'];
    $voters = $voters['voters'];
    $this->addMembersInElectionGroup($voters, $this->election);

    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_VOTING_IN_PROGRESS, $this->election);
    for ($i = 0; $i < 5; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $secondCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(3, $thirdCandidate['id']);
    }
    for ($i = 5; $i < 7; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $secondCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(3, $thirdCandidate['id']);
    }
    for ($i = 7; $i < 10; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(3, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $secondCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $thirdCandidate['id']);
    }
    $this->addMemberVotes($memberVotes);
    $this->election = $this->generateElectionResults($this->election);
    $this->assertElectionPositionStatus(CRM_Elections_BAO_Election::$RESULTS_NO_MAJORITY);

    $this->assertRank($winnerCandidate['id'], $this->electionPosition['id'], 1);
    $this->assertRank($secondCandidate['id'], $this->electionPosition['id'], 1);
    $this->assertRank($thirdCandidate['id'], $this->electionPosition['id'], 3);
  }

  /**
   * Test that a correct result status is set of election position when there is majority between four candidates.
   */
  public function testMajorityBetweenFourCandidatesForPosition() {
    $this->electionPosition = $this->createElectionPosition($this->election);
    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $this->election);
    $members = $this->createNominationForResults($this->electionPosition['id'], 4, 4);
    $winnerCandidate = $members['candidates'][0];
    $secondCandidate = $members['candidates'][1];
    $thirdCandidate  = $members['candidates'][2];
    $fourthCandidate = $members['candidates'][3];

    $voters = $this->getVoters(10);

    $memberVotes = $voters['memberVotes'];
    $voters = $voters['voters'];
    $this->addMembersInElectionGroup($voters, $this->election);

    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_VOTING_IN_PROGRESS, $this->election);
    for ($i = 0; $i < 6; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $secondCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(3, $thirdCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(4, $fourthCandidate['id']);
    }
    for ($i = 6; $i < 10; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $secondCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(3, $thirdCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(4, $fourthCandidate['id']);
    }
    $this->addMemberVotes($memberVotes);
    $this->election = $this->generateElectionResults($this->election);
    $this->assertElectionPositionStatus(CRM_Elections_BAO_Election::$RESULTS_MAJORITY);

    $this->assertRank($winnerCandidate['id'], $this->electionPosition['id'], 1);
    $this->assertRank($secondCandidate['id'], $this->electionPosition['id'], 2);
    $this->assertRank($thirdCandidate['id'], $this->electionPosition['id'], 3);
    $this->assertRank($fourthCandidate['id'], $this->electionPosition['id'], 3);
  }

  /**
   * Test that a correct result status is set of election position when there is equal number of  seats after one elimination.
   */
  public function testEqualSeatsAfterEliminationBetweenThreeCandidatesForPosition() {
    $this->electionPosition = $this->createElectionPosition($this->election, array(
      'quantity' => 2,
    ));
    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $this->election);
    $members = $this->createNominationForResults($this->electionPosition['id'], 3, 3);
    $winnerCandidate = $members['candidates'][0];
    $secondCandidate = $members['candidates'][1];
    $thirdCandidate  = $members['candidates'][2];

    $voters = $this->getVoters(10);

    $memberVotes = $voters['memberVotes'];
    $voters = $voters['voters'];
    $this->addMembersInElectionGroup($voters, $this->election);

    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_VOTING_IN_PROGRESS, $this->election);
    for ($i = 0; $i < 5; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $secondCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(3, $thirdCandidate['id']);
    }
    for ($i = 5; $i < 7; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $secondCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(3, $thirdCandidate['id']);
    }
    for ($i = 7; $i < 10; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(3, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $secondCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $thirdCandidate['id']);
    }
    $this->addMemberVotes($memberVotes);
    $this->election = $this->generateElectionResults($this->election);
    $this->assertElectionPositionStatus(CRM_Elections_BAO_Election::$RESULTS_EQUAL_SEATS);

    $this->assertRank($winnerCandidate['id'], $this->electionPosition['id'], 1);
    $this->assertRank($secondCandidate['id'], $this->electionPosition['id'], 3);
    $this->assertRank($thirdCandidate['id'], $this->electionPosition['id'], 2);
  }

  /**
   * Test that a correct result status is set of election position when there is majority after first elimination between four candidates.
   */
  public function testMajorityAfterEliminationBetweenFourCandidatesForPosition() {
    $this->electionPosition = $this->createElectionPosition($this->election);
    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $this->election);
    $members = $this->createNominationForResults($this->electionPosition['id'], 4, 4);
    $winnerCandidate = $members['candidates'][0];
    $secondCandidate = $members['candidates'][1];
    $thirdCandidate  = $members['candidates'][2];
    $fourthCandidate = $members['candidates'][3];

    $voters = $this->getVoters(10);

    $memberVotes = $voters['memberVotes'];
    $voters = $voters['voters'];
    $this->addMembersInElectionGroup($voters, $this->election);

    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_VOTING_IN_PROGRESS, $this->election);
    for ($i = 0; $i < 5; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $secondCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(3, $thirdCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(4, $fourthCandidate['id']);
    }
    for ($i = 5; $i < 7; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $secondCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(3, $thirdCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(4, $fourthCandidate['id']);
    }
    for ($i = 7; $i < 9; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(3, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $secondCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $thirdCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(4, $fourthCandidate['id']);
    }
    for ($i = 9; $i < 10; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(3, $secondCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(4, $thirdCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $fourthCandidate['id']);
    }
    $this->addMemberVotes($memberVotes);

    $this->election = $this->generateElectionResults($this->election);
    $this->assertElectionPositionStatus(CRM_Elections_BAO_Election::$RESULTS_MAJORITY);

    $this->assertRank($winnerCandidate['id'], $this->electionPosition['id'], 1);
    $this->assertRank($secondCandidate['id'], $this->electionPosition['id'], 2);
    $this->assertRank($thirdCandidate['id'], $this->electionPosition['id'], 2);
    $this->assertRank($fourthCandidate['id'], $this->electionPosition['id'], 4);
  }

  /**
   * Test that a correct result status is set of election position when there is majority after first elimination between four candidates.
   */
  public function testEqualSeatsAfterTwoEliminationBetweenFourCandidatesForPosition() {
    $this->electionPosition = $this->createElectionPosition($this->election, array(
      'quantity' => 2,
    ));
    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $this->election);
    $members = $this->createNominationForResults($this->electionPosition['id'], 4, 4);
    $winnerCandidate = $members['candidates'][0];
    $secondCandidate = $members['candidates'][1];
    $thirdCandidate  = $members['candidates'][2];
    $fourthCandidate = $members['candidates'][3];

    $voters = $this->getVoters(10);

    $memberVotes = $voters['memberVotes'];
    $voters = $voters['voters'];
    $this->addMembersInElectionGroup($voters, $this->election);

    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_VOTING_IN_PROGRESS, $this->election);
    for ($i = 0; $i < 3; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $secondCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(3, $thirdCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(4, $fourthCandidate['id']);
    }
    for ($i = 3; $i < 6; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(3, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $secondCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $thirdCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(4, $fourthCandidate['id']);
    }
    for ($i = 6; $i < 8; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(3, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $secondCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $thirdCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(4, $fourthCandidate['id']);
    }
    for ($i = 8; $i < 10; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(3, $secondCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(4, $thirdCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $fourthCandidate['id']);
    }
    $this->addMemberVotes($memberVotes);

    $this->election = $this->generateElectionResults($this->election);
    $this->assertElectionPositionStatus(CRM_Elections_BAO_Election::$RESULTS_EQUAL_SEATS);

    $this->assertRank($winnerCandidate['id'], $this->electionPosition['id'], 1);
    $this->assertRank($secondCandidate['id'], $this->electionPosition['id'], 1);
    $this->assertRank($thirdCandidate['id'], $this->electionPosition['id'], 4);
    $this->assertRank($fourthCandidate['id'], $this->electionPosition['id'], 3);
  }

  /**
   * Test that votes are removed if anonymize_votes value is set in election.
   */
  public function testAnonymizeVotesInElection() {
    $this->election['anonymize_votes'] = 1;
    $this->callAPISuccess('Election', 'create', $this->election);

    $this->electionPosition = $this->createElectionPosition($this->election);
    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $this->election);
    $members = $this->createNominationForResults($this->electionPosition['id'], 2, 2);
    $winnerCandidate = $members['candidates'][0];
    $secondCandidate = $members['candidates'][1];
    $voters = $this->getVoters(5);

    $memberVotes = $voters['memberVotes'];
    $voters = $voters['voters'];
    $this->addMembersInElectionGroup($voters, $this->election);

    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_VOTING_IN_PROGRESS, $this->election);
    for ($i = 0; $i < 3; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $secondCandidate['id']);
    }
    for ($i = 3; $i < 5; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $secondCandidate['id']);
    }
    $this->addMemberVotes($memberVotes);
    $this->election = $this->generateElectionResults($this->election);
    $this->assertElectionPositionStatus(CRM_Elections_BAO_Election::$RESULTS_MAJORITY);

    $votesCount = $this->callAPISuccess('ElectionVote', 'getcount', array(
      'election_nomination_id.election_position_id.election_id.id' => $this->election['id'],
    ));

    $this->assertEquals(0, $votesCount, 'Votes count should be zero.');
  }

  /**
   * Test that votes are not removed if anonymize_votes value is 0 in election.
   */
  public function testOpenVotesInElection() {
    $this->election['anonymize_votes'] = 0;
    $this->callAPISuccess('Election', 'create', $this->election);

    $this->electionPosition = $this->createElectionPosition($this->election);
    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $this->election);
    $members = $this->createNominationForResults($this->electionPosition['id'], 2, 2);
    $winnerCandidate = $members['candidates'][0];
    $secondCandidate = $members['candidates'][1];
    $voters = $this->getVoters(5);

    $memberVotes = $voters['memberVotes'];
    $voters = $voters['voters'];
    $this->addMembersInElectionGroup($voters, $this->election);

    $this->election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_VOTING_IN_PROGRESS, $this->election);
    for ($i = 0; $i < 3; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $secondCandidate['id']);
    }
    for ($i = 3; $i < 5; $i++) {
      $memberVotes[$voters[$i]][] = $this->recordVote(2, $winnerCandidate['id']);
      $memberVotes[$voters[$i]][] = $this->recordVote(1, $secondCandidate['id']);
    }
    $this->addMemberVotes($memberVotes);
    $this->election = $this->generateElectionResults($this->election);
    $this->assertElectionPositionStatus(CRM_Elections_BAO_Election::$RESULTS_MAJORITY);

    $votesCount = $this->callAPISuccess('ElectionVote', 'getcount', array(
      'election_nomination_id.election_position_id.election_id.id' => $this->election['id'],
    ));

    $this->assertEquals(10, $votesCount, 'Votes count should be zero.');
  }


  public function recordVote($rank, $nominationId) {
    return array(
      'rank'                   => $rank,
      'election_nomination_id' => $nominationId,
    );
  }

  public function createNominationForResults($positionId, $nominationsCount = 1, $candidatesCount = 0) {
    $members = array(
      'nominations' => array(),
      'candidates'  => array(),
    );
    while ($nominationsCount > 0) {
      $nominee = $this->individualCreate(array(
        'first_name' => 'Candidate',
        'last_name'  => '#' . $nominationsCount,
      ));
      $params = array(
        'member_nominee'       => $nominee,
        'election_position_id' => $positionId,
      );
      $isCandidate = FALSE;
      if ($candidatesCount > 0) {
        $params['is_eligible_candidate']   = 1;
        $params['has_accepted_nomination'] = 1;
        $candidatesCount--;
        $isCandidate = TRUE;
      }
      $member = $this->callAPISuccess('ElectionNomination', 'create', $params);
      $member = $member['values'][$member['id']];
      if ($isCandidate) {
        $members['candidates'][] = $member;
      }
      else {
        $members['nominations'][] = $member;
      }
      $nominationsCount--;
    }
    return $members;
  }

  public function assertElectionPositionStatus($resultStatus) {
    $this->electionPosition = $this->getElectionPositionById($this->electionPosition['id']);
    $this->election = $this->getElectionById($this->election['id']);
    $this->assertEquals(1, $this->election['has_results_generated'], 'Election results failed to generate.');
    $this->assertEquals($resultStatus, $this->electionPosition['result_status'], 'Election position result status is not right.');
  }

  public function addMemberVotes($memberVotes) {
    foreach ($memberVotes as $memberId => $votes) {
      $addVotesParams = array(
        'member_id'   => $memberId,
        'election_id' => $this->election['id'],
        'votes'       => $votes,
      );
      civicrm_api3('ElectionVote', 'addvotes', $addVotesParams);
    }
  }

  public function getVoters($n = 5) {
    $voters = array();
    $memberVotes = array();
    while ($n > 0) {
      $n--;
      $voterId = $this->individualCreate(array(
        'first_name' => 'Voter',
        'last_name'  => '#' . $n,
      ));
      $voters[] = $voterId;
      $memberVotes[$voterId] = array();
    }
    return array(
      'voters' => $voters,
      'memberVotes' => $memberVotes,
    );
  }

  /**
   * Assert rank of a nomination for given position.
   *
   * @param $nominationId
   * @param $positionId
   * @param $rank
   */
  public function assertRank($nominationId, $positionId, $rank) {
    $this->callAPISuccess('ElectionResult', 'getsingle', array(
      'rank'                     => $rank,
      'election_position_id'     => $positionId,
      'election_nomination_id'   => $nominationId,
    ));
  }

}
