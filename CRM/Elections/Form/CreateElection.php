<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Elections_Form_CreateElection extends CRM_Elections_Form_Base {

  private $eId = 0;
  private $pageTitle = 'Create Election';

  public function buildQuickForm() {
    hideNonRequiredItemsOnPage($this);
    if (throwUnauthorizedMessageIfRequired($this)) {
      return;
    }
    $this->eId = CRM_Utils_Request::retrieve('eid', 'Positive', $this, FALSE, 0);
    $this->addFormElements();
    if ($this->eId) {
      $this->pageTitle = 'Edit Election';
      CRM_Utils_System::setTitle($this->pageTitle);
    }
    else {
      $this->eId = 0;
    }
    parent::buildQuickForm();
  }

  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    if (is_null($defaults) || !is_array($defaults)) {
      $defaults = [];
    }
    $election = new CRM_Elections_BAO_Election();
    $election->id = $this->eId;

    if ($election->find(TRUE)) {
      $canEdit = $election->canEdit();
      if ($canEdit !== TRUE && $election->is_visible) {
        throw new CRM_Extension_Exception($canEdit);
      }

      $defaults['name'] = $election->name;
      $defaults['description'] = $election->description;

      $defaults['visibility_start_date'] = $election->visibility_start_date;
      $defaults['visibility_end_date'] = $election->visibility_end_date;

      $defaults['nomination_start_date'] = $election->nomination_start_date;
      $defaults['nomination_end_date'] = $election->nomination_end_date;

      $defaults['advertise_candidates_date'] = $election->advertise_candidates_date;

      $defaults['voting_start_date'] = $election->voting_start_date;
      $defaults['voting_end_date'] = $election->voting_end_date;

      $defaults['result_date'] = $election->result_date;

      $defaults['anonymize_votes'] = $election->anonymize_votes;
      $defaults['allow_revote'] = $election->allow_revote;
      $defaults['required_nominations'] = $election->required_nominations;
      $defaults['allowed_groups'] = $election->allowed_groups;

      $defaults['allow_checksum_access'] = $election->allow_checksum_access;
    }
    else {
      $this->eId = 0;
      $defaults['anonymize_votes'] = 1;
      $defaults['allow_revote'] = 0;
      $defaults['required_nominations'] = 2;
      $defaults['allowed_groups'] = '';
    }

    return $defaults;
  }

  /**
   * Add form elements for create election form
   */
  private function addFormElements() {
    // election information fields
    $this->add('text', 'name', 'Name', ['size' => 35], TRUE);
    $this->add('textarea', 'description', 'Description', ['cols' => 55, 'rows' => 6], FALSE);

    // Visibility fields.
    $this->add('datepicker', 'visibility_start_date', 'Start Date', [], TRUE);
    $this->add('datepicker', 'visibility_end_date', 'End Date', [], TRUE);

    // Nominations fields.
    $this->add('datepicker', 'nomination_start_date', 'Start Date', [], TRUE);
    $this->add('datepicker', 'nomination_end_date', 'End Date', [], TRUE);

    // Advetise Candidates
    $this->add('datepicker', 'advertise_candidates_date', 'Start Date', [], TRUE);

    // Voting fields.
    $this->add('datepicker', 'voting_start_date', 'Start Date', [], TRUE);
    $this->add('datepicker', 'voting_end_date', 'End Date', [], TRUE);

    // Publish result
    $this->add('datepicker', 'result_date', 'Start Date', [], TRUE);

    // Setting fields.

    $this->add('select', 'anonymize_votes', 'Anonymise Votes', CRM_Core_SelectValues::boolean(), TRUE, [
      'placeholder' => '- Select -',
    ]);

    $this->add('select', 'allow_revote', 'Allow Members to Change Vote', CRM_Core_SelectValues::boolean(), TRUE, [
      'placeholder' => '- Select -',
    ]);

    $this->add('advcheckbox', 'allow_checksum_access', 'Allow non-logged in access');

    $this->add('text', 'required_nominations', 'Number of Required Nominations', ['size' => 15], TRUE);

    $this->addEntityRef('allowed_groups', 'Allowed by Groups', [
      'entity' => 'Group',
      'placeholder' => '- Select Groups -',
      'multiple' => TRUE,
      'api' => [
        'params' => [
          'is_active' => 1,
          'is_hidden' => 0,
        ],
      ],
    ], TRUE);

    // Submit button
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ],
    ]);
  }

  public function validate() {
    $values = $this->exportValues();
    $errors = CRM_Elections_BAO_Election::compareDates($values);
    $this->_errors = array_merge($this->_errors, $errors);

    if (!CRM_Utils_Rule::positiveInteger($values['required_nominations'])) {
      $this->_errors['required_nominations'] = 'Value should be a positive integer.';
    }
    return parent::validate();
  }

  public function postProcess() {
    $values = $this->exportValues();
    $election = [
      'name'                        => $values['name'],
      'description'                 => $values['description'],
      'visibility_start_date'       => $values['visibility_start_date'],
      'visibility_end_date'         => $values['visibility_end_date'],
      'nomination_start_date'       => $values['nomination_start_date'],
      'nomination_end_date'         => $values['nomination_end_date'],
      'advertise_candidates_date'   => $values['advertise_candidates_date'],
      'voting_start_date'           => $values['voting_start_date'],
      'voting_end_date'             => $values['voting_end_date'],
      'result_date'                 => $values['result_date'],
      'anonymize_votes'             => $values['anonymize_votes'],
      'allow_revote'                => $values['allow_revote'],
      'allow_checksum_access'       => $values['allow_checksum_access'],
      'is_visible'                  => 0,
      'required_nominations'        => $values['required_nominations'],
      'allowed_groups'              => $values['allowed_groups'],
      'created_by'                  => CRM_Core_Session::singleton()->getLoggedInContactID(),
    ];

    $messageKey = 'created';
    if ($this->eId) {
      $messageKey = 'updated';
      $election['id'] = $this->eId;
    }

    try {
      $election = civicrm_api3('Election', 'create', $election);
      if ($election['is_error']) {
        CRM_Core_Session::setStatus($election['error_message'], '', 'error');
        return;
      }
    }
    catch (CiviCRM_API3_Exception $e) {
      CRM_Core_Session::setStatus($e->getMessage(), '', 'error');
      return;
    }

    CRM_Core_Session::setStatus('Election has been ' . $messageKey . ' successfully.', '', 'success');
    parent::postProcess();
    CRM_Utils_System::redirect(Civi::url('current://civicrm/elections'));
  }

}
