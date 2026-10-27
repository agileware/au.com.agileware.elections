<?php
use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_Page_ElectionPositions extends CRM_Elections_Page_Base {

  private $eId = 0;
  private $isElectionAdmin = FALSE;
  private $election = NULL;
  private $isElectionRunning = FALSE;

  public function run() {
    $this->eId = retrieveElectionIdFromUrl($this);
    hideNonRequiredItemsOnPage($this);
    if ($this->eId == -1) {
      parent::run();
      return;
    }
    if (!isElectionAdmin()) {
      throwAccessDeniedPage($this);
      parent::run();
      return;
    }
    // The positions list is a SearchKit page (drag to reorder, edit in place). This route is kept so
    // existing links and redirects keep working.
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/elections/position-list', 'reset=1', FALSE, '/?eid=' . (int) $this->eId));
  }

}
