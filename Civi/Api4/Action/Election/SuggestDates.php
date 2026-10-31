<?php

namespace Civi\Api4\Action\Election;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

/**
 * Work out all the dates of an election from when nominations open and an election cycle.
 *
 * Used by the quick set-up on the Dates tab of the election form. See CRM_Elections_Helper_Dates::schedule().
 *
 * @method $this setCycle(string $cycle)
 * @method $this setStart(string $start)
 * @method $this setStartTime(string $time)
 * @method $this setEndTime(string $time)
 * @method $this setResults(string $results)
 * @method $this setVisibleWeeks(int $weeks)
 */
class SuggestDates extends AbstractAction {

  /**
   * One of 1-1, 2-2, 2-3, 2-4: weeks of nominations and weeks of voting.
   *
   * @var string
   * @required
   */
  protected $cycle;

  /**
   * When nominations open, in the site's time zone: Y-m-d H:i.
   *
   * @var string
   * @required
   */
  protected $start;

  /**
   * The time phases start, H:i.
   *
   * @var string
   */
  protected $startTime = '09:00';

  /**
   * The time phases end, H:i.
   *
   * @var string
   */
  protected $endTime = '17:00';

  /**
   * close: results 5 minutes after voting closes. morning: at the start time the next day.
   *
   * @var string
   */
  protected $results = 'close';

  /**
   * Weeks the election stays visible after the results are published.
   *
   * @var int
   */
  protected $visibleWeeks = 12;

  public function _run(Result $result) {
    $result[] = \CRM_Elections_Helper_Dates::schedule([
      'cycle' => $this->cycle,
      'start' => $this->start,
      'startTime' => $this->startTime,
      'endTime' => $this->endTime,
      'results' => $this->results,
      'visibleWeeks' => $this->visibleWeeks,
    ]);
  }

}
