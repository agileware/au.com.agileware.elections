<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Elections_Form_DeleteElection extends CRM_Elections_Form_Base {
  private $eId = 0;

  public function buildQuickForm() {
    hideNonRequiredItemsOnPage($this);
    if (throwUnauthorizedMessageIfRequired($this)) {
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

    $this->eId = CRM_Utils_Request::retrieve('eid', 'Positive', $this, FALSE, 0);
    if (!$this->eId) {
      $this->eId = 0;
    }
    $this->addElement('hidden', 'eid', $this->eId);
    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();
    $this->eId = (isset($values['eid'])) ? $values['eid'] : 0;
    $election = new CRM_Elections_BAO_Election();
    $election->id = $this->eId;

    if ($election->find(TRUE)) {
      $canDelete = $election->canDelete();
      if ($canDelete !== TRUE) {
        throw new CRM_Extension_Exception($canDelete);
      }

      if ($election->updated_at == 0) {
        $election->updated_at = NULL;
      }

      $election->is_deleted = 1;
      if ($election->save()) {
        CRM_Core_Session::setStatus('Election has been deleted successfully.', '', 'success');
      }
      else {
        CRM_Core_Session::setStatus('Error occurred while deleting an election.', '', 'danger');
      }
    }
    parent::postProcess();
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/elections'));
  }

}
