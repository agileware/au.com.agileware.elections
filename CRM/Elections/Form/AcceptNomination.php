<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Elections_Form_AcceptNomination extends CRM_Elections_Form_Base {
  private $enId = 0;
  private $cid = NULL;
  private $cs = NULL;
  private $electionNomination = NULL;

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
        'is_eligible_candidate' => 1,
        'return' => ['has_accepted_nomination', 'election_position_id.name', 'has_rejected_nomination', 'election_position_id.election_id.name', 'election_position_id.election_id'],
      ]);
    } catch (CRM_Core_Exception $e) {
      throwAccessDeniedException($this, E::ts('You are not authorized to access this page.'));
      return;
    }

    $election = findElectionById($this->electionNomination['election_position_id.election_id']);
    if ((!$election->isVisible && !isElectionAdmin())) {
      throwAccessDeniedPage($this);
      return;
    }

    // User is not logged in, and the election does not allow checksum access
    if ( empty( \CRM_Core_Session::getLoggedInContactID() ) && !filter_var($election->allow_checksum_access, FILTER_VALIDATE_BOOL) ) {
      throwAccessDeniedPage($this);
      return;
    }

    if ($this->electionNomination['has_accepted_nomination'] == 1) {
      throwAccessDeniedException($this, E::ts('You already have accepted this nomination.'));
      return;
    }

    if ($this->electionNomination['has_rejected_nomination'] == 1) {
      throwAccessDeniedException($this, E::ts('You cannot accept a rejected nomination.'));
      return;
    }

    if ($election->isVotingStarted) {
      throwAccessDeniedException($this, E::ts('You cannot accept the nomination once voting has started.'));
      return;
    }

    $this->assign('electionNomination', $this->electionNomination);

    $this->add('textarea', 'nominationcomments', E::ts('Candidate statement'), ['cols' => 55, 'rows' => 6], FALSE);
    $this->add('text', 'statement_url', E::ts('Link to a longer statement or CV'), ['size' => 55, 'maxlength' => 2048], FALSE);
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
      $this->assign( 'login_url', sprintf( '%s?eid=%s', $login_url, $election->id ) );

      // Add to form elements
      $this->addElement('hidden', 'cid', $this->cid);
      $this->addElement('hidden', 'cs', $this->cs);
    }

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Accept'),
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

  public function validate() {
    $values = $this->exportValues();
    if (!CRM_Elections_Helper_Candidate::isValidStatementUrl($values['statement_url'] ?? '')) {
      $this->_errors['statement_url'] = E::ts('Enter a web address starting with http:// or https://');
    }
    return parent::validate();
  }

  public function postProcess() {
    $values = $this->exportValues();

    try {
      CRM_Elections_Helper_Process::accept($this->enId, CRM_Elections_Helper_Eligibility::getActingContactId(), $values['nominationcomments'], $values['statement_url'] ?? NULL);
      CRM_Core_Session::setStatus(E::ts('Nomination accepted.'), '', 'success');
    }
    catch (CRM_Core_Exception $e) {
      CRM_Core_Session::setStatus($e->getMessage(), '', 'error');
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
