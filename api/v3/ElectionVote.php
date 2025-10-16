<?php
use CRM_Elections_ExtensionUtil as E;

/**
 * ElectionVote.create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_election_vote_create_spec(&$spec) {
  $spec['rank']['api.required'] = 1;
  $spec['election_nomination_id']['api.required'] = 1;
  $spec['member_id']['api.required'] = 1;
}

function _civicrm_api3_election_vote_deletevotes_spec(&$spec) {
  $spec['election_id'] = [
    'title'        => 'Election ID',
    'api.required' => 1,
    'FKApiName'    => 'Election',
    'FKClassName'  => 'CRM_Elections_DAO_Election',
    'type'         => CRM_Utils_Type::T_INT,
  ];
  $spec['member_id'] = [
    'title'        => 'Member ID',
    'api.required' => 1,
    'FKApiName'    => 'Contact',
    'FKClassName'  => 'CRM_Elections_DAO_Contact',
    'type'         => CRM_Utils_Type::T_INT,
  ];
}

function _civicrm_api3_election_vote_addvotes_spec(&$spec) {
  $spec['election_id'] = [
    'title'        => 'Election ID',
    'api.required' => 1,
    'FKApiName'    => 'Election',
    'FKClassName'  => 'CRM_Elections_DAO_Election',
    'type'         => CRM_Utils_Type::T_INT,
  ];
  $spec['member_id'] = [
    'title'        => 'Member ID',
    'api.required' => 1,
    'FKApiName'    => 'Contact',
    'FKClassName'  => 'CRM_Elections_DAO_Contact',
    'type'         => CRM_Utils_Type::T_INT,
  ];
  $spec['votes'] = [
    'title'        => 'Votes',
    'api.required' => 1,
  ];
}

/**
 * ElectionVote.create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_election_vote_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * ElectionVote.delete API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_election_vote_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * ElectionVote.deletevotes API
 *
 * @param array $params
 * @throws API_Exception
 */
function civicrm_api3_election_vote_deletevotes($params) {
  $electionId = $params['election_id'];
  if (is_array($params['member_id'])) {
    return civicrm_api3_create_error('Multiple members are not yet supported.');
  }
  $previousVotes = civicrm_api3('ElectionVote', 'get', [
    'election_nomination_id.election_position_id.election_id.id' => $electionId,
    'member_id'                                                  => $params['member_id'],
    'options'                                                    => ['limit' => 0],
  ]);
  $previousVotes = $previousVotes['values'];
  foreach ($previousVotes as $voteId => $previousVote) {
    civicrm_api3('ElectionVote', 'delete', [
      'id' => $voteId,
    ]);
  }
}

/**
 * ElectionVote.addvotes API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_election_vote_addvotes($params) {
  $electionId = $params['election_id'];

  $election = findElectionById($electionId, FALSE);
  if ($election->is_deleted || !$election->is_visible) {
    return civicrm_api3_create_error('Votes cannot be added for deleted election.');
  }

  if (!$election->isVotingStarted) {
    return civicrm_api3_create_error('Votes cannot be added before voting period is started.');
  }

  if ($election->isVotingEnded) {
    return civicrm_api3_create_error('Votes cannot be added after voting period is ended.');
  }

  if (is_array($params['member_id'])) {
    return civicrm_api3_create_error('Multiple members are not yet supported.');
  }

  if (hasLoggedInUserAlreadyVoted($election->id, $params['member_id'])) {
    if (!$election->allow_revote) {
      return civicrm_api3_create_error('Member has already voted in given election.');
    }
    else {
      civicrm_api3('ElectionVote', 'deletevotes', [
        'election_id' => $election->id,
        'member_id'   => $params['member_id'],
      ]);
    }
  }

  if (!isLoggedInMemberAllowedToVote($election->id, $params['member_id'])) {
    return civicrm_api3_create_error('Member is not allowed to vote in given election.');
  }

  $votes = $params['votes'];
  foreach ($votes as $vote) {
    $vote['member_id'] = $params['member_id'];
    civicrm_api3('ElectionVote', 'create', $vote);
  }

  return civicrm_api3_create_success([
    'message' => count($votes) . ' has been added successfully.',
  ], $params, 'ElectionVote', 'addvotes');
}

/**
 * ElectionVote.get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_election_vote_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
