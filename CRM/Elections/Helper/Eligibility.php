<?php

/**
 * Decides who is allowed to take part in an election.
 *
 * Kept separate from the page and form classes so the rules can be tested and
 * extended (for example different groups for nominating and voting).
 */
class CRM_Elections_Helper_Eligibility {

  /**
   * The contact acting in the election pages.
   *
   * This is the logged in contact or, when nobody is logged in, the contact
   * identified by a valid checksum link (`cid` and `cs` URL parameters).
   *
   * @param bool $useReferer
   *   For AJAX calls made by a page opened with a checksum link: take the
   *   `cid` and `cs` from the page's own address (the Referer header).
   *
   * @return int|null
   */
  public static function getActingContactId($useReferer = FALSE) {
    $loggedIn = CRM_Core_Session::singleton()->getLoggedInContactID();
    if (!empty($loggedIn)) {
      return (int) $loggedIn;
    }

    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive');
    $checksum = CRM_Utils_Request::retrieve('cs', 'String');
    if ((!$contactId || !$checksum) && $useReferer) {
      [$contactId, $checksum] = self::getChecksumFromReferer();
    }
    if (!$contactId || !$checksum) {
      return NULL;
    }
    $result = \Civi\Api4\Contact::validateChecksum(FALSE)
      ->setContactId($contactId)
      ->setChecksum($checksum)
      ->execute()
      ->first();
    return !empty($result['valid']) ? (int) $contactId : NULL;
  }

  /**
   * @return array
   *   The contact id and checksum in the address of the page that made this request.
   */
  private static function getChecksumFromReferer() {
    $referer = $_SERVER['HTTP_REFERER'] ?? (CRM_Utils_System::getRequestHeaders()['Referer'] ?? NULL);
    if (!$referer || !filter_var($referer, FILTER_VALIDATE_URL)) {
      return [NULL, NULL];
    }
    parse_str((string) parse_url($referer, PHP_URL_QUERY), $query);
    $contactId = $query['cid'] ?? NULL;
    $checksum = $query['cs'] ?? NULL;
    if (!CRM_Utils_Rule::positiveInteger($contactId) || !is_string($checksum) || $checksum === '') {
      return [NULL, NULL];
    }
    return [(int) $contactId, $checksum];
  }

  /**
   * Is the contact a member of any of the given groups, directly or through a smart group?
   *
   * @param int|null $contactId
   * @param string|array|null $groupIds
   *   Group ids as an array or the comma separated string stored on an election.
   *
   * @return bool
   */
  public static function isContactInGroups($contactId, $groupIds) {
    if (empty($contactId) || empty($groupIds)) {
      return FALSE;
    }
    if (!is_array($groupIds)) {
      $groupIds = explode(',', strval($groupIds));
    }
    $groupIds = array_filter(array_map('trim', $groupIds), 'strlen');
    if (empty($groupIds)) {
      return FALSE;
    }

    $groups = civicrm_api3('GroupContact', 'get', [
      'sequential' => TRUE,
      'contact_id' => $contactId,
      'options' => ['limit' => 0],
    ]);
    $contactGroups = array_column($groups['values'], 'group_id');
    if (count(array_intersect($contactGroups, $groupIds)) > 0) {
      return TRUE;
    }

    // Check if contact belongs to any of the selected smart groups.
    $smartGroups = CRM_Contact_BAO_GroupContactCache::contactGroup((int) $contactId);
    if (isset($smartGroups['group'])) {
      $smartGroups = array_column($smartGroups['group'], 'id');
      return count(array_intersect($smartGroups, $groupIds)) > 0;
    }

    return FALSE;
  }

  /**
   * How many people are allowed to vote: the contacts in the election's groups.
   *
   * @param string|array|null $groupIds
   *
   * @return int
   */
  public static function countContactsInGroups($groupIds) {
    if (!is_array($groupIds)) {
      $groupIds = explode(',', strval($groupIds));
    }
    $groupIds = array_values(array_filter(array_map('trim', $groupIds), 'strlen'));
    if (empty($groupIds)) {
      return 0;
    }
    return \Civi\Api4\Contact::get(FALSE)
      ->selectRowCount()
      ->addWhere('is_deleted', '=', FALSE)
      ->addWhere('groups', 'IN', $groupIds)
      ->execute()
      ->count();
  }

