<?php
/**
 * Seeds the council election personas for the Playwright tests, at the nominations stage.
 *
 * Run with `cv scr` (see setup-environment.sh). Safe to re-run: it removes the data of
 * an earlier run first. Writes fixtures/.generated/council.json with a personal link
 * (cid + cs checksum) for every persona.
 */
require_once __DIR__ . '/../../phpunit/CRM/Elections/Test/CouncilFixture.php';

use CRM_Elections_Test_CouncilFixture as Council;

// Remove an earlier run.
CRM_Core_DAO::executeQuery("DELETE FROM civicrm_election WHERE name LIKE 'Playwright%'");
foreach (['Voter', 'Nominee', 'Outsider'] as $firstName) {
  foreach (civicrm_api3('Contact', 'get', ['first_name' => $firstName, 'email' => ['LIKE' => '%@example.org'], 'options' => ['limit' => 0], 'return' => ['id']])['values'] as $contact) {
    civicrm_api3('Contact', 'delete', ['id' => $contact['id'], 'skip_undelete' => 1]);
  }
}
foreach (civicrm_api3('Group', 'get', ['name' => ['LIKE' => 'council_members_%'], 'options' => ['limit' => 0]])['values'] as $group) {
  civicrm_api3('Group', 'delete', ['id' => $group['id']]);
}

$fixture = Council::create([
  'name' => 'Playwright Council Election',
  'allow_checksum_access' => 1,
  'allow_revote' => 1,
  'anonymize_votes' => 0,
  'show_statements' => 'link',
]);

// The logged in member (a WordPress user) takes part as well.
$users = json_decode(file_get_contents(__DIR__ . '/test-users.json'), TRUE);
// WordPress only: other CMSs do not have a logged in member user in the seed.
$wpUser = function_exists('get_user_by') ? get_user_by('login', $users['member']['username']) : NULL;
if ($wpUser) {
  CRM_Core_BAO_UFMatch::synchronize($wpUser, TRUE, 'WordPress', 'Individual');
  $memberContactId = CRM_Core_BAO_UFMatch::getContactId($wpUser->ID);
  if ($memberContactId) {
    civicrm_api3('GroupContact', 'create', ['group_id' => $fixture->groupId, 'contact_id' => $memberContactId]);
  }
}

$data = $fixture->toArray();
foreach ($data['personas'] as $key => $cid) {
  // Valid for 30 days.
  $data['links'][$key] = ['cid' => $cid, 'cs' => CRM_Contact_BAO_Contact_Utils::generateChecksum($cid, NULL, 24 * 30)];
}
unset($data['personas']);
// The plan and ballots the browser tests act out (see CouncilFixture).
$data['plan'] = Council::PLAN;
$data['candidates'] = Council::CANDIDATES;
$data['ballots'] = $fixture->ballots();

$dir = __DIR__ . '/.generated';
if (!is_dir($dir)) {
  mkdir($dir, 0775, TRUE);
}
file_put_contents($dir . '/council.json', json_encode($data, JSON_PRETTY_PRINT));
file_put_contents($dir . '/council-personas.json', json_encode($fixture->toArray(), JSON_PRETTY_PRINT));
echo "Seeded election {$data['election_id']} with " . count($data['links']) . " personas\n";
