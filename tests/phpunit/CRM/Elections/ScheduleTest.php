<?php

/**
 * The quick set-up on the Dates tab: every cycle gives dates that pass the form's own rules, and the dates
 * the people running elections were shown in the plan.
 *
 * @group headless
 */
class CRM_Elections_ScheduleTest extends api_v3_ElectionBaseTestCase {

  private $timeZone;

  public function setUp(): void {
    parent::setUp();
    $this->timeZone = date_default_timezone_get();
    date_default_timezone_set('Australia/Sydney');
  }

  public function tearDown(): void {
    parent::tearDown();
    date_default_timezone_set($this->timeZone);
  }

  private function schedule(array $override = []) {
    return CRM_Elections_Helper_Dates::schedule($override + ['cycle' => '2-2', 'start' => '2027-03-01 09:00', 'startTime' => '09:00', 'endTime' => '17:00', 'results' => 'close', 'visibleWeeks' => 12]);
  }

  public function cycles() {
    return [
      '1 and 1' => ['1-1', ['2027-03-01 09:00:00', '2027-03-01 09:00:00', '2027-03-07 17:00:00', '2027-03-07 17:05:00', '2027-03-08 09:00:00', '2027-03-14 17:00:00', '2027-03-14 17:05:00', '2027-06-06 17:05:00']],
      '2 and 2' => ['2-2', ['2027-03-01 09:00:00', '2027-03-01 09:00:00', '2027-03-14 17:00:00', '2027-03-14 17:05:00', '2027-03-15 09:00:00', '2027-03-28 17:00:00', '2027-03-28 17:05:00', '2027-06-20 17:05:00']],
      '2 and 3' => ['2-3', ['2027-03-01 09:00:00', '2027-03-01 09:00:00', '2027-03-14 17:00:00', '2027-03-14 17:05:00', '2027-03-15 09:00:00', '2027-04-04 17:00:00', '2027-04-04 17:05:00', '2027-06-27 17:05:00']],
      '2 and 4' => ['2-4', ['2027-03-01 09:00:00', '2027-03-01 09:00:00', '2027-03-14 17:00:00', '2027-03-14 17:05:00', '2027-03-15 09:00:00', '2027-04-11 17:00:00', '2027-04-11 17:05:00', '2027-07-04 17:05:00']],
    ];
  }

  /**
   * @dataProvider cycles
   */
  public function testEachCycleGivesTheDatesThatWereAgreed($cycle, array $expected) {
    $dates = $this->schedule(['cycle' => $cycle]);
    $this->assertSame(array_combine(['visibility_start_date', 'nomination_start_date', 'nomination_end_date', 'advertise_candidates_date', 'voting_start_date', 'voting_end_date', 'result_date', 'visibility_end_date'], $expected), $dates);
    $this->assertSame([], CRM_Elections_BAO_Election::compareDates($dates), 'The dates pass the same rules as the form');
  }

  public function testResultsCanWaitUntilTheNextMorning() {
    $dates = $this->schedule(['results' => 'morning']);
    $this->assertSame('2027-03-29 09:00:00', $dates['result_date']);
    $this->assertSame('2027-06-21 09:00:00', $dates['visibility_end_date']);
  }

  public function testTheTimesAndVisibleWeeksAreUsed() {
    $dates = $this->schedule(['start' => '2027-05-12 10:30', 'startTime' => '08:15', 'endTime' => '20:45', 'visibleWeeks' => 4]);
    $this->assertSame('2027-05-12 10:30:00', $dates['nomination_start_date'], 'Nominations open exactly when chosen');
    $this->assertSame('2027-05-25 20:45:00', $dates['nomination_end_date'], 'Two weeks on from a Wednesday ends on the Tuesday');
    $this->assertSame('2027-05-26 08:15:00', $dates['voting_start_date']);
    $this->assertSame('2027-06-08 20:45:00', $dates['voting_end_date']);
    $this->assertSame('2027-07-06 20:50:00', $dates['visibility_end_date']);
  }

  public function testClockTimesAreKeptAcrossDaylightSavingChanges() {
    // Sydney's clocks go back on 4 April 2027 and forward on 3 October 2027.
    $dates = $this->schedule(['cycle' => '2-4', 'start' => '2027-03-15 09:00']);
    $this->assertSame('2027-04-25 17:00:00', $dates['voting_end_date']);
    $this->assertSame('2027-03-29 09:00:00', $dates['voting_start_date']);
    $dates = $this->schedule(['cycle' => '1-1', 'start' => '2027-09-27 09:00']);
    $this->assertSame('2027-10-03 17:00:00', $dates['nomination_end_date']);
    $this->assertSame('2027-10-04 09:00:00', $dates['voting_start_date']);
  }

  public function testEveryStartDayAndTimeGivesValidDates() {
    $times = [['00:00', '00:05'], ['09:00', '17:00'], ['23:50', '23:55'], ['12:00', '12:05']];
    for ($day = 0; $day < 14; $day++) {
      foreach (['1-1', '2-2', '2-3', '2-4'] as $cycle) {
        foreach ($times as [$startTime, $endTime]) {
          foreach (['close', 'morning'] as $results) {
            $start = date('Y-m-d', strtotime("2027-02-24 +$day days")) . ' ' . $startTime;
            $dates = $this->schedule(['cycle' => $cycle, 'start' => $start, 'startTime' => $startTime, 'endTime' => $endTime, 'results' => $results]);
            $this->assertSame([], CRM_Elections_BAO_Election::compareDates($dates), "$cycle from $start to $endTime, results $results");
          }
        }
      }
    }
  }

  public function testBadInputIsRefusedWithAReason() {
    foreach ([
      [['cycle' => 'nope'], 'Choose an election cycle'],
      [['start' => ''], 'Enter the date and time nominations open'],
      [['startTime' => '9am'], 'hours and minutes'],
      [['startTime' => '17:00', 'endTime' => '09:00'], 'end time must be later'],
      [['startTime' => '09:00', 'endTime' => '09:00'], 'end time must be later'],
      [['visibleWeeks' => 0], 'weeks the election stays visible'],
    ] as [$override, $message]) {
      try {
        $this->schedule($override);
        $this->fail('Refused: ' . json_encode($override));
      }
      catch (CRM_Core_Exception $e) {
        $this->assertStringContainsString($message, $e->getMessage());
      }
    }
  }

}
