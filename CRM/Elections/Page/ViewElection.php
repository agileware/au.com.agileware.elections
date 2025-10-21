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
      CRM_Utils_System::setTitle($this->election->name . ' - Result');
    }
    else {
      $canEdit = $this->election->canEdit();
      $this->isElectionRunning = ($canEdit !== TRUE) ? TRUE : FALSE;

      $this->assign('isElectionAdmin', $this->isElectionAdmin);
      $this->assign('isElectionRunning', $this->isElectionRunning);
      $this->assign('isAllowedToNominate', isLoggedInMemberAllowedToVote($this->eId));
      $this->assignPositions();
      $positionsWithNominations = CRM_Elections_BAO_ElectionPosition::findWithNominationsByElectionId($this->eId);
      CRM_Elections_BAO_ElectionResult::updateCandidateProfilePictures($positionsWithNominations, FALSE, TRUE);
      $this->assign('nominations', $positionsWithNominations);
      $this->assign('loggedInContactId', CRM_Core_Session::singleton()->getLoggedInContactID());

      $isShowingSpecificElectionFromShortCode = CRM_Utils_Request::retrieve('sse', 'Boolean', $form, FALSE, FALSE);
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

    parent::run();
  }

  private function assignPositions() {
    $electionPositions = civicrm_api3('ElectionPosition', 'get', [
      'election_id' => $this->eId,
      'sequential'  => TRUE,
	  'options' => ['limit' => 0],
    ]);

    $this->assign('positions', $electionPositions['values']);
  }

}
