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

    CRM_Utils_System::setTitle(E::ts('Add Election Position for %1', [1 => $this->election->name]));
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
        $default['write_ins_per_seat'] = $this->electionPosition['write_ins_per_seat'] ?? 0;
        $default['position_type'] = ($this->electionPosition['position_type'] ?? '') === CRM_Elections_BAO_ElectionPosition::TYPE_REFERENDUM ? CRM_Elections_BAO_ElectionPosition::TYPE_REFERENDUM : CRM_Elections_BAO_ElectionPosition::TYPE_ELECTION;
        $threshold = (string) (float) ($this->electionPosition['pass_threshold'] ?? 50);
        $standard = array_keys(self::thresholdOptions());
        $default['pass_threshold_choice'] = in_array($threshold, $standard, TRUE) ? $threshold : 'custom';
        $default['pass_threshold_custom'] = in_array($threshold, $standard, TRUE) ? '' : $threshold;
        $default['quorum_percent'] = ($this->electionPosition['quorum_percent'] ?? '') !== '' && $this->electionPosition['quorum_percent'] !== NULL ? (string) (float) $this->electionPosition['quorum_percent'] : '';
        $default['resolution_text'] = $this->electionPosition['resolution_text'] ?? '';
        foreach (CRM_Elections_BAO_ElectionPosition::getOptions($this->epId) as $option) {
          $default[$option['kind'] . '_label'] = $option['label'];
        }
        $default['description']   = (!empty($this->electionPosition['description'])) ? $this->electionPosition['description'] : '';

        CRM_Utils_System::setTitle(E::ts('Edit Election Position for %1', [1 => $this->electionPosition['name']]));
      }
      else {
        $this->epId = 0;
      }
    }
    return $default;
  }

  /**
   * The usual pass thresholds for a question, as the percentage of Yes out of Yes plus No.
   *
   * @return string[]
   */
  public static function thresholdOptions() {
    return [
      '50' => E::ts('Simple majority (more than half)'),
      '66.67' => E::ts('Two thirds'),
      '75' => E::ts('Three quarters'),
      'custom' => E::ts('Another percentage'),
    ];
  }

  /**
   * Add form elements for create election form
   */
  private function addFormElements() {
    // election information fields
    $this->add('text', 'name', E::ts('Name'), ['size' => 35], TRUE);
    $this->add('text', 'quantity', E::ts('Seats'), ['size' => 15], TRUE);
    $this->add('text', 'sortorder', E::ts('Order'), ['size' => 15], TRUE);
    $this->add('select', 'position_type', E::ts('Type'), CRM_Elections_BAO_ElectionPosition::getTypes(), TRUE);
    $this->add('text', 'yes_label', E::ts('Wording of Yes'), ['size' => 25, 'maxlength' => 255]);
    $this->add('text', 'no_label', E::ts('Wording of No'), ['size' => 25, 'maxlength' => 255]);
    $this->add('text', 'abstain_label', E::ts('Wording of Abstain'), ['size' => 25, 'maxlength' => 255]);
    $this->add('select', 'pass_threshold_choice', E::ts('Passes when'), self::thresholdOptions());
    $this->add('text', 'pass_threshold_custom', E::ts('Percentage'), ['size' => 6]);
    $this->add('text', 'quorum_percent', E::ts('Quorum'), ['size' => 6]);
    $this->add('textarea', 'resolution_text', E::ts('Resolution'), ['cols' => 55, 'rows' => 6], FALSE);
    $this->add('select', 'write_ins_per_seat', E::ts('Write-ins per seat'), array_combine(range(0, CRM_Elections_Helper_WriteIn::MAX_PER_SEAT), range(0, CRM_Elections_Helper_WriteIn::MAX_PER_SEAT)), TRUE);
    $this->add('textarea', 'description', E::ts('Description'), ['cols' => 55, 'rows' => 6], FALSE);
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
      $this->_errors['quantity'] = E::ts('Seats must be a valid positive integer.');
    }

    if (($values['position_type'] ?? '') === CRM_Elections_BAO_ElectionPosition::TYPE_REFERENDUM) {
      if (($values['pass_threshold_choice'] ?? '') === 'custom') {
        $custom = $values['pass_threshold_custom'] ?? '';
        if (!is_numeric($custom) || (float) $custom <= 0 || (float) $custom > 100) {
          $this->_errors['pass_threshold_custom'] = E::ts('Enter a percentage above 0 and up to 100.');
        }
      }
      $quorum = $values['quorum_percent'] ?? '';
      if ($quorum !== '' && (!is_numeric($quorum) || (float) $quorum < 0 || (float) $quorum > 100)) {
        $this->_errors['quorum_percent'] = E::ts('Enter a percentage from 0 to 100, or leave it empty for no quorum.');
      }
    }

    if (!CRM_Utils_Rule::positiveInteger($values['sortorder']) || ($values['sortorder'] <= 0)) {
      $this->_errors['sortorder'] = E::ts('Order must be a valid positive integer.');
    }

    return parent::validate();
  }

  public function postProcess() {
    $values = $this->exportValues();
    $epId = $values['epid'];

    $isQuestion = ($values['position_type'] ?? '') === CRM_Elections_BAO_ElectionPosition::TYPE_REFERENDUM;
    $threshold = ($values['pass_threshold_choice'] ?? '50') === 'custom' ? $values['pass_threshold_custom'] : ($values['pass_threshold_choice'] ?? '50');
    $params = [
      'name'         => $values['name'],
      'quantity'     => $isQuestion ? 1 : $values['quantity'],
      'position_type' => $isQuestion ? CRM_Elections_BAO_ElectionPosition::TYPE_REFERENDUM : CRM_Elections_BAO_ElectionPosition::TYPE_ELECTION,
      'pass_threshold' => $isQuestion ? (float) $threshold : 50,
      'quorum_percent' => ($isQuestion && ($values['quorum_percent'] ?? '') !== '' && (float) $values['quorum_percent'] > 0) ? (float) $values['quorum_percent'] : NULL,
      'resolution_text' => $isQuestion ? ($values['resolution_text'] ?? '') : NULL,
      'sortorder'    => $values['sortorder'],
      'write_ins_per_seat' => $isQuestion ? 0 : (int) $values['write_ins_per_seat'],
      'description'  => $values['description'],
      'election_id'  => $this->eId,
      'created_by'   => CRM_Core_Session::singleton()->getLoggedInContactID(),
    ];

    $successMessage = E::ts('Election position has been created successfully.');
    $electionPosition = NULL;
    if ($epId) {
      $electionPosition = civicrm_api3('ElectionPosition', 'get', [
        'id' => $epId,
	    'options' => ['limit' => 0],
      ]);
    }

    if (isset($electionPosition) && $electionPosition['count']) {
      $params['id'] = $epId;
      $successMessage = E::ts('Election position has been edited successfully.');
    }

    $electionPosition = civicrm_api3('ElectionPosition', 'create', $params);
    if ($isQuestion) {
      CRM_Elections_BAO_ElectionPosition::saveOptions((int) $electionPosition['id'], ['yes' => $values['yes_label'] ?? '', 'no' => $values['no_label'] ?? '', 'abstain' => $values['abstain_label'] ?? '']);
    }
    parent::postProcess();

    CRM_Core_Session::setStatus($successMessage, '', 'success');
    $this->finish(CRM_Utils_System::url('civicrm/elections/positions', 'reset=1&eid=' . $this->eId));
  }

}
