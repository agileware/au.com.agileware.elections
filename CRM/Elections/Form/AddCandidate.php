<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * Add a candidate directly, as an administrator, for elections where nominations are made
 * outside the website.
 */
class CRM_Elections_Form_AddCandidate extends CRM_Elections_Form_Base {

  private $eId = 0;

  private $election = NULL;

  public function buildQuickForm() {
    if (throwUnauthorizedMessageIfRequired($this)) {
      return;
    }
    $this->eId = CRM_Utils_Request::retrieve('eid', 'Positive', $this, TRUE);
    $this->election = findElectionById($this->eId);
    CRM_Utils_System::setTitle(E::ts('Add Candidate to %1', [1 => $this->election->name]));

    $positions = [];
    foreach (civicrm_api3('ElectionPosition', 'get', ['election_id' => $this->eId, 'options' => ['limit' => 0, 'sort' => 'sortorder ASC']])['values'] as $position) {
      $positions[$position['id']] = $position['name'];
    }
    $groups = array_filter(explode(',', (string) $this->election->allowed_groups));

    $this->add('select', 'position', E::ts('Position'), ['' => E::ts('- select -')] + $positions, TRUE);
    $this->addEntityRef('contact', E::ts('Candidate'), [
      'entity' => 'Contact',
      'placeholder' => E::ts('- Select person -'),
      'api' => ['params' => ['group' => ['IN' => $groups]]],
    ], TRUE);
    $this->add('advcheckbox', 'accepted', E::ts('The candidate has accepted'));
    $this->add('textarea', 'statement', E::ts('Candidate statement'), ['cols' => 55, 'rows' => 5], FALSE);
    $this->addElement('hidden', 'eid', $this->eId);
    $this->addButtons([
      ['type' => 'submit', 'name' => E::ts('Add Candidate'), 'isDefault' => TRUE],
      ['type' => 'cancel', 'name' => E::ts('Cancel')],
    ]);
    $this->assign('election', $this->election);
    parent::buildQuickForm();
  }

  public function setDefaultValues() {
    return ['accepted' => 1];
  }

  public function postProcess() {
    $values = $this->exportValues();
    try {
      CRM_Elections_BAO_ElectionNomination::addCandidate($values['position'], $values['contact'], !empty($values['accepted']), $values['statement'] ?: NULL);
      CRM_Core_Session::setStatus(E::ts('The candidate has been added.'), '', 'success');
    }
    catch (CRM_Core_Exception $e) {
      CRM_Core_Session::setStatus($e->getMessage(), E::ts('Candidate not added'), 'error');
    }
    $this->finish(CRM_Utils_System::url('civicrm/elections/candidate-list', 'reset=1', FALSE, '/?eid=' . (int) $this->eId));
  }

}
