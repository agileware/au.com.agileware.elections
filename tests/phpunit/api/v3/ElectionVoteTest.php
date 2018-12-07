<?php

/**
 * @group headless
 */
class api_v3_ElectionVoteTest extends api_v3_ElectionBaseTestCase {

  /**
   * Test that an votes are not added with empty params.
   */
  public function testAddVotesWithoutParams() {
    $this->callAPIFailure('ElectionVote', 'create', array());
  }

  /**
   * Test that votes are not added with wrong params.
   */
  public function testAddVotesWithWrongParams() {
    $params = array(
      'rank'                   => 'a',
      'election_nomination_id' => -1,
      'member_id'              => -1,
    );
    $this->callAPIFailure('ElectionVote', 'create', $params);

    $nominationParams = $this->createNomination();
    $memberId = $this->individualCreate();
    $nomination = $nominationParams['nomination'];

    $params['rank'] = 1;
    $this->callAPIFailure('ElectionVote', 'create', $params);

    $params['election_nomination_id'] = $nomination['id'];
    $this->callAPIFailure('ElectionVote', 'create', $params);

    $params['member_id'] = $memberId;
    $params['election_nomination_id'] = -1;
    $this->callAPIFailure('ElectionVote', 'create', $params);
  }

  /**
   * Test that votes are not added before voting period is started.
   */
  public function testAddVotesBeforeVotingStarted() {
    $nominationParams = $this->createNomination();
    $memberId = $this->individualCreate();
    $nomination = $nominationParams['nomination'];
    $election = $nominationParams['election'];
    $this->addMembersInElectionGroup($memberId, $election);

    $params = array(
      'member_id'              => $memberId,
      'votes'                  => array(
        array(
          'rank'                   => 1,
          'election_nomination_id' => $nomination['id'],
        ),
      ),
      'election_id'            => $election['id'],
    );

    $this->callAPIFailure('ElectionVote', 'addvotes', $params);
  }

  /**
   * Test that votes are added after voting period is started.
   */
  public function testAddVotesAfterVotingStarted() {
    $nominationParams = $this->createNomination();
    $memberId = $this->individualCreate();
    $nomination = $nominationParams['nomination'];
    $election = $nominationParams['election'];
    $this->addMembersInElectionGroup($memberId, $election);

    $params = array(
      'votes'                  => array(
        array(
          'rank'                   => 1,
          'election_nomination_id' => $nomination['id'],
        ),
      ),
      'member_id'              => $memberId,
      'election_id'            => $election['id'],
    );

    $today = new DateTime();
    $today->modify("-1 day");

    CRM_Core_DAO::executeQuery("UPDATE civicrm_election SET voting_start_date = %1 WHERE id = %2", array(
      1 => array($today->format("Y-m-d H:i:s"), 'String'),
      2 => array($election['id'], 'Integer'),
    ));

    $this->callAPISuccess('ElectionVote', 'addvotes', $params);
  }

  /**
   * Test that votes are not added after voting period is ended.
   */
  public function testAddVotesAfterVotingEnds() {
    $nominationParams = $this->createNomination();
    $memberId = $this->individualCreate();
    $nomination = $nominationParams['nomination'];
    $election = $nominationParams['election'];
    $this->addMembersInElectionGroup($memberId, $election);

    $params = array(
      'votes'                  => array(
        array(
          'rank'                   => 1,
          'election_nomination_id' => $nomination['id'],
        ),
      ),
      'member_id'              => $memberId,
      'election_id'            => $election['id'],
    );

    $today = new DateTime();
    $today->modify("-3 days");

    CRM_Core_DAO::executeQuery("UPDATE civicrm_election SET voting_start_date = %1 WHERE id = %2", array(
      1 => array($today->format("Y-m-d H:i:s"), 'String'),
      2 => array($election['id'], 'Integer'),
    ));

    $today->modify("+2 days");

    CRM_Core_DAO::executeQuery("UPDATE civicrm_election SET voting_end_date = %1 WHERE id = %2", array(
      1 => array($today->format("Y-m-d H:i:s"), 'String'),
      2 => array($election['id'], 'Integer'),
    ));

    $this->callAPIFailure('ElectionVote', 'addvotes', $params);
  }

