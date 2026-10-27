<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * Copy a position to the end of its election's list.
 */
class CRM_Elections_Form_DuplicateElectionPosition extends CRM_Elections_Form_Base {

  private $positionId = 0;

  private $position = NULL;

  public function buildQuickForm() {
    if (throwUnauthorizedMessageIfRequired($this)) {
      return;
    }
    $this->positionId = CRM_Utils_Request::retrieve('epid', 'Positive', $this, TRUE);
    $this->position = civicrm_api3('ElectionPosition', 'getsingle', ['id' => $this->positionId]);
    CRM_Utils_System::setTitle(E::ts('Duplicate Position'));
    $this->add('text', 'name', E::ts('Name of the copy'), ['size' => 40], TRUE);
    $this->addElement('hidden', 'epid', $this->positionId);
    $this->assign('position', $this->position);
    $this->addButtons([
      ['type' => 'submit', 'name' => E::ts('Duplicate'), 'isDefault' => TRUE],
      ['type' => 'cancel', 'name' => E::ts('Cancel')],
    ]);
    parent::buildQuickForm();
  }

  public function setDefaultValues() {
    return ['name' => E::ts('Copy of %1', [1 => $this->position['name']])];
  }

  public function postProcess() {
    $values = $this->exportValues();
    try {
      $copy = CRM_Elections_BAO_ElectionPosition::duplicate($this->positionId, $values['name']);
      CRM_Core_Session::setStatus(E::ts('"%1" has been added.', [1 => $copy['name']]), '', 'success');
    }
    catch (CRM_Core_Exception $e) {
      CRM_Core_Session::setStatus($e->getMessage(), E::ts('Position not copied'), 'error');
    }
    $this->finish(CRM_Utils_System::url('civicrm/elections/positions', 'reset=1&eid=' . $this->position['election_id']));
  }

}
