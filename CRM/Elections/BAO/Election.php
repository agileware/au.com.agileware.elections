<?php
use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_BAO_Election extends CRM_Elections_DAO_Election {

  public static $RESULTS_EQUAL_SEATS  = 'equalseats';
  public static $RESULTS_MORE_SEATS  = 'moreseats';
  public static $RESULTS_MAJORITY  = 'majority';
  public static $RESULTS_NO_NOMINATIONS  = 'nonominations';
  public static $RESULTS_NO_MAJORITY = 'tie';

  public static function getResultStatues() {
    return array(
      'results_no_majority' => self::$RESULTS_NO_MAJORITY,
      'results_equal_seats' => self::$RESULTS_EQUAL_SEATS,
      'results_more_seats' => self::$RESULTS_MORE_SEATS,
      'results_majority' => self::$RESULTS_MAJORITY,
      'results_no_nominations' => self::$RESULTS_NO_NOMINATIONS,
    );
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
      return 'You\'re not allowed to edit deleted election details.';
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
  }

  /**
   * Check if today falls between given two dates.
   *
   * @param $dateA
   * @param $dateB
   * @return bool
   */
  public static function isTodayBetweenDates($dateA, $dateB) {
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
   * Compare election dates and check if they are valid.
   *
   * @param $values
   * @return array of errors
   */
  public static function compareDates($values, $dateFormat = "Y-m-d H:i:s") {
    $errors = array();

    // Check election visibility
    if (CRM_Elections_Helper_Dates::compare($values['visibility_start_date'], $values['visibility_end_date'], $dateFormat) != 1) {
      $errors['visibility_end_date'] = 'End date must be after start date.';
    }

    // Check nominations
    if (CRM_Elections_Helper_Dates::compare($values['visibility_start_date'], $values['nomination_start_date'], $dateFormat) != 1) {
      $errors['nomination_start_date'] = 'Start date must be after visibility start date.';
    }

    if (CRM_Elections_Helper_Dates::compare($values['nomination_start_date'], $values['nomination_end_date'], $dateFormat) != 1) {
      $errors['nomination_end_date'] = 'End date must be after start date.';
    }
    elseif (CRM_Elections_Helper_Dates::compare($values['visibility_end_date'], $values['nomination_end_date'], $dateFormat) == 1) {
      $errors['nomination_end_date'] = 'End date must be before visibility end date.';
    }

    // Check advertise candidates
    if (CRM_Elections_Helper_Dates::compare($values['nomination_end_date'], $values['advertise_candidates_date'], $dateFormat) != 1) {
      $errors['advertise_candidates_date'] = 'Start Date must be after nomination end date.';
    }
    elseif (CRM_Elections_Helper_Dates::compare($values['visibility_end_date'], $values['advertise_candidates_date'], $dateFormat) == 1) {
      $errors['advertise_candidates_date'] = 'Start Date must be before visibility end date.';
    }

    // Checking voting with advertise candidates
    if (CRM_Elections_Helper_Dates::compare($values['advertise_candidates_date'], $values['voting_start_date'], $dateFormat) != 1) {
      $errors['voting_start_date'] = 'Start date must be after advertise candidate date.';
    }

    // Check voting end date
    if (CRM_Elections_Helper_Dates::compare($values['voting_start_date'], $values['voting_end_date'], $dateFormat) != 1) {
      $errors['voting_end_date'] = 'End date must be after start date.';
    }
    elseif (CRM_Elections_Helper_Dates::compare($values['visibility_end_date'], $values['voting_end_date'], $dateFormat) == 1) {
      $errors['voting_end_date'] = 'End date must be before visibility end date.';
    }

    // Check results date
    if (CRM_Elections_Helper_Dates::compare($values['voting_end_date'], $values['result_date'], $dateFormat) != 1) {
      $errors['result_date'] = 'Start Date must be after voting end date.';
    }
    elseif (CRM_Elections_Helper_Dates::compare($values['visibility_end_date'], $values['result_date'], $dateFormat) == 1) {
      $errors['result_date'] = 'Start Date must be before visibility end date.';
    }

    return $errors;
  }

}
