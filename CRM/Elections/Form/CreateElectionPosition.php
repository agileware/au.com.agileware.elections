<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Elections_Form_CreateElectionPosition extends CRM_Elections_Form_Base {

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
    if ($canEdit !== TRUE && $this->election->is_visible) {
      throw new CRM_Extension_Exception($canEdit);
    }

    $this->epId = CRM_Utils_Request::retrieve('epid', 'Positive', $this, FALSE, 0);
    if (!$this->epId) {
      $this->epId = 0;
    }

    $this->assign('election', $this->election);
    $this->addFormElements();

    CRM_Utils_System::setTitle('Add Election Position for ' . $this->election->name);
    parent::buildQuickForm();
  }

  public function setDefaultValues() {
    $default = parent::setDefaultValues();
    if ($this->epId) {
      $this->electionPosition = civicrm_api3('ElectionPosition', 'get', [
        'id'         => $this->epId,
        'election_id' => $this->eId,
        'sequential' => TRUE,
        'options' => ['limit' => 0],
      ]);

      if ($this->electionPosition['count']) {
        $this->electionPosition   = $this->electionPosition['values'][0];

        $default['name']          = $this->electionPosition['name'];
        $default['quantity']      = $this->electionPosition['quantity'];
        $default['sortorder']     = $this->electionPosition['sortorder'];
        $default['description']   = (!empty($this->electionPosition['description'])) ? $this->electionPosition['description'] : '';

        CRM_Utils_System::setTitle('Edit Election Position for ' . $this->electionPosition['name']);
      }
      else {
        $this->epId = 0;
      }
    }
    return $default;
  }

  /**
   * Add form elements for create election form
   */
  private function addFormElements() {
    // election information fields
    $this->add('text', 'name', 'Name', ['size' => 35], TRUE);
    $this->add('text', 'quantity', 'Seats', ['size' => 15], TRUE);
    $this->add('text', 'sortorder', 'Order', ['size' => 15], TRUE);
    $this->add('textarea', 'description', 'Description', ['cols' => 55, 'rows' => 6], FALSE);
    $this->addElement('hidden', 'eid', $this->eId);
    $this->addElement('hidden', 'epid', $this->epId);

    // Submit button
    $this->addButtons([
        [
          'type' => 'submit',
          'name' => E::ts(($this->epId) ? 'Edit Position' : 'Create'),
          'isDefault' => TRUE,
        ],
    ]);
  }

  public function validate() {
    $values = $this->exportValues();

    if (!CRM_Utils_Rule::positiveInteger($values['quantity'])) {
      $this->_errors['quantity'] = 'Seats must be a valid positive integer.';
    }

    if (!CRM_Utils_Rule::positiveInteger($values['sortorder']) || ($values['sortorder'] <= 0)) {
      $this->_errors['sortorder'] = 'Rank must be a valid positive integer.';
    }

    return parent::validate();
  }

  public function postProcess() {
    $values = $this->exportValues();
    $epId = $values['epid'];

    $params = [
      'name'         => $values['name'],
      'quantity'     => $values['quantity'],
      'sortorder'    => $values['sortorder'],
      'description'  => $values['description'],
      'election_id'  => $this->eId,
      'created_by'   => CRM_Core_Session::singleton()->getLoggedInContactID(),
    ];

    $successTag = 'created';
    $electionPosition = NULL;
    if ($epId) {
      $electionPosition = civicrm_api3('ElectionPosition', 'get', [
        'id' => $epId,
	    'options' => ['limit' => 0],
      ]);
    }

    if (isset($electionPosition) && $electionPosition['count']) {
      $params['id'] = $epId;
      $successTag = 'edited';
    }

    $electionPosition = civicrm_api3('ElectionPosition', 'create', $params);
    parent::postProcess();

    CRM_Core_Session::setStatus('Election position has been ' . $successTag . ' successfully.', '', 'success');
    CRM_Utils_System::redirect(Civi::url('backend://civicrm/elections/positions', 'eid=' . $this->eId));
  }

}
