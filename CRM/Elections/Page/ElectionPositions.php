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
    // The positions list is a SearchKit page. While the election is active (switched on, nominations
    // started) the positions cannot change, so the read-only page is opened instead of the one with drag
    // and drop and editing in place. This route is also kept so existing links keep working.
    $locked = findElectionById($this->eId)->canEdit() !== TRUE;
    CRM_Utils_System::redirect(CRM_Utils_System::url($locked ? 'civicrm/elections/position-list-active' : 'civicrm/elections/position-list', 'reset=1', FALSE, '/?eid=' . (int) $this->eId));
  }

}
