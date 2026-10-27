<?php

require_once 'elections.civix.php';
use \Symfony\Component\DependencyInjection\ContainerBuilder;
use \Symfony\Component\Config\Resource\FileResource;
use CRM_Elections_ExtensionUtil as E;

/**
 * Implements hook_civicrm_container()
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_container/
 *
 * @return void
 */
function elections_civicrm_container(ContainerBuilder $container) {
	$container->addResource(new FileResource(E::path('CRM/Elections/Tokens.php')));
	$dispatcher = $container->findDefinition('dispatcher');
	$dispatcher->addMethodCall('addListener', ['civi.token.eval', ['CRM_Elections_Tokens', 'evaluate']]);
	$dispatcher->addMethodCall('addListener', ['civi.token.list', ['CRM_Elections_Tokens', 'register']]);
  $container->addCompilerPass(new Civi\Elections\CompilerPass());
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function elections_civicrm_config(&$config) {
  _elections_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function elections_civicrm_install() {
  _elections_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function elections_civicrm_enable() {
  _elections_civix_civicrm_enable();
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_pre().
 *
 * Business rules that must hold however an election is changed - the forms, the
 * API, or an inline edit in a SearchKit list.
 */
function elections_civicrm_pre($op, $objectName, $id, &$params) {
  CRM_Elections_Helper_Guard::pre($op, $objectName, $id, $params);
  if ($objectName === 'ElectionPosition' && in_array($op, ['create', 'edit', 'delete'], TRUE)) {
    $changesThePosition = TRUE;
    if ($op === 'edit' && $id) {
      // Writing a result (the status of the count) is not a change to the position itself.
      $current = CRM_Core_DAO::executeQuery('SELECT name, quantity, sortorder, description, election_id FROM civicrm_election_position WHERE id = %1', [1 => [$id, 'Positive']]);
      $current->fetch();
      $changesThePosition = FALSE;
      foreach (['name', 'quantity', 'sortorder', 'description', 'election_id'] as $field) {
        if (array_key_exists($field, (array) $params) && (string) $params[$field] !== (string) $current->$field) {
          $changesThePosition = TRUE;
        }
      }
    }
    if ($changesThePosition) {
      $electionId = $params['election_id'] ?? ($id ? CRM_Core_DAO::getFieldValue('CRM_Elections_DAO_ElectionPosition', $id, 'election_id') : NULL);
      if ($electionId) {
        CRM_Elections_BAO_ElectionPosition::assertCanChange($electionId);
      }
      // Moving a position (drag and drop sets its order to where it was dropped): make room by
      // shifting the others, so every position keeps its own place.
      if ($op === 'edit' && $id && isset($params['sortorder']) && $params['sortorder'] !== '') {
        $oldOrder = (int) $current->sortorder;
        $newOrder = (int) $params['sortorder'];
        // APIv4 (SortableEntity) has already made room by the time this runs; other routes have not,
        // and then another position of the election still holds the place being moved into.
        $placeTaken = (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_position WHERE election_id = %1 AND sortorder = %2 AND id <> %3', [
          1 => [(int) $current->election_id, 'Integer'],
          2 => [$newOrder, 'Integer'],
          3 => [(int) $id, 'Integer'],
        ]);
        if ($oldOrder !== $newOrder && $placeTaken > 0) {
          CRM_Utils_Weight::updateOtherWeights('CRM_Elections_DAO_ElectionPosition', $oldOrder, $newOrder, ['election_id' => (int) $current->election_id], 'sortorder');
        }
      }
    }
  }
  if ($objectName === 'ElectionNomination' && $op === 'edit' && $id) {
    CRM_Elections_BAO_ElectionNomination::assertCanChange($id, (array) $params);
  }

  if ($objectName === 'Election' && $op === 'edit' && $id && !empty($params['is_visible'])) {
    $isVisibleNow = CRM_Core_DAO::getFieldValue('CRM_Elections_DAO_Election', $id, 'is_visible');
    if (!$isVisibleNow) {
      CRM_Elections_BAO_Election::assertCanActivate($id);
    }
  }
}

/**
 * Implements hook_civicrm_post(): the audit log.
 */
function elections_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  CRM_Elections_Helper_Guard::post($op, $objectName, $objectId, $objectRef);
}

/**
 * Implements hook_civicrm_merge(): when two contacts are merged, move what they did in elections to the
 * contact that is kept, so deleting the other contact does not remove or change anything.
 */
function elections_civicrm_merge($type, &$data, $mainId = NULL, $otherId = NULL, $tables = NULL) {
  if ($type !== 'sqls' || !$mainId || !$otherId) {
    return;
  }
  $main = (int) $mainId;
  $other = (int) $otherId;
  $data[] = "UPDATE civicrm_election_nomination SET member_nominee = $main WHERE member_nominee = $other";
  $data[] = "UPDATE civicrm_election_nomination_seconder SET member_nominator = $main WHERE member_nominator = $other";
  // A ballot the kept contact also cast stays where it is: one ballot for each person.
  $data[] = "UPDATE IGNORE civicrm_election_vote SET member_id = $main WHERE member_id = $other";
}

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
 */
function elections_civicrm_preProcess($formName, &$form) {
  if ($formName == 'CRM_Core_Form_ShortCode') {
    $form->components['elections'] = [
      'label'  => 'Elections',
      'select' => [],
    ];
    $form->components['electioninfo'] = [
      'label'  => 'Election Info',
      'select' => [
        'key' => 'id',
        'entity' => 'Election',
        'api' => [
          'params' => [
            'is_deleted' => 0,
          ],
        ],
      ],
    ];
    $form->options[] = [
      'key' => 'action',
      'components' => ['elections'],
      'options' => [
        'visible' => 'Display visible elections',
        'hidden'  => 'Display non visible elections',
      ],
    ];
  }
}

/**
 * Hide breadcrumb and Footer from the pages/forms.
 * @param $pageOrForm
 */
function hideNonRequiredItemsOnPage($pageOrForm) {
  $pageOrForm->assign('breadcrumb', FALSE);
  $pageOrForm->assign('urlIsPublic', TRUE);
}

if (CRM_Core_Config::singleton()->userFramework == 'WordPress') {
  /**
   * Add wordpress filters to expose Election pages as shortcode.
   */
  if (function_exists('add_filter')) {
    add_filter('shortcode_atts_civicrm', 'elections_amend_shortcode_attributes', 10, 4);
    add_filter('civicrm_shortcode_preprocess_atts', 'elections_civicrm_amend_args', 10, 2);
  }
  /**
   * Filter the CiviCRM shortcode arguments.
   *
   * Add our components and a CiviCRM path.
   *
   * @param array $args Existing shortcode arguments.
   * @param array $shortcode_atts Shortcode attributes.
   * @return array $args Modified shortcode arguments.
   */
  function elections_civicrm_amend_args($args, $shortcode_atts) {
    $args['fs'] = TRUE;

    if ($shortcode_atts['component'] == 'elections') {
      $args['q'] = 'civicrm/elections';
      $args['component'] = 'elections';
      if ($shortcode_atts['action'] != 'all') {
        $args['eaction'] = $shortcode_atts['action'];
        if ($shortcode_atts['viewpageid']) {
          $args['evaction'] = get_permalink($shortcode_atts['viewpageid']);
        }
      }
    }
    if ($shortcode_atts['component'] == 'electioninfo') {
      $args['q'] = 'civicrm/elections/view';
      $args['component'] = 'electioninfo';
      if (isset($shortcode_atts['id']) && !empty($shortcode_atts['id'])) {
        $args['eid'] = $shortcode_atts['id'];
        $args['sse'] = TRUE;
      }
    }

    return $args;
  }

  /**
   * Ammend custom shortcode attributes for this extension.
   *
   * @param $out
   * @param $pairs
   * @param $atts
   * @param $shortcodename
   * @return mixed
   */
  function elections_amend_shortcode_attributes($out, $pairs, $atts, $shortcodename) {
    if (isset($atts['viewpageid']) && !empty($atts['viewpageid'])) {
      $out['viewpageid'] = $atts['viewpageid'];
    }
    return $out;
  }
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
 */
function elections_civicrm_navigationMenu(&$menu) {
  _elections_civix_insert_navigation_menu($menu, NULL, [
    'label' => E::ts('Elections'),
    'name' => 'Elections',
    'icon'     => 'crm-i fa-check-square-o',
    'url' => 'civicrm/elections/manage',
    'permission' => 'access CiviCRM,administer Elections',
    'operator' => 'AND',
    'weight' => 62,

  ]);

  _elections_civix_insert_navigation_menu($menu, 'Administer/System Settings', [
    'label' => E::ts('Elections Settings'),
    'name' => 'configureElections',
    'url' => 'civicrm/admin/setting/elections',
    'permission' => 'administer CiviCRM',

  ]);
}

/**
 * Implements hook_civicrm_coreResourceList().
 *
 */
function elections_civicrm_coreResourceList(&$list, $region) {
  Civi::resources()->addStyleFile('au.com.agileware.elections', 'css/electionforms.css', 0, $region);
}

/**
 * Check if logged in user is member of any election admin group.
 *
 * @return bool
 * @throws CRM_Core_Exception
 */
function isElectionAdmin() {
  return CRM_Core_Permission::check('administer Elections');
}

/**
 * Throw unauthorized message if logged in contact is not allowed to perform certain actions.
 *
 * @throws CRM_Extension_Exception
 * @throws CRM_Core_Exception
 */
function throwUnauthorizedMessageIfRequired($formOrPage) {
  if (!isElectionAdmin()) {
    throwAccessDeniedException($formOrPage, E::ts('You are not authorized to perform this action.'));
    return TRUE;
  }
  return FALSE;
}

/**
 * Throw access denied exception with custom message.
 *
 * @param $exceptionMessage
 * @throws CRM_Extension_Exception
 */
function throwAccessDeniedException($formOrPage, $exceptionMessage, $pageOptions = []) {
  $formOrPage->throwError = TRUE;
  $formOrPage->assign('errormessage', $exceptionMessage);

  if (empty($pageOptions)) {
    $pageOptions['return_button_text'] = E::ts('Return to Elections');
    $pageOptions['return_button_action'] = Civi::url('current://civicrm/elections');
  }

  foreach ($pageOptions as $optionKey => $pageOption) {
    $formOrPage->assign($optionKey, $pageOption);
  }
}

/**
 * Throw access denied exception if non-member is trying access a member related page.
 *
 * @param $exceptionMessage
 * @throws CRM_Extension_Exception
 */
function throwNonMemberAccessDenied($formOrPage) {
  $formOrPage->assign('nonmember', TRUE);
  throwAccessDeniedException($formOrPage, E::ts('You may only see this page if you are a member.'));
}

/**
 * Throw access denied exception for a page.
 *
 * @throws CRM_Extension_Exception
 */
function throwAccessDeniedPage($formOrPage) {
  $formOrPage->throwError = TRUE;
  $formOrPage->assign('errormessage', E::ts('You are not authorized to access this page.'));
}

/**
 * Get election id from URL.
 *
 * @return mixed
 * @throws CRM_Core_Exception
 * @throws CRM_Extension_Exception
 */
function retrieveElectionIdFromUrl($form)
{
    $eId = CRM_Utils_Request::retrieve('eid', 'Positive', $form, FALSE, 0);
    if (!$eId) {
        throwAccessDeniedException($form, E::ts('Unable to view this Election, Election ID parameter missing.'), ['']);
        return -1;
    }
    return $eId;
}

/**
 * Get cs and cid from URL.
 *
 * @return mixed
 * @throws CRM_Core_Exception
 * @throws CRM_Extension_Exception
 */
function retrieveContactChecksumFromUrl( $form )
{
  $cid = CRM_Utils_Request::retrieve('cid', 'Positive');
  $cs = CRM_Utils_Request::retrieve('cs', 'String');

  // Only validate cs and cid if the user is not logged in
  if ( empty( \CRM_Core_Session::getLoggedInContactID() ) && $cid && $cs ) {
    $results = \Civi\Api4\Contact::validateChecksum(FALSE)
                                  ->setContactId($cid)
                                  ->setChecksum($cs)
                                  ->execute()
                                  ->first();

    if ( !$results['valid'] ) {
      // Invalid checksum
      throwAccessDeniedException( $form, E::ts('Unauthorised. Invalid contact credentials.'), [''] );
      return false;
    }

    return [ 'cid' => $cid, 'cs' => $cs ];
  }

  // Otherwise, something is missing
  throwAccessDeniedException( $form, E::ts('Unauthorised. Missing valid contact credentials.'), [''] );
  return false;
}

/**
 * Check if page/form request is by wordpress shortcode.
 *
 * @return bool
 * @throws CRM_Core_Exception
 */
function isRequestUsingShortCode() {
  $form = NULL;
  $fs = CRM_Utils_Request::retrieve('fs', 'Boolean', $form, FALSE, FALSE);
  return ($fs) ? TRUE : FALSE;
}

/**
 * Find an election by given id.
 *
 * @param $electionId
 * @param $throwErrorIfNotFound
 * @return CRM_Elections_BAO_Election
 * @throws CRM_Extension_Exception
 */
function findElectionById($electionId, $throwErrorIfNotFound = TRUE) {
  if (!$electionId) {
    throw new CRM_Extension_Exception(E::ts('You are not authorised to access this page.'));
  }

  $election = new CRM_Elections_BAO_Election();
  $election->id = $electionId;

  if (!$election->find(TRUE) && $throwErrorIfNotFound) {
    throw new CRM_Extension_Exception(E::ts('You are not authorised to access this page.'));
  }

  $election->assignStatues();

  return $election;
}

/**
 * Declare a activity type which records nomination activity.
 *
 * Implements hook_civicrm_managed().
 */
function elections_civicrm_managed(&$entities) {
  $entities[] = [
    'module' => 'au.com.agileware.elections',
    'name' => 'nomination_option_value',
    'entity' => 'OptionValue',
    'cleanup' => 'never',
    'params' => [
      'version' => 3,
      'option_group_id' => 'activity_type',
      'label' => 'Nomination',
      'name' => 'Nomination',
      'description' => 'Nominated a member',
      'is_reserved' => 1,
      'weight' => 1,
      'is_active' => 1,
    ],
  ];
  $entities[] = [
    'module' => 'au.com.agileware.elections',
    'name' => 'vote_option_value',
    'entity' => 'OptionValue',
    'cleanup' => 'never',
    'params' => [
      'version' => 3,
      'option_group_id' => 'activity_type',
      'label' => 'Vote',
      'name' => 'Vote',
      'description' => 'Voted in an election',
      'is_reserved' => 1,
      'weight' => 1,
      'is_active' => 1,
    ],
  ];
}

/**
 * Check if logged in member has current membership return if he/she is allowed to vote/nominate in election.
 *
 * @return bool
 * @throws CRM_Core_Exception
 */
function isLoggedInMemberAllowedToVote($electionId, $contactId = NULL) {
  return CRM_Elections_Helper_Eligibility::canVote($electionId, $contactId);
}

/**
 * Check if members are allowed to re-vote.
 *
 * @param $electionId
 *
 * @return bool
 */
function isMemberAllowedToReVote($electionId) {
  $election = findElectionById($electionId);
  return ($election->allow_revote != 0);
}

/**
 * Check if logged in user has already voted or not.
 *
 * @param $electionId
 * @return bool
 * @throws CRM_Core_Exception
 */
function hasLoggedInUserAlreadyVoted($electionId, $memberId = NULL) {
  return CRM_Elections_Helper_Eligibility::hasVoted($electionId, $memberId);
}

/**
 * Check if logged in user has already voted or not.
 *
 * @param $electionId
 * @return bool
 * @throws CRM_Core_Exception
 */
function getLoggedInUserVoteDate($electionId) {
  return CRM_Elections_Helper_Eligibility::getVoteDate($electionId);
}

/**
 * Implements hook_civicrm_permission().
 *
 */
function elections_civicrm_permission(&$permissions) {
  $permissions['administer Elections'] = [
    'label' => E::ts('CiviCRM: administer elections'),
    'description' => E::ts('Grants the necessary permissions for administrating elections in CiviCRM.'),
  ];
  $permissions['view Elections'] = [
    'label' => E::ts('CiviCRM: view Elections'),
    'description' => E::ts('Grants the necessary permissions for participating in elections.'),
  ];
}

/**
 * Shuffle the array keeping the keys.
 *
 * @param $list
 * @return array
 */
function elections_shuffle_assoc($list) {
  if (!is_array($list)) {
    return $list;
  }

  $keys = array_keys($list);
  shuffle($keys);
  $random = [];
  foreach ($keys as $key) {
    $random[$key] = $list[$key];
  }
  return $random;
}

/**
 * @param $entity
 * @param $action
 * @param $params
 * @param $permissions
 * Implements hook_civicrm_alterAPIPermissions().
 */
function elections_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions) {
  // Logged in users need 'view Elections'. People using a personal checksum link are granted
  // it for the nominee search only, see elections_civicrm_permission_check().
  $permissions['election_nominee']['getlist'] = ['view Elections'];
}

/**
 * Implements hook_civicrm_permission_check().
 *
 * Someone using a personal link (checksum) is not logged in, so the nominee search on the
 * nomination form would be refused. The search page carries the link's `cid` and `cs` in its
 * address, repeated in the Referer of the search request. With a valid checksum the person is
 * granted just the permissions that one search needs, for that request only, rather than
 * granting them to everybody who is not logged in.
 */
function elections_civicrm_permission_check($permission, &$granted, $contactId) {
  static $checking = FALSE;
  if ($granted || $checking || !in_array($permission, ['access AJAX API', 'view Elections'], TRUE)) {
    return;
  }
  if (!empty(CRM_Core_Session::getLoggedInContactID())) {
    return;
  }
  if (strtolower($_REQUEST['entity'] ?? '') !== 'electionnominee' || strtolower($_REQUEST['action'] ?? '') !== 'getlist') {
    return;
  }
  $checking = TRUE;
  try {
    if (CRM_Elections_Helper_Eligibility::getActingContactId(TRUE)) {
      $granted = TRUE;
    }
  }
  finally {
    $checking = FALSE;
  }
}

/**
 * Helper function to get the login URL for the site.
 */
function getLoginPageURL( $redirect = '' ) {
  $config = CRM_Core_Config::singleton();
  $login_url = $config->userSystem->getLoginURL($redirect);

  switch (CIVICRM_UF) {
    case 'Drupal8':
      break;

    case 'Drupal':
    case 'Backdrop':
      // TODO
      break;

    case 'Joomla':
      // TODO
      break;

    case 'WordPress':
      $login_url = apply_filters( 'elections_get_login_page_url', $login_url, $redirect );
      break;
  }

  return $login_url;
}
