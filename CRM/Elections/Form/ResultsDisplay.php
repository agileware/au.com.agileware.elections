<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * The look of the results page: logo, header text, footer text and the celebration. These are display
 * settings only, so they can be changed after the election has started, which the election form does not allow.
 */
class CRM_Elections_Form_ResultsDisplay extends CRM_Elections_Form_Base {

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
    CRM_Utils_System::setTitle(E::ts('Results page for %1', [1 => $this->election->name]));

    CRM_Elections_Helper_ResultsDisplay::addFormElements($this);
    $this->addElement('hidden', 'eid', $this->eId);
    $this->addButtons([
      ['type' => 'submit', 'name' => E::ts('Save'), 'isDefault' => TRUE],
      ['type' => 'cancel', 'name' => E::ts('Cancel')],
    ]);
    $this->assign('election', $this->election);
    $this->assign('resultsLogo', CRM_Elections_Helper_ResultsDisplay::getDisplay($this->election, 'eid=' . (int) $this->eId));
    CRM_Elections_Helper_ResultsDisplay::addPageResources();
    parent::buildQuickForm();
  }

  public function setDefaultValues() {
    $election = findElectionById(retrieveElectionIdFromUrl($this));
    return CRM_Elections_Helper_ResultsDisplay::getDefaults($election);
  }

  public function postProcess() {
    $values = $this->exportValues();
    $electionId = (int) ($values['eid'] ?? 0);
    try {
      \Civi\Api4\Election::update(FALSE)
        ->addWhere('id', '=', $electionId)
        ->setValues(CRM_Elections_Helper_ResultsDisplay::getValuesToSave($values))
        ->execute();
      CRM_Elections_Helper_ResultsDisplay::saveLogo($electionId, $values, CRM_Elections_Helper_ResultsDisplay::upload());
      CRM_Core_Session::setStatus(E::ts('The results page has been saved.'), '', 'success');
    }
    catch (CRM_Core_Exception $e) {
      CRM_Core_Session::setStatus($e->getMessage(), E::ts('Results page not saved'), 'error');
    }
    parent::postProcess();
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/elections/overview', 'reset=1&eid=' . $electionId));
  }

}
