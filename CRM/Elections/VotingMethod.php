<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * How the votes of an election are cast and counted.
 *
 * - `irv`: ranked choice (instant runoff). The voter numbers the candidates in order of preference.
 * - `plurality`: the voter ticks up to as many candidates as there are seats; the most ticks win.
 *
 * Both store a ballot the same way (one row for each choice, with the rank), so the one-ballot-per-member
 * rule, anonymising, fingerprints and the review step do not depend on the method. A plurality ballot
 * stores every tick with rank 1.
 */
class CRM_Elections_VotingMethod {

  const IRV = 'irv';

  const PLURALITY = 'plurality';

  /**
   * @return string[]
   *   Label for each method, keyed by method.
   */
  public static function getOptions() {
    return [
      self::IRV => E::ts('Ranked choice (voters number the candidates)'),
      self::PLURALITY => E::ts('Most votes win (voters tick candidates)'),
    ];
  }

  /**
   * The method to use for a stored value. Elections made before 2.0, or with no value, use ranked choice.
   *
   * @param string|null $value
   *
   * @return string
   */
  public static function normalise($value) {
    return array_key_exists((string) $value, self::getOptions()) ? (string) $value : self::IRV;
  }

  /**
   * @param int $electionId
   *
   * @return string
   */
  public static function forElection($electionId) {
    return self::normalise(CRM_Core_DAO::singleValueQuery('SELECT voting_method FROM civicrm_election WHERE id = %1', [1 => [(int) $electionId, 'Integer']]));
  }

  /**
   * Check the rules of a method that a ballot must follow for one position.
   *
   * @param string $method
   * @param int[] $ranks
   *   The ranks the voter gave in this position.
   * @param int $seats
   *
   * @return string|null
   *   An error message, or NULL when the choices are fine.
   */
  public static function validatePosition($method, array $ranks, $seats) {
    if ($method === self::PLURALITY) {
      foreach ($ranks as $rank) {
        if ((int) $rank !== 1) {
          return 'In this election candidates are chosen, not ranked.';
        }
      }
      if (count($ranks) > max(1, (int) $seats)) {
        return 'You can choose no more candidates than there are seats for the position.';
      }
    }
    return NULL;
  }

  /**
   * The instructions at the top of the ballot.
   *
   * @param string $method
   *
   * @return string[]
   */
  public static function instructions($method) {
    if ($method === self::PLURALITY) {
      return [
        E::ts('Use this form to vote for your preferred candidates in each position.'),
        E::ts('Tick the candidates you want to vote for. The candidates with the most votes win.'),
        E::ts('For positions with more than one seat you can tick up to as many candidates as there are seats.'),
      ];
    }
    return [
      E::ts('Use this form to vote for your preferred candidates in each position.'),
      E::ts('Votes are numbered from 1 onwards, where 1 is your highest preference, followed by 2 and so on.'),
      E::ts('For positions where multiple seats are available, you only need to vote once, with your order of preferences being considered across all seats.'),
    ];
  }

  /**
   * The heading of a position on the ballot.
   *
   * @param string $method
   * @param string $name
   * @param int $candidates
   * @param int $seats
   *
   * @return string
   */
  public static function positionPrompt($method, $name, $candidates, $seats) {
    if ($method === self::PLURALITY) {
      $seats = max(1, (int) $seats);
      return $seats === 1
        ? E::ts('Vote for %1. Tick one candidate.', [1 => $name])
        : E::ts('Vote for %1. Tick up to %2 candidates.', [1 => $name, 2 => $seats]);
    }
    return E::ts('Vote for %1. Please number the candidates from 1 to %2 in the order of your preferences.', [1 => $name, 2 => $candidates]);
  }

  /**
   * Count the votes of one position.
   *
   * Candidates with the same number of votes share a rank. When the candidates tied on votes run across
   * the last seat the position is a tie: it is never settled by choosing one of them.
   *
   * @param int[] $votes
   *   Number of votes for each nomination id.
   * @param int $seats
   *
   * @return array
   *   `status` and `candidateranks` (rank for each nomination id).
   */
  public static function countPlurality(array $votes, $seats) {
    $seats = max(1, (int) $seats);
    arsort($votes);
    $ranks = [];
    $previous = NULL;
    $rank = 0;
    foreach ($votes as $nominationId => $count) {
      if ($count !== $previous) {
        $rank++;
        $previous = $count;
      }
      $ranks[$nominationId] = $rank;
    }
    $counts = array_values($votes);
    if ($seats >= count($counts)) {
      $status = $seats > count($counts) ? CRM_Elections_BAO_Election::$RESULTS_MORE_SEATS : CRM_Elections_BAO_Election::$RESULTS_EQUAL_SEATS;
    }
    elseif ($counts[$seats - 1] === $counts[$seats]) {
      $status = CRM_Elections_BAO_Election::$RESULTS_NO_MAJORITY;
    }
    else {
      $status = CRM_Elections_BAO_Election::$RESULTS_MOST_VOTES;
    }
    return ['status' => $status, 'candidateranks' => $ranks];
  }

}
