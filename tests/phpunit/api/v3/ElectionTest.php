<?php

/**
 * @group headless
 */
class api_v3_ElectionTest extends api_v3_ElectionBaseTestCase {

  /**
   * Test that a election is not created without any params and throw errors.
   */
  public function testCreateElectionWithoutAnyParams() {
    $params = array();
    $this->callAPIFailure('election', 'create', $params);
  }

  /**
   * Test that a election should not be created with wrong date and throw errors.
   */
  public function testCreateElectionWithWrongDates() {
    $electionDates = $this->getElectionDates();
    $electionDates['visibility_end_date'] = $electionDates['visibility_start_date'];
    $this->modifyDatesInString($electionDates);
    $params = array(
      'name'        => 'CiviTest Election',
    );
    $params = array_merge($params, $electionDates);
    $this->callAPIFailure('election', 'create', $params);
  }

  /**
   * Test that a election should be created with valid data.
   */
  public function testCreateElectionWithValidData() {
    $this->createElection();
  }

  /**
   * Test that a election should be edited if its not started yet.
   */
  public function testEditNonStartedElection() {
    $electionName = 'Non started election';
    $election = $this->createElection(array(
      'name'        => $electionName,
      'description' => 'Description of non started election',
    ));
    $this->assertEquals($election['name'], $electionName);

    $electionName = 'Modified election name';
    $election['name'] = $electionName;

    $this->callAPISuccess('election', 'create', $election);
    $this->assertEquals($election['name'], $electionName);
  }

  /**
   * Test that a election should be deleted if its not started yet.
   */
  public function testDeleteNonStartedElection() {
    $election = $this->createElection();
    $election['name'] = 'Deleting non started election';
    $election['is_deleted'] = 1;
    $election = $this->callAPISuccess('election', 'create', $election);
    $election = $election['values'][$election['id']];
    $this->assertEquals($election['is_deleted'], 1);
  }

  /**
   * Test that a election should not be edit/delete if it has been started and active.
   */
  public function testEditDeleteActiveStartedElection() {
    $election = $this->createElection(array(), api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS);
    $election['name'] = 'Deleting started election';
    $election['is_deleted'] = 1;
    $this->callAPIFailure('election', 'create', $election);
  }

  /**
   * Test that a election should be edited/deleted if it has been started and not active.
   */
  public function testEditDeleteInactiveStartedElection() {
    $election = $this->createElection(array(), api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS);

    $election['is_visible'] = 0;
    $election = $this->callAPISuccess('election', 'create', $election);
    $election = $election['values'][$election['id']];

    $election['name'] = 'Deleting started election';
    $election['is_deleted'] = 1;
    $this->callAPISuccess('election', 'create', $election);
  }

}
