<?php
use CRM_Elections_ExtensionUtil as E;

/**
 * ElectionNominee.Getlist API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_election_nominee_Getlist_spec(&$spec) {
  $spec['election_id'] = [
    'title' => 'Election ID',
    'api.required' => 1,
    'FKApiName' => 'Election',
    'FKClassName' => 'CRM_Elections_DAO_Election',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $spec['input'] = [
    'title' => 'Search Term',
    'api.required' => 1,
  ];
  $spec['write_in'] = [
    'title' => 'Search for a person to write in (a voter, during voting)',
    'api.required' => 0,
  ];
  $spec['page_num'] = [
    'title' => 'Page Number',
    'api.required' => 1,
  ];
}

/**
 * ElectionNominee.Getlist API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws CRM_Core_Exception
 */
function civicrm_api3_election_nominee_Getlist($params) {
  $electionId = $params['election_id'];
  $election = findElectionById($electionId);

  $actingContactId = CRM_Elections_Helper_Eligibility::getActingContactId(TRUE);
  if (!empty($params['write_in'])) {
    // A voter looking for someone to write in: only while voting is open, only for people in this election's
    // candidate groups, names only, a few letters first and a limited number of searches, so the list of
    // members cannot be browsed.
    $positionsWithWriteIns = (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_position WHERE election_id = %1 AND write_ins_per_seat > 0', [1 => [(int) $electionId, 'Integer']]);
    if (!$actingContactId || !$positionsWithWriteIns || !$election->isVotingStarted || $election->isVotingEnded || !CRM_Elections_Helper_Eligibility::canVote($electionId, $actingContactId)) {
      return civicrm_api3_create_success([], $params, 'ElectionNominee', 'getlist');
    }
    if (!isset($params['id'])) {
      if (mb_strlen(trim((string) ($params['input'] ?? ''))) < 3) {
        return civicrm_api3_create_success([], $params, 'ElectionNominee', 'getlist');
      }
      $cache = Civi::cache('short');
      $key = 'elections_writein_search_' . (int) $actingContactId . '_' . floor(time() / 60);
      $searches = (int) $cache->get($key, 0);
      if ($searches >= 30) {
        return civicrm_api3_create_success([], $params, 'ElectionNominee', 'getlist');
      }
      $cache->set($key, $searches + 1, 120);
    }
  }
  // Only people who can nominate in the election may search for nominees.
  elseif (!$actingContactId || !CRM_Elections_Helper_Eligibility::canNominate($electionId, $actingContactId)) {
    return civicrm_api3_create_success([], $params, 'ElectionNominee', 'getlist');
  }

  $allowedGroupIds = CRM_Elections_Helper_Eligibility::groupsFor($election, 'candidate');
  if (!empty($allowedGroupIds)) {
    $allowedGroupIds = explode(',', $allowedGroupIds);
  }

  $apiParams = [
    'params'   => [
      'group'    => ['IN' => $allowedGroupIds],
    ],
  ];
  if (isset($params['id'])) {
    $apiParams['id'] = $params['id'];
  }
  else {
    $apiParams['input'] = $params['input'];
    $apiParams['page_num'] = $params['page_num'];
  }
  $contacts = civicrm_api3('Contact', 'getlist', $apiParams);
  foreach($contacts['values'] as &$contact) {
    unset($contact['description']);
  }
  return $contacts;
}
