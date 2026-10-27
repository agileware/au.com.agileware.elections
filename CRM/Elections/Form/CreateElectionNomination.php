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

    // Not allowed to take part (logged in, or using a personal link)
    if ( !CRM_Elections_Helper_Eligibility::canNominate( $this->eId ) ) {
      throwNonMemberAccessDenied($this);
      return;
    }

    if (!$this->checkSecondNomination()) {
      throwAccessDeniedPage($this);
      return;
    }

    $this->assign('election', $this->election);
    $this->assign('isElectionAdmin', $this->isElectionAdmin);

    CRM_Utils_System::setTitle(E::ts('Submit Nomination for %1', [1 => $this->election->name]));

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
      catch (CRM_Core_Exception $e) {
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
      if (($electionPosition['position_type'] ?? '') === CRM_Elections_BAO_ElectionPosition::TYPE_REFERENDUM) {
        // A question has no candidates.
        continue;
      }
      $positions[] = [
        'text' => $electionPosition['name'],
        'id'   => $electionPosition['id'],
      ];
    }

    if (empty($positions)) {
      throwAccessDeniedException($this, E::ts('There are no positions available for selected election.'));
      return;
    }

    $this->add('select2', 'position', E::ts('Nominated Position'), $positions, TRUE, ['placeholder' => E::ts('- Select Position -')]);
    $this->addEntityRef('contact', 'Nominee', [
      'entity' => 'ElectionNominee',
      'placeholder' => E::ts('- Select Nominee -'),
      'api' => [
        'election_id' => $this->election->id,
      ],
    ], TRUE);
    $this->addElement('hidden', 'eid', $this->eId);
    if ( $this->cid && $this->cs ) {
      $this->addElement('hidden', 'cid', $this->cid);
      $this->addElement('hidden', 'cs', $this->cs);
    }
    $this->add('textarea', 'reason', E::ts('Why do you want to nominate this person for this position?'), ['cols' => 55, 'rows' => 6], FALSE);

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Nominate'),
        'isDefault' => TRUE,
      ],
    ]);
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
    $nominatorId = CRM_Elections_Helper_Eligibility::getActingContactId();

    $redirectUrl = Civi::url('current://civicrm/elections/view');
    $redirectUrl->addQuery(['eid' => $this->eId]);
    // Conditionally add contact ID and checksum
    if ($this->cid && $this->cs) {
      $redirectUrl->addQuery(['cid' => $this->cid]);
      $redirectUrl->addQuery(['cs' => $this->cs]);
    }

    try {
      CRM_Elections_Helper_Process::nominate($this->eId, $values['position'], $values['contact'], $nominatorId, $values['reason'] ?? NULL);
      CRM_Core_Session::setStatus(E::ts('Nomination submitted.'), '', 'success');
    }
    catch (CRM_Core_Exception $e) {
      CRM_Core_Session::setStatus($e->getMessage(), '', 'error');
    }

    parent::postProcess();
    CRM_Utils_System::redirect($redirectUrl);
  }

}
