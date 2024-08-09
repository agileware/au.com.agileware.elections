<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Elections_Form_AcceptNomination extends CRM_Elections_Form_Base {
  private $enId = 0;
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
      $this->electionNomination = civicrm_api3('ElectionNomination', 'getsingle', array(
        'id' => $this->enId,
        'member_nominee' => CRM_Core_Session::singleton()->getLoggedInContactID(),
        'is_eligible_candidate' => 1,
        'return' => ["has_accepted_nomination", "election_position_id.name", "has_rejected_nomination", "election_position_id.election_id.name", "election_position_id.election_id"],
      ));
    } catch (CiviCRM_API3_Exception $e) {
      throwAccessDeniedException($this, $e->getMessage());
      return;
    }

    $election = findElectionById($this->electionNomination['election_position_id.election_id']);
    if ((!$election->isVisible && !isElectionAdmin())) {
      throwAccessDeniedPage($this);
      return;
    }

    if ($this->electionNomination['has_accepted_nomination'] == 1) {
      throwAccessDeniedException($this, 'You have accepted this nomination.');
      return;
    }

    if ($this->electionNomination['has_rejected_nomination'] == 1) {
      throwAccessDeniedException($this, 'You cannot accept the rejected nomination.');
      return;
    }

    if ($election->isVotingStarted) {
      throwAccessDeniedException($this, 'You cannot accept the nomination once voting is started.');
      return;
    }

    $this->assign('electionNomination', $this->electionNomination);

    $this->add('textarea', 'nominationcomments', 'Comments', array('cols' => 55, 'rows' => 6), FALSE);
    $this->addElement('hidden', 'enid', $this->enId);

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Accept'),
        'isDefault' => TRUE,
      ),
    ));

    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();

    civicrm_api3('ElectionNomination', 'create', array(
      'id' => $this->enId,
      'comments' => $values['nominationcomments'],
      'has_accepted_nomination' => 1,
    ));

    CRM_Core_Session::setStatus('You have accepted the nomination.', '', 'success');
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/elections/view', 'eid=' . $this->electionNomination['election_position_id.election_id'] . ''));

    parent::postProcess();
  }
}
