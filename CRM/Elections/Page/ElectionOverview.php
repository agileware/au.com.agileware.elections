<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * One election at a glance for administrators: stage, timeline, counts and what to do next.
 */
class CRM_Elections_Page_ElectionOverview extends CRM_Elections_Page_Base {

  public function run() {
    $eId = retrieveElectionIdFromUrl($this);
    hideNonRequiredItemsOnPage($this);
    if ($eId == -1) {
      parent::run();
      return;
    }
    if (!isElectionAdmin()) {
      throwAccessDeniedPage($this);
      parent::run();
      return;
    }

    $election = findElectionById($eId);
    $stage = $election->getStage();
    $counts = CRM_Elections_BAO_Election::getCounts($eId);
    CRM_Utils_System::setTitle(E::ts('Election: %1', [1 => $election->name]));

    $now = new DateTime();
    $timeline = [];
    foreach ([
      'visibility_start_date' => E::ts('Visible to people'),
      'nomination_start_date' => E::ts('Nominations open'),
      'nomination_end_date' => E::ts('Nominations close'),
      'advertise_candidates_date' => E::ts('Candidates advertised'),
      'voting_start_date' => E::ts('Voting opens'),
      'voting_end_date' => E::ts('Voting closes'),
      'result_date' => E::ts('Results published'),
      'visibility_end_date' => E::ts('Hidden from people'),
    ] as $field => $label) {
      $when = DateTime::createFromFormat('Y-m-d H:i:s', $election->$field);
      $timeline[] = ['label' => $label, 'date' => $election->$field, 'passed' => $when && $when <= $now];
    }

    $this->assign('election', $election);
    $this->assign('stage', $stage);
    $this->assign('counts', $counts);
    $voters = CRM_Elections_Helper_Eligibility::countContactsInGroups($election->allowed_groups);
    $this->assign('eligibleVoters', $voters);
    $this->assign('turnoutPercent', $voters > 0 ? round(100 * $counts['ballots'] / $voters) : NULL);
    $this->assign('turnout', CRM_Elections_BAO_Election::getTurnout($eId));
    $this->assign('hasResults', !empty($election->has_results_generated));
    $this->assign('integrity', CRM_Elections_Helper_Integrity::verify($eId));
    $this->assign('auditHead', CRM_Elections_Helper_Audit::getHeadHash());
    $this->assign('auditRecent', CRM_Elections_Helper_Audit::entries($eId, 8));
    $this->assign('timeline', $timeline);
    $this->assign('nextStep', CRM_Elections_BAO_Election::getNextStep($stage, $counts));
    $this->assign('canEdit', $election->canEdit() === TRUE);
    parent::run();
  }

}
