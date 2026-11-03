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
    CRM_Elections_Helper_Utils::assignBackToElection($this, $this->eId, $this->cid, $this->cs);

    // User is not logged in, and the election does not allow checksum access
    if ( empty( \CRM_Core_Session::getLoggedInContactID() ) && !filter_var($this->election->allow_checksum_access, FILTER_VALIDATE_BOOL) ) {
      throwAccessDeniedPage($this);
      return;
    }

    $this->isElectionAdmin = isElectionAdmin();

    $this->election->assignDisplayOptions();
    if (!$this->election->onlineNominations) {
      throwAccessDeniedException($this, E::ts('Candidates for this election are chosen by the organisers, so there are no online nominations.'));
      return;
    }
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

    CRM_Utils_System::setTitle(E::ts('Nominate in %1', [1 => $this->election->name]));

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
    // A nomination that was refused comes back with what the person had chosen and written.
    return CRM_Elections_Helper_Utils::recallInput('nominate') + $this->_defaults;
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
      throwAccessDeniedException($this, E::ts('There are no positions to nominate for yet. Come back when the organisers have added them.'), [
        'return_button_text' => E::ts('Back to the election'),
        'return_button_action' => CRM_Elections_Helper_Utils::backToElectionUrl($this->eId, $this->cid, $this->cs),
      ]);
      return;
    }

    $this->add('select2', 'position', E::ts('Position'), $positions, TRUE, ['placeholder' => E::ts('Select a position')]);
    $this->addEntityRef('contact', E::ts('Nominee'), [
      'entity' => 'ElectionNominee',
      'placeholder' => E::ts('Select a person'),
      'api' => [
        'election_id' => $this->election->id,
      ],
    ], TRUE);
    $this->addElement('hidden', 'eid', $this->eId);
    if ( $this->cid && $this->cs ) {
      $this->addElement('hidden', 'cid', $this->cid);
      $this->addElement('hidden', 'cs', $this->cs);
    }
    $this->add('textarea', 'reason', E::ts('Reason for the nomination'), ['cols' => 55, 'rows' => 6], FALSE);

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
      // The person stays on the form, with what they chose and wrote still in it.
      CRM_Core_Session::setStatus($e->getMessage(), E::ts('Nomination not submitted'), 'error');
      CRM_Elections_Helper_Utils::rememberInput('nominate', array_intersect_key($values, array_flip(['position', 'contact', 'reason'])));
      $formUrl = Civi::url('current://civicrm/elections/nominations/create')->addQuery(['eid' => $this->eId]);
      if ($this->enId) {
        $formUrl->addQuery(['enid' => $this->enId]);
      }
      if ($this->cid && $this->cs) {
        $formUrl->addQuery(['cid' => $this->cid, 'cs' => $this->cs]);
      }
      CRM_Utils_System::redirect($formUrl);
    }

    parent::postProcess();
    CRM_Utils_System::redirect($redirectUrl);
  }

}
