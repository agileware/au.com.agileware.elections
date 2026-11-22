<?php
/**
 * Seeds the invented "Beekeepers Association of Australia": the organisation, 30 Beekeeper contacts with
 * groups, and one election of each kind, each left at a stage that suits reviewing it. It uses CiviCRM only,
 * so it runs the same on WordPress, Drupal and Joomla:
 *
 *   cv scr tests/seed/seed.php
 *
 * (on the Joomla dev site the command is `jcv`). WordPress logins for some Beekeepers are made by
 * wordpress-logins.php, and personal links by links.php.
 *
 * Safe to re-run: it removes what an earlier run made first (the elections it names, the Beekeeper
 * contacts and the three groups). People and elections are all invented; every e-mail address is on the
 * seed domain (beekeepers.example.org by default) and so goes nowhere.
 *
 * Environment (all optional):
 *   ELECTIONS_SEED_DOMAIN       The e-mail domain of the Beekeepers and of the organisation. Default beekeepers.example.org.
 *   ELECTIONS_SEED_ELECTIONS    Which elections to build: "all" (default) or a comma separated list of numbers 1 to 8
 *                               or keys (see ELECTION_KINDS below), e.g. "2,5" or "referendum,results".
 *                               Every election this script names is removed first, whichever are built.
 *   ELECTIONS_SEED_STATE_FILE   Where to record the contact and election ids (for links.php and wordpress-logins.php).
 *                               Default: .generated/seeded.json in this folder (git-ignored).
 */

use CRM_Elections_Helper_Process as Process;

// `cv scr` may not set __DIR__ to this file's folder, so find the folder from __DIR__, the script path or ELECTIONS_SEED_DIR.
$seedDir = is_file(__DIR__ . '/common.php') ? __DIR__ : (isset($argv[0]) && is_file(dirname($argv[0]) . '/common.php') ? dirname($argv[0]) : (string) getenv('ELECTIONS_SEED_DIR'));
if (!is_file($seedDir . '/common.php')) {
  fwrite(STDERR, "Cannot find the seed scripts' folder: set ELECTIONS_SEED_DIR.\n");
  exit(1);
}
require_once $seedDir . '/common.php';
require_once $seedDir . '/stages.php';

define('BEE_DOMAIN', seed_env('DOMAIN', 'beekeepers.example.org'));

// ------------------------------------------------------------------------------------------------
// Elections made by this script (also used to clear an earlier run), in number order.
const ELECTION_NAMES = [
  'Beekeeper Committee 2027 Election',
  'Honey Show Stewards 2027 (Most Votes Win)',
  'Life Membership Award Panel 2027 (Candidates Chosen by the Organisers)',
  'Regional Delegates 2027 (Write-ins Allowed)',
  'Constitution Amendments 2027 (Referendum)',
  'Annual General Meeting 2027 (Positions and Questions)',
  'Research Grants Committee 2027 (Separate Nominating and Candidate Groups)',
  'Beekeeper Committee 2026 (Results)',
];

/**
 * The kinds, by number: key => what it shows and the stage it is left at.
 */
const ELECTION_KINDS = [
  1 => 'committee',       // Ranked choice, people nominate and second. Nominations stage.
  2 => 'honey-show',      // Most votes win. Voting stage.
  3 => 'life-award',      // Candidates chosen by the organisers, no nomination form. Candidates announced.
  4 => 'write-ins',       // Voters can write in a person. Voting stage.
  5 => 'referendum',      // Yes or no questions only. Voting stage.
  6 => 'agm',             // A position and a question on one ballot. Voting stage.
  7 => 'research-grants', // Separate nominating and candidate groups. Nominations stage.
  8 => 'results',         // A finished election with results. Results stage.
];

// Which elections to build.
$wanted = [];
foreach (array_filter(array_map('trim', explode(',', strtolower((string) seed_env('ELECTIONS', 'all'))))) as $item) {
  if ($item === 'all') {
    $wanted = array_keys(ELECTION_KINDS);
  }
  elseif (ctype_digit($item) && isset(ELECTION_KINDS[(int) $item])) {
    $wanted[] = (int) $item;
  }
  elseif (($number = array_search($item, ELECTION_KINDS, TRUE)) !== FALSE) {
    $wanted[] = $number;
  }
  else {
    fwrite(STDERR, "Unknown election '$item'. Use all, 1 to 8, or one of: " . implode(', ', ELECTION_KINDS) . ".\n");
    exit(1);
  }
}
$wanted = array_values(array_unique($wanted));
sort($wanted);

