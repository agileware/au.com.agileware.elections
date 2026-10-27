<?php
/**
 * Seeds three elections with version 1.3.2 of the extension installed: one finished (results generated), one with
 * voting open and some ballots cast, and one in its nomination period. Run with `cv scr` (see upgrade-from-v1.sh).
 * Prints a SNAPSHOT_JSON line: what the upgraded site is checked against.
 */
// `cv scr` does not set __DIR__ to this file's folder, so the folder is passed in.

$groupId = (int) civicrm_api3('Group', 'create', ['title' => 'Upgrade Members ' . uniqid(), 'name' => 'upgrade_members_' . uniqid()])['id'];
$people = [];
for ($i = 1; $i <= 12; $i++) {
  $people['U' . sprintf('%02d', $i)] = (int) civicrm_api3('Contact', 'create', [
    'contact_type' => 'Individual', 'first_name' => 'Upgrade', 'last_name' => 'Member' . sprintf('%02d', $i),
    'email' => 'upgrade' . sprintf('%02d', $i) . '.' . uniqid() . '@example.org',
  ])['id'];
  civicrm_api3('GroupContact', 'create', ['group_id' => $groupId, 'contact_id' => $people['U' . sprintf('%02d', $i)]]);
}

function dates($offsets) {
  $keys = ['visibility_start_date', 'nomination_start_date', 'nomination_end_date', 'advertise_candidates_date', 'voting_start_date', 'voting_end_date', 'result_date', 'visibility_end_date'];
  $dates = [];
  foreach ($keys as $i => $key) {
    $dates[$key] = date('Y-m-d H:i:s', strtotime(($offsets[$i] >= 0 ? '+' : '') . $offsets[$i] . ' days'));
  }
  return $dates;
}
function stageOffsets($stage) {
  return [
    'nominations' => [-10, -2, 5, 6, 10, 20, 25, 60],
    'voting' => [-30, -20, -13, -12, -1, 9, 12, 60],
    'results' => [-40, -30, -23, -22, -10, -2, -1, 60],
  ][$stage];
}

function makeElection($name, $groupId, $anonymize) {
  $election = civicrm_api3('Election', 'create', [
    'name' => $name, 'description' => 'Seeded with version 1.3.2', 'allowed_groups' => $groupId,
    'required_nominations' => 2, 'allow_revote' => 1, 'anonymize_votes' => $anonymize, 'allow_checksum_access' => 1,
    'is_visible' => 0, 'is_deleted' => 0,
  ] + dates(stageOffsets('nominations')));
  $id = (int) $election['id'];
  $positions = [];
  foreach ([['President', 1], ['Council', 2]] as $order => [$position, $seats]) {
    $positions[$position] = (int) civicrm_api3('ElectionPosition', 'create', ['election_id' => $id, 'name' => $position, 'quantity' => $seats, 'sortorder' => $order + 1])['id'];
  }
  CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET is_visible = 1 WHERE id = %1', [1 => [$id, 'Integer']]);
  return [$id, $positions];
}

function nominate($positionId, $nominee, array $nominators, $accept) {
  $nomination = (int) civicrm_api3('ElectionNomination', 'create', ['election_position_id' => $positionId, 'member_nominee' => $nominee])['id'];
  foreach ($nominators as $nominator) {
    civicrm_api3('ElectionNominationSeconder', 'create', ['election_nomination_id' => $nomination, 'member_nominator' => $nominator, 'description' => 'Seeded']);
  }
  if (count($nominators) >= 2) {
    civicrm_api3('ElectionNomination', 'create', ['id' => $nomination, 'is_eligible_candidate' => 1]);
    if ($accept) {
      civicrm_api3('ElectionNomination', 'create', ['id' => $nomination, 'has_accepted_nomination' => 1, 'comments' => 'A statement written in version 1.3.2']);
    }
  }
  return $nomination;
}

function moveTo($electionId, $stage) {
  $sets = []; $params = [1 => [$electionId, 'Integer']]; $i = 2;
  foreach (dates(stageOffsets($stage)) as $key => $value) {
    $sets[] = "$key = %$i"; $params[$i++] = [$value, 'String'];
  }
  CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET ' . implode(', ', $sets) . ' WHERE id = %1', $params);
}

function candidates($positions, $people) {
  return [
    'President' => [$positions['President'], ['U01', 'U02']],
    'Council' => [$positions['Council'], ['U01', 'U02', 'U03']],
  ];
}

$snapshot = ['people' => $people, 'group_id' => $groupId, 'elections' => []];
$voterNames = ['U04', 'U05', 'U06', 'U07', 'U08', 'U09', 'U10', 'U11', 'U12'];

