<?php

use CRM_Elections_Test_CouncilFixture as Council;

/**
 * Write-ins: a voter names a person who is not on the ballot (where the position allows it). The person is told,
 * and votes for them count only if they accept before voting closes. Nothing may show who wrote whom in.
 *
 * @group headless
 */
class CRM_Elections_WriteInTest extends api_v3_ElectionBaseTestCase {

  public function setUp(): void {
    parent::setUp();
    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();
    Civi::settings()->set('mailing_backend', ['outBound_option' => CRM_Mailing_Config::OUTBOUND_OPTION_REDIRECT_TO_DB]);
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_mailing_spool');
  }

  public function tearDown(): void {
    Civi::settings()->revert('mailing_backend');
    parent::tearDown();
  }

  /**
   * The council at the voting stage with President open to two write-ins for each seat (and Treasurer closed).
   */
  private function voting($perSeat = 2) {
    $fixture = Council::create(['allow_revote' => 1])->playUntil(Council::STAGE_ADVERTISE);
    CRM_Core_DAO::executeQuery('UPDATE civicrm_election_position SET write_ins_per_seat = %2 WHERE id = %1', [1 => [$fixture->positions['President'], 'Integer'], 2 => [$perSeat, 'Integer']]);
    civicrm_api3('Email', 'create', ['contact_id' => $fixture->id('N09'), 'email' => 'n09.writein@example.org', 'is_primary' => 1, 'location_type_id' => 1]);
    $fixture->moveToStage(Council::STAGE_VOTING);
    return $fixture;
  }

  private function mails() {
    return CRM_Core_DAO::executeQuery('SELECT recipient_email, headers, body FROM civicrm_mailing_spool ORDER BY id')->fetchAll();
  }

  private function nomination(Council $fixture, $position, $persona) {
    return (int) CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_election_nomination WHERE election_position_id = %1 AND member_nominee = %2', [1 => [$fixture->positions[$position], 'Integer'], 2 => [$fixture->id($persona), 'Integer']]);
  }