// ------------------------------------------------------------------------------------------------
// Clear an earlier run.
foreach (ELECTION_NAMES as $name) {
  CRM_Core_DAO::executeQuery('DELETE FROM civicrm_election WHERE name = %1', [1 => [$name, 'String']]);
}
if (!CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election')) {
  // Nothing else is left, so the elections are numbered from 1 again.
  CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_election AUTO_INCREMENT = 1');
}
// Only the Beekeepers: the administrator's and the organisation's own contacts share the email domain.
if (civicrm_api3('ContactType', 'getcount', ['name' => 'Beekeeper'])) {
  foreach (civicrm_api3('Contact', 'get', ['contact_sub_type' => 'Beekeeper', 'return' => ['id'], 'options' => ['limit' => 0]])['values'] as $contact) {
    // A contact linked to a website login cannot be deleted. The link is removed here; the login itself is a
    // CMS matter (wordpress-logins.php replaces the WordPress ones it made).
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_uf_match WHERE contact_id = %1', [1 => [(int) $contact['id'], 'Integer']]);
    civicrm_api3('Contact', 'delete', ['id' => $contact['id'], 'skip_undelete' => 1]);
  }
}
foreach (['beekeepers', 'executive_committee', 'committee_candidates'] as $groupName) {
  foreach (civicrm_api3('Group', 'get', ['name' => $groupName])['values'] as $group) {
    civicrm_api3('Group', 'delete', ['id' => $group['id']]);
  }
}

// ------------------------------------------------------------------------------------------------
// The organisation.
$domain = civicrm_api3('Domain', 'getsingle', ['id' => 1]);
civicrm_api3('Contact', 'create', ['id' => $domain['contact_id'], 'contact_type' => 'Organization', 'organization_name' => 'Beekeepers Association of Australia']);
civicrm_api3('Domain', 'create', ['id' => 1, 'name' => 'Beekeepers Association of Australia', 'description' => 'The national association of Australian beekeepers (a test site: all people and elections are invented).']);
$fromEmail = '"Beekeepers Association of Australia" <elections@' . BEE_DOMAIN . '>';
\Civi\Api4\OptionValue::update(FALSE)
  ->addWhere('option_group_id:name', '=', 'from_email_address')
  ->addWhere('is_default', '=', TRUE)
  ->addValue('label', $fromEmail)
  ->addValue('name', $fromEmail)
  ->execute();
civicrm_api3('Email', 'create', ['contact_id' => $domain['contact_id'], 'email' => 'info@' . BEE_DOMAIN, 'location_type_id' => 'Work', 'is_primary' => 1]);

// ------------------------------------------------------------------------------------------------
// The Beekeeper contact type and 30 Beekeepers.
if (!civicrm_api3('ContactType', 'getcount', ['name' => 'Beekeeper'])) {
  civicrm_api3('ContactType', 'create', ['name' => 'Beekeeper', 'label' => 'Beekeeper', 'parent_id' => 'Individual', 'is_active' => 1]);
}

