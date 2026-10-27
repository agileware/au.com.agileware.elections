<?php

use CRM_Elections_Test_CouncilFixture as Council;

/**
 * Referendum questions: a yes or no question on the same ballot as the positions. One choice for each voter, counted by
 * a pass threshold and an optional quorum, kept as secret and tamper-evident as every other ballot row.
 *
 * @group headless
 */
class CRM_Elections_ReferendumTest extends api_v3_ElectionBaseTestCase {

  /**
   * The council with a question added before voting.
   */
  private function council(array $question = []) {
    $fixture = Council::create(['allow_revote' => 1, 'anonymize_votes' => 1])->playUntil(Council::STAGE_ADVERTISE);
    $position = $this->addQuestion($fixture, 'Adopt the new constitution?', $question);
    $fixture->moveToStage(Council::STAGE_VOTING);
    return [$fixture, $position];
  }

  /**
   * Add a question the way an administrator does: while the election is not visible to people.
   */
  private function addQuestion(Council $fixture, $name, array $question = []) {
    CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET is_visible = 0 WHERE id = %1', [1 => [$fixture->electionId, 'Integer']]);
    $position = (int) civicrm_api3('ElectionPosition', 'create', $question + [
      'election_id' => $fixture->electionId,
      'name' => $name,
      'quantity' => 1,
      'sortorder' => 20,
      'position_type' => 'referendum',
      'pass_threshold' => 50,
    ])['id'];
    CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET is_visible = 1 WHERE id = %1', [1 => [$fixture->electionId, 'Integer']]);
    return $position;
  }

  private function option($positionId, $kind) {
    foreach (CRM_Elections_BAO_ElectionPosition::getOptions($positionId) as $option) {
      if ($option['kind'] === $kind) {
        return $option['id'];
      }
    }
    return NULL;
  }

