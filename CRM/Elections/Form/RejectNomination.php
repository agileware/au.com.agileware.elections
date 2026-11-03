<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Elections_Form_RejectNomination extends CRM_Elections_Form_Base {
  private $enId = 0;
  private $cid = NULL;
  private $cs = NULL;
  private $electionNomination = NULL;
  private $isCandidate = FALSE;

  public function buildQuickForm() {
    $this->enId = CRM_Utils_Request::retrieve('enid', 'Positive', $this, FALSE, 0);
    hideNonRequiredItemsOnPage($this);

    if (!$this->enId) {
      $this->enId = 0;
      throwAccessDeniedPage($this);
      return;
    }

    // The nominee is the logged in contact, or the contact identified by a valid checksum link.
    $nomineeId = CRM_Elections_Helper_Eligibility::getActingContactId();
    if (empty($nomineeId)) {
      throwAccessDeniedPage($this);
      return;
    }

    try {
      $this->electionNomination = civicrm_api3('ElectionNomination', 'getsingle', [
        'id' => $this->enId,
        'member_nominee' => $nomineeId,
        'return' => ['has_accepted_nomination', 'is_write_in', 'is_eligible_candidate', 'election_position_id.name', 'election_position_id.election_id.name', 'has_rejected_nomination', 'has_withdrawn_nomination', 'election_position_id.election_id'],
      ]);
    }
    catch (CRM_Core_Exception $e) {
      throwAccessDeniedException($this, E::ts('You do not have access to this nomination. Log in as the person who was nominated, or use the personal link in your email.'));
      return;
    }

    $election = findElectionById($this->electionNomination['election_position_id.election_id']);
    CRM_Elections_Helper_Utils::assignBackToElection($this, $election->id, $this->cid, $this->cs);
    // The words follow what the person has done so far: a nominee who has not accepted declines, a candidate
    // who accepted withdraws. Decision R2 (a stored difference between the two) plugs in here: it will replace
    // $this->isCandidate, which for now is read from has_accepted_nomination.
    $this->isCandidate = $this->electionNomination['has_accepted_nomination'] == 1;
    $this->assign('isCandidate', $this->isCandidate);
    if ((!$election->isVisible && !isElectionAdmin())) {
      throwAccessDeniedPage($this);
      return;
    }

    // User is not logged in, and the election does not allow checksum access
    if ( empty( \CRM_Core_Session::getLoggedInContactID() ) && !filter_var($election->allow_checksum_access, FILTER_VALIDATE_BOOL) ) {
      throwAccessDeniedPage($this);
      return;
    }

    if ($this->electionNomination['has_rejected_nomination'] == 1) {
      throwAccessDeniedException($this, !empty($this->electionNomination['has_withdrawn_nomination']) ? E::ts('You have already withdrawn this nomination.') : E::ts('You have already declined this nomination.'));
      return;
    }

    if (!empty($this->electionNomination['is_write_in']) && !$this->electionNomination['has_accepted_nomination']) {
      // Written in by a voter and not yet answered: can decline until voting closes.
      if ($election->isVotingEnded) {
        throwAccessDeniedException($this, CRM_Elections_Helper_WriteIn::closedMessage($election, $this->electionNomination['election_position_id.name']), [
          'errorKind' => 'status',
          'return_button_text' => E::ts('Back to the election'),
          'return_button_action' => CRM_Elections_Helper_Mailer::link($election, $nomineeId, 'civicrm/elections/view'),
        ]);
        return;
      }
    }
    elseif ($election->isVotingStarted) {
      throwAccessDeniedException($this, $this->isCandidate
        ? E::ts('You can no longer withdraw because voting has started.')
        : E::ts('You can no longer decline this nomination because voting has started.'));
      return;
    }

    $this->assign('electionNomination', $this->electionNomination);
    CRM_Utils_System::setTitle($this->isCandidate ? E::ts('Withdraw Nomination') : E::ts('Decline Nomination'));

    $this->add('textarea', 'nominationcomments', E::ts('Comments'), ['cols' => 55, 'rows' => 6], FALSE);
    $this->addElement('hidden', 'enid', $this->enId);

    if ( $this->cid && $this->cs ) {
      // Expose to Smarty
      $contact = \Civi\Api4\Contact::get(FALSE)
                                    ->addSelect('email_primary', 'display_name')
                                    ->addWhere('id', '=', $this->cid)
                                    ->execute()
                                    ->first();

      $this->assign( 'checksum_authenticated', $contact );

      $login_url = getLoginPageURL(\CRM_Utils_System::currentPath());
      $this->assign( 'login_url', sprintf( '%s?eid=%s', $login_url, $this->electionNomination['election_position_id.election_id'] ) );

      // Add to form elements
      $this->addElement('hidden', 'cid', $this->cid);
      $this->addElement('hidden', 'cs', $this->cs);
    }

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => $this->isCandidate ? E::ts('Withdraw') : E::ts('Decline'),
        'isDefault' => TRUE,
      ],
    ]);

    parent::buildQuickForm();
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

    try {
      // R2: this is the call that records the answer; it will record which of the two it was.
      CRM_Elections_Helper_Process::withdraw($this->enId, CRM_Elections_Helper_Eligibility::getActingContactId(), $values['nominationcomments']);
      CRM_Core_Session::setStatus($this->isCandidate ? E::ts('Nomination withdrawn.') : E::ts('Nomination declined.'), '', 'success');
    }
    catch (CRM_Core_Exception $e) {
      CRM_Core_Session::setStatus($e->getMessage(), $this->isCandidate ? E::ts('Nomination not withdrawn') : E::ts('Nomination not declined'), 'error');
    }

    // Redirect back to the main election info view
    $redirectUrl = Civi::url('current://civicrm/elections/view');
    $redirectUrl->addQuery(['eid' => $this->electionNomination['election_position_id.election_id']]);

    // Conditionally add contact ID and checksum
    if ( $this->cid && $this->cs ) {
        $redirectUrl->addQuery(['cid' => $this->cid]);
        $redirectUrl->addQuery(['cs' => $this->cs]);
    }
    CRM_Utils_System::redirect( $redirectUrl );

    parent::postProcess();
  }

}
