<?php
use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_Page_ElectionSummary extends CRM_Elections_Page_Base {

  private $eId = 0;
  private $isElectionAdmin = FALSE;
  private $election = NULL;

  public function run() {
    $this->eId = retrieveElectionIdFromUrl($this);
    hideNonRequiredItemsOnPage($this);
    if ($this->eId == -1) {
      parent::run();
      return;
    }
    $this->election = findElectionById($this->eId);
    $this->isElectionAdmin = isElectionAdmin();

    CRM_Utils_System::setTitle("Election Summary - " . $this->election->name);

    if (!$this->election->isResultsOut) {
      throwAccessDeniedException($this, "Election summary is not available yet.");
      parent::run();
      return;
    }

    $this->assign('election', $this->election);
    $positions = CRM_Elections_BAO_ElectionResult::getResultsAndSummary($this->election->id, TRUE);
    $this->assign('positions', $positions);
    $this->assign('resultStatuses', CRM_Elections_BAO_Election::getResultStatues());

    parent::run();
  }

}
