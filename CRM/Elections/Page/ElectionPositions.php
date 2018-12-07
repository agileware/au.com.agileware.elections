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
    $this->election = findElectionById($this->eId);

    $this->isElectionAdmin = isElectionAdmin();
    $canEdit = $this->election->canEdit();
    $this->isElectionRunning = ($canEdit !== TRUE) ? TRUE : FALSE;

    if (!$this->isElectionAdmin && !$this->election->isVisible) {
      throwAccessDeniedPage($this);
      parent::run();
      return;
    }

    $this->assign('isElectionAdmin', $this->isElectionAdmin);
    $this->assign('isElectionRunning', $this->isElectionRunning);
    $this->assign('election', $this->election);

    CRM_Utils_System::setTitle('Election Positions - ' . $this->election->name);

    $electionPositions = civicrm_api3('ElectionPosition', 'get', array(
      'election_id' => $this->eId,
      'options'     => array('sort' => 'sortorder ASC'),
      'sequential'  => TRUE,
    ));

    $this->assign('positions', $electionPositions['values']);

    parent::run();
  }

  public static function reOrder() {
    $positionsString = CRM_Utils_Request::retrieve('neworder', 'String');
    if ($positionsString) {
      $positionsWithOrder = json_decode($positionsString, TRUE);
      foreach ($positionsWithOrder as $positionWithOrder) {
        $electionPostion = new CRM_Elections_BAO_ElectionPosition();
        $electionPostion->id = $positionWithOrder['id'];

        if ($electionPostion->find(TRUE)) {
          $electionPostion->sortorder = $positionWithOrder['order'];
          $electionPostion->save();
        }
      }
    }

    CRM_Utils_JSON::output(array(
      'status' => 1,
    ));
  }

}
