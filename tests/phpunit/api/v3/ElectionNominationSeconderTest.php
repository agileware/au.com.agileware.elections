<?php

/**
 * @group headless
 */
class api_v3_ElectionNominationSeconderTest extends api_v3_ElectionBaseTestCase {

  /**
   * Test that an election nomination seconder should not be created for second time.
   */
  public function testCreateNominationSeconderSecondTime() {
    $params = $this->createNominationSeconder();
    $this->callAPIFailure('ElectionNominationSeconder', 'create', $params['params'], 'already nominated this person');
  }

  /**
   * Test that an election nomination seconder should not be created after nominations end.
   */
  public function testCreateNominationSeconderAfterNominationsEnd() {
    $nominator = $this->individualCreate();
    $nominationParams = $this->createNomination();
    $nomination = $nominationParams['nomination'];
    $position = $nominationParams['position'];

    $params = [
      'member_nominator'       => $nominator,
      'election_nomination_id' => $nomination['id'],
    ];

    $election = civicrm_api3('Election', 'getsingle', [
      'id' => $position['election_id'],
    ]);

    $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_ADVERTISE_CANDIDATES_STARTED, $election);
    $this->callAPIFailure('ElectionNominationSeconder', 'create', $params, 'Nominations closed on');
  }

  /**
   * Test that an election nomination seconder should not be created for deleted election.
   */
  public function testCreateNominationSeconderForDeletedElection() {
    $nominator = $this->individualCreate();
    $nominationParams = $this->createNomination();
    $nomination = $nominationParams['nomination'];
    $position = $nominationParams['position'];

    $params = [
      'member_nominator'       => $nominator,
      'election_nomination_id' => $nomination['id'],
    ];

    CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET is_deleted = 1 WHERE id = %1', [
      1 => [$position['election_id'], 'Integer'],
    ]);
    $this->callAPIFailure('ElectionNominationSeconder', 'create', $params, 'deleted or not active');
  }

  /**
   * Test that an election nomination seconder should not be created for hidden election.
   */
  public function testCreateNominationSeconderForHiddenElection() {
    $nominator = $this->individualCreate();
    $nominationParams = $this->createNomination();
    $nomination = $nominationParams['nomination'];
    $position = $nominationParams['position'];

    $params = [
      'member_nominator'       => $nominator,
      'election_nomination_id' => $nomination['id'],
    ];

    CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET is_visible = 0 WHERE id = %1', [
      1 => [$position['election_id'], 'Integer'],
    ]);
    $this->callAPIFailure('ElectionNominationSeconder', 'create', $params, 'deleted or not active');
  }

  /**
   * Create nomination seconder.
   *
   * @return array
   * @throws CRM_Core_Exception
   */
  private function createNominationSeconder() {
    $nominator = $this->individualCreate();
    $nominationParams = $this->createNomination();
    $nomination = $nominationParams['nomination'];
    $election = $nominationParams['election'];

    $params = [
      'member_nominator'       => $nominator,
      'election_nomination_id' => $nomination['id'],
    ];

    $nominationSeconder = $this->callAPISuccess('ElectionNominationSeconder', 'create', $params);

    return [
      'params'     => $params,
      'seconder'   => $nominationSeconder,
      'nomination' => $nomination,
      'election'   => $election,
    ];
  }

}
