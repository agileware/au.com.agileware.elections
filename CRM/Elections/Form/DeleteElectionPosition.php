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
    $canEdit = $this->election->canEdit();
    if ($canEdit !== TRUE) {
      throw new CRM_Extension_Exception($canEdit);
    }
    if (!$this->getElectionPosition()) {
      return;
    }

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Delete'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
      ],
    ]);

    $this->addElement('hidden', 'eid', $this->eId);
    $this->addElement('hidden', 'epid', $this->epId);

    parent::buildQuickForm();
  }

  public function postProcess() {
    civicrm_api3('ElectionPosition', 'delete', [
       'id' => $this->electionPosition['id'],
    ]);

    CRM_Core_Session::setStatus('Election position has been deleted successfully.', '', 'success');

    parent::postProcess();

    CRM_Utils_System::redirect(Civi::url('current://civicrm/elections/positions', 'eid=' . $this->eId));
  }

  private function getElectionPosition() {
    $this->epId = CRM_Utils_Request::retrieve('epid', 'Positive', $this, FALSE, 0);
    if (!$this->epId) {
      $this->epId = 0;
    }

    if ($this->epId == 0) {
      throwAccessDeniedPage($this);
      return FALSE;
    }

    $this->electionPosition = civicrm_api3('ElectionPosition', 'get', [
      'id' => $this->epId,
      'election_id' => $this->eId,
    ]);

    if ($this->electionPosition['count'] == 0) {
      throwAccessDeniedPage($this);
      return FALSE;
    }

    return TRUE;
  }

}
