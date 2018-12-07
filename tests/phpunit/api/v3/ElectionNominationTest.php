<?php

/**
 * @group headless
 */
class api_v3_ElectionNominationTest extends api_v3_ElectionBaseTestCase {

  /**
   * Test that a election nomination is not created without any params and throw errors.
   */
  public function testCreateElectionNominationWithoutAnyParams() {
    $this->callAPIFailure('ElectionNomination', 'create', array());
  }

  /**
   * Test that a election nomination is not created with wrong data.
   */
  public function testCreateElectionNominationWithWrongData() {
    $nominee = $this->individualCreate();
    $params = array(
      'member_nominee'       => $nominee,
      'election_position_id' => -1,
    );
    $this->callAPIFailure('ElectionNomination', 'create', $params);

    $position = $this->createElectionPosition();
    $params = array(
      'member_nominee'       => -1,
      'election_position_id' => $position['id'],
    );
    $this->callAPIFailure('ElectionNomination', 'create', $params);
  }

  /**
   * Test that a election nomination is not before nominations period is started.
   */
  public function testCreateElectionNominationBeforeNominationsStarted() {
    $nominee = $this->individualCreate();
    $position = $this->createElectionPosition();
    $params = array(
      'member_nominee'       => $nominee,
      'election_position_id' => $position['id'],
    );
    $this->callAPIFailure('ElectionNomination', 'create', $params);
  }

  /**
   * Test that a election nomination.
   */
  public function testCreateElectionNomination() {
    $this->createNomination();
  }

  /**
   * Test that a election nomination.
   */
  public function testCreateElectionNominationAfterNominationsEnd() {
    $nominee = $this->individualCreate();
    $position = $this->createElectionPosition();
    $params = array(
      'member_nominee'       => $nominee,
      'election_position_id' => $position['id'],
    );
    $election = civicrm_api3('Election', 'getsingle', array(
      'id' => $position['election_id'],
    ));
    $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_ADVERTISE_CANDIDATES_STARTED, $election);
    $this->callAPIFailure('ElectionNomination', 'create', $params);
  }

  /**
   * Test that a election nomination should not be added for the second time and same position.
   */
  public function testAddNominationSecondTime() {
    $params = $this->createNomination();
    $this->callAPIFailure('ElectionNomination', 'create', $params);
  }

  /**
   * Test that a election nomination should not be added for deleted election.
   */
  public function testCreateNominationForDeletedElection() {
    $nominee = $this->individualCreate();
    $position = $this->createElectionPosition();
    $params = array(
      'member_nominee'       => $nominee,
      'election_position_id' => $position['id'],
    );
    $election = civicrm_api3('Election', 'getsingle', array(
      'id' => $position['election_id'],
    ));

    $election = $this->markElectionInactive($election);

    $election['is_deleted'] = 1;
    $this->callAPISuccess('Election', 'create', $election);
    $this->callAPIFailure('ElectionNomination', 'create', $params);
  }

  /**
   * Test that a election nomination should not be added for hidden election.
   */
  public function testCreateNominationForHiddenElection() {
    $nominee = $this->individualCreate();
    $position = $this->createElectionPosition();
    $params = array(
      'member_nominee'       => $nominee,
      'election_position_id' => $position['id'],
    );
    $election = civicrm_api3('Election', 'getsingle', array(
      'id' => $position['election_id'],
    ));
    $election['is_visible'] = 0;
    civicrm_api3('Election', 'create', $election);
    $this->callAPIFailure('ElectionNomination', 'create', $params);
  }

}
