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
 * @throws CRM_Core_Exception
 */
function civicrm_api3_election_vote_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * ElectionVote.delete API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws CRM_Core_Exception
 */
function civicrm_api3_election_vote_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * ElectionVote.deletevotes API
 *
 * @param array $params
 * @throws CRM_Core_Exception
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
 * @throws CRM_Core_Exception
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

  if (!isLoggedInMemberAllowedToVote($election->id, $params['member_id'])) {
    return civicrm_api3_create_error('Member is not allowed to vote in given election.');
  }

  // One ballot at a time for each member: two requests sent together (a double click, a second tab, a
  // script) must not both find "has not voted yet" and both write a ballot.
  $lock = Civi::lockManager()->acquire('data.elections.vote.' . (int) $election->id . '.' . (int) $params['member_id'], 10);
  if (!$lock->isAcquired()) {
    return civicrm_api3_create_error('Your ballot is already being saved. Please wait a moment and try again.');
  }
  try {
    // Check everything before touching an earlier ballot, so a rejected ballot never costs someone their vote.
    $hasVoted = hasLoggedInUserAlreadyVoted($election->id, $params['member_id']);
    if ($hasVoted && !$election->allow_revote) {
      return civicrm_api3_create_error('Member has already voted in given election.');
    }

    $votes = $params['votes'];
    $voteError = _civicrm_api3_election_vote_validate_ballot($election->id, $votes);
    if ($voteError) {
      return civicrm_api3_create_error($voteError);
    }

    $transaction = new CRM_Core_Transaction();
    try {
      if ($hasVoted) {
        CRM_Elections_Helper_Integrity::$replacingBallot = TRUE;
        civicrm_api3('ElectionVote', 'deletevotes', [
          'election_id' => $election->id,
          'member_id'   => $params['member_id'],
        ]);
        CRM_Elections_Helper_Integrity::$replacingBallot = FALSE;
      }
      foreach ($votes as $vote) {
        $vote['member_id'] = $params['member_id'];
        civicrm_api3('ElectionVote', 'create', $vote);
      }
    }
    catch (Exception $e) {
      CRM_Elections_Helper_Integrity::$replacingBallot = FALSE;
      $transaction->rollback();
      throw $e;
    }
    $transaction->commit();
  }
  finally {
    $lock->release();
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
 * @throws CRM_Core_Exception
 */
function civicrm_api3_election_vote_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}


/**
 * Check a ballot only ranks real candidates of the election, with sensible ranks.
 *
 * @param int $electionId
 * @param array $votes
 *   Each with election_nomination_id and rank.
 *
 * @return string|null
 *   An error message, or NULL when the ballot is fine.
 */
function _civicrm_api3_election_vote_validate_ballot($electionId, $votes) {
  if (!is_array($votes) || empty($votes)) {
    return 'A ballot must rank at least one candidate.';
  }

  $nominationIds = [];
  foreach ($votes as $vote) {
    if (!isset($vote['election_nomination_id']) || !CRM_Utils_Rule::positiveInteger($vote['election_nomination_id'])) {
      return 'Each vote must be for a candidate.';
    }
    if (!isset($vote['rank']) || !CRM_Utils_Rule::positiveInteger($vote['rank']) || (int) $vote['rank'] < 1) {
      return 'Rank must be a valid positive integer.';
    }
    if (in_array($vote['election_nomination_id'], $nominationIds)) {
      return 'A candidate can only be ranked once.';
    }
    $nominationIds[] = $vote['election_nomination_id'];
  }

  $candidateCounts = CRM_Core_DAO::executeQuery('
    SELECT n.election_position_id, COUNT(*) AS total
    FROM civicrm_election_nomination n
    INNER JOIN civicrm_election_position p ON p.id = n.election_position_id
    WHERE p.election_id = %1
      AND n.is_eligible_candidate = 1 AND n.has_accepted_nomination = 1 AND IFNULL(n.has_rejected_nomination, 0) = 0
    GROUP BY n.election_position_id
  ', [1 => [$electionId, 'Positive']])->fetchMap('election_position_id', 'total');

  $candidates = CRM_Core_DAO::executeQuery('
    SELECT n.id, n.election_position_id
    FROM civicrm_election_nomination n
    INNER JOIN civicrm_election_position p ON p.id = n.election_position_id
    WHERE p.election_id = %1
      AND n.is_eligible_candidate = 1 AND n.has_accepted_nomination = 1 AND IFNULL(n.has_rejected_nomination, 0) = 0
      AND n.id IN (' . implode(',', array_map('intval', $nominationIds)) . ')
  ', [1 => [$electionId, 'Positive']])->fetchMap('id', 'election_position_id');

  $ranksByPosition = [];
  foreach ($votes as $vote) {
    $positionId = $candidates[$vote['election_nomination_id']] ?? NULL;
    if (!$positionId) {
      return 'You can only vote for candidates of this election.';
    }
    if ((int) $vote['rank'] > (int) ($candidateCounts[$positionId] ?? 0)) {
      return 'A rank cannot be higher than the number of candidates for the position.';
    }
    if (in_array($vote['rank'], $ranksByPosition[$positionId] ?? [])) {
      return 'Rank must be different for each candidate.';
    }
    $ranksByPosition[$positionId][] = $vote['rank'];
  }
  foreach ($ranksByPosition as $ranks) {
    if (!in_array(1, $ranks)) {
      return 'If you rank candidates for a position you must give one of them rank 1.';
    }
  }

  return NULL;
}
