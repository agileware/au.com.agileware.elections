<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Elections_Form_ElectionVisibility extends CRM_Elections_Form_Base {

  private $eId = 0;
  private $election = NULL;

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

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ($this->election->is_visible) ? E::ts('Deactivate Election') : E::ts('Activate Election'),
        'isDefault' => TRUE,
      ],
    ]);

    if ($this->election->is_visible) {
      CRM_Utils_System::setTitle(E::ts('Deactivate Election'));
    }
    else {
      CRM_Utils_System::setTitle(E::ts('Activate Election'));
    }

    $this->assign('election', $this->election);
    $this->addElement('hidden', 'eid', $this->eId);

    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();
    $this->eId = (isset($values['eid'])) ? $values['eid'] : 0;
    $isActive = (bool) CRM_Core_DAO::getFieldValue('CRM_Elections_DAO_Election', $this->eId, 'is_visible');

    try {
      if (!$isActive) {
        CRM_Elections_BAO_Election::assertCanActivate($this->eId);
      }
      \Civi\Api4\Election::update(FALSE)
        ->addWhere('id', '=', $this->eId)
        ->addValue('is_visible', !$isActive)
        ->execute();
      CRM_Core_Session::setStatus($isActive ? E::ts('Election has been deactivated successfully.') : E::ts('Election has been activated successfully.'), '', 'success');
    }
    catch (CRM_Core_Exception $e) {
      CRM_Core_Session::setStatus($e->getMessage(), E::ts('Election not changed'), 'error');
    }

    parent::postProcess();
    $this->finish(CRM_Utils_System::url('civicrm/elections/manage', 'reset=1'));
  }

}
