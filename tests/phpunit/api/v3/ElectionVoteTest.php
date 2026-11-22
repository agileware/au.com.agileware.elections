<?php

/**
 * @group headless
 */
class api_v3_ElectionVoteTest extends api_v3_ElectionBaseTestCase {

  /**
   * Test that votes are not added for deleted election.
   */
  public function testAddVotesForDeletedElection() {
    $nominationParams = $this->createNomination();
    $memberId = $this->individualCreate();
    $nomination = $nominationParams['nomination'];
    $this->makeCandidate($nomination['id']);
    $election = $nominationParams['election'];
    $this->addMembersInElectionGroup($memberId, $election);

    $params = [
      'votes'                  => [
        [
          'rank'                   => 1,
          'election_nomination_id' => $nomination['id'],
        ],
      ],
      'member_id'              => $memberId,
      'election_id'            => $election['id'],
    ];

    CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET is_deleted = 1 WHERE id = %1', [
      1 => [$election['id'], 'Integer'],
    ]);

    $this->callAPIFailure('ElectionVote', 'addvotes', $params, 'not open for voting');
  }

  /**
   * Test that votes are not added for hidden election.
   */
  public function testAddVotesForHiddenElection() {
    $nominationParams = $this->createNomination();
    $memberId = $this->individualCreate();
    $nomination = $nominationParams['nomination'];
    $this->makeCandidate($nomination['id']);
    $election = $nominationParams['election'];
    $this->addMembersInElectionGroup($memberId, $election);

    $params = [
      'votes'                  => [
        [
          'rank'                   => 1,
          'election_nomination_id' => $nomination['id'],
        ],
      ],
      'member_id'              => $memberId,
      'election_id'            => $election['id'],
    ];

    CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET is_visible = 0 WHERE id = %1', [
      1 => [$election['id'], 'Integer'],
    ]);

    $this->callAPIFailure('ElectionVote', 'addvotes', $params, 'not open for voting');
  }

}
