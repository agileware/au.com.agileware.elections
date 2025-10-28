<?php
use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_Page_ElectionCandidate extends CRM_Elections_Page_Base {

  private $enId = 0;
  private $electionNomination = NULL;
  private $election = NULL;

  public function run() {
    $this->enId = CRM_Utils_Request::retrieve('enid', 'Positive', $this, FALSE, 0);
    hideNonRequiredItemsOnPage($this);

    if (!$this->enId) {
      $this->enId = 0;
      throwAccessDeniedPage($this);
      parent::run();
      return;
    }

    try {
      $this->electionNomination = civicrm_api3('ElectionNomination', 'getsingle', [
        'id' => $this->enId,
        'return' => ['has_accepted_nomination', 'comments', 'election_position_id.name', 'election_position_id.election_id.name', 'election_position_id.election_id', 'member_nominee', 'member_nominee.display_name', 'member_nominee.image_URL'],
      ]);
    }
    catch (CRM_Core_Exception $e) {
      throwAccessDeniedException($this, $e->getMessage());
      parent::run();
      return;
    }

    $this->election = findElectionById($this->electionNomination['election_position_id.election_id']);
    if ((!$this->election->isVisible && !isElectionAdmin())) {
      throwAccessDeniedPage($this);
      parent::run();
      return;
    }

    CRM_Elections_Helper_Utils::replaceSingleProfilePic($this->electionNomination, 'member_nominee.image_URL', 'member_nominee');
    $this->assign('nomination', $this->electionNomination);
    $positionsWithNominations = CRM_Elections_BAO_ElectionPosition::findWithNominationsByElectionId($this->electionNomination['election_position_id.election_id']);
    $this->removeNonRequiredPositions($positionsWithNominations);
    $this->assign('positions', $positionsWithNominations);
    $this->assign('election', $this->election);
    CRM_Utils_System::setTitle($this->electionNomination['member_nominee.display_name'] . ' - ' . $this->election->name);

    $this->assign('checksum_query', [
      'query' => sprintf( 'eid=%s', $this->electionNomination['election_position_id.election_id'] )
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
          'query' => sprintf( 'eid=%s&cs=%s&cid=%s', $this->electionNomination['election_position_id.election_id'], $contact_valid_checksum['cs'], $contact_valid_checksum['cid'] )
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
        $this->assign( 'login_url', sprintf( '%s?eid=%s', $login_url, $this->electionNomination['election_position_id.election_id'] ) );
      }
    }

    parent::run();
  }

  private function removeNonRequiredPositions(&$positionsWithNominations) {
    $positionsToRemove = [];
    foreach ($positionsWithNominations as $index => $positionWithNominations) {
      if (isset($positionWithNominations['nominations'])) {
        $nominations = $positionWithNominations['nominations'];

        $nominationsToRemove = [];
        $foundNominee = FALSE;
        foreach ($nominations as $nominationIndex => $nomination) {
          if (($nomination['member_nominee'] == $this->electionNomination['member_nominee']) and (($this->election->isNominationsInProgress) or (!$this->election->isNominationsInProgress and count($nomination['seconders']) >= 2)) and (!$this->election->isVotingStarted || ($this->election->isVotingStarted && $nomination['has_accepted_nomination']))) {
            $foundNominee = TRUE;
          }
          else {
            $nominationsToRemove[] = $nominationIndex;
          }
        }

        foreach ($nominationsToRemove as $nominationToRemove) {
          unset($positionsWithNominations[$index]['nominations'][$nominationToRemove]);
        }

        if (!$foundNominee) {
          $positionsToRemove[] = $index;
        }
      }
    }

    foreach ($positionsWithNominations as $index => $positionsWithNomination) {
      if (empty($positionsWithNomination['nominations'])) {
        $positionsToRemove[] = $index;
      }
    }

    foreach ($positionsToRemove as $positionToRemove) {
      unset($positionsWithNominations[$positionToRemove]);
    }
  }

}
