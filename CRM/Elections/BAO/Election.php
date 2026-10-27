<?php
use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_BAO_Election extends CRM_Elections_DAO_Election {

  public static $RESULTS_EQUAL_SEATS  = 'equalseats';
  public static $RESULTS_MORE_SEATS  = 'moreseats';
  public static $RESULTS_MAJORITY  = 'majority';
  public static $RESULTS_NO_NOMINATIONS  = 'nonominations';
  public static $RESULTS_NO_MAJORITY = 'tie';
  public static $RESULTS_MOST_VOTES = 'mostvotes';
  public static $RESULTS_PASSED = 'passed';
  public static $RESULTS_FAILED = 'failed';
  public static $RESULTS_NO_QUORUM = 'noquorum';

  public static function getResultStatues() {
    return [
      'results_no_majority' => self::$RESULTS_NO_MAJORITY,
      'results_equal_seats' => self::$RESULTS_EQUAL_SEATS,
      'results_more_seats' => self::$RESULTS_MORE_SEATS,
      'results_majority' => self::$RESULTS_MAJORITY,
      'results_no_nominations' => self::$RESULTS_NO_NOMINATIONS,
      'results_most_votes' => self::$RESULTS_MOST_VOTES,
      'results_passed' => self::$RESULTS_PASSED,
      'results_failed' => self::$RESULTS_FAILED,
      'results_no_quorum' => self::$RESULTS_NO_QUORUM,
    ];
  }

  const STATEMENTS_NONE = 'none';

  const STATEMENTS_LINK = 'link';

  const STATEMENTS_INLINE = 'inline';

  /**
   * Options for how candidate statements are shown on ballots and nominations.
   *
   * @return array
   */
  public static function getStatementDisplayOptions() {
    return [
      self::STATEMENTS_NONE => E::ts('Not on the ballot'),
      self::STATEMENTS_LINK => E::ts('Expandable "Read statement" on the ballot'),
      self::STATEMENTS_INLINE => E::ts('In full on the ballot'),
    ];
  }

  /**
   * The stage the election is in today.
   *
   * @return array
   *   `key` (inactive, upcoming, nominations, advertise, voting, closed, results) and a translated `label`.
   */
  public function getStage() {
    $this->assignStatues();
    if ($this->is_deleted) {
      return ['key' => 'deleted', 'label' => E::ts('Deleted')];
    }
    if (!$this->is_visible) {
      return ['key' => 'inactive', 'label' => E::ts('Inactive')];
    }
    if ($this->isResultsOut) {
      return ['key' => 'results', 'label' => E::ts('Results published')];
    }
    if ($this->isVotingEnded) {
      return ['key' => 'closed', 'label' => E::ts('Voting closed')];
    }
    if ($this->isVotingStarted) {
      return ['key' => 'voting', 'label' => E::ts('Voting open')];
    }
    if ($this->advertiseCandidatesStarted) {
      return ['key' => 'advertise', 'label' => E::ts('Candidates advertised')];
    }
    if ($this->isNominationsStarted) {
      return ['key' => $this->isNominationsInProgress ? 'nominations' : 'advertise', 'label' => $this->isNominationsInProgress ? E::ts('Nominations open') : E::ts('Nominations closed')];
    }
    return ['key' => 'upcoming', 'label' => E::ts('Not started')];
  }

  /**
   * Counts shown on the overview: positions, nominations, candidates and ballots cast.
   *
   * @param int $electionId
   *
   * @return array
   */
  public static function getCounts($electionId) {
    $p = [1 => [$electionId, 'Positive']];
    return [
      'positions' => (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_position WHERE election_id = %1', $p),
      'nominations' => (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_nomination n INNER JOIN civicrm_election_position pos ON pos.id = n.election_position_id WHERE pos.election_id = %1', $p),
      'eligible' => (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_nomination n INNER JOIN civicrm_election_position pos ON pos.id = n.election_position_id WHERE pos.election_id = %1 AND n.is_eligible_candidate = 1', $p),
      'candidates' => (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_nomination n INNER JOIN civicrm_election_position pos ON pos.id = n.election_position_id WHERE pos.election_id = %1 AND n.is_eligible_candidate = 1 AND n.has_accepted_nomination = 1', $p),
      'ballots' => max(
        (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(DISTINCT member_id) FROM (
          SELECT v.member_id FROM civicrm_election_vote v INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id INNER JOIN civicrm_election_position pos ON pos.id = n.election_position_id WHERE pos.election_id = %1
          UNION
          SELECT ov.member_id FROM civicrm_election_option_vote ov INNER JOIN civicrm_election_position pos ON pos.id = ov.election_position_id WHERE pos.election_id = %1
        ) t WHERE member_id IS NOT NULL', $p),
        // Anonymised votes no longer say who cast them, but each voter still has a Vote activity.
        self::countVoters($electionId)
      ),
      'votes' => (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_vote v INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id INNER JOIN civicrm_election_position pos ON pos.id = n.election_position_id WHERE pos.election_id = %1', $p)
        + (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_option_vote ov INNER JOIN civicrm_election_position pos ON pos.id = ov.election_position_id WHERE pos.election_id = %1', $p),
    ];
  }

  /**
   * How many different people voted in the election, from their Vote activities.
   *
   * Unlike the votes themselves, the activities are kept when the votes are anonymised.
   *
   * @param int $electionId
   *
   * @return int
   */
  public static function countVoters($electionId) {
    $sourceType = (int) CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Source');
    $voteType = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Vote');
    if (!$voteType) {
      return 0;
    }
    return (int) CRM_Core_DAO::singleValueQuery('
      SELECT COUNT(DISTINCT ac.contact_id)
      FROM civicrm_activity a
      INNER JOIN civicrm_activity_contact ac ON ac.activity_id = a.id AND ac.record_type_id = %2
      WHERE a.activity_type_id = %3 AND a.source_record_id = %1 AND a.is_deleted = 0 AND a.is_test = 0
    ', [1 => [(int) $electionId, 'Positive'], 2 => [$sourceType, 'Integer'], 3 => [(int) $voteType, 'Integer']]);
  }

  /**
   * Turnout for each position: candidates standing and ballots that ranked someone for it.
   *
   * Counts only, never who voted for whom. Once votes are anonymised the ballots for a position
   * can no longer be told apart, so the count for each position is NULL.
   *
   * @param int $electionId
   *
   * @return array[] Each with the keys name, candidates and ballots (int or NULL).
   */
  public static function getTurnout($electionId) {
    $dao = CRM_Core_DAO::executeQuery('
      SELECT pos.id, pos.name, pos.position_type,
        (SELECT COUNT(*) FROM civicrm_election_nomination n WHERE n.election_position_id = pos.id AND n.is_eligible_candidate = 1 AND n.has_accepted_nomination = 1) AS candidates,
        (SELECT COUNT(*) FROM civicrm_election_vote v INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id WHERE n.election_position_id = pos.id)
          + (SELECT COUNT(*) FROM civicrm_election_option_vote ov WHERE ov.election_position_id = pos.id) AS vote_rows,
        (SELECT COUNT(DISTINCT member_id) FROM (
          SELECT v.member_id, n.election_position_id FROM civicrm_election_vote v INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id
          UNION
          SELECT ov.member_id, ov.election_position_id FROM civicrm_election_option_vote ov
        ) t WHERE t.election_position_id = pos.id AND t.member_id IS NOT NULL) AS ballots
      FROM civicrm_election_position pos
      WHERE pos.election_id = %1
      ORDER BY pos.sortorder ASC, pos.id ASC
    ', [1 => [(int) $electionId, 'Positive']]);
    $rows = [];
    while ($dao->fetch()) {
      $rows[] = [
        'name' => $dao->name,
        'isQuestion' => $dao->position_type === CRM_Elections_BAO_ElectionPosition::TYPE_REFERENDUM,
        'candidates' => (int) $dao->candidates,
        'ballots' => ((int) $dao->vote_rows > 0 && (int) $dao->ballots === 0) ? NULL : (int) $dao->ballots,
      ];
    }
    return $rows;
  }

  /**
   * What the administrator should do next, in plain words.
   *
   * @param array $stage
   * @param array $counts
   *
   * @return string
   */
  public static function getNextStep(array $stage, array $counts) {
    switch ($stage['key']) {
      case 'deleted':
        return E::ts('This election has been deleted.');

      case 'inactive':
        return $counts['positions'] < 1
          ? E::ts('Add at least one position, then activate the election so people can see it.')
          : E::ts('Activate the election when it is ready for people to see.');

      case 'upcoming':
        return E::ts('Nominations have not opened yet. You can still change the settings and positions.');

      case 'nominations':
        return E::ts('Nominations are open. People nominate and second each other on the website; you can also add candidates directly.');

      case 'advertise':
        return E::ts('Nominees are accepting or withdrawing. Check the candidates before voting opens.');

      case 'voting':
        return E::ts('Voting is open. Who is standing is now fixed; you can still correct statements.');

      case 'closed':
        return E::ts('Voting has closed. Results are calculated by the scheduled job and published on the results date.');

      default:
        return E::ts('The results are published.');
    }
  }

  /**
   * Activating an election (making it visible) needs at least one position.
   *
   * Used by every write path - the activate form, the API and inline edits in
   * SearchKit - so the rule cannot be bypassed.
   *
   * @param int $electionId
   *
   * @throws CRM_Core_Exception
   */
  public static function assertCanActivate($electionId) {
    $positions = (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_election_position WHERE election_id = %1', [1 => [$electionId, 'Positive']]);
    if ($positions < 1) {
      throw new CRM_Core_Exception(E::ts('At least one Position must be defined before the Election can be active.'));
    }
  }

  /**
   * Copy an election, with its positions, as a new inactive election.
   *
   * Nominations, votes and results are not copied. All dates move by the same
   * amount so the stages keep their spacing.
   *
   * @param int $electionId
   *   The election to copy.
   * @param string|null $name
   *   Name of the copy. Defaults to "Copy of <name>".
   * @param int|null $shiftSeconds
   *   How far to move the dates. Defaults to the time left until the copy's
   *   visibility start would be a day from now.
   *
   * @return array
   *   The new election.
   *
   * @throws CRM_Core_Exception
   */
  public static function duplicate($electionId, $name = NULL, $shiftSeconds = NULL) {
    $source = civicrm_api3('Election', 'getsingle', ['id' => $electionId]);
    $dateFields = ['visibility_start_date', 'visibility_end_date', 'nomination_start_date', 'nomination_end_date', 'advertise_candidates_date', 'voting_start_date', 'voting_end_date', 'result_date'];

    if ($shiftSeconds === NULL) {
      $shiftSeconds = strtotime('+1 day') - strtotime($source['visibility_start_date']);
    }
    $values = [
      'name' => $name ?: E::ts('Copy of %1', [1 => $source['name']]),
      'description' => $source['description'] ?? NULL,
      'is_visible' => 0,
      'is_deleted' => 0,
      'has_results_generated' => 0,
      'created_by' => CRM_Core_Session::singleton()->getLoggedInContactID(),
    ];
    foreach ($dateFields as $field) {
      $values[$field] = date('Y-m-d H:i:s', strtotime($source[$field]) + $shiftSeconds);
    }
    foreach (['anonymize_votes', 'required_nominations', 'allow_revote', 'allow_checksum_access', 'allowed_groups', 'nomination_groups', 'candidate_groups', 'send_emails', 'show_photos', 'show_statements', 'show_nominators', 'name_format', 'voting_method'] as $field) {
      if (array_key_exists($field, $source)) {
        $values[$field] = $source[$field];
      }
    }
    $copy = civicrm_api3('Election', 'create', $values);
    $copyId = (int) $copy['id'];

    $positions = civicrm_api3('ElectionPosition', 'get', ['election_id' => $electionId, 'options' => ['limit' => 0, 'sort' => 'sortorder ASC']]);
    foreach ($positions['values'] as $position) {
      civicrm_api3('ElectionPosition', 'create', [
        'election_id' => $copyId,
        'name' => $position['name'],
        'quantity' => $position['quantity'] ?? 1,
        'write_ins_per_seat' => $position['write_ins_per_seat'] ?? 0,
        'sortorder' => $position['sortorder'] ?? NULL,
        'description' => $position['description'] ?? NULL,
      ]);
    }

    return civicrm_api3('Election', 'getsingle', ['id' => $copyId]);
  }

  /**
   * Check if user is allowed to edit an election.
   *
   * @return bool|string TRUE if election is allowed to be edited, An error string if it's not.
   */
  public function canEdit() {
    if ($this->canDelete() !== TRUE) {
      return 'Election details cannot be edited once it has been run.';
    }

    if ($this->is_deleted) {
      return 'Election has been deleted and cannot be edited.';
    }

    return TRUE;
  }

  /**
   * Check if user is allowed to delete an election.
   *
   * @return bool|string TRUE if election is allowed to be edited, An error string if it's not.
   */
  public function canDelete() {
    if (self::isElectionInProcess($this->nomination_start_date) && $this->is_visible) {
      return 'Election cannot be deleted once it has been run.';
    }

    return TRUE;
  }

  /**
   * Check if election is in process.
   *
   * @param $processStartDate
   * @return bool
   */
  public static function isElectionInProcess($processStartDate) {
    if (empty($processStartDate)) {
      return FALSE;
    }
    $nominationStartDate = DateTime::createFromFormat('Y-m-d H:i:s', $processStartDate);
    $today = new DateTime();

    return ($today >= $nominationStartDate);
  }

  /**
   * Assign different statues of an election.
   */
  public function assignStatues() {
    $this->isVisible = (self::isTodayBetweenDates($this->visibility_start_date, $this->visibility_end_date) && $this->is_visible);
    $this->isNominationsInProgress = self::isTodayBetweenDates($this->nomination_start_date, $this->nomination_end_date);
    $this->isNominationsStarted = self::isTodayBetweenDates($this->nomination_start_date, NULL);
    $this->advertiseCandidatesStarted = self::isTodayBetweenDates($this->advertise_candidates_date, NULL);
    $this->isVotingStarted = self::isTodayBetweenDates($this->voting_start_date, NULL);
    $this->isVotingEnded = self::isTodayBetweenDates($this->voting_end_date, NULL);
    $this->isResultsOut = (self::isTodayBetweenDates($this->result_date, NULL) && $this->has_results_generated);
    $this->assignDisplayOptions();
  }

  /**
   * Resolve the display options of the election for templates.
   *
   * An empty value means the option was never set (older elections or API
   * callers), in which case the pre-2.0 behaviour applies.
   */
  public function assignDisplayOptions() {
    $this->showPhotos = ($this->show_photos === NULL || $this->show_photos === '') ? TRUE : (bool) $this->show_photos;
    $this->showNominators = ($this->show_nominators === NULL || $this->show_nominators === '') ? TRUE : (bool) $this->show_nominators;
    $statements = $this->show_statements;
    $this->statementDisplay = array_key_exists($statements ?? '', self::getStatementDisplayOptions()) ? $statements : self::STATEMENTS_NONE;
    $this->showStatements = ($this->statementDisplay !== self::STATEMENTS_NONE);
  }

  /**
   * Check if today falls between given two dates.
   *
   * @param $dateA
   * @param $dateB
   * @return bool
   */
  public static function isTodayBetweenDates($dateA, $dateB) {
    if (empty($dateA)) {
      return FALSE;
    }
    $today = new DateTime();
    $dateA = DateTime::createFromFormat('Y-m-d H:i:s', $dateA);

    if ($dateB !== NULL) {
      $dateB = DateTime::createFromFormat('Y-m-d H:i:s', $dateB);
    }
    if ($dateA <= $today && ($dateB === NULL || $dateB > $today)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * The rules the election dates must follow, in the order they are checked.
   *
   * Each rule says a date must come after ('after') or must not come after ('notAfter') another one.
   * The first rule a date fails is the one reported. The election form uses the same table in the
   * browser to show the problem as soon as a date is changed.
   *
   * @return array[] Each with the keys field, rule, other and message.
   */
  public static function getDateRules() {
    $rule = function ($field, $rule, $other, $message) {
      return ['field' => $field, 'rule' => $rule, 'other' => $other, 'message' => $message];
    };
    return [
      $rule('visibility_end_date', 'after', 'visibility_start_date', E::ts('End date must be after start date.')),
      $rule('nomination_start_date', 'after', 'visibility_start_date', E::ts('Start date must be after visibility start date.')),
      $rule('nomination_end_date', 'after', 'nomination_start_date', E::ts('End date must be after start date.')),
      $rule('nomination_end_date', 'notAfter', 'visibility_end_date', E::ts('End date must be before visibility end date.')),
      $rule('advertise_candidates_date', 'after', 'nomination_end_date', E::ts('Start Date must be after nomination end date.')),
      $rule('advertise_candidates_date', 'notAfter', 'visibility_end_date', E::ts('Start Date must be before visibility end date.')),
      $rule('voting_start_date', 'after', 'advertise_candidates_date', E::ts('Start date must be after advertise candidate date.')),
      $rule('voting_end_date', 'after', 'voting_start_date', E::ts('End date must be after start date.')),
      $rule('voting_end_date', 'notAfter', 'visibility_end_date', E::ts('End date must be before visibility end date.')),
      $rule('result_date', 'after', 'voting_end_date', E::ts('Start Date must be after voting end date.')),
      $rule('result_date', 'notAfter', 'visibility_end_date', E::ts('Start Date must be before visibility end date.')),
    ];
  }

  /**
   * Check the order of the election dates.
   *
   * @param array $values The election dates, keyed by field.
   * @param string $dateFormat Format of the dates in $values.
   * @return array Error message for each date that is out of order, keyed by field.
   */
  public static function compareDates($values, $dateFormat = 'Y-m-d H:i:s') {
    $errors = [];
    foreach (self::getDateRules() as $rule) {
      if (isset($errors[$rule['field']])) {
        continue;
      }
      $result = CRM_Elections_Helper_Dates::compare($values[$rule['other']], $values[$rule['field']], $dateFormat);
      // compare() gives 1 when the second date is later, -1 when it is earlier.
      $failed = ($rule['rule'] === 'after') ? ($result != 1) : ($result == 1);
      if ($failed) {
        $errors[$rule['field']] = $rule['message'];
      }
    }
    return $errors;
  }

}
