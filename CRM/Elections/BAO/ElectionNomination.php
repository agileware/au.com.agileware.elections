<?php
use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_BAO_ElectionNomination extends CRM_Elections_DAO_ElectionNomination {

  /**
   * Fields that decide who is on the ballot. They are frozen once voting starts.
   */
  const BALLOT_FIELDS = ['is_eligible_candidate', 'has_accepted_nomination', 'has_rejected_nomination', 'member_nominee', 'election_position_id'];

  /**
   * Fields an administrator can still correct while voting is open.
   */
  const STATEMENT_FIELDS = ['comments', 'statement_url', 'rejection_comments'];

  /**
   * Keep the ballot honest, whichever way a nomination is changed (forms, API, SearchKit
   * inline edits): who stands cannot change once voting starts, and nothing changes once
   * voting has ended.
   *
   * @param int $nominationId
   * @param array $changes
   *   Field => new value. Only fields that really change count.
   *
   * @throws CRM_Core_Exception
   */
  public static function assertCanChange($nominationId, array $changes) {
    $current = CRM_Core_DAO::executeQuery('
      SELECT n.is_eligible_candidate, n.has_accepted_nomination, n.has_rejected_nomination, n.member_nominee, n.election_position_id, n.comments, n.statement_url, n.rejection_comments, p.election_id
      FROM civicrm_election_nomination n
      INNER JOIN civicrm_election_position p ON p.id = n.election_position_id
      WHERE n.id = %1
    ', [1 => [$nominationId, 'Positive']]);
    if (!$current->fetch()) {
      return;
    }

    $changed = [];
    foreach ($changes as $field => $value) {
      if (in_array($field, array_merge(self::BALLOT_FIELDS, self::STATEMENT_FIELDS), TRUE) && (string) $value !== (string) $current->$field) {
        $changed[] = $field;
      }
    }
    if (empty($changed)) {
      return;
    }

    $election = findElectionById($current->election_id, FALSE);
    if ($election->isVotingEnded) {
      throw new CRM_Core_Exception(E::ts('Nominations cannot be changed once voting has ended.'));
    }
    if ($election->isVotingStarted && array_intersect($changed, self::BALLOT_FIELDS)) {
      throw new CRM_Core_Exception(E::ts('Who is standing cannot be changed once voting has started.'));
    }
  }

  /**
   * Add a candidate directly, as an administrator. For elections where nominations are made
   * outside the website, so there is no online nomination stage.
   *
   * @param int $positionId
   * @param int $nomineeId
   * @param bool $accepted
   *   TRUE to record that the candidate has accepted, so they appear on the ballot.
   * @param string|null $statement
   *
   * @return array
   *   The nomination.
   *
   * @throws CRM_Core_Exception
   */
  public static function addCandidate($positionId, $nomineeId, $accepted = TRUE, $statement = NULL) {
    $electionId = (int) CRM_Core_DAO::getFieldValue('CRM_Elections_DAO_ElectionPosition', $positionId, 'election_id');
    if (!$electionId) {
      throw new CRM_Core_Exception(E::ts('The selected position is not part of this election.'));
    }
    $election = findElectionById($electionId);
    if ($election->is_deleted) {
      throw new CRM_Core_Exception(E::ts('Candidates cannot be added to a deleted election.'));
    }
    if ($election->isVotingStarted) {
      throw new CRM_Core_Exception(E::ts('Candidates cannot be added once voting has started.'));
    }
    if (!CRM_Elections_Helper_Eligibility::canBeNominated($electionId, $nomineeId)) {
      throw new CRM_Core_Exception(E::ts('Only people who can stand in this election can be candidates. Add the person to one of the election groups first.'));
    }

    $existing = civicrm_api3('ElectionNomination', 'get', ['member_nominee' => $nomineeId, 'election_position_id' => $positionId, 'sequential' => 1]);
    $values = [
      'is_eligible_candidate' => 1,
      'has_accepted_nomination' => $accepted ? 1 : 0,
      'has_rejected_nomination' => 0,
      'comments' => $statement,
    ];
    if ($existing['count']) {
      $values['id'] = $existing['values'][0]['id'];
    }
    else {
      $values['member_nominee'] = $nomineeId;
      $values['election_position_id'] = $positionId;
    }
    // APIv4 skips the nomination window rule of APIv3: an administrator may add candidates at any time before voting.
    $result = civicrm_api4('ElectionNomination', empty($values['id']) ? 'create' : 'update', empty($values['id']) ? ['values' => $values, 'checkPermissions' => FALSE] : ['values' => $values, 'where' => [['id', '=', $values['id']]], 'checkPermissions' => FALSE]);
    $nomination = $result->first();
    if (!$accepted) {
      // An administrator added them but they have not said yes: ask them to respond.
      CRM_Elections_Helper_Mailer::nomineeInvited($nomination['id']);
    }
    return $nomination;
  }

}
