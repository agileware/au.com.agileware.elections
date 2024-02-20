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
      $this->electionNomination = civicrm_api3('ElectionNomination', 'getsingle', array(
        'id' => $this->enId,
        'return' => ["has_accepted_nomination", "comments", "election_position_id.name", "election_position_id.election_id.name", "election_position_id.election_id", "member_nominee", "member_nominee.display_name", "member_nominee.image_URL"],
      ));
    }
    catch (CiviCRM_API3_Exception $e) {
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
    CRM_Utils_System::setTitle($this->electionNomination['member_nominee.display_name'] . " - " . $this->election->name);

    parent::run();
  }

  private function removeNonRequiredPositions(&$positionsWithNominations) {
    $positionsToRemove = array();
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
