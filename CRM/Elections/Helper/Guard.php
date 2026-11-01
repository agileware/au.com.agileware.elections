<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * Rules that protect the ballots, the results and the settings that decide who may vote and how the
 * votes are counted. They are applied in hook_civicrm_pre, so they hold however a change is made:
 * a form, APIv3, APIv4, a SearchKit inline edit or drag and drop. Changes are written to the audit log
 * in hook_civicrm_post.
 */
class CRM_Elections_Helper_Guard {

  /**
   * Election settings that cannot change once ballots have been cast.
   */
  const LOCKED_ONCE_VOTED = ['allowed_groups', 'nomination_groups', 'candidate_groups', 'anonymize_votes', 'allow_revote', 'required_nominations', 'voting_method'];

  /**
   * Election settings that also cannot change once the results are generated. (Dates before that can
   * change, for example to extend voting, and every change is written to the audit log.)
   */
  const LOCKED_ONCE_RESULTS = ['voting_start_date', 'voting_end_date', 'has_results_generated'];

  /**
   * Election fields written to the audit log when they change.
   */
  const AUDITED_ELECTION_FIELDS = [
    'name', 'description', 'is_visible', 'is_deleted', 'visibility_start_date', 'visibility_end_date',
    'nomination_start_date', 'nomination_end_date', 'advertise_candidates_date', 'voting_start_date',
    'voting_end_date', 'result_date', 'allowed_groups', 'nomination_groups', 'candidate_groups', 'anonymize_votes', 'allow_revote',
    'allow_checksum_access', 'required_nominations', 'has_results_generated', 'show_photos',
    'show_statements', 'show_nominators', 'name_format', 'voting_method', 'send_emails', 'allow_online_nominations', 'nomination_message_template_id',
    'results_header_text', 'results_footer_text', 'results_logo_file_id', 'results_logo_alt', 'celebration_enabled', 'celebration_effect', 'celebration_days',
  ];

  const DATE_FIELDS = [
    'visibility_start_date', 'visibility_end_date', 'nomination_start_date', 'nomination_end_date',
    'advertise_candidates_date', 'voting_start_date', 'voting_end_date', 'result_date',
  ];

  /**
   * The record as it was before a change, for the audit entry written afterwards.
   *
   * @var array
   */
  private static $before = [];

  /**
   * TRUE only while a ballot is being saved by ElectionVote.addvotes, which checks who may vote, the
   * candidates and the method's rules. Ballot rows cannot be added any other way.
   *
   * @var bool
   */
  public static $castingBallot = FALSE;

  /**
   * TRUE only while a write-in is being created or answered through CRM_Elections_Helper_WriteIn and
   * CRM_Elections_Helper_Process. It is the one way the candidates of an election can change once
   * voting has started.
   *
   * @var bool
   */
  public static $writeIn = FALSE;

  /**
   * TRUE only while an active election whose nominations have started is being withdrawn through the
   * Withdraw form, which asks for the election's name. Switching such an election off lets it be edited
   * again, so no other write path may do it.
   *
   * @var bool
   */
  public static $withdrawing = FALSE;