// [first, last, nickname, street, city, state, postcode]
$people = [
  1 => ['Eleanor', 'Whitlock', 'Ellie', '14 Orchard Lane', 'Orange', 'NSW', '2800'],
  2 => ['Marcus', 'Delaney', '', '7 Wattle Street', 'Bendigo', 'VIC', '3550'],
  3 => ['Priya', 'Raman', '', '22 Station Road', 'Parramatta', 'NSW', '2150'],
  4 => ['Tom', 'Harrington', 'Tommo', '3 Creek Road', 'Toowoomba', 'QLD', '4350'],
  5 => ['Julia', 'Kowalski', '', '41 Hills Highway', 'Mount Barker', 'SA', '5251'],
  6 => ['Wei', 'Zhang', '', '9 Tamar Valley Drive', 'Launceston', 'TAS', '7250'],
  7 => ['Hamish', 'McAllister', '', '120 Rusden Street', 'Armidale', 'NSW', '2350'],
  8 => ['Sophie', 'Nguyen', '', '5 Moorabool Street', 'Geelong', 'VIC', '3220'],
  9 => ['Declan', "O'Rourke", '', '18 Burnett Street', 'Bundaberg', 'QLD', '4670'],
  10 => ['Margaret', 'Doyle', 'Maggie', '2 Princess Royal Drive', 'Albany', 'WA', '6330'],
  11 => ['Ahmed', 'Al-Sayed', '', '66 Baylis Street', 'Wagga Wagga', 'NSW', '2650'],
  12 => ['Chloe', 'Ferguson', '', '31 Vincent Street', 'Daylesford', 'VIC', '3460'],
  13 => ['Bruce', 'Tindall', 'Bluey', '8 Mary Valley Road', 'Gympie', 'QLD', '4570'],
  14 => ['Aroha', 'Williams', '', '55 Elizabeth Street', 'Hobart', 'TAS', '7000'],
  15 => ['Samuel', 'Petrov', '', '12 Deakin Avenue', 'Mildura', 'VIC', '3500'],
  16 => ['Fiona', 'Lachlan', '', '27 Victoria Street', 'Bunbury', 'WA', '6230'],
  17 => ['Jack', 'Mulvaney', '', '90 Macquarie Street', 'Dubbo', 'NSW', '2830'],
  18 => ['Leila', 'Haddad', '', '4 Rundle Mall', 'Adelaide', 'SA', '5000'],
  19 => ['Ravi', 'Patel', '', '17 Abbott Street', 'Cairns', 'QLD', '4870'],
  20 => ['Georgia', 'Hutchins', '', '6 Dauncey Street', 'Kingscote', 'SA', '5223'],
  21 => ['Owen', 'Blackwood', '', '33 Northbourne Avenue', 'Canberra', 'ACT', '2600'],
  22 => ['Beatrice', 'Fontaine', '', '15 George Street', 'Bathurst', 'NSW', '2795'],
  23 => ['Lachlan', 'Burke', '', '48 Sturt Street', 'Ballarat', 'VIC', '3350'],
  24 => ['Mei Lin', 'Tan', '', '101 Hay Street', 'Perth', 'WA', '6000'],
  25 => ['Dylan', 'Cooper', 'Dill', '20 Smith Street', 'Darwin', 'NT', '0800'],
  26 => ['Annika', 'Sorensen', '', '11 Katherine Terrace', 'Katherine', 'NT', '0850'],
  27 => ['Patrick', 'Gallagher', '', '73 Peel Street', 'Tamworth', 'NSW', '2340'],
  28 => ['Zara', 'Mitchell', '', '29 Victoria Street', 'Mackay', 'QLD', '4740'],
  29 => ['Henry', 'Ashworth', '', '5 Main Street', 'Bairnsdale', 'VIC', '3875'],
  30 => ['Noelene', 'Fraser', '', '8 Federal Street', 'Narrogin', 'WA', '6312'],
];

$beekeepers = civicrm_api3('Group', 'create', ['name' => 'beekeepers', 'title' => 'Beekeepers', 'description' => 'People in the Association who may vote in its elections.', 'group_type' => ['Mailing List']])['id'];
$executive = civicrm_api3('Group', 'create', ['name' => 'executive_committee', 'title' => 'Executive Committee', 'description' => 'The current executive: may nominate people for the Research Grants Committee.', 'group_type' => ['Mailing List']])['id'];
$candidatesGroup = civicrm_api3('Group', 'create', ['name' => 'committee_candidates', 'title' => 'Committee Candidates', 'description' => 'People who have agreed to be considered for committee positions.', 'group_type' => ['Mailing List']])['id'];

