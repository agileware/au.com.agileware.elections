<?php
use CRM_Elections_ExtensionUtil as E;

/**
 * ElectionNominationSeconder.create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_election_nomination_seconder_create_spec(&$spec) {
  $spec['member_nominator']['api.required'] = 1;
  $spec['election_nomination_id']['api.required'] = 1;
}

/**
 * ElectionNominationSeconder.create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_election_nomination_seconder_create($params) {
  if (!isset($params['id'])) {
    $nominationSeconderCount = civicrm_api3('ElectionNominationSeconder', 'getcount', [
      'member_nominator'       => $params['member_nominator'],
      'election_nomination_id' => $params['election_nomination_id'],
    ]);
    if ($nominationSeconderCount > 0) {
      return civicrm_api3_create_error('Contact is already nominated by given nominator.');
    }

    $electionInfo = civicrm_api3('ElectionNomination', 'get', [
      'id'         => $params['election_nomination_id'],
      'sequential' => TRUE,
      'return'    => [
        'election_position_id.election_id.id',
      ],
	  'options' => ['limit' => 0],
    ]);
    $electionId = $electionInfo['values'][0]['election_position_id.election_id.id'];

    $election = findElectionById($electionId, FALSE);
    if ($election->is_deleted || !$election->is_visible) {
      return civicrm_api3_create_error('Nomination seconder cannot be added for a deleted election.');
    }

    if (!$election->isNominationsInProgress) {
      return civicrm_api3_create_error('Nomination seconder cannot be added after nomination period is ended.');
    }
  }

  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * ElectionNominationSeconder.delete API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_election_nomination_seconder_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * ElectionNominationSeconder.get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_election_nomination_seconder_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
