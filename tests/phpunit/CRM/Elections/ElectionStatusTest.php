<?php

/**
 * What the election page says about the stage and about what the person looking can do.
 *
 * @group headless
 */
class CRM_Elections_ElectionStatusTest extends api_v3_ElectionBaseTestCase {

  private function fixture($stage) {
    return CRM_Elections_Test_CouncilFixture::create()->playUntil($stage);
  }

  private function election($fixture) {
    return findElectionById($fixture->electionId);
  }

  private function states(array $steps) {
    return array_column($steps, 'state');
  }

  public function testTheStepperFollowsTheStages() {
    $expected = [
      CRM_Elections_Test_CouncilFixture::STAGE_NOMINATIONS => ['current', 'upcoming', 'upcoming', 'upcoming'],
      CRM_Elections_Test_CouncilFixture::STAGE_ADVERTISE => ['passed', 'current', 'upcoming', 'upcoming'],
      CRM_Elections_Test_CouncilFixture::STAGE_VOTING => ['passed', 'passed', 'current', 'upcoming'],
    ];
    foreach ($expected as $stage => $states) {
      $fixture = $this->fixture($stage);
      $steps = CRM_Elections_Helper_ElectionStatus::steps($this->election($fixture));
      $this->assertSame(['nominations', 'candidates', 'voting', 'results'], array_column($steps, 'key'));
      $this->assertSame($states, $this->states($steps), $stage);
    }
    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_RESULTS);
    $this->assertSame(['passed', 'passed', 'passed', 'current'], $this->states(CRM_Elections_Helper_ElectionStatus::steps($this->election($fixture))));
  }

  public function testEachPersonIsToldWhatTheyCanDoNow() {
    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_NOMINATIONS);
    $election = $this->election($fixture);
    $state = ['canNominate' => TRUE, 'canVote' => TRUE, 'hasVoted' => FALSE, 'voteDate' => NULL, 'allowedToChangeVote' => FALSE];
    $nominate = CRM_Elections_Helper_ElectionStatus::nextAction($election, $fixture->id('V01'), 'eid=' . $fixture->electionId, $state);
    $this->assertSame('action', $nominate['type']);
    $this->assertStringContainsString('Nominations are open until', $nominate['message']);
    $this->assertSame('Submit Nomination', $nominate['actions'][0]['label']);
    $this->assertStringContainsString('civicrm/elections/nominations/create', $nominate['actions'][0]['url']);

    $outsider = CRM_Elections_Helper_ElectionStatus::nextAction($election, $fixture->id('OUT'), 'eid=' . $fixture->electionId, ['canNominate' => FALSE] + $state);
    $this->assertSame('info', $outsider['type']);
    $this->assertSame([], $outsider['actions']);

    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_ADVERTISE);
    $election = $this->election($fixture);
    $pending = CRM_Core_DAO::executeQuery('SELECT member_nominee FROM civicrm_election_nomination WHERE is_eligible_candidate = 1 AND IFNULL(has_accepted_nomination, 0) = 0 AND IFNULL(has_rejected_nomination, 0) = 0 AND election_position_id IN (SELECT id FROM civicrm_election_position WHERE election_id = %1) LIMIT 1', [1 => [$fixture->electionId, 'Integer']]);
    // Everyone who was eligible has answered in the fixture, so make one nomination wait for its answer.
    CRM_Core_DAO::executeQuery('UPDATE civicrm_election_nomination SET has_accepted_nomination = 0 WHERE is_eligible_candidate = 1 AND IFNULL(has_rejected_nomination, 0) = 0 AND election_position_id IN (SELECT id FROM civicrm_election_position WHERE election_id = %1) LIMIT 1', [1 => [$fixture->electionId, 'Integer']]);
    $row = CRM_Core_DAO::executeQuery('SELECT id, member_nominee FROM civicrm_election_nomination WHERE is_eligible_candidate = 1 AND IFNULL(has_accepted_nomination, 0) = 0 AND IFNULL(has_rejected_nomination, 0) = 0 AND election_position_id IN (SELECT id FROM civicrm_election_position WHERE election_id = %1) LIMIT 1', [1 => [$fixture->electionId, 'Integer']]);
    $row->fetch();
    $respond = CRM_Elections_Helper_ElectionStatus::nextAction($election, (int) $row->member_nominee, 'eid=' . $fixture->electionId, $state);
    $this->assertSame('Respond to Nomination', $respond['actions'][0]['label']);
    $this->assertStringContainsString('enid=' . $row->id, $respond['actions'][0]['url']);
    $this->assertStringContainsString('You have been nominated for', $respond['message']);
    $this->assertNotNull($pending);
  }

  public function testVotingResultsAndClosedMessages() {
    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_VOTING);
    $election = $this->election($fixture);
    $base = ['canNominate' => TRUE, 'canVote' => TRUE, 'hasVoted' => FALSE, 'voteDate' => NULL, 'allowedToChangeVote' => TRUE];
    $query = 'eid=' . $fixture->electionId;

    $new = CRM_Elections_Helper_ElectionStatus::nextAction($election, $fixture->id('V29'), $query, $base);
    $this->assertSame('Vote Now', $new['actions'][0]['label']);

    $voted = CRM_Elections_Helper_ElectionStatus::nextAction($election, $fixture->id('V01'), $query, ['hasVoted' => TRUE, 'voteDate' => date('Y-m-d H:i:s')] + $base);
    $this->assertSame('done', $voted['type']);
    $this->assertStringContainsString('You voted on', $voted['message']);
    $this->assertSame('Change Vote', $voted['actions'][0]['label']);

    $locked = CRM_Elections_Helper_ElectionStatus::nextAction($election, $fixture->id('V01'), $query, ['hasVoted' => TRUE, 'voteDate' => date('Y-m-d H:i:s'), 'allowedToChangeVote' => FALSE] + $base);
    $this->assertSame([], $locked['actions']);

    $notAllowed = CRM_Elections_Helper_ElectionStatus::nextAction($election, $fixture->id('OUT'), $query, ['canVote' => FALSE] + $base);
    $this->assertStringContainsString('Only members can vote', $notAllowed['message']);

    // Voting has closed but the results are not generated yet.
    $fixture->moveToStage(CRM_Elections_Test_CouncilFixture::STAGE_RESULTS);
    $closed = CRM_Elections_Helper_ElectionStatus::nextAction($this->election($fixture), $fixture->id('V01'), $query, $base);
    $this->assertSame('closed', $closed['type']);
    $this->assertStringContainsString('Voting has closed', $closed['message']);
    $this->assertSame([], $closed['actions']);
    $fixture->generateResults();
    $this->assertSame('done', CRM_Elections_Helper_ElectionStatus::nextAction($this->election($fixture), $fixture->id('V01'), $query, $base)['type']);
  }

  public function testEachCardSaysItsStage() {
    $cases = [
      ['stage' => 'upcoming', 'flags' => []],
      ['stage' => 'nominations', 'flags' => ['hasNominationsStarted' => TRUE, 'isNominationsInProgress' => TRUE]],
      ['stage' => 'advertise', 'flags' => ['hasNominationsStarted' => TRUE]],
      ['stage' => 'advertise', 'flags' => ['hasNominationsStarted' => TRUE, 'advertiseCandidatesStarted' => TRUE]],
      ['stage' => 'voting', 'flags' => ['hasNominationsStarted' => TRUE, 'advertiseCandidatesStarted' => TRUE, 'isVotingStarted' => TRUE, 'isUserAllowedToVote' => TRUE]],
      ['stage' => 'closed', 'flags' => ['isVotingStarted' => TRUE, 'isVotingEnded' => TRUE]],
      ['stage' => 'results', 'flags' => ['isVotingStarted' => TRUE, 'isVotingEnded' => TRUE, 'isResultsOut' => TRUE]],
    ];
    $dates = ['nomination_start_date' => '2031-01-05 09:00:00', 'nomination_end_date' => '2031-01-12 09:00:00', 'advertise_candidates_date' => '2031-01-15 09:00:00', 'voting_start_date' => '2031-01-20 09:00:00', 'voting_end_date' => '2031-01-27 09:00:00', 'result_date' => '2031-02-01 09:00:00'];
    foreach ($cases as $case) {
      $card = CRM_Elections_Helper_ElectionStatus::card($case['flags'] + $dates);
      $this->assertSame($case['stage'], $card['stage']);
      $this->assertNotEmpty($card['stageLabel']);
      $this->assertNotEmpty($card['line']);
    }
    $voting = CRM_Elections_Helper_ElectionStatus::card(['isVotingStarted' => TRUE, 'isUserAllowedToVote' => FALSE] + $dates);
    $this->assertSame('', $voting['button'], 'No vote button for someone who cannot vote');
    $this->assertSame('', CRM_Elections_Helper_ElectionStatus::card($dates)['button'], 'No button before nominations open');
  }

  public function testPeopleSeeTheirOwnNominationsAndBallotOnly() {
    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_VOTING);
    $election = $this->election($fixture);
    $query = 'eid=' . $fixture->electionId;

    $mine = CRM_Elections_Helper_ElectionStatus::ownNominations($election, $fixture->id('N01'), $query);
    $this->assertNotEmpty($mine);
    foreach ($mine as $row) {
      $this->assertContains($row['state'], ['waiting', 'respond', 'accepted', 'withdrawn']);
      // Voting has started, so there is nothing left to accept, withdraw or edit.
      $this->assertSame([], $row['actions']);
    }
    $this->assertSame([], CRM_Elections_Helper_ElectionStatus::ownNominations($election, $fixture->id('OUT'), $query));
    $this->assertSame([], CRM_Elections_Helper_ElectionStatus::ownNominations($election, NULL, $query));

    $ballot = CRM_Elections_Helper_ElectionStatus::ownBallot($election, $fixture->id('V01'));
    $this->assertNotEmpty($ballot);
    $this->assertSame(['position', 'candidates'], array_keys($ballot[0]));
    $this->assertSame([], CRM_Elections_Helper_ElectionStatus::ownBallot($election, $fixture->id('OUT')), 'Someone who did not vote has no ballot');
    $this->assertSame([], CRM_Elections_Helper_ElectionStatus::ownBallot($election, NULL));

    // Once the votes are anonymised the ballot no longer says whose it is, so nothing is shown.
    (new CRM_Elections_Helper_Results())->removeVotes($fixture->electionId);
    $this->assertSame([], CRM_Elections_Helper_ElectionStatus::ownBallot($election, $fixture->id('V01')));
  }

  public function testNominationsStillWaitingForNominatorsAreSaidSo() {
    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_NOMINATIONS);
    $election = $this->election($fixture);
    // A nomination with one nominator when two are needed.
    $position = (int) CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_election_position WHERE election_id = %1 ORDER BY sortorder LIMIT 1', [1 => [$fixture->electionId, 'Integer']]);
    $nominee = $this->individualCreate();
    civicrm_api3('GroupContact', 'create', ['group_id' => $election->allowed_groups, 'contact_id' => $nominee]);
    $nomination = (int) civicrm_api3('ElectionNomination', 'create', ['election_position_id' => $position, 'member_nominee' => $nominee])['id'];
    CRM_Core_DAO::executeQuery('INSERT INTO civicrm_election_nomination_seconder (member_nominator, election_nomination_id) VALUES (%1, %2)', [1 => [$fixture->id('V01'), 'Integer'], 2 => [$nomination, 'Integer']]);
    $mine = CRM_Elections_Helper_ElectionStatus::ownNominations($election, $nominee, 'eid=' . $fixture->electionId);
    $this->assertSame('waiting', $mine[0]['state']);
    $this->assertSame('Waiting for 1 more nominator', $mine[0]['stateLabel']);
    $this->assertSame('Withdraw Nomination', $mine[0]['actions'][0]['label']);
    $this->assertTrue($mine[0]['actions'][0]['quiet']);
  }

}
