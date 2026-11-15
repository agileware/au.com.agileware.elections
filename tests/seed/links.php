<?php
/**
 * Personal links (no website login needed) for every Beekeeper that seed.php made, in every election it made.
 * Valid for 90 days by default. A personal link carries a checksum that lets anyone who holds it act as that
 * person, so it is written to a git-ignored file with owner-only permissions, or to standard output, and is
 * never committed.
 *
 *   cv scr tests/seed/links.php
 *
 * Environment (all optional):
 *   ELECTIONS_SEED_LINKS_FILE   Where to write the links. Default: .generated/PERSONAL-LINKS.md in this folder.
 *                               "-" writes to standard output.
 *   ELECTIONS_SEED_LINKS_DAYS   How long the links stay valid, in days. Default 90.
 *   ELECTIONS_SEED_LINK_STYLE   The form of the address: "wordpress" (<base>/civicrm/elections/view/?eid=..&cid=..&cs=..,
 *                               the pretty-permalink form), "drupal" (<base>/civicrm/elections/view?..) or "joomla"
 *                               (<base>/index.php?option=com_civicrm&task=civicrm/elections/view&..), as in
 *                               tests/playwright/fixtures/cms.ts; or "civicrm" to let CiviCRM build the address.
 *                               Default "auto": chosen from the CMS the script runs on. Set it on the Joomla dev
 *                               site: its `jcv` runs CiviCRM without Joomla.
 *   ELECTIONS_SEED_BASE_URL     The address of the site's front end. Default: CiviCRM's own base URL (with Joomla's
 *                               /administrator/ taken off).
 *   ELECTIONS_SEED_STATE_FILE   The record seed.php made. Default: .generated/seeded.json in this folder.
 *   ELECTION                    Only this election: its id or part of its name. Default: all that seed.php made.
 */
// `cv scr` may not set __DIR__ to this file's folder, so find the folder from __DIR__, the script path or ELECTIONS_SEED_DIR.
$seedDir = is_file(__DIR__ . '/common.php') ? __DIR__ : (isset($argv[0]) && is_file(dirname($argv[0]) . '/common.php') ? dirname($argv[0]) : (string) getenv('ELECTIONS_SEED_DIR'));
if (!is_file($seedDir . '/common.php')) {
  fwrite(STDERR, "Cannot find the seed scripts' folder: set ELECTIONS_SEED_DIR.\n");
  exit(1);
}
require_once $seedDir . '/common.php';

$seeded = seed_read_state();
$days = max(1, (int) seed_env('LINKS_DAYS', 90));
$style = strtolower((string) seed_env('LINK_STYLE', 'auto'));
if ($style === 'auto') {
  $style = ['WordPress' => 'wordpress', 'Drupal' => 'drupal', 'Drupal8' => 'drupal', 'Joomla' => 'joomla'][CIVICRM_UF] ?? 'civicrm';
}
if (!in_array($style, ['wordpress', 'drupal', 'joomla', 'civicrm'], TRUE) || (CIVICRM_UF === 'UnitTests' && $style === 'civicrm')) {
  fwrite(STDERR, "Cannot tell which CMS this is (CIVICRM_UF is " . CIVICRM_UF . "). Set ELECTIONS_SEED_LINK_STYLE to wordpress, drupal or joomla.\n");
  exit(1);
}
$only = getenv('ELECTION') ?: '';
$target = seed_env('LINKS_FILE', seed_generated_dir() . '/PERSONAL-LINKS.md');
$base = rtrim(preg_replace('#/administrator/?$#', '', rtrim((string) seed_env('BASE_URL', CIVICRM_UF_BASEURL), '/')), '/');

$elections = [];
foreach ($seeded['elections'] as $name => $electionId) {
  if ($only === '' || (ctype_digit($only) ? (int) $only === (int) $electionId : stripos($name, $only) !== FALSE)) {
    $elections[$name] = (int) $electionId;
  }
}
if (!$elections) {
  fwrite(STDERR, "No seeded election matches '$only'.\n");
  exit(1);
}

$names = [];
foreach ($seeded['beekeepers'] as $n => $cid) {
  $names[$cid] = civicrm_api3('Contact', 'getvalue', ['id' => $cid, 'return' => 'display_name']);
}

$md = "# Personal links (local test site only)\n\n";
$md .= "A personal link lets a Beekeeper act in an election without logging in to the website. Each link opens the election page. For another page, swap `elections/view` in the address: `elections/nominations/create` (nominate), `elections/vote` (vote). Anyone who holds a link can act as that person: do not commit or share this file.\n\n";
$md .= "Valid for $days days from " . date('j F Y') . ".\n";
foreach ($elections as $name => $electionId) {
  $md .= "\n## [$electionId] $name\n\n| Beekeeper | Personal link |\n|---|---|\n";
  foreach ($seeded['beekeepers'] as $n => $cid) {
    $cs = CRM_Contact_BAO_Contact_Utils::generateChecksum($cid, NULL, 24 * $days);
    $query = sprintf('eid=%d&cid=%d&cs=%s', $electionId, $cid, $cs);
    $link = match ($style) {
      'wordpress' => "$base/civicrm/elections/view/?$query",
      'drupal' => "$base/civicrm/elections/view?$query",
      'joomla' => "$base/index.php?option=com_civicrm&task=civicrm/elections/view&$query",
      default => CRM_Utils_System::url('civicrm/elections/view', $query, TRUE, NULL, FALSE, TRUE),
    };
    $md .= sprintf("| %s | %s |\n", $names[$cid], $link);
  }
}
if ($target === '-') {
  echo $md;
}
else {
  seed_write_private_file($target, $md);
  echo "Wrote $target (" . count($elections) . ' elections, ' . count($seeded['beekeepers']) . " Beekeepers). Do not commit it.\n";
}
