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
    $this->eId = CRM_Utils_Request::retrieve('eid', 'Positive', $this, FALSE, 0);
    if (!$this->eId) {
      $this->eId = 0;
    }
    $this->addElement('hidden', 'eid', $this->eId);
    CRM_Utils_System::setTitle(E::ts('Delete Election'));

    $election = new CRM_Elections_BAO_Election();
    $election->id = $this->eId;
    $found = $this->eId && $election->find(TRUE);
    $canDelete = $found ? $election->canDelete() : TRUE;
    if (!$found) {
      $this->assign('deleteBlocked', E::ts('The election could not be found.'));
    }
    elseif ($canDelete !== TRUE) {
      // Say why inside the dialog, and leave only Cancel: there is nothing to confirm.
      $this->assign('deleteBlocked', $canDelete);
    }
    else {
      $this->assign('deleteMessage', E::ts('This deletes the election "%1". It can no longer be seen or used, and its positions, nominations and votes cannot be recovered.', [1 => CRM_Elections_Helper_ResultsDisplay::decode($election->name)]));
    }

    $buttons = [];
    if ($found && $canDelete === TRUE) {
      $buttons[] = ['type' => 'submit', 'name' => E::ts('Delete'), 'isDefault' => TRUE, 'icon' => 'fa-trash', 'class' => 'election-danger'];
    }
    $buttons[] = ['type' => 'cancel', 'name' => E::ts('Cancel')];
    $this->addButtons($buttons);
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
        $this->stayOnForm($canDelete, E::ts('Election not deleted'));
        return;
      }

      if ($election->updated_at == 0) {
        $election->updated_at = NULL;
      }

      $election->is_deleted = 1;
      if ($election->save()) {
        CRM_Core_Session::setStatus(E::ts('Election deleted.'), '', 'success');
      }
      else {
        CRM_Core_Session::setStatus(E::ts('The election could not be deleted. Try again, and ask a site administrator if it happens again.'), E::ts('Election not deleted'), 'error');
      }
    }
    parent::postProcess();
    $this->finish(CRM_Utils_System::url('civicrm/elections/manage', 'reset=1'));
  }

}
