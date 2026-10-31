<?php
/**
 * Checks a site that was seeded with version 1.3.2 (seed-v1.php) and has been upgraded to version 2: nothing was lost
 * or changed, the new settings have the values that keep the old behaviour, and the old elections still run.
 * Run with `cv scr`. Prints one line for each check and exits non-zero if any failed.
 */
$snapshot = json_decode(file_get_contents((getenv('UPGRADE_DIR') ?: __DIR__) . '/.generated/v1-snapshot.json'), TRUE);
// `cv scr` runs this inside a function, so a plain global is not shared: the count lives in the function.
function check($name, $ok = NULL, $detail = '') {
  static $failed = 0;
  if ($name === '__failed') {
    return $failed;
  }
  echo ($ok ? 'PASS ' : 'FAIL ') . $name . ($ok ? '' : ($detail !== '' ? ' -- ' . $detail : '')) . "\n";
  if (!$ok) {
    $failed++;
  }
}
function q($sql, $params = []) {
  return CRM_Core_DAO::singleValueQuery($sql, $params);
}
$like = "WHERE e.name LIKE 'Upgrade %'";

// --- the schema
foreach (['civicrm_election' => ['voting_method', 'nomination_groups', 'candidate_groups', 'send_emails', 'allow_online_nominations', 'show_photos', 'show_statements', 'name_format', 'nomination_message_template_id', 'results_header_text', 'results_footer_text', 'results_logo_file_id', 'results_logo_alt', 'celebration_enabled', 'celebration_effect', 'celebration_days'], 'civicrm_election_position' => ['write_ins_per_seat', 'position_type', 'pass_threshold', 'quorum_percent', 'resolution_text'], 'civicrm_election_nomination' => ['is_write_in', 'write_in_notified_date', 'write_in_reminded_date', 'write_in_closed_notified_date', 'statement_url']] as $table => $columns) {
  foreach ($columns as $column) {
    check("column $table.$column exists", CRM_Core_BAO_SchemaHandler::checkIfFieldExists($table, $column));
  }
}
foreach (['civicrm_election_audit', 'civicrm_election_option', 'civicrm_election_option_vote'] as $table) {
  check("table $table exists", CRM_Core_DAO::checkTableExists($table));
}
check('one ballot row for each member and candidate is enforced', CRM_Core_BAO_SchemaHandler::checkIfIndexExists('civicrm_election_vote', 'UI_election_vote_member_nomination'));
check('one nomination for each person and position is enforced', CRM_Core_BAO_SchemaHandler::checkIfIndexExists('civicrm_election_nomination', 'UI_election_nomination_position_nominee'));

// --- the old behaviour is kept
check('every old election is ranked choice', (int) q("SELECT COUNT(*) FROM civicrm_election e $like AND IFNULL(e.voting_method, 'irv') <> 'irv'") === 0);
check('no old election has groups of its own for nominating or standing', (int) q("SELECT COUNT(*) FROM civicrm_election e $like AND (IFNULL(e.nomination_groups, '') <> '' OR IFNULL(e.candidate_groups, '') <> '')") === 0);
check('no old election sends emails', (int) q("SELECT COUNT(*) FROM civicrm_election e $like AND IFNULL(e.send_emails, 0) <> 0") === 0);
check('online nominating stays on', (int) q("SELECT COUNT(*) FROM civicrm_election e $like AND IFNULL(e.allow_online_nominations, 1) = 0") === 0);
check('write-ins stay off and positions stay positions', (int) q("SELECT COUNT(*) FROM civicrm_election_position p INNER JOIN civicrm_election e ON e.id = p.election_id $like AND (IFNULL(p.write_ins_per_seat, 0) <> 0 OR IFNULL(p.position_type, 'election') <> 'election')") === 0);

// --- nothing was lost or changed
foreach ($snapshot['counts'] as $what => $expected) {
  $actual = [
    'elections' => fn() => (int) q("SELECT COUNT(*) FROM civicrm_election e $like"),
    'votes' => fn() => (int) q("SELECT COUNT(*) FROM civicrm_election_vote v INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id INNER JOIN civicrm_election_position p ON p.id = n.election_position_id INNER JOIN civicrm_election e ON e.id = p.election_id $like"),
    'nominations' => fn() => (int) q("SELECT COUNT(*) FROM civicrm_election_nomination n INNER JOIN civicrm_election_position p ON p.id = n.election_position_id INNER JOIN civicrm_election e ON e.id = p.election_id $like"),
    'seconders' => fn() => (int) q("SELECT COUNT(*) FROM civicrm_election_nomination_seconder s INNER JOIN civicrm_election_nomination n ON n.id = s.election_nomination_id INNER JOIN civicrm_election_position p ON p.id = n.election_position_id INNER JOIN civicrm_election e ON e.id = p.election_id $like"),
    'results' => fn() => (int) q("SELECT COUNT(*) FROM civicrm_election_result r INNER JOIN civicrm_election_position p ON p.id = r.election_position_id INNER JOIN civicrm_election e ON e.id = p.election_id $like"),
  ][$what]();
  check("$what: $expected before, $actual after", $actual === $expected);
}
$finished = $snapshot['elections']['finished']['id'];
$voting = $snapshot['elections']['voting']['id'];
$results = CRM_Core_DAO::executeQuery('SELECT r.election_nomination_id, r.`rank` FROM civicrm_election_result r INNER JOIN civicrm_election_position p ON p.id = r.election_position_id WHERE p.election_id = %1 ORDER BY r.election_nomination_id', [1 => [$finished, 'Integer']])->fetchAll();
check('the finished election has the same results', json_encode(array_map('array_values', $results)) === json_encode(array_map('array_values', $snapshot['result_rows'])));
$votes = CRM_Core_DAO::executeQuery('SELECT v.election_nomination_id, v.`rank`, v.member_id FROM civicrm_election_vote v INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id INNER JOIN civicrm_election_position p ON p.id = n.election_position_id WHERE p.election_id IN (%1, %2) ORDER BY v.election_nomination_id, v.member_id', [1 => [$finished, 'Integer'], 2 => [$voting, 'Integer']])->fetchAll();
check('the ballots are the same', json_encode(array_map('array_values', $votes)) === json_encode(array_map('array_values', $snapshot['vote_rows'])));