$B = [];
foreach ($people as $n => [$first, $last, $nick, $street, $city, $state, $postcode]) {
  $email = strtolower(preg_replace('/[^a-z.]/i', '', str_replace(' ', '', $first) . '.' . $last)) . '@' . BEE_DOMAIN;
  $cid = (int) civicrm_api3('Contact', 'create', [
    'contact_type' => 'Individual',
    'contact_sub_type' => ['Beekeeper'],
    'first_name' => $first,
    'last_name' => $last,
    'nick_name' => $nick,
    'email' => $email,
    'api.Phone.create' => ['phone' => sprintf('04%02d %03d %03d', 10 + $n, 200 + $n * 7, 300 + $n * 11), 'phone_type_id' => 'Mobile', 'location_type_id' => 'Home'],
  ])['id'];
  \Civi\Api4\Address::create(FALSE)
    ->addValue('contact_id', $cid)
    ->addValue('location_type_id:name', 'Home')
    ->addValue('is_primary', TRUE)
    ->addValue('street_address', $street)
    ->addValue('city', $city)
    ->addValue('postal_code', $postcode)
    ->addValue('state_province_id:abbr', $state)
    ->addValue('country_id:abbr', 'AU')
    ->execute();
  $B[$n] = $cid;
  civicrm_api3('GroupContact', 'create', ['group_id' => $beekeepers, 'contact_id' => $cid]);
}
foreach ([1, 2, 10, 21, 25] as $n) {
  civicrm_api3('GroupContact', 'create', ['group_id' => $executive, 'contact_id' => $B[$n]]);
}
foreach ([1, 3, 5, 8, 10, 11, 14, 18, 21, 22, 23, 24, 26, 27] as $n) {
  civicrm_api3('GroupContact', 'create', ['group_id' => $candidatesGroup, 'contact_id' => $B[$n]]);
}

// ------------------------------------------------------------------------------------------------
// Elections.

/**
 * Create an election with positions, at the nominations stage (visible, so people can see it).
 *
 * @return array [election id, position name => id]
 */
function make_election(array $params, array $positions): array {
  $election = civicrm_api3('Election', 'create', $params + [
    'allow_checksum_access' => 1,
    'is_visible' => 0,
    'is_deleted' => 0,
  ] + stage_dates('nominations'));
  $electionId = (int) $election['id'];
  $ids = [];
  foreach (array_values($positions) as $order => $position) {
    $position += ['quantity' => 1, 'sortorder' => $order + 1];
    $name = $position['name'];
    $options = $position['options'] ?? NULL;
    unset($position['options']);
    $ids[$name] = (int) civicrm_api3('ElectionPosition', 'create', $position + ['election_id' => $electionId])['id'];
    if ($options) {
      CRM_Elections_BAO_ElectionPosition::saveOptions($ids[$name], $options);
    }
  }
  // Positions can only be added to an election that people cannot see yet.
  CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET is_visible = 1 WHERE id = %1', [1 => [$electionId, 'Integer']]);
  return [$electionId, $ids];
}

/** People nominate: [position name, nominee number, [nominator numbers]]. */
function nominate(int $electionId, array $positions, array $B, array $plan): array {
  $nominations = [];
  foreach ($plan as [$position, $nominee, $nominators]) {
    foreach ($nominators as $nominator) {
      $result = Process::nominate($electionId, $positions[$position], $B[$nominee], $B[$nominator], "Nominated by Beekeeper $nominator for $position");
      $nominations["$position|$nominee"] = (int) $result['nomination_id'];
    }
  }
  return $nominations;
}

/** Nominees respond: ["position|nominee" => 'accept'|'withdraw']. */
function respond(array $B, array $nominations, array $responses): void {
  foreach ($responses as $key => $what) {
    [$position, $nominee] = explode('|', $key);
    if ($what === 'accept') {
      Process::accept($nominations[$key], $B[$nominee], "I would be glad to serve the Association as $position and bring my years of beekeeping to the work.", 'https://example.org/beekeepers/' . $nominee);
    }
    else {
      Process::withdraw($nominations[$key], $B[$nominee], 'Not this year.');
    }
  }
}

/** Candidates added by the organisers: [position name => [nominee numbers]]; returns "position|nominee" => nomination id. */
function add_candidates(array $positions, array $B, array $plan, bool $accepted = FALSE): array {
  $nominations = [];
  foreach ($plan as $position => $nominees) {
    foreach ($nominees as $nominee) {
      $nominations["$position|$nominee"] = (int) CRM_Elections_BAO_ElectionNomination::addCandidate($positions[$position], $B[$nominee], $accepted)['id'];
    }
  }
  return $nominations;
}

