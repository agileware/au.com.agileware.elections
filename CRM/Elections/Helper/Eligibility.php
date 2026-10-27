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
   * Is the contact allowed to nominate (and second) in the election?
   *
   * At present the same groups apply as for voting.
   *
   * @param int $electionId
   * @param int|null $contactId
   *
   * @return bool
   */
  public static function canNominate($electionId, $contactId = NULL) {
    return self::canVote($electionId, $contactId);
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
    $votesCount = civicrm_api3('ElectionVote', 'getcount', [
      'sequential' => TRUE,
      'member_id' => $contactId,
      'election_nomination_id.election_position_id.election_id.id' => $electionId,
      'options' => ['limit' => 0],
    ]);
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
    $voteDate = civicrm_api3('ElectionVote', 'get', [
      'sequential' => TRUE,
      'return' => ['created_at'],
      'options' => ['limit' => 0],
      'member_id' => $contactId,
      'election_nomination_id.election_position_id.election_id.id' => $electionId,
    ]);
    return $voteDate['values'][0]['created_at'] ?? NULL;
  }

}
