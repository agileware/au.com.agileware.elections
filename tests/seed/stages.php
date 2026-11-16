<?php
/**
 * The stages of an election as dates, shared by seed.php and move-election.php.
 */

/**
 * The days (from now) of each date of an election at each stage, in this order:
 * visibility start, nominations open, nominations close, candidates announced, voting opens,
 * voting closes, results, visibility end. Wide windows, so an election stays at its stage for weeks.
 */
const STAGE_DAYS = [
  'nominations' => [-5, -3, 21, 22, 28, 35, 36, 90],
  'advertise' => [-20, -18, -8, -7, 14, 21, 22, 90],
  'voting' => [-30, -25, -16, -15, -3, 25, 26, 90],
  'results' => [-60, -55, -45, -44, -35, -14, -13, 90],
];
const DATE_KEYS = ['visibility_start_date', 'nomination_start_date', 'nomination_end_date', 'advertise_candidates_date', 'voting_start_date', 'voting_end_date', 'result_date', 'visibility_end_date'];

function stage_dates(string $stage): array {
  $dates = [];
  foreach (DATE_KEYS as $i => $key) {
    $days = STAGE_DAYS[$stage][$i];
    // Opening dates at 9am and closing dates at 5pm, local time, read better than the time of the run.
    $time = in_array($key, ['nomination_end_date', 'voting_end_date'], TRUE) ? '17:00:00' : '09:00:00';
    $dates[$key] = date('Y-m-d', strtotime(($days >= 0 ? '+' : '') . $days . ' days')) . ' ' . $time;
  }
  return $dates;
}

function move_to_stage(int $electionId, string $stage): void {
  $sets = [];
  $params = [1 => [$electionId, 'Integer']];
  $i = 2;
  foreach (stage_dates($stage) as $key => $value) {
    $sets[] = "$key = %$i";
    $params[$i++] = [$value, 'String'];
  }
  CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET ' . implode(', ', $sets) . ' WHERE id = %1', $params);
}