function cast(int $electionId, int $voterId, array $votes = [], array $writeIns = [], array $optionVotes = []): void {
  $params = ['election_id' => $electionId, 'member_id' => $voterId];
  if ($votes) {
    $params['votes'] = array_map(fn($nominationId, $rank) => ['election_nomination_id' => $nominationId, 'rank' => $rank], array_keys($votes), array_values($votes));
  }
  if ($writeIns) {
    $params['write_ins'] = $writeIns;
  }
  if ($optionVotes) {
    $params['option_votes'] = $optionVotes;
  }
  civicrm_api3('ElectionVote', 'addvotes', $params);
}

function answer(int $positionId, string $kind): array {
  foreach (CRM_Elections_BAO_ElectionPosition::getOptions($positionId) as $option) {
    if ($option['kind'] === $kind) {
      return ['position_id' => $positionId, 'option_id' => $option['id']];
    }
  }
  throw new RuntimeException("No $kind option on position $positionId");
}

$all = range(1, 30);
$made = [];
$groups = ['beekeepers' => $beekeepers, 'executive' => $executive, 'candidates' => $candidatesGroup];

// One function for each kind of election. Each returns the id of the election it made.
$builders = [];

// 1. The standard committee election: ranked choice, people nominate and second. At the nominations
//    stage, with some nominations made and some still needing a second.
$builders[1] = function () use ($B, $groups): int {
  [$id, $pos] = make_election([
    'name' => ELECTION_NAMES[0],
    'description' => 'Election of the Association committee for 2027: President, Secretary, Treasurer and three Committee Members. People in the Association nominate and second; the people nominated then accept before the candidates are announced. Votes are counted by ranked choice.',
    'allowed_groups' => $groups['beekeepers'],
    'required_nominations' => 2,
    'voting_method' => 'irv',
    'allow_revote' => 0,
    'anonymize_votes' => 1,
    'send_emails' => 1,
    'show_statements' => 'link',
  ], array_map(fn($name) => ['name' => $name], ['President', 'Secretary', 'Treasurer', 'Committee Member 1', 'Committee Member 2', 'Committee Member 3']));
  nominate($id, $pos, $B, [
    ['President', 1, [2, 3]],
    ['President', 10, [4]],
    ['Secretary', 5, [6, 7]],
    ['Treasurer', 8, [9]],
    ['Committee Member 1', 12, [13, 14]],
  ]);
  move_to_stage($id, 'nominations');
  return $id;
};

// 2. Most votes win: the voter ticks up to as many candidates as there are seats. At the voting stage.
$builders[2] = function () use ($B, $groups): int {
  [$id, $pos] = make_election([
    'name' => ELECTION_NAMES[1],
    'description' => 'Choose the stewards for the 2027 Honey Show. Tick up to as many candidates as there are places; the candidates with the most ticks win.',
    'allowed_groups' => $groups['beekeepers'],
    'required_nominations' => 2,
    'voting_method' => 'plurality',
    'allow_revote' => 1,
    'anonymize_votes' => 0,
    'send_emails' => 1,
    'show_statements' => 'link',
  ], [['name' => 'Head Steward'], ['name' => 'Honey Show Steward', 'quantity' => 3]]);
  $nominations = nominate($id, $pos, $B, [
    ['Head Steward', 11, [1, 2]], ['Head Steward', 12, [3, 4]], ['Head Steward', 13, [5, 6]],
    ['Honey Show Steward', 14, [7, 8]], ['Honey Show Steward', 15, [9, 10]], ['Honey Show Steward', 16, [11, 12]],
    ['Honey Show Steward', 17, [13, 14]], ['Honey Show Steward', 18, [15, 16]],
  ]);
  move_to_stage($id, 'advertise');
  respond($B, $nominations, array_fill_keys(array_keys($nominations), 'accept'));
  move_to_stage($id, 'voting');
  foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12] as $k => $voter) {
    cast($id, $B[$voter], [
      $nominations['Head Steward|' . [11, 12, 13][$k % 3]] => 1,
      $nominations['Honey Show Steward|' . [14, 15, 16, 17, 18][$k % 5]] => 1,
      $nominations['Honey Show Steward|' . [14, 15, 16, 17, 18][($k + 2) % 5]] => 1,
    ]);
  }
  move_to_stage($id, 'voting');
  return $id;
};

