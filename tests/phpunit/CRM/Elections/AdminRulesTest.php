<?php

/**
 * Rules for administering elections that must hold however a change is made - forms, API,
 * SearchKit inline edits and drag and drop.
 *
 * @group headless
 */
class CRM_Elections_AdminRulesTest extends api_v3_ElectionBaseTestCase {

  private function positionsInOrder($electionId) {
    return array_column(civicrm_api3('ElectionPosition', 'get', [
      'election_id' => $electionId,
      'options' => ['limit' => 0, 'sort' => 'sortorder ASC'],
      'sequential' => 1,
    ])['values'], 'name');
  }

  private function inactiveElection() {
    return $this->createElection(['is_visible' => 0, 'is_deleted' => 0]);
  }

  // ------------------------------------------------------------- activation

  public function testAnElectionWithoutPositionsCannotBeActivated() {
    $election = $this->inactiveElection();
    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('At least one Position');
    \Civi\Api4\Election::update(FALSE)->addWhere('id', '=', $election['id'])->addValue('is_visible', TRUE)->execute();
  }

  public function testAnElectionWithAPositionCanBeActivatedAndDeactivated() {
    $election = $this->inactiveElection();
    $this->createElectionPosition($election);
    \Civi\Api4\Election::update(FALSE)->addWhere('id', '=', $election['id'])->addValue('is_visible', TRUE)->execute();
    $this->assertEquals(1, CRM_Core_DAO::getFieldValue('CRM_Elections_DAO_Election', $election['id'], 'is_visible'));
    \Civi\Api4\Election::update(FALSE)->addWhere('id', '=', $election['id'])->addValue('is_visible', FALSE)->execute();
    $this->assertEquals(0, CRM_Core_DAO::getFieldValue('CRM_Elections_DAO_Election', $election['id'], 'is_visible'));
  }

  // -------------------------------------------------------------- positions

  public function testPositionsAreLockedOnceTheElectionHasStarted() {
    $election = $this->inactiveElection();
    $position = $this->createElectionPosition($election);
    $election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $election);
    $this->callAPISuccess('Election', 'create', ['id' => $election['id'], 'is_visible' => 1] + $election);