  public static function pre($op, $objectName, $id, &$params) {
    switch ($objectName) {
      case 'ElectionVote':
        self::guardVote($op, $id, (array) $params);
        break;

      case 'ElectionResult':
        if (!CRM_Elections_Helper_Results::$writing) {
          throw new CRM_Elections_Exception(E::ts('Election results can only be written when they are generated.'));
        }
        break;

      case 'Election':
        if (in_array($op, ['create', 'edit'], TRUE) && !empty($params['voting_method']) && !array_key_exists($params['voting_method'], CRM_Elections_VotingMethod::getOptions())) {
          throw new CRM_Elections_Exception(E::ts('Unknown voting method.'));
        }
        if ($op === 'edit' && $id) {
          self::guardElectionEdit($id, (array) $params);
        }
        self::remember('civicrm_election', $objectName, $op, $id);
        break;

      case 'ElectionPosition':
      case 'ElectionNomination':
        if ($objectName === 'ElectionPosition' && in_array($op, ['create', 'edit'], TRUE) && array_key_exists('write_ins_per_seat', (array) $params) && $params['write_ins_per_seat'] !== NULL && $params['write_ins_per_seat'] !== '') {
          if (!CRM_Utils_Rule::positiveInteger($params['write_ins_per_seat']) && (string) $params['write_ins_per_seat'] !== '0' || (int) $params['write_ins_per_seat'] > CRM_Elections_Helper_WriteIn::MAX_PER_SEAT) {
            throw new CRM_Elections_Exception(E::ts('Write-ins per seat must be a whole number from 0 to %1.', [1 => CRM_Elections_Helper_WriteIn::MAX_PER_SEAT]));
          }
        }
        if ($objectName === 'ElectionPosition' && in_array($op, ['create', 'edit'], TRUE)) {
          self::guardPositionSettings($id, (array) $params);
        }
        if ($objectName === 'ElectionNomination' && $op === 'create') {
          self::guardNominationCreate((array) $params);
        }
        if ($objectName === 'ElectionNomination' && in_array($op, ['create', 'edit'], TRUE) && !empty($params['statement_url']) && !CRM_Elections_Helper_Candidate::isValidStatementUrl($params['statement_url'])) {
          throw new CRM_Elections_Exception(E::ts('Enter a web address starting with http:// or https://'));
        }
        self::remember($objectName === 'ElectionPosition' ? 'civicrm_election_position' : 'civicrm_election_nomination', $objectName, $op, $id);
        break;

      case 'ElectionOption':
        self::guardOption($op, $id, (array) $params);
        break;

      case 'ElectionOptionVote':
        self::guardOptionVote($op, $id, (array) $params);
        break;

      case 'Individual':
      case 'Organization':
      case 'Household':
        if ($op === 'delete' && $id) {
          self::guardContactDelete((int) $id);
        }
        break;
    }
  }

  public static function post($op, $objectName, $id, &$object) {
    switch ($objectName) {
      case 'Election':
        self::auditChange($op, 'election', $id, (int) $id, self::AUDITED_ELECTION_FIELDS, $object);
        break;

      case 'ElectionPosition':
        if (in_array($op, ['create', 'edit'], TRUE) && CRM_Core_DAO::singleValueQuery('SELECT position_type FROM civicrm_election_position WHERE id = %1', [1 => [(int) $id, 'Integer']]) === CRM_Elections_BAO_ElectionPosition::TYPE_REFERENDUM
          && !CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_option WHERE election_position_id = %1', [1 => [(int) $id, 'Integer']])) {
          // A question always has its Yes and No.
          CRM_Elections_BAO_ElectionPosition::saveOptions((int) $id);
        }
        self::auditChange($op, 'position', $id, self::electionOf('position', $id, $object), ['name', 'quantity', 'sortorder', 'description', 'result_status', 'position_type', 'pass_threshold', 'quorum_percent', 'resolution_text', 'write_ins_per_seat'], $object);
        break;

      case 'ElectionNomination':
        self::auditChange($op, 'nomination', $id, self::electionOf('nomination', $id, $object), ['member_nominee', 'is_eligible_candidate', 'has_accepted_nomination', 'has_rejected_nomination', 'comments', 'statement_url', 'rejection_comments', 'is_write_in'], $object);
        break;

      case 'ElectionOptionVote':
      case 'ElectionVote':
        // Only the removal of a ballot by someone other than the voter replacing it is worth a record;
        // adding ballots is not logged one by one, as that could be matched to the time a person voted.
        if ($op === 'delete' && !CRM_Elections_Helper_Integrity::$replacingBallot) {
          $electionId = self::$before['vote'][$id] ?? NULL;
          if ($electionId) {
            CRM_Elections_Helper_Audit::log($electionId, 'vote_removed', ['rows' => 1]);
          }
        }
        break;
    }
  }

  // ----------------------------------------------------------------- referendum options and choices

  /**
   * The settings of a position that make it a referendum question: the type, the pass threshold and the quorum.
   */
  private static function guardPositionSettings($id, array $params) {
    if (!empty($params['position_type']) && !array_key_exists($params['position_type'], CRM_Elections_BAO_ElectionPosition::getTypes())) {
      throw new CRM_Elections_Exception(E::ts('Unknown position type.'));
    }
    foreach (['pass_threshold' => [0, 100, E::ts('The pass threshold must be a percentage above 0 and up to 100.')], 'quorum_percent' => [-1, 100, E::ts('The quorum must be a percentage from 0 to 100.')]] as $field => [$min, $max, $message]) {
      if (array_key_exists($field, $params) && $params[$field] !== NULL && $params[$field] !== '') {
        if (!is_numeric($params[$field]) || (float) $params[$field] <= $min || (float) $params[$field] > $max) {
          throw new CRM_Elections_Exception($message);
        }
      }
    }
    if ($id && ($params['position_type'] ?? NULL) === CRM_Elections_BAO_ElectionPosition::TYPE_REFERENDUM
      && CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_nomination WHERE election_position_id = %1', [1 => [(int) $id, 'Integer']])) {
      throw new CRM_Elections_Exception(E::ts('A position that has nominations cannot become a referendum question.'));
    }
  }

