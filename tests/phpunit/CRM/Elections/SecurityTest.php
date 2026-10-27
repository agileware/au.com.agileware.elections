<?php

/**
 * The election must not be able to be subverted: ballots cannot be added, changed or removed outside the
 * voting window, results cannot be written or reset, settings that decide who votes and how votes count
 * cannot change once people have voted, anonymisation leaves nothing that identifies a voter, and
 * anything changed behind the extension's back is found.
 *
 * @group headless
 */
class CRM_Elections_SecurityTest extends api_v3_ElectionBaseTestCase {

  private function fixture($stage) {
    return CRM_Elections_Test_CouncilFixture::create()->playUntil($stage);
  }

  private function refused(callable $change, $needle, $message = '') {
    try {
      $change();
    }
    catch (\Throwable $e) {
      $this->assertStringContainsStringIgnoringCase($needle, $e->getMessage(), $message ?: 'Refused for the right reason');
      return;
    }
    $this->fail('Expected the change to be refused: ' . $message);
  }

  private function firstVote($electionId) {
    $dao = CRM_Core_DAO::executeQuery('SELECT v.id, v.election_nomination_id, v.member_id, v.`rank` FROM civicrm_election_vote v INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id INNER JOIN civicrm_election_position p ON p.id = n.election_position_id WHERE p.election_id = %1 ORDER BY v.id LIMIT 1', [1 => [$electionId, 'Integer']]);
    $dao->fetch();
    return $dao->toArray();
  }

  // ------------------------------------------------------------------ ballots

