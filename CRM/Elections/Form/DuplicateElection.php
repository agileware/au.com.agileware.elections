<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * Copy an election, with its positions, as a new inactive election.
 */
class CRM_Elections_Form_DuplicateElection extends CRM_Elections_Form_Base {

  private $eId = 0;

  private $election = NULL;

  public function buildQuickForm() {
    if (throwUnauthorizedMessageIfRequired($this)) {
      return;
    }
    $this->eId = CRM_Utils_Request::retrieve('eid', 'Positive', $this, TRUE);
    $this->election = findElectionById($this->eId);
    CRM_Utils_System::setTitle(E::ts('Duplicate Election'));

    $this->add('text', 'name', E::ts('Name of the copy'), ['size' => 40], TRUE);
    $this->add('datepicker', 'visibility_start_date', E::ts('Start date of the copy'), [], TRUE);
    $this->addElement('hidden', 'eid', $this->eId);
    $this->assign('election', $this->election);
    $this->addButtons([
      ['type' => 'submit', 'name' => E::ts('Duplicate'), 'isDefault' => TRUE],
      ['type' => 'cancel', 'name' => E::ts('Cancel')],
    ]);
    parent::buildQuickForm();
  }

  public function setDefaultValues() {
    return [
      'name' => E::ts('Copy of %1', [1 => $this->election->name]),
      'visibility_start_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
    ];
  }

  public function postProcess() {
    $values = $this->exportValues();
    $shift = strtotime($values['visibility_start_date']) - strtotime($this->election->visibility_start_date);
    $copy = CRM_Elections_BAO_Election::duplicate($this->eId, $values['name'], $shift);
    CRM_Core_Session::setStatus(E::ts('"%1" has been created as a copy. It is inactive until you activate it.', [1 => $copy['name']]), '', 'success');
    $this->finish(CRM_Utils_System::url('civicrm/elections/manage', 'reset=1'));
  }

}
