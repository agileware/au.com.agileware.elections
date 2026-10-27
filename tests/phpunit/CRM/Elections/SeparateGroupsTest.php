<?php

use CRM_Elections_Test_CouncilFixture as Council;

/**
 * Who may nominate, who may stand and who may vote can be different groups. An election with no groups of
 * its own for nominating or standing uses its voting groups, so elections made before 2.0 behave as before.
 *
 * @group headless
 */
class CRM_Elections_SeparateGroupsTest extends api_v3_ElectionBaseTestCase {

  private function group($title) {
    return (int) civicrm_api3('Group', 'create', ['title' => $title . uniqid(), 'name' => strtolower($title) . '_' . uniqid()])['id'];
  }

  private function put($contactId, $groupId) {
    civicrm_api3('GroupContact', 'create', ['group_id' => $groupId, 'contact_id' => $contactId, 'status' => 'Added']);
  }

  private function applyGroups(Council $fixture, array $values) {
    civicrm_api3('Election', 'create', $values + civicrm_api3('Election', 'getsingle', ['id' => $fixture->electionId]));
  }

  private function refused(callable $change, $needle) {
    try {
      $change();
    }
    catch (\Throwable $e) {
      $this->assertStringContainsStringIgnoringCase($needle, $e->getMessage());
      return;
    }
    $this->fail('Expected a refusal containing: ' . $needle);
  }

  public function testWithNoGroupsOfItsOwnTheVotingGroupsApplyToEveryRole() {
    $fixture = Council::create();
    $election = findElectionById($fixture->electionId);
    $this->assertSame((string) $fixture->groupId, CRM_Elections_Helper_Eligibility::groupsFor($election, 'nominating'));
    $this->assertSame((string) $fixture->groupId, CRM_Elections_Helper_Eligibility::groupsFor($election, 'candidate'));
    $v = $fixture->id('V01');
    $this->assertTrue(CRM_Elections_Helper_Eligibility::canVote($fixture->electionId, $v));
    $this->assertTrue(CRM_Elections_Helper_Eligibility::canNominate($fixture->electionId, $v));
    $this->assertTrue(CRM_Elections_Helper_Eligibility::canBeNominated($fixture->electionId, $v));
  }

  public function testNominatingAndStandingHaveTheirOwnGroups() {
    $fixture = Council::create();
    $nominators = $this->group('Nominators');
    $candidates = $this->group('Candidates');
    $this->put($fixture->id('V01'), $nominators);
    $this->put($fixture->id('N01'), $candidates);
    $this->applyGroups($fixture, ['nomination_groups' => (string) $nominators, 'candidate_groups' => (string) $candidates]);

    // V01 votes (voting group) and nominates (nomination group), but cannot stand.
    $this->assertTrue(CRM_Elections_Helper_Eligibility::canVote($fixture->electionId, $fixture->id('V01')));
    $this->assertTrue(CRM_Elections_Helper_Eligibility::canNominate($fixture->electionId, $fixture->id('V01')));
    $this->assertFalse(CRM_Elections_Helper_Eligibility::canBeNominated($fixture->electionId, $fixture->id('V01')));
    // V02 votes only.
    $this->assertTrue(CRM_Elections_Helper_Eligibility::canVote($fixture->electionId, $fixture->id('V02')));
    $this->assertFalse(CRM_Elections_Helper_Eligibility::canNominate($fixture->electionId, $fixture->id('V02')));
    // N01 stands (candidate group) and votes (voting group) but cannot nominate.
    $this->assertTrue(CRM_Elections_Helper_Eligibility::canBeNominated($fixture->electionId, $fixture->id('N01')));
    $this->assertFalse(CRM_Elections_Helper_Eligibility::canNominate($fixture->electionId, $fixture->id('N01')));
  }

