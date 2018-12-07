<?php

/**
 * @group headless
 */
class api_v3_ElectionPositionTest extends api_v3_ElectionBaseTestCase {

  /**
   * Test that a election position is not created without any params and throw errors.
   */
  public function testCreateElectionPositionWithoutAnyParams() {
    $this->callAPIFailure('ElectionPosition', 'create', array());
  }

  /**
   * Test that a election position is not created with wrong params and throw errors.
   */
  public function testCreateElectionPositionWithWrongData() {
    $election = $this->createElection();
    $params = array(
      'name'        => 'President',
      'quantity'    => 'a',
      'election_id' => $election['id'],
    );
    $this->callAPIFailure('ElectionPosition', 'create', $params);
    $params['quantity'] = 1;
    $params['election_id'] = -1;
    $this->callAPIFailure('ElectionPosition', 'create', $params);
  }

  /**
   * Test that a election position is created with valid data.
   */
  public function testCreateElectionPositionWithValidData() {
    $this->createElectionPosition();
  }

  /**
   * Test that a election position is not created for active election.
   */
  public function testCreateElectionPositionForActiveElection() {
    $election = $this->createElection(array(
      'is_deleted' => 0,
      'is_visible' => 1,
    ), api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS);
    $params = array(
      'name'        => 'President',
      'quantity'    => 1,
      'election_id' => $election['id'],
    );
    $this->callAPIFailure('ElectionPosition', 'create', $params);
  }

  /**
   * Test that a election position is not created after nominations has been started.
   */
  public function testCreateElectionPositionOfDeletedElection() {
    $election = $this->createElection(array(
      'is_deleted' => 1,
    ));
    $params = array(
      'name'        => 'President',
      'quantity'    => 1,
      'election_id' => $election['id'],
    );
    $this->callAPIFailure('ElectionPosition', 'create', $params);
  }

  /**
   * Test that a election position is deleted.
   */
  public function testDeleteElectionPosition() {
    $electionPosition = $this->createElectionPosition();

    $this->callAPISuccess('ElectionPosition', 'delete', array(
      'id' => $electionPosition['id'],
    ));
  }

  /**
   * Test that a election position is deleted for inactive running election.
   */
  public function testDeleteElectionPositionForInactiveElection() {
    $election = $this->createElection();
    $electionPosition = $this->createElectionPosition($election);
    $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $election);

    $this->callAPISuccess('ElectionPosition', 'delete', array(
      'id' => $electionPosition['id'],
    ));
  }

  /**
   * Test that a election position is edited.
   */
  public function testEditElectionPosition() {
    $electionPosition = $this->createElectionPosition();
    $electionPosition['name'] = 'IT Head';
    $this->callAPISuccess('ElectionPosition', 'create', $electionPosition);
  }

  /**
   * Test that a election position is added for inactive running election.
   */
  public function testEditElectionPositionForInactiveElection() {
    $election = $this->createElection();
    $electionPosition = $this->createElectionPosition($election);
    $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $election);
    $electionPosition['name'] = 'IT Head';

    $this->callAPISuccess('ElectionPosition', 'create', $electionPosition);
  }

  /**
   * Test that a election position is not added for active running election.
   */
  public function testEditElectionPositionForActiveElection() {
    $election = $this->createElection();
    $electionPosition = $this->createElectionPosition($election);
    $election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $election);

    $election['is_visible'] = 1;
    $this->callAPISuccess('Election', 'create', $election);

    $electionPosition['name'] = 'IT Head';

    $this->callAPIFailure('ElectionPosition', 'create', $electionPosition);
  }

  /**
   * Test that a election position is added for inactive running election.
   */
  public function testCreateElectionPositionForInactiveElection() {
    $election = $this->createElection();
    $election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $election);
    $electionPosition = $this->createElectionPosition($election);
  }

}