  /**
   * The wording of a question's choices is part of the question: it cannot change once the election has started.
   */
  private static function guardOption($op, $id, array $params) {
    $positionId = $params['election_position_id'] ?? NULL;
    if (!$positionId && $id) {
      $positionId = CRM_Core_DAO::getFieldValue('CRM_Elections_DAO_ElectionOption', $id, 'election_position_id');
    }
    $electionId = $positionId ? CRM_Core_DAO::getFieldValue('CRM_Elections_DAO_ElectionPosition', $positionId, 'election_id') : NULL;
    if ($electionId) {
      CRM_Elections_BAO_ElectionPosition::assertCanChange($electionId);
    }
    if ($op === 'create' && !empty($params['kind']) && !in_array($params['kind'], ['yes', 'no', 'abstain'], TRUE)) {
      throw new CRM_Elections_Exception(E::ts('A referendum choice is yes, no or abstain.'));
    }
  }

  /**
   * A choice on a question is part of the ballot: it follows the rules of every other ballot row.
   */
  private static function guardOptionVote($op, $id, array $params) {
    if ($op === 'edit') {
      throw new CRM_Elections_Exception(E::ts('A vote cannot be changed. A voter changes their vote by voting again.'));
    }
    $positionId = $params['election_position_id'] ?? NULL;
    if ($op === 'delete' && $id) {
      $positionId = CRM_Core_DAO::getFieldValue('CRM_Elections_DAO_ElectionOptionVote', $id, 'election_position_id');
      $electionId = $positionId ? (int) CRM_Core_DAO::getFieldValue('CRM_Elections_DAO_ElectionPosition', $positionId, 'election_id') : 0;
      self::$before['vote'][$id] = $electionId;
    }
    $electionId = $positionId ? (int) CRM_Core_DAO::getFieldValue('CRM_Elections_DAO_ElectionPosition', $positionId, 'election_id') : 0;
    if (!$electionId) {
      return;
    }
    $election = findElectionById($electionId, FALSE);
    if ($election->is_deleted || !$election->is_visible || !$election->isVotingStarted || $election->isVotingEnded || $election->has_results_generated) {
      throw new CRM_Elections_Exception(E::ts('Votes can only be added or removed while voting is open.'));
    }
    if ($op === 'create' && !self::$castingBallot) {
      throw new CRM_Elections_Exception(E::ts('A vote can only be cast through the ballot (ElectionVote.addvotes), which checks who may vote and what may be chosen.'));
    }
  }

  // ----------------------------------------------------------------- votes and results

  private static function guardVote($op, $id, array $params) {
    if ($op === 'edit') {
      throw new CRM_Elections_Exception(E::ts('A vote cannot be changed. A voter changes their vote by voting again.'));
    }
    $electionId = NULL;
    if ($op === 'create') {
      $nominationId = (int) ($params['election_nomination_id'] ?? 0);
      $electionId = (int) CRM_Core_DAO::singleValueQuery('SELECT p.election_id FROM civicrm_election_nomination n INNER JOIN civicrm_election_position p ON p.id = n.election_position_id WHERE n.id = %1', [1 => [$nominationId, 'Integer']]);
    }
    elseif ($op === 'delete' && $id) {
      $electionId = (int) CRM_Core_DAO::singleValueQuery('SELECT p.election_id FROM civicrm_election_vote v INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id INNER JOIN civicrm_election_position p ON p.id = n.election_position_id WHERE v.id = %1', [1 => [(int) $id, 'Integer']]);
      self::$before['vote'][$id] = $electionId;
    }
    if (!$electionId) {
      return;
    }
    $election = findElectionById($electionId, FALSE);
    if ($election->is_deleted || !$election->is_visible || !$election->isVotingStarted || $election->isVotingEnded || $election->has_results_generated) {
      throw new CRM_Elections_Exception(E::ts('Votes can only be added or removed while voting is open.'));
    }
    if ($op === 'create' && !self::$castingBallot) {
      throw new CRM_Elections_Exception(E::ts('A vote can only be cast through the ballot (ElectionVote.addvotes), which checks who may vote and what may be chosen.'));
    }
  }

