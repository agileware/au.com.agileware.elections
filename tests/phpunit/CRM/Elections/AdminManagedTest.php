<?php

use CRM_Elections_Test_CouncilFixture as Council;

/**
 * An election can turn online nominating off: there is no nomination form, the organisers add the candidates, and
 * everything after that works as usual. Elections made before 2.0 keep online nominations on.
 *
 * @group headless
 */
class CRM_Elections_AdminManagedTest extends api_v3_ElectionBaseTestCase {

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

  public function testOnlineNominationsAreOnUnlessTurnedOff() {
    $fixture = Council::create();
    $election = findElectionById($fixture->electionId);
    $election->assignDisplayOptions();
    $this->assertTrue($election->onlineNominations, 'The default, and what every earlier election does');
    CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET allow_online_nominations = NULL WHERE id = %1', [1 => [$fixture->electionId, 'Integer']]);
    $election = findElectionById($fixture->electionId);
    $election->assignDisplayOptions();
    $this->assertTrue($election->onlineNominations, 'Empty means on');
  }

  public function testWithOnlineNominationsOffNobodyCanNominateOrSecond() {
    $fixture = Council::create(['allow_online_nominations' => 0]);
    $this->refused(fn() => CRM_Elections_Helper_Process::nominate($fixture->electionId, $fixture->positions['President'], $fixture->id('N01'), $fixture->id('V01'), 'no form'), 'chosen by the organisers');
    $this->assertSame(0, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_nomination'));
  }

  public function testTheOrganisersAddCandidatesAndTheyAcceptAsUsual() {
    $fixture = Council::create(['allow_online_nominations' => 0]);
    $nomination = CRM_Elections_BAO_ElectionNomination::addCandidate($fixture->positions['President'], $fixture->id('N01'), FALSE);
    $this->assertNotEmpty($nomination['id']);
    $fixture->moveToStage(Council::STAGE_ADVERTISE);
    CRM_Elections_Helper_Process::accept((int) $nomination['id'], $fixture->id('N01'), 'Chosen by the board');
    $this->assertEquals(1, CRM_Core_DAO::singleValueQuery('SELECT has_accepted_nomination FROM civicrm_election_nomination WHERE id = %1', [1 => [(int) $nomination['id'], 'Integer']]));
    $fixture->moveToStage(Council::STAGE_VOTING);
    $fixture->reloadNominations();
    CRM_Elections_Helper_Process::vote($fixture->electionId, $fixture->id('V01'), [(int) $nomination['id'] => 1]);
    $this->assertSame(1, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_vote'));
  }

  public function testTheStatusSaysTheOrganisersChooseAndOffersNoNominateButton() {
    $fixture = Council::create(['allow_online_nominations' => 0]);
    $election = findElectionById($fixture->electionId);
    $action = CRM_Elections_Helper_ElectionStatus::nextAction($election, $fixture->id('V01'), 'eid=' . $fixture->electionId, ['canNominate' => TRUE, 'canVote' => TRUE, 'hasVoted' => FALSE, 'voteDate' => NULL, 'allowedToChangeVote' => FALSE]);
    $this->assertStringContainsString('chosen by the organisers', $action['message']);
    $this->assertSame([], $action['actions']);
  }

  public function testACopyKeepsTheSetting() {
    $fixture = Council::create(['allow_online_nominations' => 0]);
    $copy = CRM_Elections_BAO_Election::duplicate($fixture->electionId);
    $this->assertEquals(0, $copy['allow_online_nominations']);
  }

}
