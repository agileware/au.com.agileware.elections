<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Elections_Form_CreateElection extends CRM_Elections_Form_Base {

  private $eId = 0;
  private $pageTitle = NULL;

  public function buildQuickForm() {
    hideNonRequiredItemsOnPage($this);
    if (throwUnauthorizedMessageIfRequired($this)) {
      return;
    }
    $this->eId = CRM_Utils_Request::retrieve('eid', 'Positive', $this, FALSE, 0);
    $this->addFormElements();
    $this->addPageResources();
    $this->assign('groupsHidden', CRM_Elections_Helper_Visibility::groupsHidden());
    if ($this->eId) {
      $this->pageTitle = E::ts('Edit Election');
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
      $defaults['voting_method'] = CRM_Elections_VotingMethod::normalise($election->voting_method);
      $defaults['required_nominations'] = $election->required_nominations;
      $defaults['allowed_groups'] = $election->allowed_groups;
      $defaults['nomination_groups'] = $election->nomination_groups;
      $defaults['send_emails'] = (int) $election->send_emails;
      $election->assignDisplayOptions();
      $defaults['allow_online_nominations'] = (int) $election->onlineNominations;
      $defaults['candidate_groups'] = $election->candidate_groups;

      $defaults['allow_checksum_access'] = $election->allow_checksum_access;

      $election->assignDisplayOptions();
      $defaults['show_photos'] = (int) $election->showPhotos;
      $defaults['show_statements'] = $election->statementDisplay;
      $defaults['show_nominators'] = (int) $election->showNominators;
      $defaults['name_format'] = $election->name_format ?? '';
    }
    else {
      $this->eId = 0;
      $defaults['anonymize_votes'] = 1;
      $defaults['allow_revote'] = 0;
      $defaults['voting_method'] = CRM_Elections_VotingMethod::IRV;
      $defaults['required_nominations'] = 2;
      $defaults['allowed_groups'] = '';
      $defaults['nomination_groups'] = '';
      $defaults['send_emails'] = 1;
      $defaults['allow_online_nominations'] = 1;
      $defaults['candidate_groups'] = '';
      $defaults['show_photos'] = 1;
      $defaults['show_statements'] = CRM_Elections_BAO_Election::STATEMENTS_LINK;
      $defaults['show_nominators'] = 1;
      $defaults['name_format'] = '';
    }

    return $defaults;
  }

  /**
   * Script for the tabs, the date checks and the timeline preview, with the rules and wording it needs.
   */
  private function addPageResources() {
    $stage = function ($label, $start, $end = NULL, $until = NULL) {
      return array_filter(['label' => $label, 'start' => $start, 'end' => $end, 'until' => $until]);
    };
    $rules = CRM_Elections_BAO_Election::getDateRules();
    Civi::resources()
      ->addVars('elections', [
        'dateRules' => $rules,
        'dateFields' => [
          'visibility_start_date', 'visibility_end_date', 'nomination_start_date', 'nomination_end_date',
          'advertise_candidates_date', 'voting_start_date', 'voting_end_date', 'result_date',
        ],
        'stages' => [
          $stage(E::ts('Visible to voters'), 'visibility_start_date', 'visibility_end_date'),
          $stage(E::ts('Nominations open'), 'nomination_start_date', 'nomination_end_date'),
          $stage(E::ts('Candidates advertised'), 'advertise_candidates_date', NULL, 'voting_start_date'),
          $stage(E::ts('Voting open'), 'voting_start_date', 'voting_end_date'),
          $stage(E::ts('Results published'), 'result_date', NULL, 'visibility_end_date'),
        ],
        'nowLabel' => E::ts('Now'),
      ])
      ->addScriptFile(E::LONG_NAME, 'js/election-form.js', 100, 'page-footer');
  }

  /**
   * Add form elements for create election form
   */
  private function addFormElements() {
    // election information fields
    $this->add('text', 'name', E::ts('Name'), ['size' => 35], TRUE);
    $this->add('textarea', 'description', E::ts('Description'), ['cols' => 55, 'rows' => 6], FALSE);

    // Visibility fields.
    $this->add('datepicker', 'visibility_start_date', E::ts('Start Date'), [], TRUE);
    $this->add('datepicker', 'visibility_end_date', E::ts('End Date'), [], TRUE);

    // Nominations fields.
    $this->add('datepicker', 'nomination_start_date', E::ts('Start Date'), [], TRUE);
    $this->add('datepicker', 'nomination_end_date', E::ts('End Date'), [], TRUE);

    // Advetise Candidates
    $this->add('datepicker', 'advertise_candidates_date', E::ts('Start Date'), [], TRUE);

    // Voting fields.
    $this->add('datepicker', 'voting_start_date', E::ts('Start Date'), [], TRUE);
    $this->add('datepicker', 'voting_end_date', E::ts('End Date'), [], TRUE);

    // Publish result
    $this->add('datepicker', 'result_date', E::ts('Start Date'), [], TRUE);

    // Setting fields.

    $this->add('select', 'anonymize_votes', E::ts('Anonymise Votes'), CRM_Core_SelectValues::boolean(), TRUE, [
      'placeholder' => '- Select -',
    ]);

    $this->add('select', 'voting_method', E::ts('Voting Method'), CRM_Elections_VotingMethod::getOptions(), TRUE);

    $this->add('select', 'allow_revote', E::ts('Allow people to change their vote'), CRM_Core_SelectValues::boolean(), TRUE, [
      'placeholder' => '- Select -',
    ]);

    $this->add('advcheckbox', 'allow_checksum_access', E::ts('Allow non-logged in access'));

    $this->add('text', 'required_nominations', E::ts('Number of Required Nominations'), ['size' => 15], TRUE);

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

    $this->add('advcheckbox', 'allow_online_nominations', E::ts('Allow people to nominate online'));
    $this->add('advcheckbox', 'send_emails', E::ts('Email people about their nominations'));

    // Who may nominate, and who may stand, when that is not the same as who may vote.
    foreach (['nomination_groups' => E::ts('Nomination Groups'), 'candidate_groups' => E::ts('Candidate Groups')] as $field => $label) {
      $this->addEntityRef($field, $label, [
        'entity' => 'Group',
        'placeholder' => E::ts('- Same as voting groups -'),
        'multiple' => TRUE,
        'api' => [
          'params' => [
            'is_active' => 1,
            'is_hidden' => 0,
          ],
        ],
      ], FALSE);
    }

    // Display options.
    $this->add('advcheckbox', 'show_photos', E::ts('Show candidate photos'));
    $this->add('select', 'show_statements', E::ts('Candidate statements'), CRM_Elections_BAO_Election::getStatementDisplayOptions(), TRUE);
    $this->add('advcheckbox', 'show_nominators', E::ts('Show who nominated each candidate'));
    $this->add('select', 'name_format', E::ts('Candidate name format'), ['' => E::ts('Use the site setting')] + CRM_Elections_Helper_Candidate::getNameFormats());

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
      $this->_errors['required_nominations'] = E::ts('Value should be a positive integer.');
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
      'voting_method'               => $values['voting_method'],
      'allow_checksum_access'       => $values['allow_checksum_access'],
      'is_visible'                  => 0,
      'required_nominations'        => $values['required_nominations'],
      'allowed_groups'              => $values['allowed_groups'],
      'send_emails'                 => !empty($values['send_emails']) ? 1 : 0,
      'allow_online_nominations'    => !empty($values['allow_online_nominations']) ? 1 : 0,
      'nomination_groups'           => $values['nomination_groups'] ?? '',
      'candidate_groups'            => $values['candidate_groups'] ?? '',
      'show_photos'                 => !empty($values['show_photos']) ? 1 : 0,
      'show_statements'             => $values['show_statements'],
      'show_nominators'             => !empty($values['show_nominators']) ? 1 : 0,
      'name_format'                 => $values['name_format'] !== '' ? $values['name_format'] : NULL,
      'created_by'                  => CRM_Core_Session::singleton()->getLoggedInContactID(),
    ];

    $successMessage = E::ts('Election has been created successfully.');
    if ($this->eId) {
      $successMessage = E::ts('Election has been updated successfully.');
      $election['id'] = $this->eId;
    }

    try {
      $election = civicrm_api3('Election', 'create', $election);
      if ($election['is_error']) {
        CRM_Core_Session::setStatus($election['error_message'], '', 'error');
        return;
      }
    }
    catch (CRM_Core_Exception $e) {
      CRM_Core_Session::setStatus($e->getMessage(), '', 'error');
      return;
    }

    CRM_Core_Session::setStatus($successMessage, '', 'success');
    parent::postProcess();
    $this->finish(CRM_Utils_System::url('civicrm/elections/manage', 'reset=1'));
  }

}