  /**
   * Once voting has started nobody can be added to the ballot, except a person a voter writes in.
   */
  private static function guardNominationCreate(array $params) {
    if (self::$writeIn || empty($params['election_position_id'])) {
      return;
    }
    $electionId = (int) CRM_Core_DAO::singleValueQuery('SELECT election_id FROM civicrm_election_position WHERE id = %1', [1 => [(int) $params['election_position_id'], 'Integer']]);
    if (!$electionId) {
      return;
    }
    $election = findElectionById($electionId, FALSE);
    if ($election->isVotingStarted) {
      throw new CRM_Elections_Exception(E::ts('Who is standing cannot be changed once voting has started.'));
    }
  }

  // ----------------------------------------------------------------- election settings

  private static function guardElectionEdit($id, array $params) {
    $current = CRM_Core_DAO::executeQuery('SELECT * FROM civicrm_election WHERE id = %1', [1 => [(int) $id, 'Integer']]);
    if (!$current->fetch()) {
      return;
    }
    if (array_key_exists('is_visible', $params) && !(int) $params['is_visible'] && (int) $current->is_visible && !self::$withdrawing
      && !empty($current->nomination_start_date) && strtotime($current->nomination_start_date) <= time()) {
      throw new CRM_Elections_Exception(E::ts('This election is active and its nominations have started, so it cannot be switched off here. Use Withdraw on the Manage Elections page.'));
    }
    $resultsGenerated = (bool) $current->has_results_generated;
    $hasBallots = $resultsGenerated
      || (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_vote v INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id INNER JOIN civicrm_election_position p ON p.id = n.election_position_id WHERE p.election_id = %1', [1 => [(int) $id, 'Integer']]) > 0
      || (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_option_vote ov INNER JOIN civicrm_election_position p ON p.id = ov.election_position_id WHERE p.election_id = %1', [1 => [(int) $id, 'Integer']]) > 0;
    if (!$hasBallots) {
      return;
    }
    $locked = self::LOCKED_ONCE_VOTED;
    if ($resultsGenerated) {
      $locked = array_merge($locked, self::LOCKED_ONCE_RESULTS);
    }
    // The system itself sets has_results_generated from 0 to 1 when it generates the results.
    foreach ($locked as $field) {
      if (!array_key_exists($field, $params)) {
        continue;
      }
      if ($field === 'has_results_generated' && !$resultsGenerated) {
        continue;
      }
      if (self::normalise($field, $params[$field]) !== self::normalise($field, $current->$field)) {
        throw new CRM_Elections_Exception(E::ts('%1 cannot be changed once people have voted or the results are generated.', [1 => $field]));
      }
    }
  }

  private static function normalise($field, $value) {
    if ($field === 'voting_method') {
      return CRM_Elections_VotingMethod::normalise($value);
    }
    if ($value === NULL || $value === '') {
      return '';
    }
    if (in_array($field, self::DATE_FIELDS, TRUE)) {
      $time = strtotime((string) $value);
      return $time === FALSE ? (string) $value : (string) $time;
    }
    if (is_bool($value)) {
      return $value ? '1' : '0';
    }
    if (is_array($value)) {
      $value = implode(',', $value);
    }
    $value = (string) $value;
    if (in_array($field, ['allowed_groups', 'nomination_groups', 'candidate_groups'], TRUE)) {
      $parts = array_filter(array_map('trim', explode(',', str_replace(CRM_Core_DAO::VALUE_SEPARATOR, ',', $value))), 'strlen');
      sort($parts);
      return implode(',', $parts);
    }
    return $value;
  }

  // ----------------------------------------------------------------- contacts

  private static function guardContactDelete($contactId) {
    $name = CRM_Core_DAO::singleValueQuery('
      SELECT e.name FROM civicrm_election e
      WHERE e.is_deleted = 0 AND (e.is_visible = 1 OR e.has_results_generated = 1) AND (
        EXISTS (SELECT 1 FROM civicrm_election_nomination n INNER JOIN civicrm_election_position p ON p.id = n.election_position_id WHERE p.election_id = e.id AND n.member_nominee = %1)
        OR EXISTS (SELECT 1 FROM civicrm_election_nomination_seconder s INNER JOIN civicrm_election_nomination n ON n.id = s.election_nomination_id INNER JOIN civicrm_election_position p ON p.id = n.election_position_id WHERE p.election_id = e.id AND s.member_nominator = %1)
        OR EXISTS (SELECT 1 FROM civicrm_election_vote v INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id INNER JOIN civicrm_election_position p ON p.id = n.election_position_id WHERE p.election_id = e.id AND v.member_id = %1)
        OR EXISTS (SELECT 1 FROM civicrm_election_option_vote ov INNER JOIN civicrm_election_position p ON p.id = ov.election_position_id WHERE p.election_id = e.id AND ov.member_id = %1)
      )
      LIMIT 1
    ', [1 => [$contactId, 'Integer']]);
    if ($name) {
      throw new CRM_Elections_Exception(E::ts('This contact is a candidate, nominator or voter in the election "%1" and cannot be deleted. Deleting them would change the election.', [1 => $name]));
    }
  }

  // ----------------------------------------------------------------- audit

  private static function remember($table, $objectName, $op, $id) {
    if (in_array($op, ['edit', 'delete'], TRUE) && $id) {
      $dao = CRM_Core_DAO::executeQuery("SELECT * FROM `$table` WHERE id = %1", [1 => [(int) $id, 'Integer']]);
      if ($dao->fetch()) {
        self::$before[$objectName][(int) $id] = $dao->toArray();
        if ($objectName === 'ElectionNomination') {
          self::$before[$objectName][(int) $id]['_election_id'] = (int) CRM_Core_DAO::singleValueQuery('SELECT election_id FROM civicrm_election_position WHERE id = %1', [1 => [(int) $dao->election_position_id, 'Integer']]);
        }
        if ($objectName === 'ElectionPosition') {
          self::$before[$objectName][(int) $id]['_election_id'] = (int) $dao->election_id;
        }
      }
    }
  }

  private static function electionOf($kind, $id, $object) {
    $key = $kind === 'position' ? 'ElectionPosition' : 'ElectionNomination';
    if (!empty(self::$before[$key][(int) $id]['_election_id'])) {
      return self::$before[$key][(int) $id]['_election_id'];
    }
    if ($kind === 'position') {
      return (int) ($object->election_id ?? 0) ?: NULL;
    }
    $positionId = (int) ($object->election_position_id ?? 0);
    return $positionId ? (int) CRM_Core_DAO::singleValueQuery('SELECT election_id FROM civicrm_election_position WHERE id = %1', [1 => [$positionId, 'Integer']]) : NULL;
  }

  private static function auditChange($op, $kind, $id, $electionId, array $fields, $object) {
    if ($kind === 'nomination' && $op === 'create' && self::$writeIn) {
      // A voter writing someone in is not recorded when it happens: the time and the acting person would say
      // whom that voter chose. The write-ins and how each ended are summarised when the results are generated.
      return;
    }
    $key = ['election' => 'Election', 'position' => 'ElectionPosition', 'nomination' => 'ElectionNomination'][$kind];
    $before = self::$before[$key][(int) $id] ?? [];
    $changes = [];
    foreach ($fields as $field) {
      $new = $object->$field ?? NULL;
      $old = $before[$field] ?? NULL;
      if ($op === 'edit' && $old !== NULL && $new === NULL) {
        continue;
      }
      if ($op === 'edit' && self::normalise($field, $old) === self::normalise($field, $new)) {
        continue;
      }
      if ($op === 'create' && ($new === NULL || $new === '')) {
        continue;
      }
      $changes[$field] = $op === 'edit' ? ['from' => self::clip($old), 'to' => self::clip($new)] : self::clip($new);
    }
    if ($op === 'edit' && !$changes) {
      return;
    }
    $details = ['id' => (int) $id] + ($changes ? ['changes' => $changes] : []);
    if ($kind === 'election' && $op === 'create') {
      $details = ['id' => (int) $id, 'name' => self::clip($object->name ?? '')];
    }
    CRM_Elections_Helper_Audit::log($electionId ?: NULL, $kind . '_' . ($op === 'edit' ? 'changed' : ($op === 'create' ? 'created' : 'deleted')), $details);
    unset(self::$before[$key][(int) $id]);
  }

  private static function clip($value) {
    if ($value === NULL) {
      return NULL;
    }
    $value = (string) $value;
    return mb_strlen($value) > 300 ? mb_substr($value, 0, 300) . '…' : $value;
  }

}
