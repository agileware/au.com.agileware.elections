<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * The order the election dates must follow.
 *
 * @group headless
 */
class CRM_Elections_DateRulesTest extends api_v3_ElectionBaseTestCase {

  private function dates(array $override = []) {
    return $override + [
      'visibility_start_date' => '2031-01-01 09:00:00',
      'visibility_end_date' => '2031-03-31 17:00:00',
      'nomination_start_date' => '2031-01-05 09:00:00',
      'nomination_end_date' => '2031-01-12 17:00:00',
      'advertise_candidates_date' => '2031-01-15 09:00:00',
      'voting_start_date' => '2031-01-20 09:00:00',
      'voting_end_date' => '2031-01-27 17:00:00',
      'result_date' => '2031-02-01 09:00:00',
    ];
  }

  public function testValidDatesHaveNoErrors() {
    $this->assertSame([], CRM_Elections_BAO_Election::compareDates($this->dates()));
  }

  public function testEachDateMustFollowThePreviousStage() {
    $cases = [
      'visibility_end_date' => ['visibility_end_date' => '2030-12-31 09:00:00'],
      'nomination_start_date' => ['nomination_start_date' => '2030-12-31 09:00:00'],
      'nomination_end_date' => ['nomination_end_date' => '2031-01-04 09:00:00'],
      'advertise_candidates_date' => ['advertise_candidates_date' => '2031-01-12 17:00:00'],
      'voting_start_date' => ['voting_start_date' => '2031-01-15 09:00:00'],
      'voting_end_date' => ['voting_end_date' => '2031-01-20 09:00:00'],
      'result_date' => ['result_date' => '2031-01-27 17:00:00'],
    ];
    foreach ($cases as $field => $override) {
      $errors = CRM_Elections_BAO_Election::compareDates($this->dates($override));
      $this->assertArrayHasKey($field, $errors, "$field out of order is reported");
      $this->assertNotEmpty($errors[$field]);
    }
  }

  public function testNominationsMayOpenAtTheSameMomentTheElectionBecomesVisible() {
    $this->assertSame([], CRM_Elections_BAO_Election::compareDates($this->dates(['nomination_start_date' => '2031-01-01 09:00:00'])));
    $errors = CRM_Elections_BAO_Election::compareDates($this->dates(['nomination_start_date' => '2031-01-01 08:59:00']));
    $this->assertSame(E::ts('Start date must not be before the visibility start date.'), $errors['nomination_start_date']);
  }

  public function testNothingMayEndAfterTheElectionStopsBeingVisible() {
    foreach (['nomination_end_date', 'advertise_candidates_date', 'voting_end_date', 'result_date'] as $field) {
      $errors = CRM_Elections_BAO_Election::compareDates($this->dates([$field => '2031-04-30 09:00:00']));
      $this->assertArrayHasKey($field, $errors, "$field after visibility ends is reported");
    }
  }

  public function testOnlyTheFirstProblemOfADateIsReported() {
    // Voting ends before it starts and also after visibility ends: one message for the field.
    $errors = CRM_Elections_BAO_Election::compareDates($this->dates(['voting_start_date' => '2031-05-01 09:00:00', 'voting_end_date' => '2031-04-01 09:00:00']));
    $this->assertSame(E::ts('End date must be after start date.'), $errors['voting_end_date']);
  }

  public function testRuleTableIsUsableByTheBrowser() {
    foreach (CRM_Elections_BAO_Election::getDateRules() as $rule) {
      $this->assertContains($rule['rule'], ['after', 'notAfter', 'notBefore']);
      $this->assertNotEmpty($rule['message']);
      $this->assertNotEmpty($rule['field']);
      $this->assertNotEmpty($rule['other']);
    }
  }

}
