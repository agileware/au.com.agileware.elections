<?php
use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_Helper_Dates {

  /**
   * Compare two dates.
   *
   * @param $dateFirst
   * @param $dateSecond
   * @return int Return 0 if both are equals, 1 if the second date is greater and -1 if first date is greater.
   */
  public static function compare($dateFirst, $dateSecond, $dateFormat = 'Y-m-d H:i:s') {
    $dateFirst = DateTime::createFromFormat($dateFormat, $dateFirst);
    $dateSecond = DateTime::createFromFormat($dateFormat, $dateSecond);
    if ($dateFirst == $dateSecond) {
      return 0;
    }
    if ($dateFirst > $dateSecond) {
      return -1;
    }
    return 1;
  }

  /**
   * The election cycles offered by the quick set-up on the Dates tab: nominations weeks, voting weeks.
   *
   * @return array
   *   Key => [label, nomination weeks, voting weeks].
   */
  public static function getCycles() {
    return [
      '1-1' => [E::ts('1 week of nominations, 1 week of voting'), 1, 1],
      '2-2' => [E::ts('2 weeks of nominations, 2 weeks of voting'), 2, 2],
      '2-3' => [E::ts('2 weeks of nominations, 3 weeks of voting'), 2, 3],
      '2-4' => [E::ts('2 weeks of nominations, 4 weeks of voting'), 2, 4],
    ];
  }

  /**
   * Work out all the election dates from when nominations open.
   *
   * - Visible from: the moment nominations open.
   * - Nominations close at the end time on the last day of their weeks (Monday to Sunday when they open on a Monday).
   * - Candidates are advertised from 5 minutes after nominations close.
   * - Voting opens at the start time on the day after candidates are advertised, and closes at the end time on the
   *   last day of its weeks.
   * - Results are published 5 minutes after voting closes, or at the start time the next day.
   * - Visible until: a number of weeks after the results.
   * Any date that would not follow the one before it moves forward in 5 minute steps. Times are the site's
   * clock times, so Daylight Saving changes do not shift them.
   *
   * @param array $options
   *   - cycle: a key of getCycles()
   *   - start: when nominations open, Y-m-d H:i or Y-m-d H:i:s
   *   - startTime, endTime: H:i, the time phases start and end
   *   - results: close (5 minutes after voting closes) or morning (at the start time the next day)
   *   - visibleWeeks: weeks the election stays visible after the results
   *
   * @return array
   *   The eight date fields, Y-m-d H:i:s.
   * @throws CRM_Core_Exception
   */
  public static function schedule(array $options) {
    $cycles = self::getCycles();
    $cycle = $cycles[$options['cycle'] ?? ''] ?? NULL;
    if (!$cycle) {
      throw new CRM_Elections_Exception(E::ts('Choose an election cycle.'));
    }
    [, $nominationWeeks, $votingWeeks] = $cycle;
    $timeZone = new DateTimeZone(date_default_timezone_get());
    $start = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', strlen($options['start'] ?? '') === 16 ? $options['start'] . ':00' : (string) ($options['start'] ?? ''), $timeZone);
    if (!$start) {
      throw new CRM_Elections_Exception(E::ts('Enter the date and time nominations open.'));
    }
    $times = [];
    foreach (['startTime' => '09:00', 'endTime' => '17:00'] as $key => $default) {
      $value = $options[$key] ?? $default;
      if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $value, $m)) {
        throw new CRM_Elections_Exception(E::ts('Enter the times as hours and minutes, for example 09:00.'));
      }
      $times[$key] = [(int) $m[1], (int) $m[2]];
    }
    if ($times['endTime'][0] * 60 + $times['endTime'][1] <= $times['startTime'][0] * 60 + $times['startTime'][1]) {
      throw new CRM_Elections_Exception(E::ts('The end time must be later than the start time.'));
    }
    $visibleWeeks = (int) ($options['visibleWeeks'] ?? 12);
    if ($visibleWeeks < 1 || $visibleWeeks > 520) {
      throw new CRM_Elections_Exception(E::ts('Enter the number of weeks the election stays visible after the results, from 1 to 520.'));
    }
    $at = function (DateTimeImmutable $day, $key) use ($times) {
      return $day->setTime($times[$key][0], $times[$key][1], 0);
    };
    $step = '+5 minutes';

    $dates = [];
    $dates['visibility_start_date'] = $start;
    $dates['nomination_start_date'] = $start;
    $dates['nomination_end_date'] = $at($start->modify('+' . ($nominationWeeks * 7 - 1) . ' days'), 'endTime');
    $dates['advertise_candidates_date'] = $dates['nomination_end_date']->modify($step);
    $dates['voting_start_date'] = $at($dates['advertise_candidates_date']->modify('+1 day'), 'startTime');
    $dates['voting_end_date'] = $at($dates['voting_start_date']->modify('+' . ($votingWeeks * 7 - 1) . ' days'), 'endTime');
    $dates['result_date'] = (($options['results'] ?? 'close') === 'morning')
      ? $at($dates['voting_end_date']->modify('+1 day'), 'startTime')
      : $dates['voting_end_date']->modify($step);
    $dates['visibility_end_date'] = $dates['result_date']->modify('+' . $visibleWeeks . ' weeks');

    // Move any date that does not follow the one before it forward in 5 minute steps until the rules hold.
    $format = 'Y-m-d H:i:s';
    for ($pass = 0; $pass < 500; $pass++) {
      $values = array_map(fn($d) => $d->format($format), $dates);
      $errors = CRM_Elections_BAO_Election::compareDates($values);
      if (!$errors) {
        return $values;
      }
      $moved = FALSE;
      foreach (CRM_Elections_BAO_Election::getDateRules() as $rule) {
        if (isset($errors[$rule['field']]) && in_array($rule['rule'], ['after', 'notBefore'], TRUE)) {
          $dates[$rule['field']] = $dates[$rule['field']]->modify($step);
          $moved = TRUE;
        }
      }
      if (!$moved) {
        break;
      }
    }
    throw new CRM_Elections_Exception(E::ts('These settings give dates that cannot be put in order. Check the times and weeks.'));
  }

}
