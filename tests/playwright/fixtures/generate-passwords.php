<?php
/**
 * Makes a random password for each test account in test-users.json and saves them in the git-ignored
 * .generated/test-passwords.json, so no password is ever committed.
 *
 * Usage: php generate-passwords.php <test-users.json> <test-passwords.json>
 *
 * Passwords that already exist in the output file are kept, so running a setup script again does not
 * change them. Delete the output file to make new ones (then run the setup script again so that the
 * accounts get the new passwords). Needs only PHP: no CMS and no CiviCRM.
 */
if ($argc < 3) {
  fwrite(STDERR, "Usage: php generate-passwords.php <test-users.json> <test-passwords.json>\n");
  exit(1);
}
[, $usersFile, $outFile] = $argv;
$users = json_decode(file_get_contents($usersFile), TRUE);
$passwords = is_file($outFile) ? (json_decode(file_get_contents($outFile), TRUE) ?: []) : [];

$alphabet = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
foreach ($users as $key => $user) {
  if (!is_array($user) || !isset($user['username']) || !empty($passwords[$key])) {
    continue;
  }
  $password = '';
  for ($i = 0; $i < 24; $i++) {
    $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
  }
  // A short tail so that CMS password rules (a digit, an upper and a lower case letter) are always met.
  $passwords[$key] = $password . 'aZ7';
}

if (!is_dir(dirname($outFile))) {
  mkdir(dirname($outFile), 0775, TRUE);
}
file_put_contents($outFile, json_encode($passwords, JSON_PRETTY_PRINT) . "\n");
// Readable by the user that runs the browser tests, which can differ from the one running the setup script.
chmod($outFile, 0644);
