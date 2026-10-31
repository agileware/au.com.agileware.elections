<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * The actions people take during an election, with the rules that apply.
 *
 * Every method takes the person acting, so the rules are enforced the same way
 * whether the action comes from a front end form, the API or a test. Failed
 * rules throw CRM_Core_Exception with a message that can be shown to the person.
 */
class CRM_Elections_Helper_Process {

  /**
   * Nominate someone for a position, or add a nomination ("second") to an existing one.
   *
   * @param int $electionId
   * @param int $positionId
   * @param int $nomineeId
   * @param int $nominatorId
   * @param string|null $reason
   *
   * @return array
   *   - nomination_id
   *   - is_eligible_candidate: has the nomination reached the required number of nominators?
   *   - already_nominated: TRUE when this nominator had already nominated the person
   *
   * @throws CRM_Core_Exception
   */
  public static function nominate($electionId, $positionId, $nomineeId, $nominatorId, $reason = NULL) {
    $election = findElectionById($electionId);
    if (!$election->isVisible || $election->is_deleted) {
      throw new CRM_Elections_Exception(E::ts('You are not authorized to access this page.'));
    }
    $election->assignDisplayOptions();
    if (!$election->onlineNominations) {
      throw new CRM_Elections_Exception(E::ts('Candidates for this election are chosen by the organisers, so there are no online nominations.'));
    }
    if (!$election->isNominationsInProgress) {
      throw new CRM_Elections_Exception(E::ts('Nominations are not open for this election.'));
    }
    if (empty($nominatorId) || !CRM_Elections_Helper_Eligibility::canNominate($electionId, $nominatorId)) {
      throw new CRM_Elections_Exception(E::ts('You are not allowed to nominate in this election.'));
    }
    if (!CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_position WHERE id = %1 AND election_id = %2 AND IFNULL(position_type, "election") <> "referendum"', [
      1 => [$positionId, 'Positive'],
      2 => [$electionId, 'Positive'],
    ])) {
      throw new CRM_Elections_Exception(E::ts('The selected position is not part of this election.'));
    }
    if (!CRM_Elections_Helper_Eligibility::canBeNominated($electionId, $nomineeId)) {
      throw new CRM_Elections_Exception(E::ts('Only people who can stand in this election can be nominated.'));
    }

    // One request at a time for each person and position: a repeated or simultaneous request must not
    // find "nothing there yet" twice and write two rows (the database refuses them too).
    $lock = Civi::lockManager()->acquire('data.elections.nominate.' . (int) $positionId . '.' . (int) $nomineeId, 10);
    if (!$lock->isAcquired()) {
      throw new CRM_Elections_Exception(E::ts('This nomination is being saved. Please wait a moment and try again.'));
    }
    try {
      return self::nominateLocked($election, $electionId, $positionId, $nomineeId, $nominatorId, $reason);
    }
    finally {
      $lock->release();
    }
  }

  private static function nominateLocked($election, $electionId, $positionId, $nomineeId, $nominatorId, $reason) {
    $existing = civicrm_api3('ElectionNomination', 'get', [
      'member_nominee' => $nomineeId,
      'election_position_id' => $positionId,
      'sequential' => TRUE,
      'options' => ['limit' => 0],
    ]);
    if ($existing['count'] > 0) {
      if ($existing['values'][0]['has_rejected_nomination'] == 1) {
        throw new CRM_Elections_Exception(E::ts('Selected member has withdrawn the nomination, You cannot nominate the member again for selected position.'));
      }
      $nominationId = (int) $existing['values'][0]['id'];
    }
    else {
      $nominationId = (int) civicrm_api3('ElectionNomination', 'create', [
        'member_nominee' => $nomineeId,
        'election_position_id' => $positionId,
      ])['id'];
    }

    $seconderParams = [
      'member_nominator' => $nominatorId,
      'election_nomination_id' => $nominationId,
    ];
    if (civicrm_api3('ElectionNominationSeconder', 'getcount', $seconderParams) != 0) {
      return [
        'nomination_id' => $nominationId,
        'is_eligible_candidate' => (bool) CRM_Core_DAO::getFieldValue('CRM_Elections_DAO_ElectionNomination', $nominationId, 'is_eligible_candidate'),
        'already_nominated' => TRUE,
      ];
    }

    $seconderParams['description'] = $reason;
    $seconder = civicrm_api3('ElectionNominationSeconder', 'create', $seconderParams);

    $isEligible = FALSE;
    $wasEligible = (bool) CRM_Core_DAO::getFieldValue('CRM_Elections_DAO_ElectionNomination', $nominationId, 'is_eligible_candidate');
    // Counted by distinct nominator, so nobody counts twice whatever happened to the rows.
    $totalNominations = (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(DISTINCT member_nominator) FROM civicrm_election_nomination_seconder WHERE election_nomination_id = %1', [1 => [$nominationId, 'Integer']]);
    if ($totalNominations >= $election->required_nominations) {
      civicrm_api3('ElectionNomination', 'create', ['id' => $nominationId, 'is_eligible_candidate' => 1]);
      $isEligible = TRUE;
    }

    self::createNominationActivity($nominatorId, $nomineeId, $nominationId, $seconder['id'], $isEligible);
    if ($isEligible && !$wasEligible) {
      CRM_Elections_Helper_Mailer::nomineeInvited($nominationId);
    }

    return [
      'nomination_id' => $nominationId,
      'is_eligible_candidate' => $isEligible,
      'already_nominated' => FALSE,
    ];
  }

  /**
   * A nominee accepts their own nomination, becoming a candidate.
   *
   * @param int $nominationId
   * @param int $contactId
   *   The person acting. Must be the nominee.
   * @param string|null $statement
   * @param string|null $statementUrl
   *
   * @throws CRM_Core_Exception
   */
  public static function accept($nominationId, $contactId, $statement = NULL, $statementUrl = NULL) {
    $nomination = self::loadOwnNomination($nominationId, $contactId);
    $election = findElectionById($nomination['election_position_id.election_id']);
    $writeIn = !empty($nomination['is_write_in']);
    self::assertElectionOpenToNominees($election, NULL, $writeIn);

    if (!CRM_Elections_Helper_Eligibility::canBeNominated($election->id, $contactId)) {
      throw new CRM_Elections_Exception(E::ts('Only people who can stand in this election can accept a nomination.'));
    }
    if (empty($nomination['is_eligible_candidate'])) {
      throw new CRM_Elections_Exception(E::ts('This nomination has not received enough nominations to be accepted.'));
    }
    if ($nomination['has_accepted_nomination'] == 1) {
      throw new CRM_Elections_Exception(E::ts('You already have accepted this nomination.'));
    }
    if ($nomination['has_rejected_nomination'] == 1) {
      throw new CRM_Elections_Exception(E::ts('You cannot accept a rejected nomination.'));
    }
    if (!CRM_Elections_Helper_Candidate::isValidStatementUrl($statementUrl)) {
      throw new CRM_Elections_Exception(E::ts('Enter a web address starting with http:// or https://'));
    }

    $statementUrl = trim((string) $statementUrl);
    // A person written in answers during voting: the one change to who is standing that is allowed then.
    CRM_Elections_Helper_Guard::$writeIn = $writeIn && $election->isVotingStarted;
    try {
      civicrm_api3('ElectionNomination', 'create', [
        'id' => $nominationId,
        'comments' => $statement,
        'statement_url' => $statementUrl !== '' ? $statementUrl : NULL,
        'has_accepted_nomination' => 1,
      ]);
    }
    finally {
      CRM_Elections_Helper_Guard::$writeIn = FALSE;
    }
  }

  /**
   * A nominee withdraws their own nomination (or candidacy) before voting starts.
   *
   * @param int $nominationId
   * @param int $contactId
   * @param string|null $comments
   *
   * @throws CRM_Core_Exception
   */
  public static function withdraw($nominationId, $contactId, $comments = NULL) {
    $nomination = self::loadOwnNomination($nominationId, $contactId);
    $election = findElectionById($nomination['election_position_id.election_id']);
    $writeIn = !empty($nomination['is_write_in']);
    self::assertElectionOpenToNominees($election, E::ts('You cannot withdraw the nomination once voting is started.'), $writeIn);

    if ($nomination['has_rejected_nomination'] == 1) {
      throw new CRM_Elections_Exception(E::ts('You have withdrawn this nomination.'));
    }
    if ($writeIn && $election->isVotingStarted && $nomination['has_accepted_nomination'] == 1) {
      // Voters have seen them on the ballot since they accepted, as with any other candidate.
      throw new CRM_Elections_Exception(E::ts('You cannot withdraw the nomination once voting is started.'));
    }

    CRM_Elections_Helper_Guard::$writeIn = $writeIn && $election->isVotingStarted;
    try {
      civicrm_api3('ElectionNomination', 'create', [
        'id' => $nominationId,
        'rejection_comments' => $comments,
        'has_rejected_nomination' => 1,
        'has_accepted_nomination' => 0,
      ]);
    }
    finally {
      CRM_Elections_Helper_Guard::$writeIn = FALSE;
    }
  }

  /**
   * Cast (or replace) a ballot.
   *
   * @param int $electionId
   * @param int $voterId
   * @param array $ranks
   *   Candidate nomination id => rank (1 is the first preference). Candidates
   *   left out are not ranked.
   *
   * @throws CRM_Core_Exception
   */
  public static function vote($electionId, $voterId, array $ranks) {
    $votes = [];
    foreach ($ranks as $nominationId => $rank) {
      $votes[] = ['rank' => $rank, 'election_nomination_id' => $nominationId];
    }
    civicrm_api3('ElectionVote', 'addvotes', [
      'election_id' => $electionId,
      'member_id' => $voterId,
      'votes' => $votes,
    ]);
  }

  /**
   * @param int $nominationId
   * @param int $contactId
   *
   * @return array
   * @throws CRM_Core_Exception
   */
  private static function loadOwnNomination($nominationId, $contactId) {
    if (empty($contactId)) {
      throw new CRM_Elections_Exception(E::ts('You are not authorized to access this page.'));
    }
    $nominations = civicrm_api3('ElectionNomination', 'get', [
      'id' => $nominationId,
      'member_nominee' => $contactId,
      'return' => ['is_eligible_candidate', 'has_accepted_nomination', 'has_rejected_nomination', 'is_write_in', 'election_position_id.election_id'],
    ]);
    if ($nominations['count'] != 1) {
      throw new CRM_Elections_Exception(E::ts('You are not authorized to access this page.'));
    }
    return reset($nominations['values']);
  }

  /**
   * @param CRM_Elections_BAO_Election $election
   * @param string|null $votingStartedMessage
   *
   * @throws CRM_Core_Exception
   */
  private static function assertElectionOpenToNominees($election, $votingStartedMessage = NULL, $writeIn = FALSE) {
    if (!$election->isVisible || $election->is_deleted) {
      throw new CRM_Elections_Exception(E::ts('You are not authorized to access this page.'));
    }
    if ($writeIn) {
      // A person written in can answer until voting closes.
      if ($election->isVotingEnded) {
        throw new CRM_Elections_Exception(CRM_Elections_Helper_WriteIn::closedMessage($election));
      }
      return;
    }
    if ($election->isVotingStarted) {
      throw new CRM_Elections_Exception($votingStartedMessage ?: E::ts('You cannot accept the nomination once voting has started.'));
    }
  }

  private static function createNominationActivity($nominatorId, $nomineeId, $nominationId, $seconderId, $isEligible) {
    $nomination = civicrm_api3('ElectionNomination', 'getsingle', [
      'return' => ['member_nominee.display_name', 'election_position_id.name'],
      'id' => $nominationId,
    ]);
    civicrm_api3('Activity', 'create', [
      'source_contact_id' => $nominatorId,
      'activity_type_id' => 'Nomination',
      'activity_date_time' => (new DateTime())->format('Y-m-d H:i:s'),
      'status_id' => 'Completed',
      'assignee_id' => $nominatorId,
      'target_id' => $nomineeId,
      'source_record_id' => $seconderId,
      'is_star' => $isEligible ? 1 : 0,
      'subject' => E::ts('Nominated %1 for the position of %2', [1 => $nomination['member_nominee.display_name'], 2 => $nomination['election_position_id.name']]),
    ]);
  }

}
