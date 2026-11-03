<?php

class CRM_Elections_Page_Base extends CRM_Core_Page {
  public $throwError = FALSE;

  public function getTemplateFileName() {
    if ($this->throwError) {
      return 'CRM/Elections/Helper/Error.tpl';
    }
    return parent::getTemplateFileName();
  }

  public function assignUserVotingVariables($electionId) {
    $election = findElectionById($electionId);
    $variables = $this->getUserVotingVariables($election->isVotingStarted, $electionId);

    $this->assign('userVoteDate', $variables['user_vote_date']);
    $this->assign('isUserAllowedToVote', $variables['is_user_allowed_to_vote']);
    $this->assign('hasUserAlreadyVoted', $variables['has_user_already_voted']);
  }

  public function getUserVotingVariables($isVotingStarted, $electionId) {
    $isUserAllowedToVote = FALSE;
    $hasAlreadyVoted = FALSE;
    $userVoteDate = '';

    if ($isVotingStarted) {
      $hasAlreadyVoted = hasLoggedInUserAlreadyVoted($electionId);
      if (($hasAlreadyVoted && isMemberAllowedToReVote($electionId)) || !$hasAlreadyVoted) {
        $isUserAllowedToVote = TRUE;
      }
      if ($hasAlreadyVoted) {
        $userVoteDate = getLoggedInUserVoteDate($electionId);
      }
    }

    return [
      'user_vote_date' => $userVoteDate,
      'is_user_allowed_to_vote' => $isUserAllowedToVote,
      'has_user_already_voted'  => $hasAlreadyVoted,
    ];
  }

  /**
   * Give the page the address of the "Back to the election" link (CRM/Elections/Frame/BackToElection.tpl).
   *
   * Keeps the election and, for a person on a personal link, the link itself, so the election page does not
   * refuse them. Call it once the page has worked out its "checksum_query".
   *
   * @param int $electionId
   */
  public function assignBackToElection($electionId) {
    $checksum = $this->getTemplateVars('checksum_query');
    $query = $checksum['query'] ?? ('eid=' . (int) $electionId . CRM_Elections_Page_Elections::personalLinkQuery());
    $this->assign('backToElectionUrl', CRM_Utils_System::url('civicrm/elections/view', $query, FALSE, NULL, FALSE));
  }

  public function run() {
    // The site time zone is said once on a page, not beside every date.
    $this->assign('siteTimeZone', CRM_Elections_Helper_TimeZone::getTimeZoneLabel());
    $this->assign('siteTimeZoneLine', CRM_Elections_Helper_TimeZone::getTimeZoneLine());
    parent::run();
  }

}
