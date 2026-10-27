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
    // Who is in each role, shown when the election has groups of its own for nominating or standing.
    $roles = [];
    foreach (['nominating' => E::ts('May nominate and second'), 'candidate' => E::ts('May stand as candidates')] as $role => $label) {
      $own = $role === 'nominating' ? $election->nomination_groups : $election->candidate_groups;
      if (trim((string) $own, ',') !== '') {
        $roles[] = ['label' => $label, 'count' => CRM_Elections_Helper_Eligibility::countContactsInGroups(CRM_Elections_Helper_Eligibility::groupsFor($election, $role))];
      }
    }
    $this->assign('eligibleRoles', $roles);
    // Write-ins: who has been written in and where they stand. Never who wrote them in, or how many votes they have.
    $writeIns = [];
    $votingEnded = !empty($election->isVotingEnded);
    $dao = CRM_Core_DAO::executeQuery('
      SELECT p.name AS position, c.display_name, n.has_accepted_nomination AS accepted, n.has_rejected_nomination AS declined, n.write_in_reminded_date, n.write_in_closed_notified_date
      FROM civicrm_election_nomination n
      INNER JOIN civicrm_election_position p ON p.id = n.election_position_id
      INNER JOIN civicrm_contact c ON c.id = n.member_nominee
      WHERE p.election_id = %1 AND n.is_write_in = 1
      ORDER BY p.sortorder, c.sort_name
    ', [1 => [(int) $eId, 'Integer']]);
    while ($dao->fetch()) {
      $writeIns[] = [
        'position' => $dao->position,
        'name' => $dao->display_name,
        'status' => $dao->accepted ? E::ts('Accepted: on the ballot') : ($dao->declined ? E::ts('Declined') : ($votingEnded ? ($dao->write_in_closed_notified_date ? E::ts('Did not answer before voting closed (told)') : E::ts('Did not answer before voting closed')) : ($dao->write_in_reminded_date ? E::ts('Waiting for an answer (reminded)') : E::ts('Waiting for an answer')))),
      ];
    }
    $this->assign('writeIns', $writeIns);
    $writeInPositions = (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_position WHERE election_id = %1 AND write_ins_per_seat > 0', [1 => [(int) $eId, 'Integer']]);
    $this->assign('writeInsWithoutRevote', $writeInPositions > 0 && !$election->allow_revote);
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
