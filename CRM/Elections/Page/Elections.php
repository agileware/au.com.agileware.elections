<?php
use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_Page_Elections extends CRM_Elections_Page_Base {

  private $elections;
  private $isElectionAdmin = FALSE;
  private $isFromShortCode = FALSE;
  private $electionsAction = 'all';
  private $viewAction = NULL;

  public function run() {
    $this->isElectionAdmin = isElectionAdmin();
    $this->assign('isElectionAdmin', $this->isElectionAdmin);
    $this->isFromShortCode = isRequestUsingShortCode();
    $this->electionsAction = CRM_Utils_Request::retrieve('eaction', 'String');
    $this->viewAction = CRM_Utils_Request::retrieve('evaction', 'String');
    $this->assign('viewAction', $this->viewAction);

    if ($this->electionsAction === NULL) {
      $this->electionsAction = 'all';
    }

    $this->elections = $this->getElections();
    $this->assign('elections', $this->elections);
    hideNonRequiredItemsOnPage($this);

    if ($this->isFromShortCode) {
      CRM_Utils_System::setTitle('');
    }

    $this->assign('isFromShortCode', $this->isFromShortCode);

    parent::run();
  }

  private function getCurrentDateTime() {
    return (new DateTime())->format('Y-m-d H:i:s');
  }

  /**
   * Function to get the elections
   *
   * @return array
   * @access protected
   */
  protected function getElections() {
    $params = [
      'is_deleted'  => 0,
      'options'    => ['sort' => 'voting_start_date ASC'],
    ];
    if (!$this->isElectionAdmin || ($this->isFromShortCode && $this->electionsAction == 'visible')) {
      $params['visibility_start_date'] = ['<=' => $this->getCurrentDateTime()];
      $params['visibility_end_date'] = ['>' => $this->getCurrentDateTime()];
      $params['is_visible'] = 1;
    }

    if ($this->isFromShortCode && $this->electionsAction == 'hidden') {
      $params['visibility_start_date'] = ['>' => $this->getCurrentDateTime()];
      $params['visibility_end_date'] = ['<=' => $this->getCurrentDateTime()];
      $params['is_visible'] = 0;
      $params['options'] = ['or' => [['visibility_start_date', 'is_visible', 'visibility_end_date']]];
    }

    $elections = civicrm_api3('Election', 'get', $params);
    $elections = $elections['values'];
    $this->modifyElectionValues($elections);
    $elections = $this->getSortedElections($elections);
    return $elections;
  }

  private function getSortedElections($elections) {
    $activeElections = [];
    $inactiveElections = [];

    foreach ($elections as $election) {
      if ($election['is_visible'] == 1) {
        $activeElections[] = $election;
      }
      else {
        $inactiveElections[] = $election;
      }
    }

    $activeElections = $this->sortByRunningCompleted($activeElections);
    $inactiveElections = $this->sortByRunningCompleted($inactiveElections);

    $elections = array_merge($activeElections, $inactiveElections);
    return $elections;
  }

  private function sortByRunningCompleted($elections) {
    $runningElections = [];
    $completedElections = [];

    $today = new DateTime();
    foreach ($elections as $election) {
      $voteEndDate = DateTime::createFromFormat('Y-m-d H:i:s', $election['voting_end_date']);
      if ($today < $voteEndDate) {
        $runningElections[] = $election;
      }
      else {
        $completedElections[] = $election;
      }
    }

    $completedElections = array_reverse($completedElections);
    $elections = array_merge($runningElections, $completedElections);
    return $elections;
  }

  /**
   * Modify values in elections.
   *
   * @param $elections
   */
  private function modifyElectionValues(&$elections) {
    foreach ($elections as $index => $election) {
      $isElectionInProcess = CRM_Elections_BAO_Election::isElectionInProcess($election['nomination_start_date']);
      $elections[$index]['candelete'] = (!$isElectionInProcess || !$election['is_visible']);
      $elections[$index]['canedit']   = (!$isElectionInProcess || !$election['is_visible']);
      $elections[$index]['hasNominationsStarted']   = CRM_Elections_BAO_Election::isTodayBetweenDates($election['nomination_start_date'], NULL);
      $elections[$index]['isVotingStarted'] = CRM_Elections_BAO_Election::isTodayBetweenDates($election['voting_start_date'], NULL);
      $elections[$index]['isResultsOut'] = (CRM_Elections_BAO_Election::isTodayBetweenDates($election['result_date'], NULL) && $election['has_results_generated']);

      $elections[$index]['isVotingEnded'] = CRM_Elections_BAO_Election::isTodayBetweenDates($election['voting_end_date'], NULL);
      $elections[$index]['advertiseCandidatesStarted'] = CRM_Elections_BAO_Election::isTodayBetweenDates($election['advertise_candidates_date'], NULL);
      $elections[$index]['isNominationsInProgress'] = CRM_Elections_BAO_Election::isTodayBetweenDates($election['nomination_start_date'], $election['nomination_end_date']);

      $variables = $this->getUserVotingVariables($elections[$index]['isVotingStarted'], $election['id']);

      $elections[$index]['userVoteDate'] = $variables['user_vote_date'];
      $elections[$index]['isUserAllowedToVote'] = $variables['is_user_allowed_to_vote'];
      $elections[$index]['hasUserAlreadyVoted'] = $variables['has_user_already_voted'];

      $elections[$index]['positions'] = civicrm_api3('ElectionPosition', 'getcount', [
        'election_id' => $election['id'],
      ]);
    }
  }

}