  private function ballot(Council $fixture, $voter, array $ranks, array $writeIns = []) {
    $votes = [];
    foreach ($ranks as $persona => $rank) {
      $votes[] = ['election_nomination_id' => $fixture->nominations["President|$persona"], 'rank' => $rank];
    }
    $writes = [];
    foreach ($writeIns as $persona => $rank) {
      $writes[] = ['position_id' => $fixture->positions['President'], 'contact_id' => $fixture->id($persona), 'rank' => $rank];
    }
    return civicrm_api3('ElectionVote', 'addvotes', ['election_id' => $fixture->electionId, 'member_id' => $fixture->id($voter), 'votes' => $votes, 'write_ins' => $writes]);
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

  private function results(Council $fixture) {
    $fixture->moveToStage(Council::STAGE_RESULTS);
    $fixture->generateResults();
    return CRM_Core_DAO::executeQuery('SELECT n.member_nominee, r.`rank` FROM civicrm_election_result r INNER JOIN civicrm_election_nomination n ON n.id = r.election_nomination_id WHERE r.election_position_id = %1', [1 => [$fixture->positions['President'], 'Integer']])->fetchMap('member_nominee', 'rank');
  }

  // ------------------------------------------------------------------ limits

  public function testWriteInsAreOffUnlessThePositionAllowsThem() {
    $fixture = $this->voting(0);
    $this->refused(fn() => $this->ballot($fixture, 'V01', ['N01' => 1], ['N09' => 2]), 'not allowed for this position');
  }

  public function testAVoterCannotWriteInMoreThanThePositionAllows() {
    $fixture = $this->voting(2);
    $this->refused(fn() => $this->ballot($fixture, 'V01', ['N01' => 1], ['N09' => 2, 'N10' => 3, 'N07' => 4]), 'more write-ins');
    $this->assertSame(0, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_vote'));
    $this->assertSame(0, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_nomination WHERE is_write_in = 1'), 'Nobody is written in by a refused ballot');
    $this->assertCount(0, $this->mails());
  }

  public function testOnlyPeopleWhoCanStandCanBeWrittenIn() {
    $fixture = $this->voting();
    $this->refused(fn() => $this->ballot($fixture, 'V01', ['N01' => 1], ['OUT' => 2]), 'cannot be written in');
  }

  public function testTheNumberPerSeatIsKeptFromZeroToTen() {
    $election = civicrm_api3('Election', 'create', ['name' => 'Positions'] + $this->plainDates() + ['is_visible' => 0]);
    foreach ([-1, 11] as $bad) {
      $this->refused(fn() => civicrm_api3('ElectionPosition', 'create', ['election_id' => $election['id'], 'name' => 'Odd', 'quantity' => 1, 'sortorder' => 9, 'write_ins_per_seat' => $bad]), 'from 0 to 10');
    }
    $position = civicrm_api3('ElectionPosition', 'create', ['election_id' => $election['id'], 'name' => 'Ten', 'quantity' => 1, 'sortorder' => 1, 'write_ins_per_seat' => 10]);
    $this->assertSame(10, (int) CRM_Core_DAO::singleValueQuery('SELECT write_ins_per_seat FROM civicrm_election_position WHERE id = %1', [1 => [(int) $position['id'], 'Integer']]));
  }

  private function plainDates() {
    $keys = ['visibility_start_date' => -10, 'nomination_start_date' => 2, 'nomination_end_date' => 5, 'advertise_candidates_date' => 6, 'voting_start_date' => 10, 'voting_end_date' => 20, 'result_date' => 25, 'visibility_end_date' => 60];
    return array_map(fn($days) => date('Y-m-d H:i:s', strtotime(($days >= 0 ? '+' : '') . $days . ' days')), $keys);
  }

  // ------------------------------------------------------------------ writing in

  public function testWritingSomeoneInRecordsThemOnceAndTellsThemOnce() {
    $fixture = $this->voting();
    $this->ballot($fixture, 'V01', ['N01' => 1], ['N09' => 2]);
    $this->ballot($fixture, 'V02', ['N02' => 1], ['N09' => 2]);

    $rows = CRM_Core_DAO::executeQuery('SELECT * FROM civicrm_election_nomination WHERE is_write_in = 1')->fetchAll();
    $this->assertCount(1, $rows, 'One record for the person however many voters write them in');
    $this->assertEquals($fixture->id('N09'), $rows[0]['member_nominee']);
    $this->assertEquals(1, $rows[0]['is_eligible_candidate']);
    $this->assertEquals(0, (int) $rows[0]['has_accepted_nomination']);
    $this->assertSame(2, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_vote WHERE election_nomination_id = %1', [1 => [(int) $rows[0]['id'], 'Integer']]));

    $mails = $this->mails();
    $this->assertCount(1, $mails, 'The person is told once');
    $this->assertStringContainsString('n09.writein@example.org', $mails[0]['recipient_email']);
    $this->assertStringContainsString('written you in', $mails[0]['body']);
    $this->assertStringContainsString('nominations/accept', $mails[0]['body']);
    $this->assertStringContainsString('nominations/reject', $mails[0]['body']);
  }

  public function testNothingShowsWhoWroteWhomIn() {
    $fixture = $this->voting();
    $voter = $fixture->id('V01');
    $this->ballot($fixture, 'V01', ['N01' => 1], ['N09' => 2]);
    $nomination = $this->nomination($fixture, 'President', 'N09');
    $row = CRM_Core_DAO::executeQuery('SELECT created_at, updated_at, write_in_notified_date FROM civicrm_election_nomination WHERE id = %1', [1 => [$nomination, 'Integer']])->fetchAll()[0];
    $opened = CRM_Core_DAO::singleValueQuery('SELECT voting_start_date FROM civicrm_election WHERE id = %1', [1 => [$fixture->electionId, 'Integer']]);
    $this->assertSame($opened, $row['created_at'], 'The time of the first write-in is not kept');
    $this->assertSame($opened, $row['updated_at']);
    $this->assertSame(date('Y-m-d 00:00:00'), $row['write_in_notified_date'], 'Only the day the person was told');
    $this->assertSame(0, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_audit WHERE election_id = %1 AND (contact_id = %2 OR details LIKE %3)', [1 => [$fixture->electionId, 'Integer'], 2 => [$voter, 'Integer'], 3 => ['%"nomination_id":' . $nomination . '%', 'String']]), 'The audit log does not record the write-in or the voter');
  }

  public function testAWriteInWhoIsAlreadyACandidateIsAnOrdinaryVote() {
    $fixture = $this->voting();
    $this->ballot($fixture, 'V01', [], ['N01' => 1, 'N02' => 2]);
    $this->assertSame(0, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_nomination WHERE is_write_in = 1'));
    $this->assertSame(2, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_vote WHERE member_id = %1', [1 => [$fixture->id('V01'), 'Integer']]));
    $this->assertCount(0, $this->mails());
  }

  public function testTheSamePersonCannotBeRankedTwice() {
    $fixture = $this->voting();
    $this->refused(fn() => $this->ballot($fixture, 'V01', ['N01' => 1], ['N01' => 2]), 'only be ranked once');
  }

  // ------------------------------------------------------------------ searching

  private function search(Council $fixture, $voter, $input) {
    CRM_Core_Session::singleton()->set('userID', $fixture->id($voter));
    try {
      return civicrm_api3('ElectionNominee', 'getlist', ['election_id' => $fixture->electionId, 'input' => $input, 'page_num' => 1, 'write_in' => 1]);
    }
    finally {
      CRM_Core_Session::singleton()->set('userID', NULL);
    }
  }

  public function testTheSearchNeedsAFewLettersAndShowsOnlyNames() {
    $fixture = $this->voting();
    $this->assertCount(0, $this->search($fixture, 'V01', 'No')['values'], 'Two letters find nothing');
    $found = $this->search($fixture, 'V01', 'Nominee');
    $this->assertGreaterThan(5, count($found['values']));
    foreach ($found['values'] as $row) {
      $this->assertArrayNotHasKey('description', $row);
      $this->assertArrayNotHasKey('email', $row);
    }
  }

  public function testTheSearchIsLimitedAndOnlyForVotersWhileVotingIsOpen() {
    $fixture = $this->voting();
    $this->assertCount(0, $this->search($fixture, 'OUT', 'Nominee')['values'], 'Not someone who can vote');
    for ($i = 0; $i < 30; $i++) {
      $this->search($fixture, 'V01', 'Nominee');
    }
    $this->assertCount(0, $this->search($fixture, 'V01', 'Nominee')['values'], 'A limit on searches a minute');
    $closed = $this->voting();
    $closed->moveToStage(Council::STAGE_RESULTS);
    $this->assertCount(0, $this->search($closed, 'V02', 'Nominee')['values'], 'Not once voting has closed');
  }

  // ------------------------------------------------------------------ answering

  public function testAPersonWhoAcceptsBecomesACandidateAndTheirVotesCount() {
    $fixture = $this->voting();
    foreach (['V01', 'V02', 'V03', 'V04'] as $voter) {
      $this->ballot($fixture, $voter, ['N01' => 2], ['N09' => 1]);
    }
    $this->ballot($fixture, 'V05', ['N02' => 1]);
    $this->ballot($fixture, 'V06', ['N02' => 1]);
    $nomination = $this->nomination($fixture, 'President', 'N09');
    CRM_Elections_Helper_Process::accept($nomination, $fixture->id('N09'), 'Written in and accepted');
    $this->assertEquals(1, CRM_Core_DAO::singleValueQuery('SELECT has_accepted_nomination FROM civicrm_election_nomination WHERE id = %1', [1 => [$nomination, 'Integer']]));

    // Later voters can vote for them like any other candidate.
    $fixture->reloadNominations();
    $this->ballot($fixture, 'V07', ['N09' => 1]);

    $ranks = $this->results($fixture);
    $this->assertSame(1, (int) $ranks[$fixture->id('N09')], 'Five votes for the person written in win');
  }

  public function testVotesForAPersonWhoDoesNotAnswerAreDroppedAndTheBallotMovesOn() {
    $fixture = $this->voting();
    // V01 writes N09 in first and ranks N01 second; V02 ranks N01; V03 ranks N02.
    $this->ballot($fixture, 'V01', ['N01' => 2], ['N09' => 1]);
    $this->ballot($fixture, 'V02', ['N01' => 1]);
    $this->ballot($fixture, 'V03', ['N02' => 1]);
    $ranks = $this->results($fixture);
    $this->assertArrayNotHasKey($fixture->id('N09'), $ranks, 'Not a candidate: not in the results');
    $this->assertSame(1, (int) $ranks[$fixture->id('N01')], 'The first choice that is not a candidate hands the vote to the second choice');
    $summary = CRM_Core_DAO::singleValueQuery('SELECT details FROM civicrm_election_audit WHERE election_id = %1 AND action = "write_ins_summary"', [1 => [$fixture->electionId, 'Integer']]);
    $this->assertStringContainsString('"unanswered":1', $summary);
    $this->assertStringNotContainsString('"member', $summary);
  }

  public function testAPersonWhoDeclinesCannotBeWrittenInAgain() {
    $fixture = $this->voting();
    $this->ballot($fixture, 'V01', ['N01' => 2], ['N09' => 1]);
    CRM_Elections_Helper_Process::withdraw($this->nomination($fixture, 'President', 'N09'), $fixture->id('N09'), 'No thanks');
    $this->refused(fn() => $this->ballot($fixture, 'V02', ['N01' => 2], ['N09' => 1]), 'declined or withdrawn');
    $ranks = $this->results($fixture);
    $this->assertArrayNotHasKey($fixture->id('N09'), $ranks);
  }

  public function testAPersonWhoAcceptedCannotWithdrawOnceVotingHasStarted() {
    $fixture = $this->voting();
    $this->ballot($fixture, 'V01', ['N01' => 2], ['N09' => 1]);
    $nomination = $this->nomination($fixture, 'President', 'N09');
    CRM_Elections_Helper_Process::accept($nomination, $fixture->id('N09'));
    $this->refused(fn() => CRM_Elections_Helper_Process::withdraw($nomination, $fixture->id('N09')), 'once voting is started');
  }

  public function testAPersonWrittenInCannotAnswerOnceVotingHasClosed() {
    $fixture = $this->voting();
    $this->ballot($fixture, 'V01', ['N01' => 2], ['N09' => 1]);
    $nomination = $this->nomination($fixture, 'President', 'N09');
    $fixture->moveToStage(Council::STAGE_RESULTS);
    $this->refused(fn() => CRM_Elections_Helper_Process::accept($nomination, $fixture->id('N09')), 'Voting has closed');
  }

  public function testOnlyTheWriteInProcessCanChangeWhoIsStandingOnceVotingHasStarted() {
    $fixture = $this->voting();
    $this->ballot($fixture, 'V01', ['N01' => 2], ['N09' => 1]);
    $nomination = $this->nomination($fixture, 'President', 'N09');
    $this->refused(fn() => civicrm_api3('ElectionNomination', 'create', ['id' => $nomination, 'has_accepted_nomination' => 1]), 'cannot be changed once voting has started');
    $this->refused(function () use ($fixture) {
      \Civi\Api4\ElectionNomination::create(FALSE)->addValue('election_position_id', $fixture->positions['President'])->addValue('member_nominee', $fixture->id('N10'))->addValue('is_eligible_candidate', TRUE)->addValue('has_accepted_nomination', TRUE)->execute();
    }, 'cannot be changed once voting has started');
  }

  // ------------------------------------------------------------------ reminders

  public function testAPersonWhoHasNotAnsweredIsRemindedOnceBeforeVotingCloses() {
    $fixture = $this->voting();
    $this->ballot($fixture, 'V01', ['N01' => 2], ['N09' => 1]);
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_mailing_spool');
    $this->assertSame(0, CRM_Elections_Helper_Mailer::sendWriteInReminders(), 'Voting closes in days: too early');
    CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET voting_end_date = %2 WHERE id = %1', [1 => [$fixture->electionId, 'Integer'], 2 => [date('Y-m-d H:i:s', time() + 3600 * 5), 'String']]);
    $this->assertSame(1, CRM_Elections_Helper_Mailer::sendWriteInReminders());
    $this->assertSame(0, CRM_Elections_Helper_Mailer::sendWriteInReminders(), 'Only once');
    $this->assertStringContainsString('reminder', strtolower($this->mails()[0]['body']));
  }

  public function testAnAcceptedWriteInIsNotReminded() {
    $fixture = $this->voting();
    $this->ballot($fixture, 'V01', ['N01' => 2], ['N09' => 1]);
    CRM_Elections_Helper_Process::accept($this->nomination($fixture, 'President', 'N09'), $fixture->id('N09'));
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_mailing_spool');
    CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET voting_end_date = %2 WHERE id = %1', [1 => [$fixture->electionId, 'Integer'], 2 => [date('Y-m-d H:i:s', time() + 3600 * 5), 'String']]);
    $this->assertSame(0, CRM_Elections_Helper_Mailer::sendWriteInReminders());
  }

}