  /**
   * Test that votes are not added for deleted election.
   */
  public function testAddVotesForDeletedElection() {
    $nominationParams = $this->createNomination();
    $memberId = $this->individualCreate();
    $nomination = $nominationParams['nomination'];
    $election = $nominationParams['election'];
    $this->addMembersInElectionGroup($memberId, $election);

    $params = array(
      'votes'                  => array(
        array(
          'rank'                   => 1,
          'election_nomination_id' => $nomination['id'],
        ),
      ),
      'member_id'              => $memberId,
      'election_id'            => $election['id'],
    );

    CRM_Core_DAO::executeQuery("UPDATE civicrm_election SET is_deleted = 1 WHERE id = %1", array(
      1 => array($election['id'], 'Integer'),
    ));

    $this->callAPIFailure('ElectionVote', 'addvotes', $params);
  }

  /**
   * Test that votes are not added for hidden election.
   */
  public function testAddVotesForHiddenElection() {
    $nominationParams = $this->createNomination();
    $memberId = $this->individualCreate();
    $nomination = $nominationParams['nomination'];
    $election = $nominationParams['election'];
    $this->addMembersInElectionGroup($memberId, $election);

    $params = array(
      'votes'                  => array(
        array(
          'rank'                   => 1,
          'election_nomination_id' => $nomination['id'],
        ),
      ),
      'member_id'              => $memberId,
      'election_id'            => $election['id'],
    );

    CRM_Core_DAO::executeQuery("UPDATE civicrm_election SET is_visible = 0 WHERE id = %1", array(
      1 => array($election['id'], 'Integer'),
    ));

    $this->callAPIFailure('ElectionVote', 'addvotes', $params);
  }

  /**
   * Test that votes are not added again if user has already votes if allow_revote setting is turned off.
   */
  public function testReVotesNotAllowedForElection() {

    $nominationParams = $this->createNomination(array(
      'election' => array(
        'params' => array(
          'allow_revote' => 0,
          'is_deleted'   => 0,
          'is_visible'   => 1,
        ),
      ),
    ));

    $memberId = $this->individualCreate();
    $nomination = $nominationParams['nomination'];
    $election = $nominationParams['election'];
    $this->addMembersInElectionGroup($memberId, $election);

    $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_VOTING_IN_PROGRESS, $election);

    $params = array(
      'votes'                  => array(
        array(
          'rank'                   => 1,
          'election_nomination_id' => $nomination['id'],
        ),
      ),
      'member_id'              => $memberId,
      'election_id'            => $election['id'],
    );

    $this->callAPISuccess('ElectionVote', 'addvotes', $params);
    $this->callAPIFailure('ElectionVote', 'addvotes', $params);
  }

  /**
   * Test that votes are added again if user has already voted if allow_revote setting is turned on.
   */
  public function testReVotesAllowdForElection() {

    $nominationParams = $this->createNomination(array(
      'election' => array(
        'params' => array(
          'allow_revote' => 1,
          'is_deleted'   => 0,
          'is_visible'   => 1,
        ),
      ),
    ));

    $memberId = $this->individualCreate();
    $nomination = $nominationParams['nomination'];
    $election = $nominationParams['election'];
    $this->addMembersInElectionGroup($memberId, $election);

    $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_VOTING_IN_PROGRESS, $election);

    $params = array(
      'votes'                  => array(
        array(
          'rank'                   => 1,
          'election_nomination_id' => $nomination['id'],
        ),
      ),
      'member_id'              => $memberId,
      'election_id'            => $election['id'],
    );

    $this->callAPISuccess('ElectionVote', 'addvotes', $params);
    $this->callAPISuccess('ElectionVote', 'addvotes', $params);
  }

  /**
   * Test that votes are added again if user has already voted if allow_revote setting is turned on.
   */
  public function testAddNonGroupMemberVoteForElection() {

    $nominationParams = $this->createNomination(array(
      'election' => array(
        'params' => array(
          'allow_revote' => 1,
          'is_deleted'   => 0,
          'is_visible'   => 1,
        ),
      ),
    ));

    $memberId = $this->individualCreate();
    $nomination = $nominationParams['nomination'];
    $election = $nominationParams['election'];

    $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_VOTING_IN_PROGRESS, $election);

    $params = array(
      'votes'                  => array(
        array(
          'rank'                   => 1,
          'election_nomination_id' => $nomination['id'],
        ),
      ),
      'member_id'              => $memberId,
      'election_id'            => $election['id'],
    );

    $this->callAPIFailure('ElectionVote', 'addvotes', $params);
  }

}