  public function testOnlyThePeopleInTheNominationGroupCanNominateOrSecond() {
    $fixture = Council::create();
    $nominators = $this->group('Nominators');
    $this->put($fixture->id('V01'), $nominators);
    $this->applyGroups($fixture, ['nomination_groups' => (string) $nominators]);
    $position = $fixture->positions['President'];

    $this->refused(function () use ($fixture, $position) {
      CRM_Elections_Helper_Process::nominate($fixture->electionId, $position, $fixture->id('N01'), $fixture->id('V02'), 'not in the group');
    }, 'not allowed to nominate');
    $result = CRM_Elections_Helper_Process::nominate($fixture->electionId, $position, $fixture->id('N01'), $fixture->id('V01'), 'in the group');
    $this->assertNotEmpty($result['nomination_id']);
    $this->refused(function () use ($fixture, $position) {
      CRM_Elections_Helper_Process::nominate($fixture->electionId, $position, $fixture->id('N01'), $fixture->id('V03'), 'second, not in the group');
    }, 'not allowed to nominate');
  }

  public function testOnlyThePeopleInTheCandidateGroupCanBeNominatedOrAdded() {
    $fixture = Council::create();
    $candidates = $this->group('Candidates');
    $this->put($fixture->id('N01'), $candidates);
    $this->applyGroups($fixture, ['candidate_groups' => (string) $candidates]);
    $position = $fixture->positions['President'];

    $this->refused(function () use ($fixture, $position) {
      CRM_Elections_Helper_Process::nominate($fixture->electionId, $position, $fixture->id('N02'), $fixture->id('V01'), 'not a candidate group member');
    }, 'can stand');
    CRM_Elections_Helper_Process::nominate($fixture->electionId, $position, $fixture->id('N01'), $fixture->id('V01'), 'fine');
    $this->refused(function () use ($position, $fixture) {
      CRM_Elections_BAO_ElectionNomination::addCandidate($position, $fixture->id('N02'));
    }, 'can stand');
    $this->assertNotEmpty(CRM_Elections_BAO_ElectionNomination::addCandidate($position, $fixture->id('N01')));
  }

  public function testANomineeWhoLeftTheCandidateGroupCannotAccept() {
    $fixture = Council::create();
    $candidates = $this->group('Candidates');
    $this->put($fixture->id('N01'), $candidates);
    $this->applyGroups($fixture, ['candidate_groups' => (string) $candidates]);
    $position = $fixture->positions['President'];
    $first = CRM_Elections_Helper_Process::nominate($fixture->electionId, $position, $fixture->id('N01'), $fixture->id('V01'), 'one');
    CRM_Elections_Helper_Process::nominate($fixture->electionId, $position, $fixture->id('N01'), $fixture->id('V02'), 'two');
    $fixture->moveToStage(Council::STAGE_ADVERTISE);
    civicrm_api3('GroupContact', 'create', ['group_id' => $candidates, 'contact_id' => $fixture->id('N01'), 'status' => 'Removed']);
    $this->refused(function () use ($first, $fixture) {
      CRM_Elections_Helper_Process::accept($first['nomination_id'], $fixture->id('N01'), 'statement');
    }, 'can stand');
  }

  public function testTheGroupsAreCopiedLockedOnceVotedAndAudited() {
    $fixture = Council::create();
    $nominators = $this->group('Nominators');
    $this->applyGroups($fixture, ['nomination_groups' => (string) $nominators]);
    $copy = CRM_Elections_BAO_Election::duplicate($fixture->electionId);
    $this->assertSame((string) $nominators, trim((string) $copy['nomination_groups']));
    $logged = (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_audit WHERE election_id = %1 AND details LIKE %2', [1 => [$fixture->electionId, 'Integer'], 2 => ['%nomination_groups%', 'String']]);
    $this->assertGreaterThan(0, $logged);

    $voted = Council::create()->playUntil(Council::STAGE_VOTING);
    $fixture = $voted;
    $this->refused(function () use ($fixture, $nominators) {
      $this->applyGroups($fixture, ['candidate_groups' => (string) $nominators]);
    }, 'cannot be changed');
  }

}
