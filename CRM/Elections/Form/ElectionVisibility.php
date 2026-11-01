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

    $withdrawing = $this->isWithdrawal();
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => $this->election->is_visible ? E::ts('Deactivate') : E::ts('Activate'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
      ],
    ]);

    if ($withdrawing) {
      CRM_Utils_System::setTitle(E::ts('Deactivate Election'));
      $this->add('text', 'confirm_name', E::ts('Type the name of the election to confirm'), ['size' => 40, 'autocomplete' => 'off'], TRUE);
      $this->addFormRule(['CRM_Elections_Form_ElectionVisibility', 'validateWithdrawal'], $this->election->name);
    }
    elseif ($this->election->is_visible) {
      CRM_Utils_System::setTitle(E::ts('Deactivate Election'));
    }
    else {
      CRM_Utils_System::setTitle(E::ts('Activate Election'));
    }

    $this->assign('election', $this->election);
    $this->assign('withdrawing', $withdrawing);
    $this->addElement('hidden', 'eid', $this->eId);

    parent::buildQuickForm();
  }

  /**
   * An election that is active and has started is locked. Deactivating it then hides the election from
   * everybody and lets it be edited again, so the name has to be typed.
   *
   * @return bool
   */
  private function isWithdrawal() {
    return $this->election->is_visible && $this->election->canEdit() !== TRUE;
  }

  /**
   * @param array $values
   * @param array $files
   * @param string $name
   *
   * @return array|bool
   */
  public static function validateWithdrawal($values, $files, $name) {
    if (mb_strtolower(trim((string) ($values['confirm_name'] ?? ''))) !== mb_strtolower(trim((string) $name))) {
      return ['confirm_name' => E::ts('The name does not match the name of this election.')];
    }
    return TRUE;
  }

  public function postProcess() {
    $values = $this->exportValues();
    $this->eId = (isset($values['eid'])) ? $values['eid'] : 0;
    $isActive = (bool) CRM_Core_DAO::getFieldValue('CRM_Elections_DAO_Election', $this->eId, 'is_visible');

    $withdrawn = $isActive && findElectionById($this->eId)->canEdit() !== TRUE;

    try {
      if (!$isActive) {
        CRM_Elections_BAO_Election::assertCanActivate($this->eId);
      }
      CRM_Elections_Helper_Guard::$withdrawing = $withdrawn;
      try {
        \Civi\Api4\Election::update(FALSE)
          ->addWhere('id', '=', $this->eId)
          ->addValue('is_visible', !$isActive)
          ->execute();
      }
      finally {
        CRM_Elections_Helper_Guard::$withdrawing = FALSE;
      }
      CRM_Core_Session::setStatus($isActive ? E::ts('Election deactivated.') : E::ts('Election activated.'), '', 'success');
    }
    catch (CRM_Core_Exception $e) {
      CRM_Core_Session::setStatus($e->getMessage(), E::ts('Election not changed'), 'error');
    }

    parent::postProcess();
    $this->finish(CRM_Utils_System::url('civicrm/elections/manage', 'reset=1'));
  }

}
