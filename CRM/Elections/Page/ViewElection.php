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
        CRM_Utils_System::setTitle("");
      }
      else {
        CRM_Utils_System::setTitle($this->election->name);
      }
    }

    parent::run();
  }

  private function assignPositions() {
    $electionPositions = civicrm_api3('ElectionPosition', 'get', array(
      'election_id' => $this->eId,
      'sequential'  => TRUE,
    ));

    $this->assign('positions', $electionPositions['values']);
  }

}
