<?php
/**
 * Makes the Joomla user groups the test organisers and voter belong to, and gives each group its
 * CiviCRM permissions (run by setup-accounts-joomla.sh, from the Joomla site root). Safe to re-run.
 *
 * Joomla keeps CiviCRM's permissions as actions on the com_civicrm asset ("access CiviCRM" is
 * civicrm.access_civicrm), set per user group, so each test role is a group here. The groups sit under
 * Registered, so they may log in to the site; the two organiser groups may also log in to the
 * administrator and open CiviCRM there (core.login.admin on the site, core.manage on com_civicrm).
 *
 * Usage: php joomla-test-groups.php
 */

const _JEXEC = 1;
define('JPATH_BASE', getcwd());
if (!file_exists(JPATH_BASE . '/includes/defines.php')) {
  fwrite(STDERR, "Run this from the Joomla site root.\n");
  exit(1);
}
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Usergroup;
use Joomla\Database\DatabaseInterface;

/** The groups: title, whether it may use the administrator, and its CiviCRM permissions (as in setup-accounts-drupal.sh). */
$groups = [
  'Elections Manager (test)' => [TRUE, ['access CiviCRM', 'administer Elections', 'view Elections', 'access AJAX API', 'view all contacts']],
  'Elections Organiser without contacts (test)' => [TRUE, ['access CiviCRM', 'administer Elections', 'view Elections', 'access AJAX API']],
  'Elections Candidates Manager (test)' => [TRUE, ['access CiviCRM', 'manage Election candidates', 'view Elections', 'access AJAX API', 'view all contacts']],
  'Elections Voter (test)' => [FALSE, ['view Elections', 'access AJAX API', 'access uploaded files', 'profile view', 'profile edit']],
];
const REGISTERED = 2;

// Joomla's tables need an application; set one up as cli/joomla.php does, without running it.
$container = Factory::getContainer();
$container->alias('session', 'session.cli')
  ->alias('JSession', 'session.cli')
  ->alias(\Joomla\CMS\Session\Session::class, 'session.cli')
  ->alias(\Joomla\Session\Session::class, 'session.cli')
  ->alias(\Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(\Joomla\Console\Application::class);

/** @var \Joomla\Database\DatabaseDriver $db */
$db = $container->get(DatabaseInterface::class);

/** CiviCRM's name for a permission on Joomla (CRM_Core_Permission_Joomla::translateJoomlaPermission). */
function joomlaAction(string $permission): string {
  return 'civicrm.' . preg_replace('/[^a-z0-9]+/', '_', strtolower($permission));
}

function groupId($db, string $title): ?int {
  $id = $db->setQuery($db->getQuery(TRUE)->select('id')->from('#__usergroups')->where($db->quoteName('title') . ' = ' . $db->quote($title)))->loadResult();
  return $id ? (int) $id : NULL;
}

function assetRules($db, string $name): array {
  $rules = $db->setQuery($db->getQuery(TRUE)->select('rules')->from('#__assets')->where($db->quoteName('name') . ' = ' . $db->quote($name)))->loadResult();
  if ($rules === NULL) {
    throw new RuntimeException("No Joomla asset $name");
  }
  return json_decode($rules ?: '{}', TRUE) ?: [];
}

function saveAssetRules($db, string $name, array $rules): void {
  $db->setQuery($db->getQuery(TRUE)->update('#__assets')->set($db->quoteName('rules') . ' = ' . $db->quote(json_encode($rules ?: new stdClass())))->where($db->quoteName('name') . ' = ' . $db->quote($name)))->execute();
}

function allow(array &$rules, string $action, int $group): void {
  $rules[$action] = ($rules[$action] ?? []);
  $rules[$action][(string) $group] = 1;
}

$root = assetRules($db, 'root.1');
$civicrm = assetRules($db, 'com_civicrm');
foreach ($groups as $title => [$admin, $permissions]) {
  $id = groupId($db, $title);
  if (!$id) {
    $table = new Usergroup($db);
    if (!$table->save(['parent_id' => REGISTERED, 'title' => $title])) {
      throw new RuntimeException("Could not make the group $title: " . $table->getError());
    }
    $table->rebuild();
    $id = groupId($db, $title);
  }
  if ($admin) {
    allow($root, 'core.login.admin', $id);
    allow($civicrm, 'core.manage', $id);
  }
  foreach ($permissions as $permission) {
    allow($civicrm, joomlaAction($permission), $id);
  }
}
saveAssetRules($db, 'root.1', $root);
saveAssetRules($db, 'com_civicrm', $civicrm);
echo "Joomla test groups ready\n";