// --- the old elections show up and are described correctly
$summary = CRM_Elections_BAO_ElectionResult::getResultsAndSummary($finished);
$winners = 0;
foreach ($summary as $position) {
  foreach ($position['candidates'] as $candidate) {
    $winners += !empty($candidate['is_winner']) ? 1 : 0;
  }
}
check('the finished election lists its winners', $winners >= 1, "winners: $winners");
$integrity = CRM_Elections_Helper_Integrity::verify($finished);
check('results made before 2.0 say their integrity cannot be checked, not that it failed', $integrity['status'] === 'no_record', $integrity['status']);
check('the audit log chain is sound', CRM_Elections_Helper_Audit::verify()['ok'] === TRUE);
check('the voting election is in its voting stage', findElectionById($voting)->isVotingStarted && !findElectionById($voting)->isVotingEnded);

// --- the old elections still run, with the new rules
$members = $snapshot['people'];
$ballotFor = function ($voter, $electionData) use ($members) {
  $n = $electionData['nominations'];
  return [['election_nomination_id' => $n['President']['U01'], 'rank' => 1], ['election_nomination_id' => $n['President']['U02'], 'rank' => 2], ['election_nomination_id' => $n['Council']['U01'], 'rank' => 1]];
};
try {
  civicrm_api3('ElectionVote', 'addvotes', ['election_id' => $voting, 'member_id' => $members['U10'], 'votes' => $ballotFor('U10', $snapshot['elections']['voting'])]);
  check('a new member can vote in the old election', TRUE);
  civicrm_api3('ElectionVote', 'addvotes', ['election_id' => $voting, 'member_id' => $members['U10'], 'votes' => $ballotFor('U10', $snapshot['elections']['voting'])]);
  $rows = (int) q('SELECT COUNT(*) FROM civicrm_election_vote v INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id INNER JOIN civicrm_election_position p ON p.id = n.election_position_id WHERE p.election_id = %1 AND v.member_id = %2', [1 => [$voting, 'Integer'], 2 => [$members['U10'], 'Integer']]);
  check('and change that vote, still one ballot', $rows === 3, "rows: $rows");
}
catch (\Throwable $e) {
  check('a new member can vote in the old election', FALSE, $e->getMessage());
}
$nominationsElection = $snapshot['elections']['nominations'];
try {
  $second = CRM_Elections_Helper_Process::nominate($nominationsElection['id'], $nominationsElection['positions']['President'], $members['U03'], $members['U06'], 'Second nominator after the upgrade');
  check('the election taking nominations accepts the second nominator, who makes the nominee eligible', $second['is_eligible_candidate'] === TRUE);
}
catch (\Throwable $e) {
  check('the election taking nominations accepts the second nominator', FALSE, $e->getMessage());
}
// Voting closes and the results are generated by the scheduled job's own code.
CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET voting_end_date = DATE_SUB(NOW(), INTERVAL 2 DAY), result_date = DATE_SUB(NOW(), INTERVAL 1 DAY) WHERE id = %1', [1 => [$voting, 'Integer']]);
try {
  (new CRM_Elections_Helper_Results())->generateResult($voting, 0);
  check('results are generated for the old voting election', (int) q('SELECT has_results_generated FROM civicrm_election WHERE id = %1', [1 => [$voting, 'Integer']]) === 1);
  check('and have an integrity record', CRM_Elections_Helper_Integrity::verify($voting)['status'] === 'ok');
}
catch (\Throwable $e) {
  check('results are generated for the old voting election', FALSE, $e->getMessage());
}

// --- the upgrade can be run again
$upgrader = new CRM_Elections_Upgrader();
$upgrader->init(['key' => 'au.com.agileware.elections']);
$again = TRUE;
foreach ([20000, 20010, 20020, 20030, 20040, 20050, 20060, 20070, 20080, 20090, 20100, 20110] as $step) {
  $again = $again && $upgrader->{'upgrade_' . $step}();
}
check('every upgrade step can be run again', $again === TRUE);

$failed = check('__failed');
echo $failed ? "$failed check(s) FAILED\n" : "all checks passed\n";
exit($failed ? 1 : 0);
