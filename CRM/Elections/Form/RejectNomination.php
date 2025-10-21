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

  public function buildQuickForm() {
    $this->enId = CRM_Utils_Request::retrieve('enid', 'Positive', $this, FALSE, 0);
    hideNonRequiredItemsOnPage($this);

    if (!$this->enId) {
      $this->enId = 0;
      throwAccessDeniedPage($this);
      return;
    }

    try {
      $this->electionNomination = civicrm_api3('ElectionNomination', 'getsingle', [
        'id' => $this->enId,
        'member_nominee' => CRM_Core_Session::singleton()->getLoggedInContactID(),
        'return' => ['has_accepted_nomination', 'is_eligible_candidate', 'election_position_id.name', 'election_position_id.election_id.name', 'has_rejected_nomination', 'election_position_id.election_id'],
      ]);
    }
    catch (CiviCRM_API3_Exception $e) {
      throwAccessDeniedException($this, $e->getMessage());
      return;
    }

    $election = findElectionById($this->electionNomination['election_position_id.election_id']);
    if ((!$election->isVisible && !isElectionAdmin())) {
      throwAccessDeniedPage($this);
      return;
    }

    if ($this->electionNomination['has_rejected_nomination'] == 1) {
      throwAccessDeniedException($this, 'You have withdrawn this nomination.');
      return;
    }

    if ($election->isVotingStarted) {
      throwAccessDeniedException($this, 'You cannot withdraw the nomination once voting is started.');
      return;
    }

    $this->assign('electionNomination', $this->electionNomination);

    $this->add('textarea', 'nominationcomments', 'Comments', ['cols' => 55, 'rows' => 6], FALSE);
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
        'name' => E::ts('Withdraw'),
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

    civicrm_api3('ElectionNomination', 'create', [
      'id' => $this->enId,
      'rejection_comments' => $values['nominationcomments'],
      'has_rejected_nomination' => 1,
      'has_accepted_nomination' => 0,
    ]);

    CRM_Core_Session::setStatus('You have withdrawn the nomination.', '', 'success');

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
