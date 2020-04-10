<?php

require_once 'elections.civix.php';
use CRM_Elections_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function elections_civicrm_config(&$config) {
  _elections_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function elections_civicrm_xmlMenu(&$files) {
  _elections_civix_civicrm_xmlMenu($files);
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
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function elections_civicrm_postInstall() {
  _elections_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function elections_civicrm_uninstall() {
  _elections_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function elections_civicrm_enable() {
  _elections_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function elections_civicrm_disable() {
  _elections_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function elections_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _elections_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function elections_civicrm_caseTypes(&$caseTypes) {
  _elections_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function elections_civicrm_angularModules(&$angularModules) {
  _elections_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function elections_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _elections_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function elections_civicrm_entityTypes(&$entityTypes) {
  _elections_civix_civicrm_entityTypes($entityTypes);
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
    $form->components['elections'] = array(
      'label'  => 'Elections',
      'select' => array(),
    );
    $form->components['electioninfo'] = array(
      'label'  => 'Election Info',
      'select' => array(
        'key' => 'id',
        'entity' => 'Election',
        'api' => array(
          'params' => array(
            'is_deleted' => 0,
          ),
        ),
      ),
    );
    $form->options[] = array(
      'key' => 'action',
      'components' => array('elections'),
      'options' => array(
        'visible' => 'Display visible elections',
        'hidden'  => 'Display non visible elections',
      ),
    );
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

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
 */
function elections_civicrm_navigationMenu(&$menu) {
  $menu[] = array(
    'attributes' => array(
      'label' => 'Elections',
      'name' => 'Elections',
      'url' => 'civicrm/elections',
      'operator' => NULL,
      'icon'     => 'crm-i fa-check-square-o',
      'separator' => NULL,
      'active' => 1,
      'weight' => 62,
    ),
  );
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
 * Thorw unathorized message if logged in contact is not allowed to perform certain actions.
 *
 * @throws CRM_Extension_Exception
 * @throws CiviCRM_API3_Exception
 */
function throwUnauthorizedMessageIfRequired($formOrPage) {
  if (!isElectionAdmin()) {
    throwAccessDeniedException($formOrPage, E::ts('You are not authorized to perform this action.'));
    return TRUE;
  }
  return FALSE;
}

/**
 * Throw accesss denied exception with custom message.
 *
 * @param $exceptionMessage
 * @throws CRM_Extension_Exception
 */
function throwAccessDeniedException($formOrPage, $exceptionMessage, $pageOptions = array()) {
  $formOrPage->thorwError = TRUE;
  $formOrPage->assign('errormessage', $exceptionMessage);

  if (empty($pageOptions)) {
    $pageOptions['return_button_text'] = E::ts('Return to Elections');
    $pageOptions['return_button_action'] = CRM_Utils_System::url('civicrm/elections');
  }

  foreach ($pageOptions as $optionKey => $pageOption) {
    $formOrPage->assign($optionKey, $pageOption);
  }
}

/**
 * Throw access denied exception if non-member is trying view a member related page.
 *
 * @param $exceptionMessage
 * @throws CRM_Extension_Exception
 */
function throwNonMemberAccessDenied($formOrPage) {
  $formOrPage->assign('nonmember', TRUE);
  throwAccessDeniedException($formOrPage, E::ts('You are not authorized to access this page.'));
}

/**
 * Throw access denied exception for a page.
 *
 * @throws CRM_Extension_Exception
 */
function throwAccessDeniedPage($formOrPage) {
  $formOrPage->thorwError = TRUE;
  $formOrPage->assign('errormessage', E::ts('You are not authorized to access this page.'));
}

/**
 * Get election id from URL.
 *
 * @return mixed
 * @throws CRM_Core_Exception
 * @throws CRM_Extension_Exception
 */
function retrieveElectionIdFromUrl($form) {
  $eId = CRM_Utils_Request::retrieve('eid', 'Positive', $form, FALSE, 0);
  if (!$eId) {
    throwAccessDeniedPage($form);
    return -1;
  }
  return $eId;
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
    throw new CRM_Extension_Exception ( E::ts('You are not authorized to access this page.'));
  }

  $election = new CRM_Elections_BAO_Election();
  $election->id = $electionId;

  if (!$election->find(TRUE) && $throwErrorIfNotFound) {
    throw new CRM_Extension_Exception ( E::ts('You are not authorized to access this page.'));
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
  $entities[] = array(
    'module' => 'au.com.agileware.elections',
    'name' => 'nomination_option_value',
    'entity' => 'OptionValue',
    'cleanup' => 'never',
    'params' => array(
      'version' => 3,
      'option_group_id' => 'activity_type',
      'label' => "Nomination",
      'name' => "Nomination",
      'description' => ts("Nominated a member"),
      'is_reserved' => 1,
      'weight' => 1,
      'is_active' => 1,
    ),
  );
  $entities[] = array(
    'module' => 'au.com.agileware.elections',
    'name' => 'vote_option_value',
    'entity' => 'OptionValue',
    'cleanup' => 'never',
    'params' => array(
      'version' => 3,
      'option_group_id' => 'activity_type',
      'label' => "Vote",
      'name' => "Vote",
      'description' => ts("Voted in an election"),
      'is_reserved' => 1,
      'weight' => 1,
      'is_active' => 1,
    ),
  );
  _elections_civix_civicrm_managed($entities);
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
    $groupIds = explode(",", $groupIds);
  }
  if (count($groupIds) == 0 || empty($groupIds)) {
    return FALSE;
  }

  $groups = civicrm_api3('GroupContact', 'get', [
    'sequential' => 1,
    'contact_id' => $contactId,
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
    'sequential' => 1,
    'member_id'  => $memberId,
    'election_nomination_id.election_position_id.election_id.id' => $electionId,
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
    'sequential' => 1,
    'return'     => array('created_at'),
    'options'    => array('limit' => 1),
    'member_id'  => CRM_Core_Session::singleton()->getLoggedInContactID(),
    'election_nomination_id.election_position_id.election_id.id' => $electionId,
  ]);
  $voteDate = $voteDate['values'][0]['created_at'];
  return $voteDate;
}

/**
 * Define election tokens to be included in emails.
 *
 * @param $tokens
 */
function elections_civicrm_tokens(&$tokens) {
  $tokensList = getElectionTokensList();
  foreach ($tokensList as $tokenKey => $tokenItems) {
    $tokens[$tokenKey] = $tokenItems;
  }
}

/**
 * Get election tokens list
 *
 * @return array
 */
function getElectionTokensList() {
  $tokensList = array(
    'election' => array(
      'election.name' => "Name",
    ),
    'electionposition' => array(
      'electionposition.name' => "Name",
    ),
    'nomination' => array(
      'nomination.nominatorname' => "Nominator Name",
      'nomination.nomineename'   => "Nominee Name",
    ),
  );
  return $tokensList;
}

/**
 * Implements hook_civicrm_tokenValues().
 *
 */
function elections_civicrm_tokenValues(&$values, $cids, $job = NULL, $tokens = array(), $context = NULL) {
  $customTokens = array_keys(getElectionTokensList());
  foreach ($customTokens as $customToken) {
    if (isset($tokens[$customToken])) {
      addPlaceholderTokenValues($tokens, $customToken, $cids, $values);
    }
  }
}

function addPlaceholderTokenValues($tokens, $customToken, $cids, &$values) {
  foreach ($tokens[$customToken] as $electionToken) {
    foreach ($cids as $cid) {
      $tokenKey = $customToken . '.' . $electionToken;
      $values[$cid][$tokenKey] = '[' . $tokenKey . ']';
    }
  }
}

/**
 * Implements hook_civicrm_permission().
 *
 */
function elections_civicrm_permission(&$permissions) {
  $permissions += array(
    'administer Elections' => array(
      E::ts('CiviCRM: administer elections'),
      E::ts('Grants the necessary permissions for administrating elections in CiviCRM.'),
    ),
    'view Elections' => array(
      E::ts('CiviCRM: view elections'),
      E::ts('Grants the necessary permissions for participating in elections.'),
    ),
  );
}

/**
 * Implements hook_civicrm_alterMailParams().
 *
 */
function elections_civicrm_alterMailParams(&$params, $context) {
  if ($params['groupName'] == 'Scheduled Reminder Sender' && $params['entity'] == 'action_schedule' && isset($params['token_params'])) {
    $tokenValues = $params['token_params'];
    if ($tokenValues['entity_table'] == 'civicrm_activity') {
      $activityId = $tokenValues['entity_id'];
      $activityInfo = civicrm_api3('Activity', 'getsingle', array(
        'id'     => $activityId,
        'return' => array(
          'source_record_id',
          'activity_type_id.name',
          'is_star',
        ),
      ));

      $subject = $params['subject'];
      $text = $params['text'];
      $html = $params['html'];

      if ($activityInfo['activity_type_id.name'] == 'Nomination') {
        $electionNominationInfo = civicrm_api3("ElectionNominationSeconder", "getsingle", array(
          'id' => $activityInfo['source_record_id'],
          'sequential' => TRUE,
          'return' => [
            "member_nominator.display_name",
            "election_nomination_id.member_nominee.display_name",
            "election_nomination_id.election_position_id.name",
            "election_nomination_id.election_position_id.election_id.name",
          ],
        ));

        $placeHolders = getTokenPlaceholders();
        foreach ($placeHolders as $placeHolder) {
          $placeHolderValue = "";
          if ($placeHolder == '[election.name]') {
            $placeHolderValue = $electionNominationInfo['election_nomination_id.election_position_id.election_id.name'];
          }
          if ($placeHolder == '[electionposition.name]') {
            $placeHolderValue = $electionNominationInfo['election_nomination_id.election_position_id.name'];
          }
          if ($placeHolder == '[nomination.nominatorname]') {
            $placeHolderValue = $electionNominationInfo['member_nominator.display_name'];
          }
          if ($placeHolder == '[nomination.nomineename]') {
            $placeHolderValue = $electionNominationInfo['election_nomination_id.member_nominee.display_name'];
          }

          replacePlaceholderValue($subject, $placeHolder, $placeHolderValue);
          replacePlaceholderValue($text, $placeHolder, $placeHolderValue);
          replacePlaceholderValue($html, $placeHolder, $placeHolderValue);
        }
      }

      if ($activityInfo['activity_type_id.name'] == 'Vote') {
        $electionInfo = civicrm_api3("Election", "getsingle", array(
            'id' => $activityInfo['source_record_id'],
            'sequential' => TRUE,
            'return' => [
                "name",
            ],
        ));

        replacePlaceholderValue($subject, '[election.name]', $electionInfo['name']);
        replacePlaceholderValue($text, '[election.name]', $electionInfo['name']);
        replacePlaceholderValue($html, '[election.name]', $electionInfo['name']);
      }

      $params['html'] = $html;
      $params['subject'] = $subject;
      $params['text'] = $text;
    }
  }
}

/**
 * Function replaces token placeholder with its actual value.
 *
 * @param $text
 * @param $placeholder
 * @param $placeholderValue
 */
function replacePlaceholderValue(&$text, $placeholder, $placeholderValue) {
  $text = str_replace($placeholder, $placeholderValue, $text);
}

/**
 * Function returns list of token placeholders.
 *
 * @return array
 */
function getTokenPlaceHolders() {
  $electionTokens = getElectionTokensList();
  $placeHolders = array();
  foreach ($electionTokens as $tokenKey => $electionTokenItems) {
    foreach ($electionTokenItems as $electionTokenItemKey => $electionTokenItem) {
      $tokenPlaceholder = '[' . $electionTokenItemKey . ']';
      $placeHolders[] = $tokenPlaceholder;
    }
  }

  return $placeHolders;
}

/**
 * Shuffle the array keeping the keys.
 *
 * @param $list
 * @return array
 */
function shuffle_assoc($list) {
  if (!is_array($list)) {
    return $list;
  }

  $keys = array_keys($list);
  shuffle($keys);
  $random = array();
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
  $permissions['election_nominee']['getlist'] = array('view Elections');
}