// 3. Candidates chosen by the organisers: no nomination form. At the stage where candidates are announced
//    and asked to accept (one has, two have not).
$builders[3] = function () use ($B, $groups): int {
  [$id, $pos] = make_election([
    'name' => ELECTION_NAMES[2],
    'description' => 'The panel that awards Life Membership. The organisers choose the candidates; there is no online nominating. Each person chosen is asked to accept.',
    'allowed_groups' => $groups['beekeepers'],
    'allow_online_nominations' => 0,
    'voting_method' => 'irv',
    'allow_revote' => 0,
    'anonymize_votes' => 1,
    'send_emails' => 1,
    'show_statements' => 'link',
  ], [['name' => 'Award Panel Member', 'quantity' => 3]]);
  $nominations = add_candidates($pos, $B, ['Award Panel Member' => [21, 22, 23, 24]]);
  move_to_stage($id, 'advertise');
  respond($B, $nominations, ['Award Panel Member|21' => 'accept']);
  move_to_stage($id, 'advertise');
  return $id;
};

// 4. Write-ins: a voter can name a person who is not on the ballot. At the voting stage with two write-ins made.
$builders[4] = function () use ($B, $groups): int {
  [$id, $pos] = make_election([
    'name' => ELECTION_NAMES[3],
    'description' => 'State delegates to the national meeting. Voters can write in a person who is not on the ballot; the person must accept before voting closes. Voters may change their vote until voting closes.',
    'allowed_groups' => $groups['beekeepers'],
    'candidate_groups' => $groups['candidates'],
    'required_nominations' => 2,
    'voting_method' => 'irv',
    'allow_revote' => 1,
    'anonymize_votes' => 0,
    'send_emails' => 1,
    'show_statements' => 'link',
  ], [['name' => 'State Delegate', 'quantity' => 2, 'write_ins_per_seat' => 2], ['name' => 'Apiary Registrar', 'write_ins_per_seat' => 1]]);
  $nominations = nominate($id, $pos, $B, [
    ['State Delegate', 1, [2, 4]], ['State Delegate', 3, [5, 6]], ['State Delegate', 5, [7, 8]],
    ['Apiary Registrar', 8, [9, 10]], ['Apiary Registrar', 11, [12, 13]],
  ]);
  move_to_stage($id, 'advertise');
  respond($B, $nominations, array_fill_keys(array_keys($nominations), 'accept'));
  move_to_stage($id, 'voting');
  cast($id, $B[2], [$nominations['State Delegate|1'] => 1, $nominations['State Delegate|3'] => 2, $nominations['Apiary Registrar|8'] => 1]);
  cast($id, $B[4], [$nominations['State Delegate|5'] => 1, $nominations['Apiary Registrar|11'] => 1]);
  // Two voters write in people who are on the candidate list but not on the ballot.
  cast($id, $B[6], [$nominations['State Delegate|3'] => 1], [['position_id' => $pos['State Delegate'], 'contact_id' => $B[21], 'rank' => 2]]);
  cast($id, $B[9], [$nominations['Apiary Registrar|8'] => 1], [['position_id' => $pos['Apiary Registrar'], 'contact_id' => $B[22], 'rank' => 2]]);
  move_to_stage($id, 'voting');
  return $id;
};

