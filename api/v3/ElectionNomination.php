<?php
use CRM_Elections_ExtensionUtil as E;

/**
 * ElectionNomination.create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_election_nomination_create_spec(&$spec) {
  $spec['member_nominee']['api.required'] = 1;
  $spec['election_position_id']['api.required'] = 1;
}

/**
 * ElectionNomination.create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_election_nomination_create($params) {
  if (!isset($params['id'])) {
    $nominationsCount = civicrm_api3("ElectionNomination", "getcount", array(
      'member_nominee'       => $params['member_nominee'],
      'election_position_id' => $params['election_position_id'],
    ));
    if ($nominationsCount > 0) {
      return civicrm_api3_create_error ( ts('Member is already nominated for the selected position.'));
    }

    $electionPosition = civicrm_api3('ElectionPosition', 'getsingle', array(
      'id' => $params['election_position_id'],
    ));
    $electionId = $electionPosition['election_id'];
    $election = findElectionById($electionId, FALSE);
    if ($election->is_deleted || !$election->is_visible) {
      return civicrm_api3_create_error ( ts('Nomination cannot be added for deleted election.'));
    }

    if (!$election->isNominationsStarted) {
      return civicrm_api3_create_error ( ts('Nomination cannot be added before nomination period is started.'));
    }

    if (!$election->isNominationsInProgress) {
      return civicrm_api3_create_error ( ts('Nomination cannot be added after nomination period is ended.'));
    }

  }

  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * ElectionNomination.delete API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_election_nomination_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * ElectionNomination.get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_election_nomination_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
