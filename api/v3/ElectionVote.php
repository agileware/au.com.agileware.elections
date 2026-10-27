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
    'api.required' => 0,
  ];
  $spec['write_ins'] = [
    'title'        => 'Write-ins',
    'description'  => 'People written in: each with position_id, contact_id and rank.',
    'api.required' => 0,
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

    $votes = $params['votes'] ?? [];
    $writeIns = $params['write_ins'] ?? [];
    if (!is_array($writeIns)) {
      return civicrm_api3_create_error('Write-ins must be a list.');
    }
    $resolved = CRM_Elections_Helper_WriteIn::resolve($election, $writeIns);
    if ($resolved['error']) {
      return civicrm_api3_create_error($resolved['error']);
    }
    $voteError = _civicrm_api3_election_vote_validate_ballot($election->id, $votes, $resolved['items']);
    if ($voteError) {
      return civicrm_api3_create_error($voteError);
    }
    $invite = [];

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
      // People written in: recorded once, the first time anyone writes them in. Their votes are ordinary ballot
      // rows on that record and count only if the person accepts before voting closes.
      foreach ($resolved['items'] as $item) {
        $nominationId = $item['nomination_id'];
        if ($item['kind'] === 'new') {
          $nominationId = CRM_Elections_Helper_WriteIn::record($election, $item['position_id'], $item['contact_id']);
          $invite[] = $nominationId;
        }
        $votes[] = ['election_nomination_id' => $nominationId, 'rank' => $item['rank']];
      }
      CRM_Elections_Helper_Guard::$castingBallot = TRUE;
      foreach ($votes as $vote) {
        $vote['member_id'] = $params['member_id'];
        civicrm_api3('ElectionVote', 'create', $vote);
      }
      CRM_Elections_Helper_Guard::$castingBallot = FALSE;
    }
    catch (Exception $e) {
      CRM_Elections_Helper_Guard::$castingBallot = FALSE;
      CRM_Elections_Helper_Integrity::$replacingBallot = FALSE;
      $transaction->rollback();
      throw $e;
    }
    $transaction->commit();
    // Told once the vote is safely saved. A failed email never undoes a vote.
    foreach ($invite as $nominationId) {
      CRM_Elections_Helper_Mailer::writeInInvited($nominationId);
    }
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
 *   Each with election_nomination_id and rank. Only candidates who are on the ballot (accepted) can be voted
 *   for this way.
 * @param array $writeIns
 *   People written in, as worked out by CRM_Elections_Helper_WriteIn::resolve(): each with position_id,
 *   rank, kind (new, pending or candidate) and the nomination_id when there is one.
 *
 * @return string|null
 *   An error message, or NULL when the ballot is fine.
 */
function _civicrm_api3_election_vote_validate_ballot($electionId, $votes, array $writeIns = []) {
  $votes = is_array($votes) ? $votes : [];
  if (empty($votes) && empty($writeIns)) {
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
  // A person written in who is already a candidate is a vote for that candidate; the rest are new places on the ballot.
  $newPlaces = [];
  foreach ($writeIns as $writeIn) {
    if ($writeIn['kind'] === 'candidate') {
      if (in_array($writeIn['nomination_id'], $nominationIds)) {
        return 'A candidate can only be ranked once.';
      }
      $nominationIds[] = $writeIn['nomination_id'];
      $votes[] = ['election_nomination_id' => $writeIn['nomination_id'], 'rank' => $writeIn['rank']];
    }
    else {
      $newPlaces[] = $writeIn;
    }
  }

  $candidateCounts = CRM_Core_DAO::executeQuery('
    SELECT n.election_position_id, COUNT(*) AS total
    FROM civicrm_election_nomination n
    INNER JOIN civicrm_election_position p ON p.id = n.election_position_id
    WHERE p.election_id = %1
      AND n.is_eligible_candidate = 1 AND n.has_accepted_nomination = 1 AND IFNULL(n.has_rejected_nomination, 0) = 0
    GROUP BY n.election_position_id
  ', [1 => [$electionId, 'Positive']])->fetchMap('election_position_id', 'total');
  foreach ($newPlaces as $writeIn) {
    // A person written in takes a place on this ballot, so ranks can reach them.
    $candidateCounts[$writeIn['position_id']] = (int) ($candidateCounts[$writeIn['position_id']] ?? 0) + 1;
  }

  $candidates = [];
  if ($nominationIds) {
    $candidates = CRM_Core_DAO::executeQuery('
      SELECT n.id, n.election_position_id
      FROM civicrm_election_nomination n
      INNER JOIN civicrm_election_position p ON p.id = n.election_position_id
      WHERE p.election_id = %1
        AND n.is_eligible_candidate = 1 AND n.has_accepted_nomination = 1 AND IFNULL(n.has_rejected_nomination, 0) = 0
        AND n.id IN (' . implode(',', array_map('intval', $nominationIds)) . ')
    ', [1 => [$electionId, 'Positive']])->fetchMap('id', 'election_position_id');
  }

  $method = CRM_Elections_VotingMethod::forElection($electionId);
  $ranksByPosition = [];
  $choices = [];
  foreach ($votes as $vote) {
    $positionId = $candidates[$vote['election_nomination_id']] ?? NULL;
    if (!$positionId) {
      return 'You can only vote for candidates of this election.';
    }
    $choices[] = [$positionId, $vote['rank']];
  }
  foreach ($newPlaces as $writeIn) {
    $choices[] = [$writeIn['position_id'], $writeIn['rank']];
  }
  foreach ($choices as [$positionId, $rank]) {
    if ((int) $rank > (int) ($candidateCounts[$positionId] ?? 0)) {
      return 'A rank cannot be higher than the number of candidates for the position.';
    }
    // Ranked choice gives each candidate a different rank; ticks all have rank 1.
    if ($method !== CRM_Elections_VotingMethod::PLURALITY && in_array($rank, $ranksByPosition[$positionId] ?? [])) {
      return 'Rank must be different for each candidate.';
    }
    $ranksByPosition[$positionId][] = $rank;
  }
  $seats = CRM_Core_DAO::executeQuery('SELECT id, quantity FROM civicrm_election_position WHERE election_id = %1', [1 => [$electionId, 'Positive']])->fetchMap('id', 'quantity');
  foreach ($ranksByPosition as $positionId => $ranks) {
    if (!in_array(1, $ranks)) {
      return 'If you rank candidates for a position you must give one of them rank 1.';
    }
    $methodError = CRM_Elections_VotingMethod::validatePosition($method, $ranks, $seats[$positionId] ?? 1);
    if ($methodError) {
      return $methodError;
    }
  }

  return NULL;
}