foreach (['finished' => ['Upgrade finished election', 0, 'results'], 'voting' => ['Upgrade voting election', 0, 'voting']] as $key => [$name, $anonymize, $stage]) {
  [$id, $positions] = makeElection($name, $groupId, $anonymize);
  $nominations = [];
  foreach (candidates($positions, $people) as $position => [$positionId, $nominees]) {
    foreach ($nominees as $k => $nominee) {
      $nominations[$position][$nominee] = nominate($positionId, $people[$nominee], [$people[$voterNames[$k]], $people[$voterNames[$k + 3]]], TRUE);
    }
  }
  moveTo($id, 'voting');
  $voters = $key === 'finished' ? $voterNames : array_slice($voterNames, 0, 4);
  foreach ($voters as $n => $voter) {
    $votes = [
      ['election_nomination_id' => $nominations['President'][$n % 2 ? 'U01' : 'U02'], 'rank' => 1],
      ['election_nomination_id' => $nominations['President'][$n % 2 ? 'U02' : 'U01'], 'rank' => 2],
      ['election_nomination_id' => $nominations['Council'][['U01', 'U02', 'U03'][$n % 3]], 'rank' => 1],
      ['election_nomination_id' => $nominations['Council'][['U02', 'U03', 'U01'][$n % 3]], 'rank' => 2],
      ['election_nomination_id' => $nominations['Council'][['U03', 'U01', 'U02'][$n % 3]], 'rank' => 3],
    ];
    civicrm_api3('ElectionVote', 'addvotes', ['election_id' => $id, 'member_id' => $people[$voter], 'votes' => $votes]);
  }
  if ($key === 'finished') {
    moveTo($id, 'results');
    (new CRM_Elections_Helper_Results())->generateResult($id, 0);
  }
  $snapshot['elections'][$key] = ['id' => $id, 'positions' => $positions, 'nominations' => $nominations];
}

// The third election is still taking nominations: one person has been nominated once of the two needed.
[$id, $positions] = makeElection('Upgrade nominations election', $groupId, 1);
nominate($positions['President'], $people['U03'], [$people['U05']], FALSE);
$snapshot['elections']['nominations'] = ['id' => $id, 'positions' => $positions];

// What the upgrade must leave exactly as it was.
$snapshot['counts'] = [
  'elections' => (int) CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM civicrm_election WHERE name LIKE 'Upgrade %'"),
  'votes' => (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_vote v INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id INNER JOIN civicrm_election_position p ON p.id = n.election_position_id INNER JOIN civicrm_election e ON e.id = p.election_id WHERE e.name LIKE "Upgrade %"'),
  'nominations' => (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_nomination n INNER JOIN civicrm_election_position p ON p.id = n.election_position_id INNER JOIN civicrm_election e ON e.id = p.election_id WHERE e.name LIKE "Upgrade %"'),
  'seconders' => (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_nomination_seconder s INNER JOIN civicrm_election_nomination n ON n.id = s.election_nomination_id INNER JOIN civicrm_election_position p ON p.id = n.election_position_id INNER JOIN civicrm_election e ON e.id = p.election_id WHERE e.name LIKE "Upgrade %"'),
  'results' => (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_result r INNER JOIN civicrm_election_position p ON p.id = r.election_position_id INNER JOIN civicrm_election e ON e.id = p.election_id WHERE e.name LIKE "Upgrade %"'),
];
$snapshot['result_rows'] = CRM_Core_DAO::executeQuery('SELECT r.election_nomination_id, r.`rank` FROM civicrm_election_result r INNER JOIN civicrm_election_position p ON p.id = r.election_position_id WHERE p.election_id = %1 ORDER BY r.election_nomination_id', [1 => [$snapshot['elections']['finished']['id'], 'Integer']])->fetchAll();
$snapshot['vote_rows'] = CRM_Core_DAO::executeQuery('SELECT v.election_nomination_id, v.`rank`, v.member_id FROM civicrm_election_vote v INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id INNER JOIN civicrm_election_position p ON p.id = n.election_position_id WHERE p.election_id IN (%1, %2) ORDER BY v.election_nomination_id, v.member_id', [1 => [$snapshot['elections']['finished']['id'], 'Integer'], 2 => [$snapshot['elections']['voting']['id'], 'Integer']])->fetchAll();
// The snapshot goes to the script that started this one (it cannot be sure where files are written).
echo 'SNAPSHOT_JSON:' . json_encode($snapshot) . "\n";
echo "seeded " . json_encode($snapshot['counts']) . "\n";