    foreach ([['name' => 'Changed'], ['sortorder' => 9], ['quantity' => 4]] as $change) {
      try {
        \Civi\Api4\ElectionPosition::update(FALSE)->addWhere('id', '=', $position['id'])->setValues($change)->execute();
        $this->fail('Changing ' . key($change) . ' of a started election must be refused.');
      }
      catch (CRM_Core_Exception $e) {
        $this->assertStringContainsString('once election has been started', $e->getMessage());
      }
    }
    try {
      \Civi\Api4\ElectionPosition::delete(FALSE)->addWhere('id', '=', $position['id'])->execute();
      $this->fail('Deleting a position of a started election must be refused.');
    }
    catch (CRM_Core_Exception $e) {
      $this->assertStringContainsString('once election has been started', $e->getMessage());
    }
    $this->assertSame([$position['name']], $this->positionsInOrder($election['id']));
  }

  public function testMovingAPositionShiftsTheOthers() {
    $election = $this->inactiveElection();
    foreach (['Alpha', 'Beta', 'Gamma', 'Delta'] as $i => $name) {
      $this->createElectionPosition($election, ['name' => $name, 'sortorder' => $i + 1]);
    }
    $gamma = civicrm_api3('ElectionPosition', 'getvalue', ['election_id' => $election['id'], 'name' => 'Gamma', 'return' => 'id']);
    // Drag and drop sets the moved position's order to where it was dropped.
    \Civi\Api4\ElectionPosition::update(FALSE)->addWhere('id', '=', $gamma)->addValue('sortorder', 1)->execute();
    $this->assertSame(['Gamma', 'Alpha', 'Beta', 'Delta'], $this->positionsInOrder($election['id']));

    $alpha = civicrm_api3('ElectionPosition', 'getvalue', ['election_id' => $election['id'], 'name' => 'Alpha', 'return' => 'id']);
    \Civi\Api4\ElectionPosition::update(FALSE)->addWhere('id', '=', $alpha)->addValue('sortorder', 4)->execute();
    $this->assertSame(['Gamma', 'Beta', 'Delta', 'Alpha'], $this->positionsInOrder($election['id']));
  }

  public function testResultsCanStillBeWrittenToAStartedElection() {
    $election = $this->inactiveElection();
    $position = $this->createElectionPosition($election);
    $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_VOTING_IN_PROGRESS, $election);
    CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET is_visible = 1 WHERE id = %1', [1 => [$election['id'], 'Integer']]);
    $this->callAPISuccess('ElectionPosition', 'create', [
      'id' => $position['id'],
      'election_id' => $election['id'],
      'result_status' => CRM_Elections_BAO_Election::$RESULTS_MAJORITY,
    ]);
    $this->assertEquals(CRM_Elections_BAO_Election::$RESULTS_MAJORITY, civicrm_api3('ElectionPosition', 'getvalue', ['id' => $position['id'], 'return' => 'result_status']));
  }

  public function testDuplicatingAPositionAppendsACopy() {
    $election = $this->inactiveElection();
    $position = $this->createElectionPosition($election, ['name' => 'Chair', 'quantity' => 2, 'sortorder' => 1]);
    $this->createElectionPosition($election, ['name' => 'Deputy', 'sortorder' => 2]);
    $copy = CRM_Elections_BAO_ElectionPosition::duplicate($position['id']);
    $this->assertSame('Copy of Chair', $copy['name']);
    $this->assertEquals(2, $copy['quantity']);
    $this->assertSame(['Chair', 'Deputy', 'Copy of Chair'], $this->positionsInOrder($election['id']));
  }

  // -------------------------------------------------------------- duplicate

  public function testDuplicatingAnElectionCopiesSettingsAndPositionsAsInactive() {
    $election = $this->createElection([
      'is_visible' => 0,
      'is_deleted' => 0,
      'required_nominations' => 3,
      'allow_revote' => 1,
      'show_statements' => 'inline',
      'show_photos' => 0,
      'name_format' => CRM_Elections_Helper_Candidate::NAME_NICK_LAST,
    ]);
    $this->createElectionPosition($election, ['name' => 'Chair', 'sortorder' => 1]);
    $this->createElectionPosition($election, ['name' => 'Treasurer', 'quantity' => 2, 'sortorder' => 2]);

    $copy = \Civi\Api4\Election::duplicate(FALSE)->setId($election['id'])->setName('Next year')->setShiftDays(365)->execute()->first();

    $this->assertSame('Next year', $copy['name']);
    $this->assertEmpty($copy['is_visible']);
    $this->assertEquals(3, $copy['required_nominations']);
    $this->assertEquals(1, $copy['allow_revote']);
    $this->assertSame('inline', $copy['show_statements']);
    $this->assertEquals(0, $copy['show_photos']);
    $this->assertSame(CRM_Elections_Helper_Candidate::NAME_NICK_LAST, $copy['name_format']);
    $this->assertSame($election['allowed_groups'], $copy['allowed_groups']);
    $this->assertSame(['Chair', 'Treasurer'], $this->positionsInOrder($copy['id']));

    $shift = strtotime($copy['voting_start_date']) - strtotime($election['voting_start_date']);
    $this->assertEqualsWithDelta(365 * 86400, $shift, 3700, 'Dates move by the same amount');
    // The original is untouched.
    $this->assertSame(['Chair', 'Treasurer'], $this->positionsInOrder($election['id']));
  }

  public function testDuplicateDoesNotCopyNominationsOrVotes() {
    $nomination = $this->createNomination();
    $copy = CRM_Elections_BAO_Election::duplicate($nomination['election']['id']);
    $positions = civicrm_api3('ElectionPosition', 'get', ['election_id' => $copy['id']])['values'];
    $this->assertCount(1, $positions);
    $this->assertSame(0, civicrm_api3('ElectionNomination', 'getcount', ['election_position_id' => ['IN' => array_keys($positions)]]));
  }

  // ---------------------------------------------------------------- overview

  public function testTheStageFollowsTheElectionThroughItsDates() {
    $fixture = CRM_Elections_Test_CouncilFixture::create();
    $expected = [
      CRM_Elections_Test_CouncilFixture::STAGE_NOMINATIONS => 'nominations',
      CRM_Elections_Test_CouncilFixture::STAGE_ADVERTISE => 'advertise',
      CRM_Elections_Test_CouncilFixture::STAGE_VOTING => 'voting',
      CRM_Elections_Test_CouncilFixture::STAGE_RESULTS => 'closed',
    ];
    foreach ($expected as $stage => $key) {
      $fixture->moveToStage($stage);
      $this->assertSame($key, findElectionById($fixture->electionId)->getStage()['key'], $stage);
    }
    // Results show once they have been generated after the result date.
    $fixture->generateResults();
    $this->assertSame('results', findElectionById($fixture->electionId)->getStage()['key']);
  }

  public function testAnInactiveElectionIsReportedAsInactive() {
    $election = $this->inactiveElection();
    $stage = findElectionById($election['id'])->getStage();
    $this->assertSame('inactive', $stage['key']);
    $counts = CRM_Elections_BAO_Election::getCounts($election['id']);
    $this->assertSame(0, $counts['positions']);
    $this->assertStringContainsString('Add at least one position', CRM_Elections_BAO_Election::getNextStep($stage, $counts));
  }

  public function testCountsFollowThePersonasThroughTheCouncilElection() {
    $fixture = CRM_Elections_Test_CouncilFixture::create()->playUntil(CRM_Elections_Test_CouncilFixture::STAGE_VOTING);
    $counts = CRM_Elections_BAO_Election::getCounts($fixture->electionId);
    $this->assertSame(6, $counts['positions']);
    $this->assertSame(17, $counts['nominations']);
    $this->assertSame(16, $counts['eligible'], 'Only the unseconded nomination is not eligible');
    $this->assertSame(11, $counts['candidates'], 'Eleven nominations were accepted and not withdrawn');
    $this->assertSame(38, $counts['ballots']);
  }

  public function testTurnoutPerPositionShowsCountsOnly() {
    $fixture = CRM_Elections_Test_CouncilFixture::create()->playUntil(CRM_Elections_Test_CouncilFixture::STAGE_VOTING);
    $turnout = CRM_Elections_BAO_Election::getTurnout($fixture->electionId);
    $this->assertSame(['President', 'Secretary', 'Treasurer', 'Council Member 1', 'Council Member 2', 'Council Member 3'], array_column($turnout, 'name'));
    foreach ($turnout as $row) {
      $this->assertSame(['name', 'candidates', 'ballots'], array_keys($row), 'No voter or candidate choice appears in the turnout');
      $this->assertGreaterThan(0, $row['ballots'], $row['name']);
      $this->assertLessThanOrEqual(38, $row['ballots'], $row['name']);
    }
    $this->assertSame(11, array_sum(array_column($turnout, 'candidates')));
  }

  public function testBallotsAreStillCountedAfterTheVotesAreAnonymised() {
    $fixture = CRM_Elections_Test_CouncilFixture::create()->playUntil(CRM_Elections_Test_CouncilFixture::STAGE_VOTING);
    foreach (range(1, 5) as $i) {
      $contactId = civicrm_api3('Contact', 'create', ['contact_type' => 'Individual', 'first_name' => 'Voter', 'last_name' => 'Activity' . $i])['id'];
      civicrm_api3('Activity', 'create', [
        'source_contact_id' => $contactId,
        'activity_type_id' => 'Vote',
        'status_id' => 'Completed',
        'source_record_id' => $fixture->electionId,
        'subject' => 'Voted in an election',
      ]);
    }
    $this->assertSame(5, CRM_Elections_BAO_Election::countVoters($fixture->electionId));

    (new CRM_Elections_Helper_Results())->removeVotes($fixture->electionId);

    $this->assertSame(5, CRM_Elections_BAO_Election::getCounts($fixture->electionId)['ballots'], 'Counted from the Vote activities once votes no longer say who voted');
    foreach (CRM_Elections_BAO_Election::getTurnout($fixture->electionId) as $row) {
      $this->assertNull($row['ballots'], $row['name'] . ' cannot be counted once anonymised');
    }
  }

}
