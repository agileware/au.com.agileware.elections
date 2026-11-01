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
    if ($this->eId && $this->refuseEdit()) {
      return;
    }
    $this->addFormElements();
    $this->addPageResources();
    $this->assign('groupsHidden', CRM_Elections_Helper_Visibility::groupsHidden());
    if ($this->eId) {
      $this->pageTitle = E::ts('Edit Election');
      CRM_Elections_Helper_Frame::assignToForm($this, CRM_Elections_Helper_Frame::trail((int) $this->eId, [], $this->pageTitle), CRM_Elections_Helper_Frame::overviewUrl((int) $this->eId));
    }
    else {
      $this->eId = 0;
      $this->pageTitle = E::ts('Add Election');
      CRM_Elections_Helper_Frame::assignToForm($this, CRM_Elections_Helper_Frame::trail(0, [], $this->pageTitle), CRM_Elections_Helper_Frame::managerUrl());
    }
    CRM_Utils_System::setTitle($this->pageTitle);
    parent::buildQuickForm();
  }

  /**
   * An election that cannot be edited shows the reason and no form: a form that cannot be saved is not offered.
   *
   * @return bool
   *   TRUE when the reason is shown instead of the form.
   */
  private function refuseEdit() {
    $election = new CRM_Elections_BAO_Election();
    $election->id = $this->eId;
    if (!$election->find(TRUE)) {
      return FALSE;
    }
    $canEdit = $election->canEdit();
    if ($canEdit === TRUE) {
      return FALSE;
    }
    CRM_Utils_System::setTitle(E::ts('Edit Election'));
    throwAccessDeniedException($this, $canEdit, [
      'return_button_text' => E::ts('Back to Manage Elections'),
      'return_button_action' => CRM_Elections_Helper_Frame::managerUrl(),
    ]);
    return TRUE;
  }

  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    if (is_null($defaults) || !is_array($defaults)) {
      $defaults = [];
    }
    $election = new CRM_Elections_BAO_Election();
    $election->id = $this->eId;

    if ($election->find(TRUE)) {
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
      $defaults['nomination_message_template_id'] = CRM_Elections_Helper_Mailer::chosenTemplate($election) ?: CRM_Elections_Helper_Mailer::getDefaultNominationTemplateId();
      $election->assignDisplayOptions();
      $defaults['allow_online_nominations'] = (int) $election->onlineNominations;
      $defaults['candidate_groups'] = $election->candidate_groups;

      $defaults['allow_checksum_access'] = $election->allow_checksum_access;

      $election->assignDisplayOptions();
      $defaults['show_photos'] = (int) $election->showPhotos;
      $defaults['show_statements'] = $election->statementDisplay;
      $defaults['show_nominators'] = (int) $election->showNominators;
      $defaults['name_format'] = $election->name_format ?? '';
      $defaults += CRM_Elections_Helper_ResultsDisplay::getDefaults($election);
      $this->assign('resultsLogo', CRM_Elections_Helper_ResultsDisplay::getDisplay($election, 'eid=' . (int) $election->id));
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
      $defaults['nomination_message_template_id'] = CRM_Elections_Helper_Mailer::getDefaultNominationTemplateId();
      $defaults['allow_online_nominations'] = 1;
      $defaults['candidate_groups'] = '';
      $defaults['show_photos'] = 1;
      $defaults['show_statements'] = CRM_Elections_BAO_Election::STATEMENTS_LINK;
      $defaults['show_nominators'] = 1;
      $defaults['name_format'] = '';
      $defaults += CRM_Elections_Helper_ResultsDisplay::getDefaults();
    }

    return $this->restoreInput($defaults);
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
          $stage(E::ts('Visible'), 'visibility_start_date', 'visibility_end_date'),
          $stage(E::ts('Nominations open'), 'nomination_start_date', 'nomination_end_date'),
          $stage(E::ts('Candidates advertised'), 'advertise_candidates_date', NULL, 'voting_start_date'),
          $stage(E::ts('Voting open'), 'voting_start_date', 'voting_end_date'),
          $stage(E::ts('Results published'), 'result_date', NULL, 'visibility_end_date'),
        ],
        'nowLabel' => E::ts('Now'),
      ])
      ->addScriptFile(E::LONG_NAME, 'js/election-form.js', 100, 'page-footer');
    CRM_Elections_Helper_ResultsDisplay::addPageResources();
  }

  /**
   * Add form elements for create election form
   */
  private function addFormElements() {
    // election information fields
    $this->add('text', 'name', E::ts('Name'), ['size' => 35], TRUE);
    $this->add('textarea', 'description', E::ts('Description'), ['cols' => 55, 'rows' => 6], FALSE);

    // Quick set-up of the dates: not saved, only used to fill in the dates below.
    $this->add('select', 'quick_cycle', E::ts('Election cycle'), ['' => E::ts('Select a cycle')] + array_map(fn($cycle) => $cycle[0], CRM_Elections_Helper_Dates::getCycles()), FALSE);
    $this->add('datepicker', 'quick_start', E::ts('Nominations start on'), [], FALSE);

    // Visibility fields.
    $this->add('datepicker', 'visibility_start_date', E::ts('Visible from'), [], TRUE);
    $this->add('datepicker', 'visibility_end_date', E::ts('Visible until'), [], TRUE);

    // Nominations fields.
    $this->add('datepicker', 'nomination_start_date', E::ts('Nominations open'), [], TRUE);
    $this->add('datepicker', 'nomination_end_date', E::ts('Nominations close'), [], TRUE);

    // Advertise candidates
    $this->add('datepicker', 'advertise_candidates_date', E::ts('Candidates advertised from'), [], TRUE);

    // Voting fields.
    $this->add('datepicker', 'voting_start_date', E::ts('Voting opens'), [], TRUE);
    $this->add('datepicker', 'voting_end_date', E::ts('Voting closes'), [], TRUE);

    // Publish result
    $this->add('datepicker', 'result_date', E::ts('Results published on'), [], TRUE);

    // Setting fields.

    $this->add('advcheckbox', 'anonymize_votes', E::ts('Anonymise votes'));

    $this->add('select', 'voting_method', E::ts('Voting method'), CRM_Elections_VotingMethod::getOptions(), TRUE);

    $this->add('advcheckbox', 'allow_revote', E::ts('Allow people to change their vote'));

    $this->add('advcheckbox', 'allow_checksum_access', E::ts('Allow people to take part without logging in'));

    $this->add('text', 'required_nominations', E::ts('Required nominations'), ['size' => 15], TRUE);

    $this->addEntityRef('allowed_groups', E::ts('Voting groups'), [
      'entity' => 'Group',
      'placeholder' => E::ts('Select groups'),
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
    $this->add('select', 'nomination_message_template_id', E::ts('Nomination email template'), CRM_Elections_Helper_Mailer::getNominationTemplates(), FALSE);
    $this->assign('canEditMessageTemplates', CRM_Core_Permission::check('edit message templates'));
    $this->assign('messageTemplatesUrl', CRM_Utils_System::url('civicrm/admin/messageTemplates', 'reset=1'));

    // Who may nominate, and who may stand, when that is not the same as who may vote.
    foreach (['nomination_groups' => E::ts('Nominating groups'), 'candidate_groups' => E::ts('Candidate groups')] as $field => $label) {
      $this->addEntityRef($field, $label, [
        'entity' => 'Group',
        'placeholder' => E::ts('Same as voting groups'),
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
    CRM_Elections_Helper_ResultsDisplay::addFormElements($this);
    $this->add('advcheckbox', 'show_photos', E::ts('Show candidate photos'));
    $this->add('select', 'show_statements', E::ts('Show candidate statements'), CRM_Elections_BAO_Election::getStatementDisplayOptions(), TRUE);
    $this->add('advcheckbox', 'show_nominators', E::ts('Show who nominated each candidate'));
    $this->add('select', 'name_format', E::ts('Candidate name format'), ['' => E::ts('Use the site setting')] + CRM_Elections_Helper_Candidate::getNameFormats());

    // Submit button
    CRM_Elections_Helper_Frame::addSubmitButtons($this);
  }

  public function validate() {
    $values = $this->exportValues();
    $errors = CRM_Elections_BAO_Election::compareDates($values);
    $this->_errors = array_merge($this->_errors, $errors);

    if (!CRM_Utils_Rule::positiveInteger($values['required_nominations']) || ((int) $values['required_nominations'] < 1)) {
      $this->_errors['required_nominations'] = E::ts('Required nominations must be a whole number of 1 or more.');
    }
    return parent::validate();
  }

  /**
   * What only a new election is given. A new election starts inactive and records who made it; editing an
   * election keeps whether it is active and who created it.
   *
   * @param int|string|null $electionId The election being edited, if any
   * @return array
   */
  public static function valuesForNewElection($electionId): array {
    if ($electionId) {
      return [];
    }
    return [
      'is_visible' => 0,
      'created_by' => CRM_Core_Session::singleton()->getLoggedInContactID(),
    ];
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
      'required_nominations'        => $values['required_nominations'],
      'allowed_groups'              => $values['allowed_groups'],
      'send_emails'                 => !empty($values['send_emails']) ? 1 : 0,
      'nomination_message_template_id' => !empty($values['nomination_message_template_id']) && (int) $values['nomination_message_template_id'] !== (int) CRM_Elections_Helper_Mailer::getDefaultNominationTemplateId() ? (int) $values['nomination_message_template_id'] : NULL,
      'allow_online_nominations'    => !empty($values['allow_online_nominations']) ? 1 : 0,
      'nomination_groups'           => $values['nomination_groups'] ?? '',
      'candidate_groups'            => $values['candidate_groups'] ?? '',
      'show_photos'                 => !empty($values['show_photos']) ? 1 : 0,
      'show_statements'             => $values['show_statements'],
      'show_nominators'             => !empty($values['show_nominators']) ? 1 : 0,
      'name_format'                 => $values['name_format'] !== '' ? $values['name_format'] : NULL,
    ] + self::valuesForNewElection($this->eId) + CRM_Elections_Helper_ResultsDisplay::getValuesToSave($values);

    $successMessage = E::ts('Election added.');
    if ($this->eId) {
      $successMessage = E::ts('Election saved.');
      $election['id'] = $this->eId;
    }

    try {
      $election = civicrm_api3('Election', 'create', $election);
      if ($election['is_error']) {
        $this->stayOnForm($election['error_message'], E::ts('Election not saved'));
        return;
      }
      CRM_Elections_Helper_ResultsDisplay::saveLogo((int) $election['id'], $values, CRM_Elections_Helper_ResultsDisplay::upload());
    }
    catch (CRM_Core_Exception $e) {
      $this->stayOnForm($e->getMessage(), E::ts('Election not saved'));
      return;
    }

    CRM_Core_Session::setStatus($successMessage, '', 'success');
    parent::postProcess();
    // Back to the page the person started from: the overview after an edit from there, the list after adding.
    $this->finish($this->startedFrom($this->eId ? CRM_Elections_Helper_Frame::overviewUrl((int) $this->eId) : CRM_Elections_Helper_Frame::managerUrl()));
  }

}
