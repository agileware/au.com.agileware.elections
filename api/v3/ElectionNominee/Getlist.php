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
  $spec['election_id'] = array(
    'title' => 'Election ID',
    'api.required' => 1,
    'FKApiName' => 'Election',
    'FKClassName' => 'CRM_Elections_DAO_Election',
    'type' => CRM_Utils_Type::T_INT,
  );
  $spec['input'] = array(
    'title' => 'Search Term',
    'api.required' => 1,
  );
  $spec['page_num'] = array(
    'title' => 'Page Number',
    'api.required' => 1,
  );
}

/**
 * ElectionNominee.Getlist API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_election_nominee_Getlist($params) {
  $electionId = $params['election_id'];
  $election = findElectionById($electionId);
  $allowedGroupIds = $election->allowed_groups;
  if (!empty($allowedGroupIds)) {
    $allowedGroupIds = explode(",", $allowedGroupIds);
  }

  $apiParams = array(
    'params'   => array(
      'group'    => array('IN' => $allowedGroupIds),
    ),
  );
  if (isset($params['id'])) {
    $apiParams['id'] = $params['id'];
  }
  else {
    $apiParams['input'] = $params['input'];
    $apiParams['page_num'] = $params['page_num'];
  }
  $contacts = civicrm_api3('Contact', 'getlist', $apiParams);
  return $contacts;
}
