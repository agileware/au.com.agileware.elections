<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * Edit the statement of a candidate.
 *
 * Candidates can change their own statement until voting starts. Election
 * administrators can correct any statement until voting ends.
 */
class CRM_Elections_Form_EditStatement extends CRM_Elections_Form_Base {
  private $enId = 0;
  private $cid = NULL;
  private $cs = NULL;
  private $electionNomination = NULL;

  public function preProcess() {
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive');
    $cs = CRM_Utils_Request::retrieve('cs', 'String');
    if (empty(\CRM_Core_Session::getLoggedInContactID()) && $cid && $cs) {
      $this->cid = $cid;
      $this->cs = $cs;
    }
  }

  public function buildQuickForm() {
    $this->enId = CRM_Utils_Request::retrieve('enid', 'Positive', $this, FALSE, 0);
    hideNonRequiredItemsOnPage($this);

    if (!$this->enId) {
      $this->enId = 0;
      throwAccessDeniedPage($this);
      return;
    }

    try {
      $this->electionNomination = civicrm_api3('ElectionNomination', 'getsingle', [
        'id' => $this->enId,
        'return' => ['comments', 'statement_url', 'member_nominee', 'has_accepted_nomination', 'has_rejected_nomination', 'election_position_id.name', 'election_position_id.election_id', 'election_position_id.election_id.name', 'member_nominee.display_name', 'member_nominee.first_name', 'member_nominee.last_name', 'member_nominee.nick_name'],
      ]);
    }
    catch (CRM_Core_Exception $e) {
      throwAccessDeniedException($this, $e->getMessage());
      return;
    }

    $election = findElectionById($this->electionNomination['election_position_id.election_id']);
    if (!$this->canEdit($election)) {
      throwAccessDeniedPage($this);
      return;
    }
    CRM_Utils_System::setTitle(E::ts('Candidate Statement for %1', [
      1 => CRM_Elections_Helper_Candidate::formatName(
        $this->electionNomination['member_nominee.display_name'],
        $this->electionNomination['member_nominee.first_name'] ?? NULL,
        $this->electionNomination['member_nominee.last_name'] ?? NULL,
        $this->electionNomination['member_nominee.nick_name'] ?? NULL,
        CRM_Elections_Helper_Candidate::getNameFormatForElection($election->id)
      ),
    ]));

    $this->assign('electionNomination', $this->electionNomination);
    $this->add('textarea', 'comments', E::ts('Candidate statement'), ['cols' => 55, 'rows' => 8], FALSE);
    $this->add('text', 'statement_url', E::ts('Link to a longer statement or CV'), ['size' => 55, 'maxlength' => 2048], FALSE);
    $this->addElement('hidden', 'enid', $this->enId);
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Save'),
        'isDefault' => TRUE,
      ],
    ]);

    if ($this->cid && $this->cs) {
      $contact = \Civi\Api4\Contact::get(FALSE)
        ->addSelect('email_primary', 'display_name')
        ->addWhere('id', '=', $this->cid)
        ->execute()
        ->first();
      $this->assign('checksum_authenticated', $contact);
      $this->assign('login_url', sprintf('%s?eid=%s', getLoginPageURL(\CRM_Utils_System::currentPath()), $election->id));
    }

    parent::buildQuickForm();
  }

  /**
   * May the current visitor edit this statement?
   *
   * @param CRM_Elections_BAO_Election $election
   *
   * @return bool
   */
  private function canEdit($election) {
    if (!$election->isVisible && !isElectionAdmin()) {
      return FALSE;
    }
    if (isElectionAdmin()) {
      return !$election->isVotingEnded;
    }
    if (empty(\CRM_Core_Session::getLoggedInContactID()) && !filter_var($election->allow_checksum_access, FILTER_VALIDATE_BOOL)) {
      return FALSE;
    }
    $actingContactId = CRM_Elections_Helper_Eligibility::getActingContactId();
    return !empty($actingContactId)
      && $actingContactId == $this->electionNomination['member_nominee']
      && $this->electionNomination['has_accepted_nomination'] == 1
      && $this->electionNomination['has_rejected_nomination'] != 1
      && !$election->isVotingStarted;
  }

  public function setDefaultValues() {
    return [
      'comments' => $this->electionNomination['comments'] ?? '',
      'statement_url' => $this->electionNomination['statement_url'] ?? '',
    ];
  }

  public function validate() {
    $values = $this->exportValues();
    if (!CRM_Elections_Helper_Candidate::isValidStatementUrl($values['statement_url'] ?? '')) {
      $this->_errors['statement_url'] = E::ts('Enter a web address starting with http:// or https://');
    }
    return parent::validate();
  }

  public function postProcess() {
    $values = $this->exportValues();
    $url = trim($values['statement_url'] ?? '');

    civicrm_api3('ElectionNomination', 'create', [
      'id' => $this->enId,
      'comments' => $values['comments'],
      'statement_url' => $url !== '' ? $url : NULL,
    ]);
    CRM_Core_Session::setStatus(E::ts('Statement saved.'), '', 'success');

    $redirectUrl = Civi::url('current://civicrm/elections/candidate');
    $redirectUrl->addQuery([
      'enid' => $this->enId,
      'eid' => $this->electionNomination['election_position_id.election_id'],
    ]);
    if ($this->cid && $this->cs) {
      $redirectUrl->addQuery(['cid' => $this->cid, 'cs' => $this->cs]);
    }
    CRM_Utils_System::redirect($redirectUrl);

    parent::postProcess();
  }

}