// 5. Referendum questions only. At the voting stage with some answers in.
$builders[5] = function () use ($B, $groups): int {
  [$id, $pos] = make_election([
    'name' => ELECTION_NAMES[4],
    'description' => 'Proposed changes to the Association constitution and fees. Each question is a yes or no answer; a question passes by its own threshold and quorum.',
    'allowed_groups' => $groups['beekeepers'],
    'allow_revote' => 1,
    'anonymize_votes' => 0,
    'send_emails' => 0,
  ], [
    ['name' => 'Raise the annual membership fee from $50 to $60?', 'position_type' => 'referendum', 'pass_threshold' => 50,
      'resolution_text' => "That the annual membership fee be raised from \$50 to \$60 from 1 July 2027, to cover the cost of the national disease surveillance programme.",
      'options' => ['yes' => 'For', 'no' => 'Against', 'abstain' => 'Abstain']],
    ['name' => 'Allow members to vote by proxy at the Annual General Meeting?', 'position_type' => 'referendum', 'pass_threshold' => 66.67, 'quorum_percent' => 40,
      'resolution_text' => "That the constitution be changed so that a member who cannot attend a general meeting may appoint another member to vote for them. This is a change to the constitution and needs a two thirds majority and at least 40% of members taking part.",
      'options' => ['yes' => 'Yes', 'no' => 'No', 'abstain' => '']],
    ['name' => "Rename the Association 'Beekeepers Australia'?", 'position_type' => 'referendum', 'pass_threshold' => 50, 'quorum_percent' => 25,
      'resolution_text' => "That the name of the Association be changed from 'Beekeepers Association of Australia' to 'Beekeepers Australia'.",
      'options' => ['yes' => 'Yes', 'no' => 'No', 'abstain' => 'Abstain']],
  ]);
  move_to_stage($id, 'voting');
  $questions = array_values($pos);
  $pattern = [
    [['yes', 'yes', 'no'], 1], [['yes', 'no', 'yes'], 2], [['no', 'yes', 'abstain'], 3], [['yes', 'yes', 'yes'], 4], [['abstain', 'no', 'no'], 5],
    [['yes', 'no', 'no'], 6], [['no', 'yes', 'yes'], 7], [['yes', 'yes', 'abstain'], 8], [['no', 'no', 'yes'], 9], [['yes', 'yes', 'yes'], 10],
  ];
  foreach ($pattern as [$kinds, $voter]) {
    cast($id, $B[$voter], [], [], array_map(fn($q, $kind) => answer($q, $kind), $questions, $kinds));
  }
  move_to_stage($id, 'voting');
  return $id;
};

// 6. Positions and a question on the same ballot. At the voting stage.
$builders[6] = function () use ($B, $groups): int {
  [$id, $pos] = make_election([
    'name' => ELECTION_NAMES[5],
    'description' => 'The 2027 Annual General Meeting: appoint the auditor and adopt the 2026 financial statements. One ballot holds the position and the question.',
    'allowed_groups' => $groups['beekeepers'],
    'required_nominations' => 2,
    'voting_method' => 'irv',
    'allow_revote' => 1,
    'anonymize_votes' => 0,
    'send_emails' => 1,
    'show_statements' => 'link',
  ], [
    ['name' => 'Auditor'],
    ['name' => 'Adopt the financial statements for 2026?', 'position_type' => 'referendum', 'pass_threshold' => 50,
      'resolution_text' => 'That the audited financial statements of the Association for the year ended 30 June 2026 be adopted.'],
  ]);
  $nominations = nominate($id, $pos, $B, [['Auditor', 16, [1, 2]], ['Auditor', 18, [3, 4]]]);
  move_to_stage($id, 'advertise');
  respond($B, $nominations, array_fill_keys(array_keys($nominations), 'accept'));
  move_to_stage($id, 'voting');
  foreach ([1, 2, 3, 4, 5, 6] as $k => $voter) {
    cast($id, $B[$voter],
      [$nominations['Auditor|16'] => $k % 2 ? 2 : 1, $nominations['Auditor|18'] => $k % 2 ? 1 : 2], [],
      [answer($pos['Adopt the financial statements for 2026?'], $k === 3 ? 'no' : 'yes')]);
  }
  move_to_stage($id, 'voting');
  return $id;
};

