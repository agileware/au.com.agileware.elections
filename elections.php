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
    'url' => 'civicrm/elections',
    'permission' => 'administer CiviCRM',
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
 * @throws CiviCRM_API3_Exception
 */
function isElectionAdmin() {
  return CRM_Core_Permission::check('administer Elections');
}

/**
 * Throw unauthorized message if logged in contact is not allowed to perform certain actions.
 *
 * @throws CRM_Extension_Exception
 * @throws CiviCRM_API3_Exception
 */
function throwUnauthorizedMessageIfRequired($formOrPage) {
  if (!isElectionAdmin()) {
    throwAccessDeniedException($formOrPage, 'You are not authorized to perform this action.');
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
    $pageOptions['return_button_text'] = 'Return to Elections';
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
  throwAccessDeniedException($formOrPage, 'You may only see this page if you are a member.');
}

/**
 * Throw access denied exception for a page.
 *
 * @throws CRM_Extension_Exception
 */
function throwAccessDeniedPage($formOrPage) {
  $formOrPage->throwError = TRUE;
  $formOrPage->assign('errormessage', 'You are not authorized to access this page.');
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
        throwAccessDeniedException($form, 'Unable to view this Election, Election ID parameter missing.', ['']);
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
      throwAccessDeniedException( $form, 'Unauthorised. Invalid contact credentials.', [''] );
      return false;
    }

    return [ 'cid' => $cid, 'cs' => $cs ];
  }

  // Otherwise, something is missing
  throwAccessDeniedException( $form, 'Unauthorised. Missing valid contact credentials.', [''] );
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
    throw new CRM_Extension_Exception('You are not authorised to access this page.');
  }

  $election = new CRM_Elections_BAO_Election();
  $election->id = $electionId;

  if (!$election->find(TRUE) && $throwErrorIfNotFound) {
    throw new CRM_Extension_Exception('You are not authorised to access this page.');
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
 * @throws CiviCRM_API3_Exception
 */
function isLoggedInMemberAllowedToVote($electionId, $contactId = NULL) {
  if ($contactId == NULL) {
    $contactId = CRM_Core_Session::singleton()->getLoggedInContactID();
  }
  $election = findElectionById($electionId);
  $groupIds = $election->allowed_groups;
  if (!empty($groupIds)) {
    $groupIds = explode(',', strval($groupIds));
  } else {
    return FALSE;
  }

  $groups = civicrm_api3('GroupContact', 'get', [
    'sequential' => TRUE,
    'contact_id' => $contactId,
    'options' => ['limit' => 0],
  ]);

  $contactGroups = array_column($groups['values'], 'group_id');
  $groupsCount = count(array_intersect($contactGroups, $groupIds));

  // Check if contact belongs to any of the selected smart groups.
  if ($groupsCount == 0) {
    $smartGroups = CRM_Contact_BAO_GroupContactCache::contactGroup(CRM_Core_Session::singleton()->getLoggedInContactID());
    if (isset($smartGroups['group'])) {
      $smartGroups = array_column($smartGroups['group'], 'id');
      $groupsCount = count(array_intersect($smartGroups, $groupIds));
    }
  }

  return ($groupsCount > 0) ? TRUE : FALSE;
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
 * @throws CiviCRM_API3_Exception
 */
function hasLoggedInUserAlreadyVoted($electionId, $memberId = NULL) {
  if (!$memberId) {
    $memberId = CRM_Core_Session::singleton()->getLoggedInContactID();
  }
  $votesCount = civicrm_api3('ElectionVote', 'getcount', [
    'sequential' => TRUE,
    'member_id'  => $memberId,
    'election_nomination_id.election_position_id.election_id.id' => $electionId,
    'options' => ['limit' => 0],
  ]);
  return ($votesCount > 0);
}

/**
 * Check if logged in user has already voted or not.
 *
 * @param $electionId
 * @return bool
 * @throws CiviCRM_API3_Exception
 */
function getLoggedInUserVoteDate($electionId) {
  $voteDate = civicrm_api3('ElectionVote', 'get', [
    'sequential' => TRUE,
    'return'     => ['created_at'],
    'options'    => ['limit' => 0],
    'member_id'  => CRM_Core_Session::singleton()->getLoggedInContactID(),
    'election_nomination_id.election_position_id.election_id.id' => $electionId,
  ]);
  $voteDate = $voteDate['values'][0]['created_at'];
  return $voteDate;
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
    'label' => E::ts('CiviCRM: view elections'),
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
  $permissions['election_nominee']['getlist'] = ['view Elections'];

  if ( $entity !== 'election_nominee' && $action !== 'getlist' ) {
    return;
  }

  // Default to logged in permissions
  if ( !empty( CRM_Core_Session::getLoggedInContactID() ) ) {
    return;
  }

  // Get cs and cid parameters via referer in AJAX calls
  $referer = CRM_Utils_System::getRequestHeaders()['Referer'] ?? NULL;
  if ( !filter_var( $referer, FILTER_VALIDATE_URL) ) {
    // Do nothing if not a URL
    return;
  }
  $referer_parsed = parse_url($referer);
  parse_str($referer_parsed['query'], $query_params);

  // Bypass 'view elections' permissions if we have a validated checksum.
  // Defer to AJAX API permissions.
  if ( $query_params['cid'] && $query_params['cs'] ) {
    $results = \Civi\Api4\Contact::validateChecksum(FALSE)
                                  ->setContactId($query_params['cid'])
                                  ->setChecksum($query_params['cs'])
                                  ->execute()
                                  ->first();

    if ( $results['valid'] ) {
      $permissions['election_nominee']['getlist'] = ['access AJAX API'];
    }
  }
}