  /**
   * Is the contact allowed to vote in the election?
   *
   * @param int $electionId
   * @param int|null $contactId
   *   Defaults to the acting contact (logged in, or validated by checksum).
   *
   * @return bool
   */
  public static function canVote($electionId, $contactId = NULL) {
    if (empty($contactId)) {
      $contactId = self::getActingContactId();
    }
    $election = findElectionById($electionId);
    return self::isContactInGroups($contactId, $election->allowed_groups);
  }

  /**
   * The groups for a role in the election: the election's own groups for that role, or the voting groups
   * when it has none (every election made before 2.0, and any election that does not need separate groups).
   *
   * @param CRM_Elections_BAO_Election|object $election
   * @param string $role
   *   voting, nominating or candidate.
   *
   * @return string
   *   Group ids, comma separated.
   */
  public static function groupsFor($election, $role) {
    $own = [
      'nominating' => $election->nomination_groups ?? NULL,
      'candidate' => $election->candidate_groups ?? NULL,
    ][$role] ?? NULL;
    $own = trim(str_replace(CRM_Core_DAO::VALUE_SEPARATOR, ',', (string) $own), ',');
    return $own !== '' ? $own : (string) $election->allowed_groups;
  }

  /**
   * Is the contact allowed to nominate (and second) in the election?
   *
   * Uses the nomination groups, or the voting groups when the election has none.
   *
   * @param int $electionId
   * @param int|null $contactId
   *
   * @return bool
   */
  public static function canNominate($electionId, $contactId = NULL) {
    if (empty($contactId)) {
      $contactId = self::getActingContactId();
    }
    return self::isContactInGroups($contactId, self::groupsFor(findElectionById($electionId), 'nominating'));
  }

  /**
   * Can the contact be nominated, stand as a candidate, or be added as one by an administrator?
   *
   * Uses the candidate groups, or the voting groups when the election has none.
   *
   * @param int $electionId
   * @param int|null $contactId
   *
   * @return bool
   */
  public static function canBeNominated($electionId, $contactId = NULL) {
    return self::isContactInGroups($contactId, self::groupsFor(findElectionById($electionId), 'candidate'));
  }

  /**
   * Has the contact voted in the election?
   *
   * @param int $electionId
   * @param int|null $contactId
   *   Defaults to the acting contact (logged in, or validated by checksum).
   *
   * @return bool
   */
  public static function hasVoted($electionId, $contactId = NULL) {
    if (empty($contactId)) {
      $contactId = self::getActingContactId();
    }
    if (empty($contactId)) {
      return FALSE;
    }
    $votesCount = (int) CRM_Core_DAO::singleValueQuery('
      SELECT (SELECT COUNT(*) FROM civicrm_election_vote v INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id INNER JOIN civicrm_election_position p ON p.id = n.election_position_id WHERE p.election_id = %1 AND v.member_id = %2)
           + (SELECT COUNT(*) FROM civicrm_election_option_vote ov INNER JOIN civicrm_election_position p ON p.id = ov.election_position_id WHERE p.election_id = %1 AND ov.member_id = %2)
    ', [1 => [(int) $electionId, 'Integer'], 2 => [(int) $contactId, 'Integer']]);
    return ($votesCount > 0);
  }

  /**
   * When did the acting contact vote in the election?
   *
   * @param int $electionId
   *
   * @return string|null
   */
  public static function getVoteDate($electionId) {
    $contactId = self::getActingContactId();
    if (empty($contactId)) {
      return NULL;
    }
    $voteDate = CRM_Core_DAO::singleValueQuery('
      SELECT MIN(created_at) FROM (
        SELECT v.created_at FROM civicrm_election_vote v INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id INNER JOIN civicrm_election_position p ON p.id = n.election_position_id WHERE p.election_id = %1 AND v.member_id = %2
        UNION ALL
        SELECT ov.created_at FROM civicrm_election_option_vote ov INNER JOIN civicrm_election_position p ON p.id = ov.election_position_id WHERE p.election_id = %1 AND ov.member_id = %2
      ) t
    ', [1 => [(int) $electionId, 'Integer'], 2 => [(int) $contactId, 'Integer']]);
    return $voteDate ?: NULL;
  }

}