// 7. Separate groups: only the Executive Committee nominates and only Committee Candidates may stand.
//    At the nominations stage.
$builders[7] = function () use ($B, $groups): int {
  [$id, $pos] = make_election([
    'name' => ELECTION_NAMES[6],
    'description' => 'The committee that decides research grants. Every Beekeeper votes, but only the Executive Committee may nominate and seconded people must be on the Committee Candidates list.',
    'allowed_groups' => $groups['beekeepers'],
    'nomination_groups' => $groups['executive'],
    'candidate_groups' => $groups['candidates'],
    'required_nominations' => 2,
    'voting_method' => 'irv',
    'allow_revote' => 0,
    'anonymize_votes' => 1,
    'send_emails' => 1,
    'show_statements' => 'link',
  ], [['name' => 'Research Grants Chair'], ['name' => 'Research Grants Member', 'quantity' => 2]]);
  nominate($id, $pos, $B, [['Research Grants Chair', 21, [1, 2]], ['Research Grants Member', 22, [10, 25]], ['Research Grants Member', 23, [1]]]);
  move_to_stage($id, 'nominations');
  return $id;
};

// 8. A finished election with results: last year's committee, ranked choice.
$builders[8] = function () use ($B, $groups, $all): int {
  [$id, $pos] = make_election([
    'name' => ELECTION_NAMES[7],
    'description' => 'The 2026 committee election, finished. The results are published.',
    'allowed_groups' => $groups['beekeepers'],
    'required_nominations' => 2,
    'voting_method' => 'irv',
    'allow_revote' => 0,
    'anonymize_votes' => 1,
    'send_emails' => 0,
    'show_statements' => 'link',
  ], array_map(fn($name) => ['name' => $name], ['President', 'Secretary', 'Treasurer', 'Committee Member 1', 'Committee Member 2', 'Committee Member 3']));
  $nominations = nominate($id, $pos, $B, [
    ['President', 21, [1, 2]], ['President', 22, [3, 4]],
    ['Secretary', 23, [5, 6]],
    ['Treasurer', 24, [7, 8]], ['Treasurer', 25, [9, 10]],
    ['Committee Member 1', 26, [11, 12]], ['Committee Member 1', 21, [13, 14]],
    ['Committee Member 2', 22, [15, 16]], ['Committee Member 2', 27, [17, 18]],
    ['Committee Member 3', 23, [19, 20]], ['Committee Member 3', 28, [21, 22]],
  ]);
  move_to_stage($id, 'advertise');
  respond($B, $nominations, array_fill_keys(array_keys($nominations), 'accept'));
  move_to_stage($id, 'voting');
  $candidates = [
    'President' => [21, 22], 'Secretary' => [23], 'Treasurer' => [24, 25],
    'Committee Member 1' => [26, 21], 'Committee Member 2' => [22, 27], 'Committee Member 3' => [23, 28],
  ];
  foreach ($all as $k => $voter) {
    if ($voter > 26) {
      // The last four people never vote.
      continue;
    }
    $ranks = [];
    foreach ($candidates as $position => $list) {
      // Clear winners everywhere except the Treasurer, which is level on purpose (to show how a tie reads).
      $first = match ($position) {
        'President' => $k < 15 ? 21 : 22,
        'Treasurer' => $k % 2 === 0 ? 24 : 25,
        'Committee Member 1' => $k % 4 === 3 ? 21 : 26,
        'Committee Member 2' => $k % 3 === 0 ? 27 : 22,
        'Committee Member 3' => $k % 5 < 2 ? 28 : 23,
        default => $list[0],
      };
      $ranks[$nominations["$position|$first"]] = 1;
      if ($k % 6 !== 0) {
        foreach (array_values(array_diff($list, [$first])) as $i => $other) {
          $ranks[$nominations["$position|$other"]] = $i + 2;
        }
      }
    }
    cast($id, $B[$voter], $ranks);
  }
  move_to_stage($id, 'results');
  (new CRM_Elections_Helper_Results())->generateResult($id, 1);
  return $id;
};

foreach ($wanted as $number) {
  $made[ELECTION_NAMES[$number - 1]] = $builders[$number]();
}

// ------------------------------------------------------------------------------------------------
// Record what was made, for links.php and wordpress-logins.php. It holds ids only: no passwords or links.
seed_write_private_file(seed_state_file(), json_encode([
  'beekeepers' => $B,
  'elections' => $made,
  'groups' => $groups,
  'email_domain' => BEE_DOMAIN,
], JSON_PRETTY_PRINT));
echo 'Seeded ' . count($B) . ' Beekeepers and ' . count($made) . " elections.\n";
foreach ($made as $name => $electionId) {
  echo "  [$electionId] $name\n";
}
