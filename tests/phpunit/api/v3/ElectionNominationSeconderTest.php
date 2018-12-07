<?php

/**
 * @group headless
 */
class api_v3_ElectionNominationSeconderTest extends api_v3_ElectionBaseTestCase {
  /**
   * Test that an election nomination seconder is not created without any params and throw errors.
   */
  public function testCreateNominationSeconderWithoutAnyParams() {
    $this->callAPIFailure('ElectionNominationSeconder', 'create', array());
  }

  /**
   * Test that an election nomination seconder is not created with wrong params.
   */
  public function testCreateNominationSeconderWithWrongParams() {
    $nominator = $this->individualCreate();
    $nominationParams = $this->createNomination();
    $nomination = $nominationParams['nomination'];

    $params = array(
      'member_nominator'       => $nominator,
      'election_nomination_id' => -1,
    );

    $this->callAPIFailure('ElectionNominationSeconder', 'create', $params);
    $params = array(
      'member_nominator'       => -1,
      'election_nomination_id' => $nomination['id'],
    );

    $this->callAPIFailure('ElectionNominationSeconder', 'create', $params);
  }

  /**
   * Test that an election nomination seconder is created with valid params.
   */
  public function testCreateNominationSeconderWithValidParams() {
    $this->createNominationSeconder();
  }

  /**
   * Test that an election nomination seconder should not be created for second time.
   */
  public function testCreateNominationSeconderSecondTime() {
    $params = $this->createNominationSeconder();
    $this->callAPIFailure('ElectionNominationSeconder', 'create', $params['params']);
  }

  /**
   * Test that an election nomination seconder should not be created after nominations end.
   */
  public function testCreateNominationSeconderAfterNominationsEnd() {
    $nominator = $this->individualCreate();
    $nominationParams = $this->createNomination();
    $nomination = $nominationParams['nomination'];
    $position = $nominationParams['position'];

    $params = array(
      'member_nominator'       => $nominator,
      'election_nomination_id' => $nomination['id'],
    );

    $election = civicrm_api3('Election', 'getsingle', array(
      'id' => $position['election_id'],
    ));

    $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_ADVERTISE_CANDIDATES_STARTED, $election);
    $this->callAPIFailure('ElectionNominationSeconder', 'create', $params);
  }

  /**
   * Test that an election nomination seconder should not be created for deleted election.
   */
  public function testCreateNominationSeconderForDeletedElection() {
    $nominator = $this->individualCreate();
    $nominationParams = $this->createNomination();
    $nomination = $nominationParams['nomination'];
    $position = $nominationParams['position'];

    $params = array(
      'member_nominator'       => $nominator,
      'election_nomination_id' => $nomination['id'],
    );

    CRM_Core_DAO::executeQuery("UPDATE civicrm_election SET is_deleted = 1 WHERE id = %1", array(
      1 => array($position['election_id'], 'Integer'),
    ));
    $this->callAPIFailure('ElectionNominationSeconder', 'create', $params);
  }

  /**
   * Test that an election nomination seconder should not be created for hidden election.
   */
  public function testCreateNominationSeconderForHiddenElection() {
    $nominator = $this->individualCreate();
    $nominationParams = $this->createNomination();
    $nomination = $nominationParams['nomination'];
    $position = $nominationParams['position'];

    $params = array(
      'member_nominator'       => $nominator,
      'election_nomination_id' => $nomination['id'],
    );

    CRM_Core_DAO::executeQuery("UPDATE civicrm_election SET is_visible = 0 WHERE id = %1", array(
      1 => array($position['election_id'], 'Integer'),
    ));
    $this->callAPIFailure('ElectionNominationSeconder', 'create', $params);
  }

  /**
   * Create nomination seconder.
   *
   * @return array
   * @throws CiviCRM_API3_Exception
   */
  private function createNominationSeconder() {
    $nominator = $this->individualCreate();
    $nominationParams = $this->createNomination();
    $nomination = $nominationParams['nomination'];
    $election = $nominationParams['election'];

    $params = array(
      'member_nominator'       => $nominator,
      'election_nomination_id' => $nomination['id'],
    );

    $nominationSeconder = $this->callAPISuccess('ElectionNominationSeconder', 'create', $params);

    return array(
      'params'     => $params,
      'seconder'   => $nominationSeconder,
      'nomination' => $nomination,
      'election'   => $election,
    );
  }

}