  public function testBallotsCanOnlyBeAddedWhileVotingIsOpen() {
    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_ADVERTISE);
    $nomination = (int) CRM_Core_DAO::singleValueQuery('SELECT n.id FROM civicrm_election_nomination n INNER JOIN civicrm_election_position p ON p.id = n.election_position_id WHERE p.election_id = %1 AND n.is_eligible_candidate = 1 LIMIT 1', [1 => [$fixture->electionId, 'Integer']]);
    $this->refused(function () use ($nomination, $fixture) {
      civicrm_api3('ElectionVote', 'create', ['election_nomination_id' => $nomination, 'rank' => 1, 'member_id' => $fixture->id('V01')]);
    }, 'while voting is open', 'a vote before voting opens');
  }

  public function testABallotRowCanOnlyBeAddedThroughTheBallotCheck() {
    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_ADVERTISE);
    $fixture->moveToStage(CRM_Elections_Test_CouncilFixture::STAGE_VOTING);
    $nomination = (int) $fixture->nominations['President|N01'];
    $this->refused(function () use ($nomination, $fixture) {
      civicrm_api3('ElectionVote', 'create', ['election_nomination_id' => $nomination, 'rank' => 1, 'member_id' => $fixture->id('V01')]);
    }, 'cast through the ballot', 'a vote written directly while voting is open');
    $this->refused(function () use ($nomination, $fixture) {
      \Civi\Api4\ElectionVote::create(FALSE)->addValue('election_nomination_id', $nomination)->addValue('rank', 1)->addValue('member_id', $fixture->id('V01'))->execute();
    }, 'cast through the ballot', 'a vote written directly through APIv4');
    $this->refused(function () use ($nomination, $fixture) {
      // Even a member who may not vote cannot be given a ballot.
      civicrm_api3('ElectionVote', 'addvotes', ['election_id' => $fixture->electionId, 'member_id' => $fixture->id('OUT'), 'votes' => [['election_nomination_id' => $nomination, 'rank' => 1]]]);
    }, 'not allowed to vote');
    $this->assertSame(0, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_vote'));
    CRM_Elections_Helper_Process::vote($fixture->electionId, $fixture->id('V01'), [$nomination => 1]);
    $this->assertSame(1, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_vote'));
  }

  public function testABallotCannotBeEditedAndCannotBeRemovedOutsideVoting() {
    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_VOTING);
    $vote = $this->firstVote($fixture->electionId);
    $this->refused(function () use ($vote) {
      civicrm_api3('ElectionVote', 'create', ['id' => $vote['id'], 'rank' => 2, 'election_nomination_id' => $vote['election_nomination_id'], 'member_id' => $vote['member_id']]);
    }, 'cannot be changed', 'editing a vote');
    $this->refused(function () use ($vote) {
      \Civi\Api4\ElectionVote::update(FALSE)->addWhere('id', '=', $vote['id'])->addValue('rank', 3)->execute();
    }, 'cannot be changed', 'editing a vote through APIv4');

    $fixture->moveToStage(CRM_Elections_Test_CouncilFixture::STAGE_RESULTS);
    $this->refused(function () use ($vote) {
      civicrm_api3('ElectionVote', 'delete', ['id' => $vote['id']]);
    }, 'while voting is open', 'removing a vote after voting closed');
  }

  public function testTheDatabaseRefusesASecondRowForTheSameMemberAndCandidate() {
    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_VOTING);
    $vote = $this->firstVote($fixture->electionId);
    $this->refused(function () use ($vote) {
      CRM_Core_DAO::executeQuery('INSERT INTO civicrm_election_vote (election_nomination_id, `rank`, member_id) VALUES (%1, 2, %2)', [1 => [(int) $vote['election_nomination_id'], 'Integer'], 2 => [(int) $vote['member_id'], 'Integer']]);
    }, 'already exists', 'a duplicate row');
  }

  public function testARankCannotBeHigherThanTheNumberOfCandidates() {
    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_VOTING);
    $nomination = (int) CRM_Core_DAO::singleValueQuery('SELECT n.id FROM civicrm_election_nomination n INNER JOIN civicrm_election_position p ON p.id = n.election_position_id WHERE p.election_id = %1 AND n.is_eligible_candidate = 1 AND n.has_accepted_nomination = 1 AND IFNULL(n.has_rejected_nomination, 0) = 0 LIMIT 1', [1 => [$fixture->electionId, 'Integer']]);
    $this->refused(function () use ($fixture, $nomination) {
      civicrm_api3('ElectionVote', 'addvotes', ['election_id' => $fixture->electionId, 'member_id' => $fixture->id('V29'), 'votes' => [['election_nomination_id' => $nomination, 'rank' => 99]]]);
    }, 'higher than the number of candidates');
  }

  public function testAMemberWhoVotesAgainReplacesTheBallotAndNeverCountsTwice() {
    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_VOTING);
    civicrm_api3('Election', 'create', ['id' => $fixture->electionId, 'allow_revote' => 1] + civicrm_api3('Election', 'getsingle', ['id' => $fixture->electionId]));
    $voter = $fixture->id('V01');
    $before = (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_vote WHERE member_id = %1', [1 => [$voter, 'Integer']]);
    $this->assertGreaterThan(0, $before);
    $rows = CRM_Core_DAO::executeQuery('SELECT election_nomination_id, `rank` FROM civicrm_election_vote WHERE member_id = %1', [1 => [$voter, 'Integer']])->fetchAll();
    $votes = array_map(fn($r) => ['election_nomination_id' => $r['election_nomination_id'], 'rank' => $r['rank']], $rows);
    civicrm_api3('ElectionVote', 'addvotes', ['election_id' => $fixture->electionId, 'member_id' => $voter, 'votes' => $votes]);
    civicrm_api3('ElectionVote', 'addvotes', ['election_id' => $fixture->electionId, 'member_id' => $voter, 'votes' => $votes]);
    $this->assertSame($before, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_vote WHERE member_id = %1', [1 => [$voter, 'Integer']]));
    $removed = (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_audit WHERE election_id = %1 AND action = "vote_removed"', [1 => [$fixture->electionId, 'Integer']]);
    $this->assertSame(0, $removed, 'A voter replacing their own ballot is not an administrator removing votes');
  }

  // ------------------------------------------------------------------ nominations

  public function testANominatorCanNeverCountTwice() {
    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_NOMINATIONS);
    $positionId = (int) CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_election_position WHERE election_id = %1 ORDER BY sortorder LIMIT 1', [1 => [$fixture->electionId, 'Integer']]);
    $nominee = $fixture->id('N09');
    $nominator = $fixture->id('V07');
    $this->assertGreaterThan(1, (int) CRM_Core_DAO::singleValueQuery('SELECT required_nominations FROM civicrm_election WHERE id = %1', [1 => [$fixture->electionId, 'Integer']]));

    $first = CRM_Elections_Helper_Process::nominate($fixture->electionId, $positionId, $nominee, $nominator, 'first');
    $again = CRM_Elections_Helper_Process::nominate($fixture->electionId, $positionId, $nominee, $nominator, 'again');
    $this->assertTrue($again['already_nominated']);
    $this->assertFalse($again['is_eligible_candidate'], 'One nominator, however many requests, is one nomination');
    $this->assertSame(1, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_nomination_seconder WHERE election_nomination_id = %1', [1 => [$first['nomination_id'], 'Integer']]));

    // Even a row written straight to the database is refused.
    $this->refused(function () use ($first, $nominator) {
      CRM_Core_DAO::executeQuery('INSERT INTO civicrm_election_nomination_seconder (member_nominator, election_nomination_id, description) VALUES (%1, %2, "again")', [1 => [$nominator, 'Integer'], 2 => [(int) $first['nomination_id'], 'Integer']]);
    }, 'already exists', 'a second row for the same nominator');
    $this->refused(function () use ($positionId, $nominee) {
      CRM_Core_DAO::executeQuery('INSERT INTO civicrm_election_nomination (election_position_id, member_nominee) VALUES (%1, %2)', [1 => [$positionId, 'Integer'], 2 => [$nominee, 'Integer']]);
    }, 'already exists', 'a second nomination for the same person and position');

    // A second, different nominator does count.
    $second = CRM_Elections_Helper_Process::nominate($fixture->electionId, $positionId, $nominee, $fixture->id('V08'), 'second');
    $this->assertTrue($second['is_eligible_candidate']);
  }

  public function testNominationsOutsideTheRulesAreRefused() {
    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_NOMINATIONS);
    $positionId = (int) CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_election_position WHERE election_id = %1 ORDER BY sortorder LIMIT 1', [1 => [$fixture->electionId, 'Integer']]);
    // Someone who is not allowed to take part cannot nominate, or be nominated.
    $this->refused(function () use ($fixture, $positionId) {
      CRM_Elections_Helper_Process::nominate($fixture->electionId, $positionId, $fixture->id('N01'), $fixture->id('OUT'));
    }, 'not allowed to nominate');
    $this->refused(function () use ($fixture, $positionId) {
      CRM_Elections_Helper_Process::nominate($fixture->electionId, $positionId, $fixture->id('OUT'), $fixture->id('V01'));
    }, 'can be nominated');
    // A position of another election.
    $other = CRM_Elections_Test_CouncilFixture::create(['name' => 'Another council']);
    $otherPosition = (int) CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_election_position WHERE election_id = %1 LIMIT 1', [1 => [$other->electionId, 'Integer']]);
    $this->refused(function () use ($fixture, $otherPosition) {
      CRM_Elections_Helper_Process::nominate($fixture->electionId, $otherPosition, $fixture->id('N01'), $fixture->id('V01'));
    }, 'not part of this election');
    // Not while nominations are closed.
    $fixture->moveToStage(CRM_Elections_Test_CouncilFixture::STAGE_ADVERTISE);
    $this->refused(function () use ($fixture, $positionId) {
      CRM_Elections_Helper_Process::nominate($fixture->electionId, $positionId, $fixture->id('N01'), $fixture->id('V01'));
    }, 'not open');
  }

  public function testNomineesCanOnlyActOnTheirOwnNominations() {
    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_ADVERTISE);
    $nomination = CRM_Core_DAO::executeQuery('SELECT id, member_nominee FROM civicrm_election_nomination WHERE is_eligible_candidate = 1 AND IFNULL(has_accepted_nomination, 0) = 0 AND IFNULL(has_rejected_nomination, 0) = 0 AND election_position_id IN (SELECT id FROM civicrm_election_position WHERE election_id = %1) LIMIT 1', [1 => [$fixture->electionId, 'Integer']]);
    $nomination->fetch();
    $someoneElse = $fixture->id('V01');
    $this->refused(function () use ($nomination, $someoneElse) {
      CRM_Elections_Helper_Process::accept($nomination->id, $someoneElse, 'I accept for them');
    }, 'not authorized', 'accepting for someone else');
    $this->refused(function () use ($nomination, $someoneElse) {
      CRM_Elections_Helper_Process::withdraw($nomination->id, $someoneElse, 'I withdraw them');
    }, 'not authorized', 'withdrawing someone else');
    $this->refused(function () use ($nomination) {
      CRM_Elections_Helper_Process::accept($nomination->id, NULL);
    }, 'not authorized', 'no one');
  }

  // ------------------------------------------------------------------ results

  public function testResultsCannotBeWrittenOrResetByAnythingElse() {
    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_RESULTS);
    $result = CRM_Core_DAO::executeQuery('SELECT * FROM civicrm_election_result WHERE election_position_id IN (SELECT id FROM civicrm_election_position WHERE election_id = %1) LIMIT 1', [1 => [$fixture->electionId, 'Integer']]);
    $result->fetch();
    $this->refused(function () use ($result) {
      civicrm_api3('ElectionResult', 'create', ['id' => $result->id, 'rank' => 5, 'election_position_id' => $result->election_position_id, 'election_nomination_id' => $result->election_nomination_id]);
    }, 'when they are generated', 'editing a result');
    $this->refused(function () use ($result) {
      \Civi\Api4\ElectionResult::delete(FALSE)->addWhere('id', '=', $result->id)->execute();
    }, 'when they are generated', 'deleting a result');
    $this->refused(function () use ($fixture) {
      \Civi\Api4\Election::update(FALSE)->addWhere('id', '=', $fixture->electionId)->addValue('has_results_generated', FALSE)->execute();
    }, 'cannot be changed', 'resetting the results so they are generated again');
  }

  public function testResultsAreNotGeneratedBeforeVotingEndsAndOnlyOnce() {
    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_VOTING);
    $this->refused(function () use ($fixture) {
      (new CRM_Elections_Helper_Results())->generateResult($fixture->electionId, 0);
    }, 'once voting has ended', 'generating during voting');

    $fixture->moveToStage(CRM_Elections_Test_CouncilFixture::STAGE_RESULTS);
    $fixture->generateResults();
    $entries = (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_audit WHERE election_id = %1 AND action = "results_generated"', [1 => [$fixture->electionId, 'Integer']]);
    $this->assertSame(1, $entries);
    $before = CRM_Elections_Helper_Integrity::resultsHash($fixture->electionId);
    $fixture->generateResults();
    $this->assertSame($before, CRM_Elections_Helper_Integrity::resultsHash($fixture->electionId));
    $this->assertSame($entries, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_audit WHERE election_id = %1 AND action = "results_generated"', [1 => [$fixture->electionId, 'Integer']]), 'Generating twice changes nothing');
  }

  public function testChangesBehindTheExtensionsBackAreFound() {
    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_RESULTS);
    $this->assertSame('ok', CRM_Elections_Helper_Integrity::verify($fixture->electionId)['status']);

    $vote = $this->firstVote($fixture->electionId);
    CRM_Core_DAO::executeQuery('UPDATE civicrm_election_vote SET `rank` = `rank` + 1 WHERE id = %1', [1 => [(int) $vote['id'], 'Integer']]);
    $check = CRM_Elections_Helper_Integrity::verify($fixture->electionId);
    $this->assertSame('mismatch', $check['status']);
    $this->assertStringContainsString('ballots', strtolower($check['message']));
    CRM_Core_DAO::executeQuery('UPDATE civicrm_election_vote SET `rank` = `rank` - 1 WHERE id = %1', [1 => [(int) $vote['id'], 'Integer']]);
    $this->assertSame('ok', CRM_Elections_Helper_Integrity::verify($fixture->electionId)['status']);

    CRM_Core_DAO::executeQuery('UPDATE civicrm_election_result SET `rank` = `rank` + 1 WHERE election_position_id IN (SELECT id FROM civicrm_election_position WHERE election_id = %1) LIMIT 1', [1 => [$fixture->electionId, 'Integer']]);
    $check = CRM_Elections_Helper_Integrity::verify($fixture->electionId);
    $this->assertSame('mismatch', $check['status']);
    $this->assertStringContainsString('results', strtolower($check['message']));
  }

  // ------------------------------------------------------------------ anonymity

  public function testAnonymisingLeavesNothingThatIdentifiesAVoter() {
    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_VOTING);
    $closed = CRM_Core_DAO::singleValueQuery('SELECT voting_end_date FROM civicrm_election WHERE id = %1', [1 => [$fixture->electionId, 'Integer']]);
    $hashBefore = CRM_Elections_Helper_Integrity::votesHash($fixture->electionId);
    $idsBefore = CRM_Core_DAO::executeQuery('SELECT v.id FROM civicrm_election_vote v INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id INNER JOIN civicrm_election_position p ON p.id = n.election_position_id WHERE p.election_id = %1', [1 => [$fixture->electionId, 'Integer']])->fetchAll();
    $idsBefore = array_column($idsBefore, 'id');

    (new CRM_Elections_Helper_Results())->removeVotes($fixture->electionId);

    $rows = CRM_Core_DAO::executeQuery('SELECT v.id, v.member_id, v.created_at FROM civicrm_election_vote v INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id INNER JOIN civicrm_election_position p ON p.id = n.election_position_id WHERE p.election_id = %1', [1 => [$fixture->electionId, 'Integer']])->fetchAll();
    $this->assertCount(count($idsBefore), $rows, 'No ballot rows lost or added');
    foreach ($rows as $row) {
      $this->assertEmpty($row['member_id']);
      $this->assertSame($closed, $row['created_at'], 'The time each ballot was cast is gone');
      $this->assertNotContains($row['id'], $idsBefore, 'Rows were written again, so their order says nothing');
    }
    $this->assertSame($hashBefore, CRM_Elections_Helper_Integrity::votesHash($fixture->electionId), 'The same ballots, as far as counting goes');
  }

  // ------------------------------------------------------------------ settings and contacts

  public function testSettingsThatDecideWhoVotesAndHowVotesCountAreLockedOnceBallotsExist() {
    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_NOMINATIONS);
    \Civi\Api4\Election::update(FALSE)->addWhere('id', '=', $fixture->electionId)->addValue('allow_revote', TRUE)->execute();

    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_VOTING);
    $current = civicrm_api3('Election', 'getsingle', ['id' => $fixture->electionId]);
    $changes = [
      'anonymize_votes' => empty($current['anonymize_votes']) ? 1 : 0,
      'allow_revote' => empty($current['allow_revote']) ? 1 : 0,
      'required_nominations' => (int) $current['required_nominations'] + 5,
      'allowed_groups' => '999999',
    ];
    foreach ($changes as $field => $value) {
      $this->refused(function () use ($fixture, $field, $value) {
        \Civi\Api4\Election::update(FALSE)->addWhere('id', '=', $fixture->electionId)->addValue($field, $value)->execute();
      }, 'cannot be changed', $field);
    }
    // What does not affect who votes or how votes are counted can still be changed.
    \Civi\Api4\Election::update(FALSE)->addWhere('id', '=', $fixture->electionId)->addValue('show_photos', FALSE)->execute();
  }

  public function testAContactInARunningElectionCannotBeDeleted() {
    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_VOTING);
    foreach (['N01', 'V01'] as $persona) {
      $this->refused(function () use ($fixture, $persona) {
        \Civi\Api4\Contact::delete(FALSE)->addWhere('id', '=', $fixture->id($persona))->setUseTrash(FALSE)->execute();
      }, 'cannot be deleted', $persona);
    }
    $stranger = $this->individualCreate();
    \Civi\Api4\Contact::delete(FALSE)->addWhere('id', '=', $stranger)->setUseTrash(FALSE)->execute();
    $this->assertSame(0, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_contact WHERE id = %1', [1 => [$stranger, 'Integer']]));
  }

  public function testMergingContactsMovesWhatTheyDidInElections() {
    $data = [];
    elections_civicrm_merge('sqls', $data, 11, 22);
    $this->assertCount(4, $data);
    $this->assertStringContainsString('member_nominee = 11 WHERE member_nominee = 22', $data[0]);
    $this->assertStringContainsString('member_nominator = 11 WHERE member_nominator = 22', $data[1]);
    $this->assertStringContainsString('UPDATE IGNORE civicrm_election_vote SET member_id = 11 WHERE member_id = 22', $data[2]);
    $this->assertStringContainsString('UPDATE IGNORE civicrm_election_option_vote SET member_id = 11 WHERE member_id = 22', $data[3]);
    $other = [];
    elections_civicrm_merge('other', $other, 11, 22);
    $this->assertSame([], $other);
  }

  // ------------------------------------------------------------------ audit log

  public function testChangesAreRecordedWithWhoAndWhatAndTheChainIsChecked() {
    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_NOMINATIONS);
    \Civi\Api4\Election::update(FALSE)->addWhere('id', '=', $fixture->electionId)->addValue('description', 'Changed for the audit test')->execute();

    $entries = CRM_Elections_Helper_Audit::entries($fixture->electionId, 100);
    $changed = array_values(array_filter($entries, fn($e) => $e['action'] === 'election_changed'));
    $this->assertNotEmpty($changed);
    $this->assertSame('Changed for the audit test', $changed[0]['details']['changes']['description']['to']);
    $this->assertSame(['ok' => TRUE] + CRM_Elections_Helper_Audit::verify(), CRM_Elections_Helper_Audit::verify());

    foreach ($entries as $entry) {
      $this->assertStringNotContainsString('rank', json_encode($entry['details']), 'No ballot content in the log');
    }

    $id = $entries[0]['id'];
    CRM_Core_DAO::executeQuery('UPDATE civicrm_election_audit SET details = %1 WHERE id = %2', [1 => ['{"edited":"later"}', 'String'], 2 => [$id, 'Integer']]);
    $check = CRM_Elections_Helper_Audit::verify();
    $this->assertFalse($check['ok']);
    $this->assertSame($id, $check['broken_at']);
  }

  public function testAnEntryCannotBeRemovedFromTheMiddleOfTheLog() {
    $fixture = $this->fixture(CRM_Elections_Test_CouncilFixture::STAGE_NOMINATIONS);
    $entries = CRM_Elections_Helper_Audit::entries($fixture->electionId, 100);
    $this->assertGreaterThan(2, count($entries));
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_election_audit WHERE id = %1', [1 => [$entries[1]['id'], 'Integer']]);
    $this->assertFalse(CRM_Elections_Helper_Audit::verify()['ok']);
  }

  public function testTheAuditLogCannotBeChangedThroughTheApi() {
    foreach (['create', 'update', 'save', 'delete', 'replace'] as $action) {
      $this->refused(function () use ($action) {
        \Civi\Api4\ElectionAudit::$action(FALSE);
      }, 'cannot be changed', $action);
    }
  }

  // ------------------------------------------------------------------ addresses and pictures

  public function testOnlyAddressesOnThisSiteAreAcceptedFromARequest() {
    $site = CRM_Utils_System::baseCMSURL();
    $host = parse_url($site, PHP_URL_HOST);
    $this->assertSame('/civicrm/elections/view', CRM_Elections_Helper_Utils::localUrlOrNull('/civicrm/elections/view'));
    $this->assertSame("https://$host/page", CRM_Elections_Helper_Utils::localUrlOrNull("https://$host/page"));
    foreach (['javascript:alert(1)', 'https://evil.example/x', '//evil.example/x', "/x\" onmouseover=\"y", 'data:text/html,x', '', NULL, '\\\\evil.example', "/path\nHeader: x"] as $bad) {
      $this->assertNull(CRM_Elections_Helper_Utils::localUrlOrNull($bad), var_export($bad, TRUE));
    }
  }

  public function testOnlyHttpAndHttpsAddressesBecomeLinks() {
    $this->assertSame('https://example.org/cv', CRM_Elections_Helper_Utils::safeHttpUrl('https://example.org/cv'));
    foreach (['javascript:alert(1)', 'data:text/html,x', 'ftp://example.org/x', '/relative', '', NULL, "https://exa mple.org", "http://x.org/\nSet-Cookie: a=b"] as $bad) {
      $this->assertSame('', CRM_Elections_Helper_Utils::safeHttpUrl($bad), var_export($bad, TRUE));
    }
  }

  public function testPictureAddressesAreHttpOrPathsOnly() {
    $fallback = '/default.jpg';
    $this->assertSame('https://example.org/a.jpg', CRM_Elections_Helper_Utils::safeImageUrl('https://example.org/a.jpg', $fallback));
    $this->assertSame('/files/a.jpg', CRM_Elections_Helper_Utils::safeImageUrl('/files/a.jpg', $fallback));
    foreach (['javascript:alert(1)', 'data:image/svg+xml,<svg onload=alert(1)>', 'x" onerror="y', '', '//evil.example/a.jpg ', 'vbscript:x'] as $bad) {
      $this->assertSame($fallback, CRM_Elections_Helper_Utils::safeImageUrl($bad, $fallback), $bad);
    }
  }

}
