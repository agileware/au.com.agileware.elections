<?php
use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_Page_ViewElection extends CRM_Elections_Page_Base {

  private $eId = 0;
  private $isElectionAdmin = FALSE;
  private $election = NULL;
  private $isElectionRunning = FALSE;

  public function getTemplateFileName() {
    if ($this->election->has_results_generated) {
      return 'CRM/Elections/Page/ElectionResults.tpl';
    }
    return parent::getTemplateFileName();
  }

  public function run() {
    $this->eId = retrieveElectionIdFromUrl($this);
    hideNonRequiredItemsOnPage($this);
    if ($this->eId == -1) {
      parent::run();
      return;
    }
    $this->election = findElectionById($this->eId);

    // User is not logged in, and the election does not allow checksum access
    if ( empty( \CRM_Core_Session::getLoggedInContactID() ) && !filter_var($this->election->allow_checksum_access, FILTER_VALIDATE_BOOL) ) {
      throwAccessDeniedPage($this);
      parent::run();
      return;
    }

    $this->isElectionAdmin = isElectionAdmin();

    if (!$this->isElectionAdmin && !$this->election->isVisible) {
      throwAccessDeniedPage($this);
      parent::run();
      return;
    }

    $this->assign('election', $this->election);
    if ($this->election->has_results_generated) {
      $positions = CRM_Elections_BAO_ElectionResult::getResultsAndSummary($this->election->id);
      $this->assign('positions', $positions);
      $this->assign('resultStatuses', CRM_Elections_BAO_Election::getResultStatues());
      CRM_Utils_System::setTitle(E::ts('%1 - Result', [1 => $this->election->name]));
    }
    else {
      $canEdit = $this->election->canEdit();
      $this->isElectionRunning = ($canEdit !== TRUE) ? TRUE : FALSE;

      $this->assign('isElectionAdmin', $this->isElectionAdmin);
      $this->assign('isElectionRunning', $this->isElectionRunning);
      $this->assign('isAllowedToNominate', CRM_Elections_Helper_Eligibility::canNominate($this->eId));
      $this->assignPositions();
      $positionsWithNominations = CRM_Elections_BAO_ElectionPosition::findWithNominationsByElectionId($this->eId);
      CRM_Elections_BAO_ElectionResult::updateCandidateProfilePictures($positionsWithNominations, FALSE, TRUE);
      $this->assign('nominations', $positionsWithNominations);
      $this->assign('loggedInContactId', CRM_Core_Session::singleton()->getLoggedInContactID());

      $isShowingSpecificElectionFromShortCode = CRM_Utils_Request::retrieve('sse', 'Boolean', $this, FALSE, FALSE);
      $this->assignUserVotingVariables($this->eId);

      if ($isShowingSpecificElectionFromShortCode) {
        CRM_Utils_System::setTitle('');
      }
      else {
        CRM_Utils_System::setTitle($this->election->name);
      }
    }

    $this->assign('checksum_query', [
      'query' => sprintf( 'eid=%s', $this->eId )
    ]);

    // Support anonymous validated via contact checksum
    // Only validate cs and cid if the user is not logged in
    if ( empty( \CRM_Core_Session::getLoggedInContactID() ) ) {
      // The custom access callback should already validate this, but
      // we want the utils function to always validate anyway, just
      // in case it is used outside of a page with the access
      // callback for whatever reason.
      $contact_valid_checksum = retrieveContactChecksumFromUrl( $this );

      if ( $contact_valid_checksum ) {
        // Build a query string including the election ID and validated checksum
        $this->assign('checksum_query', [
          'cid' => $contact_valid_checksum['cid'],
          'cs' => $contact_valid_checksum['cs'],
          'query' => sprintf( 'eid=%s&cs=%s&cid=%s', $this->eId, $contact_valid_checksum['cs'], $contact_valid_checksum['cid'] )
        ]);

        // Expose to Smarty
        $contact = \Civi\Api4\Contact::get(FALSE)
                                    ->addSelect('email_primary', 'display_name')
                                    ->addWhere('id', '=', $contact_valid_checksum['cid'])
                                    ->execute()
                                    ->first();

        $this->assign( 'checksum_authenticated', $contact );
        $this->assign( 'loggedInContactId', $contact_valid_checksum['cid'] ); // Override

        $login_url = getLoginPageURL(\CRM_Utils_System::currentPath());
        $this->assign( 'login_url', sprintf( '%s?eid=%s', $login_url, $this->eId ) );
      }
    }

    if (!$this->election->has_results_generated) {
      $this->assignStatus();
    }
    elseif ($this->election->isResultsOut || $this->isElectionAdmin) {
      $query = $this->get_template_vars('checksum_query')['query'] ?? ('eid=' . $this->eId);
      CRM_Elections_Helper_ResultsDisplay::assignToPage($this, $this->election, $query, $this->election->isResultsOut);
    }

    parent::run();
  }

  /**
   * The stage stepper and the "what you can do now" sentence for the person looking.
   */
  private function assignStatus() {
    $contactId = CRM_Elections_Helper_Eligibility::getActingContactId();
    $checksum = CRM_Core_Smarty::singleton()->getTemplateVars('checksum_query');
    $voting = $this->getUserVotingVariables($this->election->isVotingStarted, $this->eId);
    $this->assign('electionSteps', CRM_Elections_Helper_ElectionStatus::steps($this->election));
    $this->assign('myNominations', CRM_Elections_Helper_ElectionStatus::ownNominations($this->election, $contactId, $checksum['query'] ?? sprintf('eid=%d', $this->eId)));
    // The person's own ballot while voting is open (a receipt of what was recorded).
    $this->assign('ownBallot', ($this->election->isVotingStarted && !$this->election->isVotingEnded && $voting['has_user_already_voted']) ? CRM_Elections_Helper_ElectionStatus::ownBallot($this->election, $contactId) : []);
    $this->assign('electionNextAction', CRM_Elections_Helper_ElectionStatus::nextAction($this->election, $contactId, $checksum['query'] ?? sprintf('eid=%d', $this->eId), [
      'canNominate' => (bool) CRM_Elections_Helper_Eligibility::canNominate($this->eId),
      'canVote' => (bool) isLoggedInMemberAllowedToVote($this->eId),
      'hasVoted' => (bool) $voting['has_user_already_voted'],
      'voteDate' => $voting['user_vote_date'],
      'allowedToChangeVote' => (bool) $voting['is_user_allowed_to_vote'],
    ]));
  }

  private function assignPositions() {
    $electionPositions = civicrm_api3('ElectionPosition', 'get', [
      'election_id' => $this->eId,
      'sequential'  => TRUE,
	  'options' => ['limit' => 0],
    ]);

    $this->assign('positions', $electionPositions['values']);
    // The yes or no questions, with their choices.
    $questions = [];
    foreach ($electionPositions['values'] as $position) {
      if (($position['position_type'] ?? '') === CRM_Elections_BAO_ElectionPosition::TYPE_REFERENDUM) {
        $position['options'] = CRM_Elections_BAO_ElectionPosition::getOptions($position['id']);
        $questions[] = $position;
      }
    }
    $this->assign('questions', $questions);
  }

}
