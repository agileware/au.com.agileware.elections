<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Elections_Form_DeleteElectionPosition extends CRM_Elections_Form_Base {

  private $eId = 0;
  private $epId = 0;
  private $election = NULL;
  private $electionPosition = NULL;

  public function buildQuickForm() {
    hideNonRequiredItemsOnPage($this);
    if (throwUnauthorizedMessageIfRequired($this)) {
      return;
    }

    $this->eId = retrieveElectionIdFromUrl($this);
    if ($this->eId == -1) {
      return;
    }
    $this->election = findElectionById($this->eId);
    CRM_Utils_System::setTitle(E::ts('Delete Position'));
    $this->addElement('hidden', 'eid', $this->eId);

    $buttons = [];
    if ($this->election->canEdit() !== TRUE) {
      // Say why inside the dialog, and leave only Cancel: there is nothing to confirm.
      $this->assign('deleteBlocked', E::ts('Positions cannot be deleted while the election is active and its nominations have started. Deactivate the election first.'));
    }
    elseif (!$this->getElectionPosition()) {
      $this->assign('deleteBlocked', E::ts('The position could not be found.'));
    }
    else {
      $this->assign('deleteMessage', E::ts('This deletes the position "%1" and the nominations made for it. They cannot be recovered.', [1 => CRM_Elections_Helper_ResultsDisplay::decode($this->electionPosition['name'] ?? '')]));
      $buttons[] = ['type' => 'submit', 'name' => E::ts('Delete'), 'isDefault' => TRUE, 'icon' => 'fa-trash', 'class' => 'election-danger'];
    }
    $this->addElement('hidden', 'epid', $this->epId);
    $buttons[] = ['type' => 'cancel', 'name' => E::ts('Cancel')];
    $this->addButtons($buttons);

    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();
    $this->eId = (int) ($values['eid'] ?? 0);
    $this->epId = (int) ($values['epid'] ?? 0);
    if (!$this->getElectionPosition()) {
      $this->stayOnForm(E::ts('The position could not be found.'), E::ts('Position not deleted'));
      return;
    }
    try {
      civicrm_api3('ElectionPosition', 'delete', [
        'id' => $this->electionPosition['id'],
      ]);
    }
    catch (CRM_Core_Exception $e) {
      $this->stayOnForm($e->getMessage(), E::ts('Position not deleted'));
      return;
    }

    CRM_Core_Session::setStatus(E::ts('Position deleted.'), '', 'success');

    parent::postProcess();

    $this->finish(CRM_Utils_System::url('civicrm/elections/positions', 'reset=1&eid=' . $this->eId));
  }

  private function getElectionPosition() {
    $this->epId = CRM_Utils_Request::retrieve('epid', 'Positive', $this, FALSE, 0);
    if (!$this->epId) {
      $this->epId = 0;
    }

    if ($this->epId == 0) {
      return FALSE;
    }

    $this->electionPosition = civicrm_api3('ElectionPosition', 'get', [
      'id' => $this->epId,
      'election_id' => $this->eId,
    ]);

    if ($this->electionPosition['count'] == 0) {
      return FALSE;
    }
    // The API returns the matches keyed by id.
    $this->electionPosition = reset($this->electionPosition['values']);

    return TRUE;
  }

}
