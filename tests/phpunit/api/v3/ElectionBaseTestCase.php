<?php

use Civi\Test\Api3TestTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class api_v3_ElectionBaseTestCase extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  use Api3TestTrait;

  public static $ELECTION_NOT_STARTED = 'not-started';
  public static $ELECTION_NOMINATION_IN_PROGRESS = 'nomination-in-progress';
  public static $ELECTION_ADVERTISE_CANDIDATES_STARTED = 'advertise-candidates_started';
  public static $ELECTION_VOTING_IN_PROGRESS = 'voting-in-progress';
  public static $ELECTION_RESULTS_STARTED = 'results-started';

  public function setUp(): void {
    parent::setUp();
  }

  public function tearDown(): void {
    parent::tearDown();
  }

  public function setUpHeadless() {
    $env = \Civi\Test::headless()->installMe(__DIR__)->apply();
    // The shared test database may have been copied from an older site: make sure it has every 2.0 change.
    $upgrader = new CRM_Elections_Upgrader();
    $upgrader->init(['key' => 'au.com.agileware.elections']);
    $upgrader->upgrade_20000();
    $upgrader->upgrade_20010();
    $upgrader->upgrade_20020();
    $upgrader->upgrade_20030();
    return $env;
  }

  /**
   * Create an Individual contact and return its id.
   *
   * @param array $params
   * @return int
   */
  public function individualCreate($params = []) {
    $params += [
      'contact_type' => 'Individual',
      'first_name' => 'Test' . rand(0, 999999),
      'last_name' => 'Voter' . rand(0, 999999),
    ];
    return (int) $this->callAPISuccess('Contact', 'create', $params)['id'];
  }

  /**
   * Create a Group and return its id.
   *
   * @param array $params
   * @return int
   */
  public function groupCreate($params = []) {
    $params += [
      'name' => 'Test Group ' . rand(0, 999999),
      'title' => 'Test Group ' . rand(0, 999999),
    ];
    return (int) $this->callAPISuccess('Group', 'create', $params)['id'];
  }

  public function getElectionById($electionId) {
    return $this->callAPISuccess("Election", "getsingle", ['id' => $electionId]);
  }

  public function getElectionPositionById($electionPositionId) {
    return $this->callAPISuccess("ElectionPosition", "getsingle", ['id' => $electionPositionId]);
  }

  public function generateElectionResults($election) {
    $election = $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_RESULTS_STARTED, $election);
    $electionResults = new CRM_Elections_Helper_Results();
    $electionResults->generateResult($election['id'], $election['anonymize_votes']);
    return $election;
  }

  /**
   * Convert DateTime objects to Y-m-d H:i:s formatted strings.
   *
   * @param $dates
   */
  public function modifyDatesInString(&$dates) {
    foreach ($dates as $index => $date) {
      $dates[$index] = $date->format("Y-m-d H:i:s");
    }
  }

  /**
   * Get election start date based on the expected state of an election.
   *
   * @param $state
   * @return DateTime
   */
  public function getStartDateByState($state) {
    $today = new DateTime();

    if ($state == self::$ELECTION_NOMINATION_IN_PROGRESS) {
      $today->modify("-3 days");
    }

    if ($state == self::$ELECTION_ADVERTISE_CANDIDATES_STARTED) {
      $today->modify("-8 days");
    }

    if ($state == self::$ELECTION_VOTING_IN_PROGRESS) {
      $today->modify("-11 days");
    }

    if ($state == self::$ELECTION_RESULTS_STARTED) {
      $today->modify("-26 days");
    }

    return $today;
  }

  /**
   * Edit election dates by given state and election.
   *
   * @param $state
   * @param $election
   * @return mixed
   */
  public function editElectionByState($state, $election) {
    $newDates = $this->getElectionDates($this->getStartDateByState($state));
    $this->modifyDatesInString($newDates);
    $election = array_merge($election, $newDates);
    $election = $this->callAPISuccess('Election', 'create', $election);
    return $election['values'][$election['id']];
  }

  /**
   * Create an election with given custom data and expected state.
   *
   * @param array $defaultParams
   * @param null $state
   * @return mixed
   */
  public function createElection($defaultParams = [], $state = NULL) {
    if ($state == NULL) {
      $state = self::$ELECTION_NOT_STARTED;
    }

    $electionDates = $this->getElectionDates($this->getStartDateByState($state));
    $this->modifyDatesInString($electionDates);

    $electionGroup = $this->groupCreate([
      'name'  => 'Election Group #' . rand(0, 999999),
      'title' => 'Election Group #' . rand(0, 999999),
    ]);

    $params = [
      'name'           => 'CiviTest Election',
      'allowed_groups' => $electionGroup,
      'description'    => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean viverra sed felis eget tempus. Morbi lacinia purus eget erat bibendum volutpat. Aenean eu viverra enim.',
    ];
    $params = array_merge($params, $defaultParams);
    $params = array_merge($params, $electionDates);
    $election = $this->callAPISuccess('election', 'create', $params);
    return $election['values'][$election['id']];
  }

  /**
   * Add member in group if provided election.
   *
   * @param $memberId
   * @param $election
   */
  public function addMembersInElectionGroup($memberIds, $election) {
    if (!is_array($memberIds)) {
      civicrm_api3('GroupContact', 'create', [
        'group_id'   => $election['allowed_groups'],
        'contact_id' => $memberIds,
      ]);
    }
    else {
      foreach ($memberIds as $memberId) {
        civicrm_api3('GroupContact', 'create', [
          'group_id'   => $election['allowed_groups'],
          'contact_id' => $memberId,
        ]);
      }
    }
  }

  /**
   * Get election dates considering given today/election_visibility_start_date
   *
   * @param null $today
   * @return array
   */
  public function getElectionDates($today = NULL) {
    if ($today === NULL) {
      $today = new DateTime();
    }

    $visibilityStartDate = clone $today;
    $visibilityEndDate = clone $today;
    $nominationStartDate = clone $today;
    $nominationEndDate = clone $today;
    $advertiseCandidateDate = clone $today;
    $votingStartDate = clone $today;
    $votingEndDate = clone $today;
    $resultDate = clone $today;

    $visibilityEndDate->modify("+30 days");
    $nominationStartDate->modify("+2 days");
    $nominationEndDate->modify("+5 days");
    $advertiseCandidateDate->modify("+7 days");
    $votingStartDate->modify("+10 days");
    $votingEndDate->modify("+20 days");
    $resultDate->modify("+25 days");

    return [
      'visibility_start_date' => $visibilityStartDate,
      'visibility_end_date' => $visibilityEndDate,
      'nomination_start_date' => $nominationStartDate,
      'nomination_end_date' => $nominationEndDate,
      'advertise_candidates_date' => $advertiseCandidateDate,
      'voting_start_date' => $votingStartDate,
      'voting_end_date' => $votingEndDate,
      'result_date' => $resultDate,
    ];
  }

  /**
   * Mark election inactive
   *
   * @param $election
   */
  public function markElectionInactive($election) {
    $election['is_visible'] = 0;
    $election = $this->callAPISuccess('election', 'create', $election);
    $election = $election['values'][$election['id']];

    return $election;
  }

  /**
   * Create election position by given election.
   *
   * @param null $election
   * @return mixed
   */
  public function createElectionPosition($election = NULL, $defaultParams = []) {
    if ($election === NULL) {
      $election = $this->createElection([
        'is_deleted' => 0,
        'is_visible' => 1,
      ]);
    }
    $params = [
      'name'        => 'President',
      'quantity'    => 1,
      'election_id' => $election['id'],
    ];
    $params = array_merge($params, $defaultParams);
    $electionPosition = $this->callAPISuccess('ElectionPosition', 'create', $params);
    return $electionPosition['values'][$electionPosition['id']];
  }

  /**
   * Make a nomination an eligible candidate who has accepted, so it can be voted for.
   *
   * @param int $nominationId
   */
  public function makeCandidate($nominationId) {
    $this->callAPISuccess('ElectionNomination', 'create', [
      'id' => $nominationId,
      'is_eligible_candidate' => 1,
      'has_accepted_nomination' => 1,
    ]);
  }

  /**
   * Create successful nomination.
   *
   * @return array
   * @throws CRM_Core_Exception
   */
  public function createNomination($params = []) {
    $nominee = $this->individualCreate();

    $electionParams = [
      'is_deleted' => 0,
      'is_visible' => 1,
    ];
    if (isset($params['election']) && isset($params['election']['params'])) {
      $electionParams = array_merge($electionParams, $params['election']['params']);
    }
    $election = $this->createElection($electionParams);
    $position = $this->createElectionPosition($election);

    $params = [
      'member_nominee'       => $nominee,
      'election_position_id' => $position['id'],
    ];

    $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $election);
    $nomination = $this->callAPISuccess('ElectionNomination', 'create', $params);
    $nomination = $nomination['values'][$nomination['id']];
    $params['nomination'] = $nomination;
    $params['election'] = $election;
    $params['position'] = $position;
    return $params;
  }

}
