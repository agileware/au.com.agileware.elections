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

  public function run() {
    $this->assign('siteTimeZone', CRM_Elections_Helper_TimeZone::getTimeZoneLabel());
    $this->assign('siteTimeZoneConvertUrl', CRM_Elections_Helper_TimeZone::getTimeZoneConvertUrl());
    parent::run();
  }

}
