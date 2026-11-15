<?php
/**
 * Moves an election to a stage by changing its dates, as the passing of time would.
 *
 *   ELECTION=<election id or part of its name> STAGE=<nominations|advertise|voting|results> cv scr tests/seed/move-election.php
 *
 * The election and the stage can also be passed as the first and second script arguments.
 * Works on any CMS: it changes only the election's dates.
 */
// `cv scr` may not set __DIR__ to this file's folder, so find the folder from __DIR__, the script path or ELECTIONS_SEED_DIR.
$seedDir = is_file(__DIR__ . '/common.php') ? __DIR__ : (isset($argv[0]) && is_file(dirname($argv[0]) . '/common.php') ? dirname($argv[0]) : (string) getenv('ELECTIONS_SEED_DIR'));
if (!is_file($seedDir . '/common.php')) {
  fwrite(STDERR, "Cannot find the seed scripts' folder: set ELECTIONS_SEED_DIR.\n");
  exit(1);
}
require_once $seedDir . '/common.php';
require_once $seedDir . '/stages.php';

$which = getenv('ELECTION') ?: ($argv[1] ?? '');
$stage = getenv('STAGE') ?: ($argv[2] ?? '');
if (!$which || !isset(STAGE_DAYS[$stage])) {
  fwrite(STDERR, "Usage: ELECTION=<election id or part of its name> STAGE=<nominations|advertise|voting|results> cv scr move-election.php\n");
  exit(1);
}
$rows = ctype_digit($which)
  ? CRM_Core_DAO::executeQuery('SELECT id, name FROM civicrm_election WHERE id = %1', [1 => [(int) $which, 'Integer']])
  : CRM_Core_DAO::executeQuery('SELECT id, name FROM civicrm_election WHERE name LIKE %1', [1 => ['%' . $which . '%', 'String']]);
$found = [];
while ($rows->fetch()) {
  $found[(int) $rows->id] = $rows->name;
}
if (count($found) !== 1) {
  fwrite(STDERR, count($found) ? "More than one election matches:\n" . implode("\n", array_map(fn($id, $name) => "  [$id] $name", array_keys($found), $found)) . "\n" : "No election matches '$which'.\n");
  exit(1);
}
$id = array_key_first($found);
move_to_stage($id, $stage);
echo "[$id] {$found[$id]} is now at the $stage stage.\n";
if ($stage === 'results') {
  echo "Run the scheduled jobs to generate the results, as cron would: cv api3 Job.execute auth=0\n";
}
