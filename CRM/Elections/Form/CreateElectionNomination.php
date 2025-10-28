<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Elections_Form_CreateElectionNomination extends CRM_Elections_Form_Base {

  private $eId = 0;
  private $cid = NULL;
  private $cs = NULL;
  private $isElectionAdmin = FALSE;
  private $election = NULL;

  private $enId = 0;
  private $secondElectionNomination = NULL;

  public function buildQuickForm() {
    $this->eId = retrieveElectionIdFromUrl($this);
    hideNonRequiredItemsOnPage($this);
    if ($this->eId == -1) {
      return;
    }
    $this->election = findElectionById($this->eId);

    // User is not logged in, and the election does not allow checksum access
    if ( empty( \CRM_Core_Session::getLoggedInContactID() ) && !filter_var($this->election->allow_checksum_access, FILTER_VALIDATE_BOOL) ) {
      throwAccessDeniedPage($this);
      return;
    }

    $this->isElectionAdmin = isElectionAdmin();

    if (!$this->election->isNominationsInProgress || (!$this->election->isVisible && !isElectionAdmin())) {
      throwAccessDeniedPage($this);
      return;
    }

    // Logged in but not allowed to vote
    if ( !empty( \CRM_Core_Session::getLoggedInContactID() ) && !isLoggedInMemberAllowedToVote( $this->eId ) ) {
      throwNonMemberAccessDenied($this);
      return;
    }

    if (!$this->checkSecondNomination()) {
      throwAccessDeniedPage($this);
      return;
    }

    $this->assign('election', $this->election);
    $this->assign('isElectionAdmin', $this->isElectionAdmin);

    CRM_Utils_System::setTitle('Submit Nomination for ' . $this->election->name);

    if ( $this->cid && $this->cs ) {
      // Expose to Smarty
      $contact = \Civi\Api4\Contact::get(FALSE)
                                    ->addSelect('email_primary', 'display_name')
                                    ->addWhere('id', '=', $this->cid)
                                    ->execute()
                                    ->first();

      $this->assign( 'checksum_authenticated', $contact );

      $login_url = getLoginPageURL(\CRM_Utils_System::currentPath());
      $this->assign( 'login_url', sprintf( '%s?eid=%s', $login_url, $this->eId ) );
    }

    $this->addFormElements();

    parent::buildQuickForm();
  }

  private function checkSecondNomination() {
    $this->enId = CRM_Utils_Request::retrieve('enid', 'Positive', $this, FALSE, 0);
    if ($this->enId) {
      try {
        $this->secondElectionNomination = civicrm_api3('ElectionNomination', 'getsingle', [
          'id'                               => $this->enId,
          'election_position_id.election_id' => $this->eId,
        ]);
      }
      catch (CiviCRM_API3_Exception $e) {
        return FALSE;
      }
    }
    return TRUE;
  }

  public function setDefaultValues() {
    if ($this->secondElectionNomination) {
      $this->_defaults['position'] = $this->secondElectionNomination['election_position_id'];
      $this->_defaults['contact'] = $this->secondElectionNomination['member_nominee'];
    }
    return $this->_defaults;
  }

  /**
   * Add form elements election nominations
   */
  public function addFormElements() {

    $electionPositions = civicrm_api3('ElectionPosition', 'get', [
       'election_id'     => $this->election->id,
       'options'         => ['limit' => 0, 'sort' => 'sortorder ASC'],
       'sequential'      => TRUE,
    ]);
    $electionPositions = $electionPositions['values'];
    $positions = [];
    foreach ($electionPositions as $electionPosition) {
      $positions[] = [
        'text' => $electionPosition['name'],
        'id'   => $electionPosition['id'],
      ];
    }

    if (empty($positions)) {
      throwAccessDeniedException($this, 'There are no positions available for selected election.');
      return;
    }

    $this->add('select2', 'position', 'Nominated Position', $positions, TRUE);
    $this->addEntityRef('contact', 'Nominee', [
      'entity' => 'ElectionNominee',
      'placeholder' => '- Select Nominee -',
      'api' => [
        'election_id' => $this->election->id,
      ],
    ], TRUE);
    $this->addElement('hidden', 'eid', $this->eId);
    if ( $this->cid && $this->cs ) {
      $this->addElement('hidden', 'cid', $this->cid);
      $this->addElement('hidden', 'cs', $this->cs);
    }
    $this->add('textarea', 'reason', 'Why do you want to nominate this person for this position?', ['cols' => 55, 'rows' => 6], FALSE);

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Nominate'),
        'isDefault' => TRUE,
      ],
    ]);
  }

  /**
   * Get nomination id given by contact and position
   *
   * @param $values
   * @return array|int
   * @throws CRM_Extension_Exception
   * @throws CiviCRM_API3_Exception
   */
  private function getNominationId($values) {
    $nominationId = 0;
    $nomination = civicrm_api3('ElectionNomination', 'get', [
      'member_nominee'        => $values['contact'],
      'election_position_id'  => $values['position'],
      'sequential'            => TRUE,
	  'options'               => ['limit' => 0],
    ]);

    if ($nomination['count'] > 0) {
      $nomination = $nomination['values'][0];
      if ($nomination['has_rejected_nomination'] == 1) {
        CRM_Core_Session::setStatus('Selected member has withdrawn the nomination, You cannot nominate the member again for selected position.', '', 'error');
        CRM_Utils_System::redirect(Civi::url('current://civicrm/elections/view', 'eid=' . $this->eId ));
        return -1;
      }
      $nominationId = $nomination['id'];
    }
    else {
      $nominationId = civicrm_api3('ElectionNomination', 'create', [
        'member_nominee'        => $values['contact'],
        'election_position_id'  => $values['position'],
      ]);
      $nominationId = $nominationId['id'];
    }

    if (!$nominationId) {
      throwAccessDeniedException($this, 'Some error occurred while creating a nomination, Please try again.');
      return -1;
    }

    return $nominationId;
  }

  public function preProcess() {
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive');
    $cs = CRM_Utils_Request::retrieve('cs', 'String');

    // Only store these if the user is not logged in. Otherwise we want to
    // defer to the logged in contact.
    if ( empty( \CRM_Core_Session::getLoggedInContactID() ) && $cid && $cs ) {
      $this->cid = $cid;
      $this->cs = $cs;
    }
  }

  public function postProcess() {
    $values = $this->exportValues();
    $nominationId = $this->getNominationId($values);
    if ($nominationId < 0) {
      return;
    }
    $nominatorId = CRM_Core_Session::singleton()->getLoggedInContactID();

    if ( !$nominatorId && $this->cid && $this->cs ) {
      $nominatorId = $this->cid;
    }

    $seconderParams = [
      'member_nominator'       => $nominatorId,
      'election_nomination_id' => $nominationId,
    ];
    $nominationSeconder = civicrm_api3('ElectionNominationSeconder', 'getcount', $seconderParams);

    if ($nominationSeconder != 0) {
      CRM_Core_Session::setStatus('Nomination submitted.', '', 'success');
    }
    else {
      $seconderParams['description'] = $values['reason'];
      $nominationSeconder = civicrm_api3('ElectionNominationSeconder', 'create', $seconderParams);

      $isEligibleCandidate = $this->markNominationAsEligibleCandidate($nominationId);
      $this->createNominationActivity([
        'nominator'              => $nominatorId,
        'nomination_id'          => $nominationId,
        'nominee'                => $values['contact'],
        'nomination_seconder_id' => $nominationSeconder['id'],
        'is_eligible_candidate'  => ($isEligibleCandidate) ? 1 : 0,
      ]);
      CRM_Core_Session::setStatus('Nomination submitted.', '', 'success');
    }

    parent::postProcess();

    // Redirect back to the main election info view
    $redirectUrl = Civi::url('current://civicrm/elections/view');
    $redirectUrl->addQuery(['eid' => $this->eId]);

    // Conditionally add contact ID and checksum
    if ( $this->cid && $this->cs ) {
        $redirectUrl->addQuery(['cid' => $this->cid]);
        $redirectUrl->addQuery(['cs' => $this->cs]);
    }
    CRM_Utils_System::redirect( $redirectUrl );
  }

  private function createNominationActivity($params) {

    $electionNomination = civicrm_api3('ElectionNomination', 'get', [
      'sequential' => TRUE,
      'return'     => ['member_nominee.display_name', 'election_position_id.name'],
      'id'         => $params['nomination_id'],
	  'options'    => ['limit' => 0],
    ]);
    $electionNomination = $electionNomination['values'][0];

    $currentDateTime = (new DateTime())->format('Y-m-d H:i:s');
    civicrm_api3('Activity', 'create', [
      'source_contact_id' => $params['nominator'],
      'activity_type_id' => 'Nomination',
      'activity_date_time' => $currentDateTime,
      'status_id' => 'Completed',
      'assignee_id' => $params['nominator'],
      'target_id' => $params['nominee'],
      'source_record_id' => $params['nomination_seconder_id'],
      'is_star'  => $params['is_eligible_candidate'],
      'subject'  => 'Nominated ' . $electionNomination['member_nominee.display_name'] . ' for the position of ' . $electionNomination['election_position_id.name'],
    ]);
  }

  /**
   * Mark nomination as eligible candidate if it has enough nominations.
   *
   * @param $nominationId
   * @return bool
   * @throws CiviCRM_API3_Exception
   */
  private function markNominationAsEligibleCandidate($nominationId) {
    $totalNominations = civicrm_api3('ElectionNominationSeconder', 'getcount', [
      'election_nomination_id'  => $nominationId,
    ]);
    if ($totalNominations >= $this->election->required_nominations) {
      civicrm_api3('ElectionNomination', 'create', [
        'id'                    => $nominationId,
        'is_eligible_candidate' => 1,
      ]);
      return TRUE;
    }
    return FALSE;
  }

}
