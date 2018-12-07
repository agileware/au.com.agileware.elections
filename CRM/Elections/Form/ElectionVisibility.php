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

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ($this->election->is_visible) ? E::ts('Deactivate Election') : E::ts('Activate Election'),
        'isDefault' => TRUE,
      ),
    ));

    if ($this->election->is_visible) {
      CRM_Utils_System::setTitle("Deactivate Election");
    }
    else {
      CRM_Utils_System::setTitle("Activate Election");
    }

    $this->assign('election', $this->election);
    $this->addElement('hidden', 'eid', $this->eId);

    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();

    $this->eId = (isset($values["eid"])) ? $values["eid"] : 0;
    $election = new CRM_Elections_BAO_Election();
    $election->id = $this->eId;

    if ($election->is_visible != 1) {
      $electionPostions = civicrm_api3('ElectionPosition', 'get', array(
        'election_id'  => $election->id,
      ));
      if (count($electionPostions['values']) == 0) {
        return throwAccessDeniedException($this, 'At least one Position must be defined before the Election can be active.', array(
          'return_button_text'   => 'Return to Elections',
          'return_button_action' => CRM_Utils_System::url('civicrm/elections'),
        ));
      }
    }

    if ($election->find(TRUE)) {
      $election->is_visible = !($election->is_visible);
      if ($election->save()) {
        $action = "activated";
        if (!$election->is_visible) {
          $action = "deactivated";
        }
        CRM_Core_Session::setStatus('Election has been ' . $action . ' successfully.', '', 'success');
      }
    }

    parent::postProcess();
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/elections'));
  }

}
