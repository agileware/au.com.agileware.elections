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
  private $isAdminFrame = FALSE;

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
    // One form, two frames: an administrator in the back end gets the breadcrumb and Submit, Back, Back to
    // Manage Elections; a candidate gets Submit and Back to the election.
    $this->isAdminFrame = isElectionAdmin() && !isRequestUsingShortCode();
    $this->assign('isAdminFrame', $this->isAdminFrame);
    if (!$this->isAdminFrame) {
      hideNonRequiredItemsOnPage($this);
    }

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
      // The technical reason goes to the log; the person is told what to do.
      Civi::log()->warning('Elections: statement of nomination {id} could not be loaded: {message}', ['id' => $this->enId, 'message' => $e->getMessage()]);
      throwAccessDeniedException($this, E::ts('This statement could not be found.'), [
        'return_button_text' => E::ts('View Elections'),
        'return_button_action' => (string) Civi::url('current://civicrm/elections'),
      ]);
      return;
    }

    $election = findElectionById($this->electionNomination['election_position_id.election_id']);
    $electionId = (int) $election->id;
    if (!$this->canEdit($election)) {
      throwAccessDeniedPage($this);
      return;
    }
    if ($this->isAdminFrame) {
      $candidates = CRM_Elections_Helper_Frame::candidatesUrl($electionId);
      CRM_Elections_Helper_Frame::assignToForm($this, CRM_Elections_Helper_Frame::trail($electionId, [[E::ts('Candidates'), $candidates]], E::ts('Edit Statement')), $candidates);
    }
    else {
      CRM_Elections_Helper_Utils::assignBackToElection($this, $electionId, $this->cid, $this->cs);
    }
    CRM_Utils_System::setTitle(E::ts('Edit Statement for %1', [
      1 => CRM_Elections_Helper_Candidate::formatName(
        $this->electionNomination['member_nominee.display_name'],
        $this->electionNomination['member_nominee.first_name'] ?? NULL,
        $this->electionNomination['member_nominee.last_name'] ?? NULL,
        $this->electionNomination['member_nominee.nick_name'] ?? NULL,
        CRM_Elections_Helper_Candidate::getNameFormatForElection($election->id)
      ),
    ]));

    $this->assign('electionNomination', $this->electionNomination);
    $this->add('textarea', 'comments', E::ts('Statement'), ['cols' => 55, 'rows' => 8], FALSE);
    $this->add('text', 'statement_url', E::ts('Statement link'), ['size' => 55, 'maxlength' => 2048], FALSE);
    $this->addElement('hidden', 'enid', $this->enId);
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Submit'),
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
    // A statement that was refused comes back as the person wrote it.
    return CRM_Elections_Helper_Utils::recallInput('statement') + [
      'comments' => $this->electionNomination['comments'] ?? '',
      'statement_url' => $this->electionNomination['statement_url'] ?? '',
    ];
  }

  public function validate() {
    $values = $this->exportValues();
    if (!CRM_Elections_Helper_Candidate::isValidStatementUrl($values['statement_url'] ?? '')) {
      $this->_errors['statement_url'] = E::ts('The statement link must start with http:// or https://.');
    }
    return parent::validate();
  }

  public function postProcess() {
    $values = $this->exportValues();
    $url = trim($values['statement_url'] ?? '');

    $electionId = $this->electionNomination['election_position_id.election_id'];
    $query = ['enid' => $this->enId, 'eid' => $electionId];
    if ($this->cid && $this->cs) {
      $query += ['cid' => $this->cid, 'cs' => $this->cs];
    }
    try {
      civicrm_api3('ElectionNomination', 'create', [
        'id' => $this->enId,
        'comments' => $values['comments'],
        'statement_url' => $url !== '' ? $url : NULL,
      ]);
      CRM_Core_Session::setStatus(E::ts('Statement saved.'), '', 'success');
    }
    catch (CRM_Core_Exception $e) {
      // The person stays on the form, with what they wrote still in it.
      CRM_Core_Session::setStatus($e->getMessage(), E::ts('Statement not saved'), 'error');
      CRM_Elections_Helper_Utils::rememberInput('statement', ['comments' => $values['comments'], 'statement_url' => $values['statement_url'] ?? '']);
      CRM_Utils_System::redirect((string) Civi::url('current://civicrm/elections/candidate/statement')->addQuery($query));
    }

    if ($this->isAdminFrame) {
      // Back to the list the administrator came from.
      CRM_Utils_System::redirect(CRM_Elections_Helper_Frame::backUrl(CRM_Elections_Helper_Frame::candidatesUrl((int) $electionId)));
    }
    CRM_Utils_System::redirect(Civi::url('current://civicrm/elections/candidate')->addQuery($query));

    parent::postProcess();
  }

}
