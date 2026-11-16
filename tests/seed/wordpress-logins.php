<?php
/**
 * WordPress only. Creates the roles and the four website logins for Beekeepers that the review site uses: one
 * runs the elections, three take part while logged in. Each login is linked to its Beekeeper contact. Run
 * after seed.php, on a WordPress site:
 *
 *   cv scr tests/seed/wordpress-logins.php [credentials file]
 *
 * Passwords are generated and written only to the credentials file (owner-only permissions), never printed.
 * The file must stay out of git: the default is in this folder's git-ignored .generated/ folder. Drupal and Joomla
 * test accounts come from tests/playwright/fixtures/setup-accounts-drupal.sh and setup-accounts-joomla.sh.
 *
 * Where the credentials file goes: the first script argument, else ELECTIONS_SEED_CREDENTIALS_FILE, else
 * .generated/CREDENTIALS.md in this folder. If the file already exists (for example with a table of
 * administrator logins) it is kept and only the table of Beekeeper logins is replaced.
 *
 * Safe to re-run: a login of the same name is deleted first. Other environment: ELECTIONS_SEED_STATE_FILE.
 */
// `cv scr` may not set __DIR__ to this file's folder, so find the folder from __DIR__, the script path or ELECTIONS_SEED_DIR.
$seedDir = is_file(__DIR__ . '/common.php') ? __DIR__ : (isset($argv[0]) && is_file(dirname($argv[0]) . '/common.php') ? dirname($argv[0]) : (string) getenv('ELECTIONS_SEED_DIR'));
if (!is_file($seedDir . '/common.php')) {
  fwrite(STDERR, "Cannot find the seed scripts' folder: set ELECTIONS_SEED_DIR.\n");
  exit(1);
}
require_once $seedDir . '/common.php';

if (!function_exists('wp_insert_user')) {
  fwrite(STDERR, "This script needs WordPress, and this site is not running on it.\n");
  exit(1);
}

$seeded = seed_read_state();
$B = $seeded['beekeepers'];
$credentialsFile = ($argv[1] ?? '') ?: seed_env('CREDENTIALS_FILE', seed_generated_dir() . '/CREDENTIALS.md');

// ------------------------------------------------------------------------------------------------
// Roles: one for the person who runs the elections, one for people who take part while logged in.
// CiviCRM's WordPress permission check lowercases the permission and replaces runs of non-alphanumerics
// with an underscore, so the capability names are the munged forms.
$roles = [
  'elections_manager' => ['Elections Manager', ['access_civicrm', 'administer_elections', 'view_elections', 'access_ajax_api', 'view_all_contacts']],
  'elections_member' => ['Elections Participant', ['view_elections', 'access_ajax_api', 'access_uploaded_files', 'profile_view', 'profile_edit']],
];
foreach ($roles as $roleName => [$label, $capabilities]) {
  $role = get_role($roleName) ?: add_role($roleName, $label, get_role('subscriber')->capabilities);
  foreach ($capabilities as $capability) {
    $role->add_cap($capability);
  }
}

// ------------------------------------------------------------------------------------------------
// Logins for four Beekeepers.
$logins = [
  // login => [beekeeper number, role]
  'bee.manager' => [10, 'elections_manager'],
  'bee.ellie' => [1, 'elections_member'],
  'bee.marcus' => [2, 'elections_member'],
  'bee.priya' => [3, 'elections_member'],
];
require_once ABSPATH . 'wp-admin/includes/user.php';
$credentials = "\n| Beekeeper | Role | Username | Password |\n|---|---|---|---|\n";
foreach ($logins as $login => [$n, $role]) {
  if (empty($B[$n])) {
    fwrite(STDERR, "Beekeeper $n is not in the seed record: run seed.php first.\n");
    exit(1);
  }
  $contact = civicrm_api3('Contact', 'getsingle', ['id' => $B[$n], 'return' => ['first_name', 'last_name']]);
  $existing = get_user_by('login', $login);
  if ($existing) {
    wp_delete_user($existing->ID);
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_uf_match WHERE uf_id = %1', [1 => [(int) $existing->ID, 'Integer']]);
  }
  $password = wp_generate_password(16, FALSE);
  $userId = wp_insert_user([
    'user_login' => $login,
    'user_pass' => $password,
    'user_email' => civicrm_api3('Email', 'getvalue', ['contact_id' => $B[$n], 'return' => 'email']),
    'display_name' => $contact['first_name'] . ' ' . $contact['last_name'],
    'first_name' => $contact['first_name'],
    'last_name' => $contact['last_name'],
    'role' => $role,
  ]);
  if (is_wp_error($userId)) {
    throw new RuntimeException($userId->get_error_message());
  }
  $wpUser = get_user_by('id', $userId);
  CRM_Core_BAO_UFMatch::synchronize($wpUser, TRUE, 'WordPress', 'Individual');
  $credentials .= sprintf("| %s %s | %s | %s | %s |\n", $contact['first_name'], $contact['last_name'], $role === 'elections_manager' ? 'Elections Manager (administer Elections)' : 'Elections Member', $login, $password);
}
$existingFile = is_file($credentialsFile) ? file_get_contents($credentialsFile) : "# Test site credentials (local test site only; never commit)\n";
// Keep any other table (such as the administrator's), replace an earlier table of Beekeeper logins.
$existingFile = preg_replace('/\n\| Beekeeper \|.*\z/s', "\n", $existingFile);
seed_write_private_file($credentialsFile, rtrim($existingFile) . "\n" . $credentials);
echo 'Made ' . count($logins) . " logins and linked them to their Beekeepers. Passwords are in $credentialsFile (do not commit it).\n";
