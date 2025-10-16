<?php
use CRM_Elections_ExtensionUtil as E;

/**
 * Election.create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_election_create_spec(&$spec) {
  $spec['name']['api.required'] = 1;
  $spec['visibility_start_date']['api.required'] = 1;
  $spec['visibility_end_date']['api.required'] = 1;
  $spec['nomination_start_date']['api.required'] = 1;
  $spec['nomination_end_date']['api.required'] = 1;
  $spec['advertise_candidates_date']['api.required'] = 1;
  $spec['voting_start_date']['api.required'] = 1;
  $spec['voting_end_date']['api.required'] = 1;
  $spec['result_date']['api.required'] = 1;
}

/**
 * Election.create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_election_create($params) {
  $apiDateFormat = 'YmdHis';

  if (!isset($params['has_results_generated']) || $params['has_results_generated'] != 1) {
    $errors = CRM_Elections_BAO_Election::compareDates($params, $apiDateFormat);
    if (!empty($errors)) {
      return civicrm_api3_create_error('Please correct the form errors.', [
          'errors' => $errors,
      ]);
    }
    if (isset($params['id'])) {
      //Election should not be edited/deleted if nomination period is started already.
      $election = findElectionById($params['id']);
      $currentTime = new DateTime();
      $nominationStartDate = DateTime::createFromFormat('Y-m-d H:i:s', $election->nomination_start_date);
      if (CRM_Elections_Helper_Dates::compare($nominationStartDate->format($apiDateFormat), $currentTime->format($apiDateFormat), $apiDateFormat) == 1 && isset($params['is_deleted']) && $params['is_deleted'] == 1 && $election->is_visible == 1) {
        return civicrm_api3_create_error('Election cannot be deleted once it has been started');
      }
    }
  }
  $params['created_by'] = CRM_Core_Session::singleton()->getLoggedInContactID();
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Election.delete API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_election_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Election.get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_election_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Election.generateresults API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_election_generateresults($params) {
  $currentDateTime = new DateTime();
  $elections = civicrm_api3('Election', 'get', [
    'sequential' => TRUE,
    'result_date' => ['<=' => $currentDateTime->format('Y-m-d H:i:s')],
    'has_results_generated' => 0,
    'options' => ['limit' => 0],
  ]);

  $dao = NULL;
  if (!$elections['count']) {
    return civicrm_api3_create_success(1, [], NULL, NULL, $dao, [
      'message' => 'No election results are scheduled today.',
    ]);
  }

  $elections = $elections['values'];
  $electionResults = new CRM_Elections_Helper_Results();
  foreach ($elections as $election) {
    $electionResults->generateResult($election['id'], $election['anonymize_votes']);
  }

  return civicrm_api3_create_success(1, [], NULL, NULL, $dao, [
     'message' => 'Successfully generated results for ' . count($elections) . ' elections.',
  ]);

}