  private function choose(Council $fixture, $voter, $positionId, $kind, array $extra = []) {
    return civicrm_api3('ElectionVote', 'addvotes', ['election_id' => $fixture->electionId, 'member_id' => $fixture->id($voter), 'option_votes' => [['position_id' => $positionId, 'option_id' => $this->option($positionId, $kind)]]] + $extra);
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

  private function status(Council $fixture, $positionId) {
    return CRM_Core_DAO::singleValueQuery('SELECT result_status FROM civicrm_election_position WHERE id = %1', [1 => [$positionId, 'Integer']]);
  }

  private function finish(Council $fixture) {
    $fixture->moveToStage(Council::STAGE_RESULTS);
    $fixture->generateResults();
  }

  // ------------------------------------------------------------------ the rule

  public function testMoreThanHalfOfYesAndNoPasses() {
    $decide = fn($yes, $no, $abstain = 0, $threshold = 50, $quorum = NULL, $eligible = 10) => CRM_Elections_Helper_Referendum::decide(['yes' => $yes, 'no' => $no, 'abstain' => $abstain], $threshold, $quorum, $eligible);
    $this->assertSame('passed', $decide(6, 5));
    $this->assertSame('failed', $decide(5, 5), 'A tie fails');
    $this->assertSame('failed', $decide(4, 6));
    $this->assertSame('failed', $decide(0, 0), 'Nobody answered');
    $this->assertSame('passed', $decide(1, 0, 9), 'Abstentions do not count towards the share');
    $this->assertSame('passed', $decide(2, 1, 0, 66.67), 'Exactly two thirds meets two thirds');
    $this->assertSame('failed', $decide(3, 2, 0, 66.67));
    $this->assertSame('passed', $decide(3, 1, 0, 75), 'Three quarters is at least three quarters');
    $this->assertSame('failed', $decide(5, 3, 0, 75));
  }

  public function testAQuorumIsTheShareOfPeopleAllowedToVoteWhoTookPart() {
    $decide = fn($yes, $no, $abstain, $eligible) => CRM_Elections_Helper_Referendum::decide(['yes' => $yes, 'no' => $no, 'abstain' => $abstain], 50, 50, $eligible);
    $this->assertSame('noquorum', $decide(3, 1, 0, 10), '4 of 10 is under half');
    $this->assertSame('passed', $decide(3, 1, 1, 10), 'An abstention counts towards the quorum');
    $this->assertSame('noquorum', $decide(3, 1, 1, 0), 'Nobody is allowed to vote: no quorum');
  }

  // ------------------------------------------------------------------ the question

  public function testAQuestionHasItsYesAndNoAndOptionalAbstain() {
    [$fixture, $position] = $this->council();
    $options = CRM_Elections_BAO_ElectionPosition::getOptions($position);
    $this->assertSame(['yes', 'no'], array_values(array_column($options, 'kind')), 'No Abstain unless it has wording');
    CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET is_visible = 0, voting_start_date = DATE_ADD(NOW(), INTERVAL 5 DAY), voting_end_date = DATE_ADD(NOW(), INTERVAL 9 DAY), nomination_start_date = DATE_ADD(NOW(), INTERVAL 1 DAY) WHERE id = %1', [1 => [$fixture->electionId, 'Integer']]);
    CRM_Elections_BAO_ElectionPosition::saveOptions($position, ['yes' => 'For', 'no' => 'Against', 'abstain' => 'Abstain']);
    $labels = array_column(CRM_Elections_BAO_ElectionPosition::getOptions($position), 'label', 'kind');
    $this->assertSame(['yes' => 'For', 'no' => 'Against', 'abstain' => 'Abstain'], $labels);
    CRM_Elections_BAO_ElectionPosition::saveOptions($position, ['yes' => 'For', 'no' => 'Against', 'abstain' => '']);
    $this->assertCount(2, CRM_Elections_BAO_ElectionPosition::getOptions($position), 'Empty wording removes Abstain');
  }

  public function testTheQuestionIsFrozenOnceTheElectionHasStarted() {
    [$fixture, $position] = $this->council();
    $this->refused(fn() => civicrm_api3('ElectionPosition', 'create', ['id' => $position, 'election_id' => $fixture->electionId, 'pass_threshold' => 75]), 'once election has been started');
    $this->refused(fn() => civicrm_api3('ElectionPosition', 'create', ['id' => $position, 'election_id' => $fixture->electionId, 'position_type' => 'election']), 'once election has been started');
    $this->refused(fn() => CRM_Elections_BAO_ElectionPosition::saveOptions($position, ['yes' => 'Rewritten']), 'once election has been started');
  }

  public function testSettingsAreKeptInRange() {
    $fixture = Council::create();
    CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET is_visible = 0 WHERE id = %1', [1 => [$fixture->electionId, 'Integer']]);
    foreach (['pass_threshold' => [0, 101], 'quorum_percent' => [101, -5]] as $field => $bad) {
      foreach ($bad as $value) {
        $this->refused(fn() => civicrm_api3('ElectionPosition', 'create', [$field => $value, 'election_id' => $fixture->electionId, 'name' => 'Odd', 'quantity' => 1, 'sortorder' => 30, 'position_type' => 'referendum']), 'percentage');
      }
    }
    $this->refused(fn() => civicrm_api3('ElectionPosition', 'create', ['position_type' => 'poll', 'election_id' => $fixture->electionId, 'name' => 'Odd', 'quantity' => 1, 'sortorder' => 30]), 'not a valid option');
  }

  public function testAPositionWithNominationsCannotBecomeAQuestion() {
    $fixture = Council::create();
    $fixture->runNominations();
    CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET is_visible = 0 WHERE id = %1', [1 => [$fixture->electionId, 'Integer']]);
    $this->refused(fn() => civicrm_api3('ElectionPosition', 'create', ['id' => $fixture->positions['President'], 'election_id' => $fixture->electionId, 'position_type' => 'referendum']), 'has nominations');
  }

  public function testNobodyCanBeNominatedOrAddedAsACandidateForAQuestion() {
    $fixture = Council::create();
    $question = $this->addQuestion($fixture, 'Adopt the new constitution?');
    $this->refused(fn() => CRM_Elections_Helper_Process::nominate($fixture->electionId, $question, $fixture->id('N01'), $fixture->id('V01'), 'a question has no candidates'), 'not part of this election');
    $this->refused(fn() => CRM_Elections_BAO_ElectionNomination::addCandidate($question, $fixture->id('N01')), 'no candidates');
  }

  // ------------------------------------------------------------------ the ballot

  public function testAVoterChoosesOneAnswerAndCanChangeIt() {
    [$fixture, $position] = $this->council();
    $this->choose($fixture, 'V01', $position, 'yes');
    $voter = $fixture->id('V01');
    $this->assertSame(1, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_option_vote WHERE member_id = %1', [1 => [$voter, 'Integer']]));
    $this->choose($fixture, 'V01', $position, 'no');
    $this->assertSame(1, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_option_vote WHERE member_id = %1', [1 => [$voter, 'Integer']]), 'The new choice replaces the old');
    $this->assertSame($this->option($position, 'no'), (int) CRM_Core_DAO::singleValueQuery('SELECT election_option_id FROM civicrm_election_option_vote WHERE member_id = %1', [1 => [$voter, 'Integer']]));
    $this->assertTrue(CRM_Elections_Helper_Eligibility::hasVoted($fixture->electionId, $voter), 'A choice on a question is a ballot');
  }

  public function testAVoterCanAnswerAQuestionAndVoteForPeopleOnOneBallot() {
    [$fixture, $position] = $this->council();
    $president = $fixture->nominations['President|N01'];
    $this->choose($fixture, 'V02', $position, 'yes', ['votes' => [['election_nomination_id' => $president, 'rank' => 1]]]);
    $this->assertSame(1, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_vote WHERE member_id = %1', [1 => [$fixture->id('V02'), 'Integer']]));
    $this->assertSame(1, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_option_vote WHERE member_id = %1', [1 => [$fixture->id('V02'), 'Integer']]));
    // Voting again with only the people removes the old answer to the question.
    civicrm_api3('ElectionVote', 'addvotes', ['election_id' => $fixture->electionId, 'member_id' => $fixture->id('V02'), 'votes' => [['election_nomination_id' => $president, 'rank' => 1]]]);
    $this->assertSame(0, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_option_vote WHERE member_id = %1', [1 => [$fixture->id('V02'), 'Integer']]));
  }

  public function testBadChoicesAreRefusedAndNothingIsStored() {
    [$fixture, $position] = $this->council();
    $other = $this->addQuestion($fixture, 'A second question', ['sortorder' => 21]);
    $yes = $this->option($position, 'yes');
    $voter = $fixture->id('V01');
    $ballot = fn(array $choices) => fn() => civicrm_api3('ElectionVote', 'addvotes', ['election_id' => $fixture->electionId, 'member_id' => $voter, 'option_votes' => $choices]);
    $this->refused($ballot([['position_id' => $other, 'option_id' => $yes]]), 'does not belong');
    $this->refused($ballot([['position_id' => $position, 'option_id' => $yes], ['position_id' => $position, 'option_id' => $this->option($position, 'no')]]), 'one choice');
    $this->refused($ballot([['position_id' => $fixture->positions['President'], 'option_id' => $yes]]), 'not a question');
    $this->refused($ballot([['position_id' => $position, 'option_id' => 999999]]), 'does not belong');
    $this->assertSame(0, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_option_vote'));
  }

  public function testTheDatabaseKeepsOneChoiceForEachMemberAndQuestion() {
    [$fixture, $position] = $this->council();
    $this->choose($fixture, 'V01', $position, 'yes');
    $this->refused(fn() => CRM_Core_DAO::executeQuery('INSERT INTO civicrm_election_option_vote (election_option_id, election_position_id, member_id) VALUES (%1, %2, %3)', [1 => [$this->option($position, 'no'), 'Integer'], 2 => [$position, 'Integer'], 3 => [$fixture->id('V01'), 'Integer']]), 'already exists');
  }

  public function testChoicesFollowTheRulesOfEveryOtherBallotRow() {
    [$fixture, $position] = $this->council();
    $yes = $this->option($position, 'yes');
    $this->refused(fn() => civicrm_api4('ElectionOptionVote', 'create', ['values' => ['election_position_id' => $position, 'election_option_id' => $yes, 'member_id' => $fixture->id('V01')], 'checkPermissions' => FALSE]), 'cast through the ballot');
    $this->choose($fixture, 'V01', $position, 'yes');
    $row = (int) CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_election_option_vote WHERE member_id = %1', [1 => [$fixture->id('V01'), 'Integer']]);
    $this->refused(fn() => civicrm_api4('ElectionOptionVote', 'update', ['values' => ['election_option_id' => $this->option($position, 'no')], 'where' => [['id', '=', $row]], 'checkPermissions' => FALSE]), 'cannot be changed');
    $fixture->moveToStage(Council::STAGE_RESULTS);
    $this->refused(fn() => civicrm_api4('ElectionOptionVote', 'delete', ['where' => [['id', '=', $row]], 'checkPermissions' => FALSE]), 'while voting is open');
  }

  public function testAVoterOfAQuestionCannotBeDeleted() {
    [$fixture, $position] = $this->council();
    $this->choose($fixture, 'V01', $position, 'yes');
    $this->refused(fn() => civicrm_api3('Contact', 'delete', ['id' => $fixture->id('V01'), 'skip_undelete' => 1]), 'Council Election');
  }

  // ------------------------------------------------------------------ results

  public function testTheQuestionIsCountedAndTheChoicesAreAnonymised() {
    [$fixture, $position] = $this->council(['quorum_percent' => 25]);
    $voters = array_slice(array_keys($fixture->members()), 0, 12);
    foreach ($voters as $i => $voter) {
      // Seven yes, four no, one abstention would need an abstain option; leave that one unanswered.
      if ($i < 7) {
        $this->choose($fixture, $voter, $position, 'yes');
      }
      elseif ($i < 11) {
        $this->choose($fixture, $voter, $position, 'no');
      }
    }
    $this->finish($fixture);
    $this->assertSame('passed', $this->status($fixture, $position));
    $this->assertSame(11, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_option_vote'));
    $this->assertSame(0, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_option_vote WHERE member_id IS NOT NULL'), 'No voter is kept');
    $closed = CRM_Core_DAO::singleValueQuery('SELECT voting_end_date FROM civicrm_election WHERE id = %1', [1 => [$fixture->electionId, 'Integer']]);
    $this->assertSame(0, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_option_vote WHERE created_at <> %1', [1 => [$closed, 'String']]), 'The time of each choice is the time voting closed');
  }

  public function testAQuestionThatMissesItsQuorumIsNotDecided() {
    [$fixture, $position] = $this->council(['quorum_percent' => 90]);
    foreach (['V01', 'V02', 'V03'] as $voter) {
      $this->choose($fixture, $voter, $position, 'yes');
    }
    $this->finish($fixture);
    $this->assertSame('noquorum', $this->status($fixture, $position));
  }

  public function testAQuestionThatTiesFails() {
    [$fixture, $position] = $this->council();
    $this->choose($fixture, 'V01', $position, 'yes');
    $this->choose($fixture, 'V02', $position, 'no');
    $this->finish($fixture);
    $this->assertSame('failed', $this->status($fixture, $position));
  }

  public function testPeopleAreStillCountedOnTheSameBallot() {
    [$fixture, $position] = $this->council();
    $fixture->castBallots();
    $this->choose($fixture, 'V01', $position, 'yes', ['votes' => [['election_nomination_id' => $fixture->nominations['President|N01'], 'rank' => 1]]]);
    $this->finish($fixture);
    $this->assertSame('passed', $this->status($fixture, $position));
    $this->assertSame(CRM_Elections_BAO_Election::$RESULTS_MAJORITY, CRM_Core_DAO::singleValueQuery('SELECT result_status FROM civicrm_election_position WHERE id = %1', [1 => [$fixture->positions['President'], 'Integer']]));
    $this->assertSame(0, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_result WHERE election_position_id = %1', [1 => [$position, 'Integer']]), 'A question has no ranked results');
  }

  public function testTheFingerprintCoversTheChoices() {
    [$fixture, $position] = $this->council();
    $this->choose($fixture, 'V01', $position, 'yes');
    $this->choose($fixture, 'V02', $position, 'yes');
    $this->choose($fixture, 'V03', $position, 'no');
    $this->finish($fixture);
    $this->assertSame('ok', CRM_Elections_Helper_Integrity::verify($fixture->electionId)['status']);
    // Someone with database access turns a no into a yes.
    CRM_Core_DAO::executeQuery('UPDATE civicrm_election_option_vote SET election_option_id = %1 WHERE election_option_id = %2', [1 => [$this->option($position, 'yes'), 'Integer'], 2 => [$this->option($position, 'no'), 'Integer']]);
    $this->assertSame('mismatch', CRM_Elections_Helper_Integrity::verify($fixture->electionId)['status']);
  }

  public function testTurnoutCountsQuestionOnlyBallots() {
    [$fixture, $position] = $this->council();
    $this->choose($fixture, 'V01', $position, 'yes');
    $this->choose($fixture, 'V02', $position, 'no');
    $counts = CRM_Elections_BAO_Election::getCounts($fixture->electionId);
    $this->assertSame(2, $counts['ballots']);
    $turnout = array_column(CRM_Elections_BAO_Election::getTurnout($fixture->electionId), NULL, 'name');
    $this->assertTrue($turnout['Adopt the new constitution?']['isQuestion']);
    $this->assertSame(2, $turnout['Adopt the new constitution?']['ballots']);
  }

  public function testCopiesKeepTheQuestionAndItsWording() {
    [$fixture, $position] = $this->council(['pass_threshold' => 66.67, 'quorum_percent' => 40, 'resolution_text' => 'That the rules be replaced.']);
    CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET is_visible = 0 WHERE id = %1', [1 => [$fixture->electionId, 'Integer']]);
    $copy = CRM_Elections_BAO_Election::duplicate($fixture->electionId);
    $copied = CRM_Core_DAO::executeQuery('SELECT * FROM civicrm_election_position WHERE election_id = %1 AND position_type = "referendum"', [1 => [(int) $copy['id'], 'Integer']])->fetchAll();
    $this->assertCount(1, $copied);
    $this->assertEquals(66.67, $copied[0]['pass_threshold']);
    $this->assertEquals(40, $copied[0]['quorum_percent']);
    $this->assertSame('That the rules be replaced.', $copied[0]['resolution_text']);
    $this->assertCount(2, CRM_Elections_BAO_ElectionPosition::getOptions((int) $copied[0]['id']));
    $again = CRM_Elections_BAO_ElectionPosition::duplicate($position);
    $this->assertSame('referendum', $again['position_type']);
    $this->assertCount(2, CRM_Elections_BAO_ElectionPosition::getOptions((int) $again['id']));
  }

}
