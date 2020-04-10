<?php
use CRM_Elections_ExtensionUtil as E;

/**
 * ElectionPosition.create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_election_position_create_spec(&$spec) {
  $spec['name']['api.required'] = 1;
  $spec['quantity']['api.required'] = 1;
  $spec['election_id']['api.required'] = 1;
}

/**
 * ElectionPosition.create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_election_position_create($params) {
  $electionId = $params['election_id'];
  $election = findElectionById($electionId, FALSE);
  if (!isset($params['result_status']) || $params['result_status'] == '') {
    if ($election->isNominationsStarted && $election->is_visible == 1) {
      return civicrm_api3_create_error ( ts('Election position cannot be added/modified once election has been started.'));
    }
    if ($election->is_deleted) {
      return civicrm_api3_create_error ( ts('Election position cannot be added/modified for deleted election.'));
    }
  }
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * ElectionPosition.delete API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_election_position_delete($params) {
  $electionPosition = civicrm_api3_election_position_get($params);
  $electionId = $electionPosition['values'][$electionPosition['id']]['election_id'];

  $election = findElectionById($electionId, FALSE);
  if ($election->isNominationsStarted && $election->is_visible == 1) {
    return civicrm_api3_create_error ( ts('Election position cannot be deleted once election has been started.'));
  }

  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * ElectionPosition.get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_election_position_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
