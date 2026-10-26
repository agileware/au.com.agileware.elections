<?php

/**
 * A realistic council election with named personas, used by the automated
 * tests and by the browser testing seed script.
 *
 * Personas (all members of the election's group, except the outsider):
 *  - 30 voters      V01..V30
 *  - 10 nominees    N01..N10 (members too, so they also vote)
 *  - 6 candidates   N01..N06 (the nominees who accepted at least one position)
 *  - 1 outsider     OUT  (not a member, must never take part)
 *
 * Roles (six single seat positions): President, Secretary, Treasurer,
 * Council Member 1, Council Member 2, Council Member 3.
 *
 * Stages move the election through its dates with SQL, as the clock would.
 */
class CRM_Elections_Test_CouncilFixture {

  const STAGE_NOMINATIONS = 'nominations';
  const STAGE_ADVERTISE = 'advertise';
  const STAGE_VOTING = 'voting';
  const STAGE_RESULTS = 'results';

  const POSITIONS = ['President', 'Secretary', 'Treasurer', 'Council Member 1', 'Council Member 2', 'Council Member 3'];

  /**
   * Nominations: position, nominee, nominators (voters) and what the nominee then does.
   *  - accept: accepts the nomination and is a candidate
   *  - withdraw: withdraws before voting
   *  - ignore: never responds, so is not a candidate
   *  - accept_then_withdraw: accepts, then changes their mind
   *  - unseconded: only one of the two required nominators, so never eligible
   */
  const PLAN = [
    ['President', 'N01', ['V01', 'V02'], 'accept'],
    ['President', 'N02', ['V03', 'V04'], 'accept'],
    ['President', 'N03', ['V05', 'V06'], 'withdraw'],
    ['President', 'N08', ['V01'], 'unseconded'],
    ['Secretary', 'N03', ['V07', 'V08'], 'accept'],
    ['Secretary', 'N04', ['V09', 'V10'], 'ignore'],
    ['Secretary', 'N09', ['V02', 'V03'], 'ignore'],
    ['Treasurer', 'N04', ['V11', 'V12'], 'accept'],
    ['Treasurer', 'N05', ['V13', 'V14'], 'accept'],
    ['Treasurer', 'N07', ['V15', 'V16'], 'withdraw'],
    ['Council Member 1', 'N06', ['V17', 'V18'], 'accept'],
    ['Council Member 1', 'N01', ['V19', 'V20'], 'accept'],
    ['Council Member 2', 'N02', ['V21', 'V22'], 'accept'],
    ['Council Member 2', 'N06', ['V23', 'V24'], 'accept'],
    ['Council Member 3', 'N03', ['V25', 'V26'], 'accept'],
    ['Council Member 3', 'N05', ['V27', 'V28'], 'accept'],
    ['Council Member 3', 'N10', ['V29', 'V30'], 'accept_then_withdraw'],
  ];

  /**
   * The candidates expected per position once acceptances are done.
   */
  const CANDIDATES = [
    'President' => ['N01', 'N02'],
    'Secretary' => ['N03'],
    'Treasurer' => ['N04', 'N05'],
    'Council Member 1' => ['N06', 'N01'],
    'Council Member 2' => ['N02', 'N06'],
    'Council Member 3' => ['N03', 'N05'],
  ];

  /** @var int */
  public $electionId;

  /** @var int */
  public $groupId;

  /** @var array persona key (V01, N01, OUT) => contact id */
  public $personas = [];

  /** @var array position name => position id */
  public $positions = [];

  /** @var array "Position|N01" => nomination id */
  public $nominations = [];

  /**
   * Create the personas, the group, the election and its positions, at the nominations stage.
   *
   * @param array $electionParams
   *   Overrides for the election (for example anonymize_votes, allow_revote).
   *
   * @return static
   */
  public static function create(array $electionParams = []) {
    $fixture = new static();
    $fixture->createPersonas();
    $fixture->createElection($electionParams);
    return $fixture;
  }

  private function createPersonas() {
    $this->groupId = (int) civicrm_api3('Group', 'create', [
      'title' => 'Council Members ' . uniqid(),
      'name' => 'council_members_' . uniqid(),
    ])['id'];

    for ($i = 1; $i <= 30; $i++) {
      $key = sprintf('V%02d', $i);
      $this->personas[$key] = $this->createContact('Voter', sprintf('%02d', $i), '');
    }
    $nicknames = ['N01' => 'Butch', 'N03' => 'Mo'];
    for ($i = 1; $i <= 10; $i++) {
      $key = sprintf('N%02d', $i);
      $this->personas[$key] = $this->createContact('Nominee', sprintf('%02d', $i), $nicknames[$key] ?? '');
    }
    foreach ($this->members() as $contactId) {
      civicrm_api3('GroupContact', 'create', ['group_id' => $this->groupId, 'contact_id' => $contactId]);
    }
    // Not a member: never allowed to nominate, be nominated or vote.
    $this->personas['OUT'] = $this->createContact('Outsider', 'Person', '');
  }

  private function createContact($first, $last, $nick) {
    return (int) civicrm_api3('Contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => $first,
      'last_name' => $last,
      'nick_name' => $nick,
      'email' => strtolower($first . $last) . '.' . uniqid() . '@example.org',
    ])['id'];
  }

  private function createElection(array $electionParams) {
    $election = civicrm_api3('Election', 'create', $electionParams + [
      'name' => 'Council Election',
      'description' => 'Annual election of the council.',
      'allowed_groups' => $this->groupId,
      'required_nominations' => 2,
      'allow_checksum_access' => 1,
      'allow_revote' => 1,
      'anonymize_votes' => 0,
      'is_visible' => 0,
      'is_deleted' => 0,
    ] + $this->dates(self::STAGE_NOMINATIONS));
    $this->electionId = (int) $election['id'];

    foreach (self::POSITIONS as $order => $name) {
      $this->positions[$name] = (int) civicrm_api3('ElectionPosition', 'create', [
        'name' => $name,
        'quantity' => 1,
        'sortorder' => $order + 1,
        'election_id' => $this->electionId,
      ])['id'];
    }
    // Positions can only be added to an inactive election.
    CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET is_visible = 1 WHERE id = %1', [1 => [$this->electionId, 'Integer']]);
  }

  /**
   * Election dates for a stage.
   *
   * @param string $stage
   *
   * @return array
   */
  public function dates($stage) {
    $offsets = [
      self::STAGE_NOMINATIONS => [-10, -2, 5, 6, 10, 20, 25, 60],
      self::STAGE_ADVERTISE => [-20, -10, -3, -2, 5, 15, 20, 60],
      self::STAGE_VOTING => [-30, -20, -13, -12, -1, 9, 12, 60],
      self::STAGE_RESULTS => [-40, -30, -23, -22, -10, -2, -1, 60],
    ][$stage];
    $keys = ['visibility_start_date', 'nomination_start_date', 'nomination_end_date', 'advertise_candidates_date', 'voting_start_date', 'voting_end_date', 'result_date', 'visibility_end_date'];
    $dates = [];
    foreach ($keys as $i => $key) {
      $dates[$key] = date('Y-m-d H:i:s', strtotime(($offsets[$i] >= 0 ? '+' : '') . $offsets[$i] . ' days'));
    }
    return $dates;
  }

  /**
   * Move the election to a stage by changing its dates, as the passing of time would.
   *
   * @param string $stage
   */
  public function moveToStage($stage) {
    $sets = [];
    $params = [1 => [$this->electionId, 'Integer']];
    $i = 2;
    foreach ($this->dates($stage) as $key => $value) {
      $sets[] = "$key = %$i";
      $params[$i++] = [$value, 'String'];
    }
    CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET ' . implode(', ', $sets) . ' WHERE id = %1', $params);
  }

  /**
   * Bring the election to a stage by playing every earlier stage as the personas.
   *
   * @param string $stage
   *
   * @return $this
   */
  public function playUntil($stage) {
    $order = [self::STAGE_NOMINATIONS, self::STAGE_ADVERTISE, self::STAGE_VOTING, self::STAGE_RESULTS];
    $target = array_search($stage, $order);
    $this->moveToStage(self::STAGE_NOMINATIONS);
    if ($target >= 1) {
      $this->runNominations();
      $this->moveToStage(self::STAGE_ADVERTISE);
      $this->runResponses();
    }
    if ($target >= 2) {
      $this->moveToStage(self::STAGE_VOTING);
      $this->castBallots();
    }
    if ($target >= 3) {
      $this->moveToStage(self::STAGE_RESULTS);
      $this->generateResults();
    }
    return $this;
  }

  /**
   * Every member of the group (voters and nominees).
   *
   * @return array persona key => contact id
   */
  public function members() {
    return array_filter($this->personas, function ($key) {
      return $key !== 'OUT';
    }, ARRAY_FILTER_USE_KEY);
  }

  public function id($persona) {
    return $this->personas[$persona];
  }

  /**
   * Nominators nominate the nominees, each as themselves.
   */
  public function runNominations() {
    foreach (self::PLAN as [$position, $nominee, $nominators]) {
      foreach ($nominators as $nominator) {
        $result = CRM_Elections_Helper_Process::nominate(
          $this->electionId,
          $this->positions[$position],
          $this->id($nominee),
          $this->id($nominator),
          "$nominator nominates $nominee for $position"
        );
        $this->nominations["$position|$nominee"] = $result['nomination_id'];
      }
    }
  }

  /**
   * Nominees respond, each as themselves.
   */
  public function runResponses() {
    foreach (self::PLAN as [$position, $nominee, , $outcome]) {
      $nominationId = $this->nominations["$position|$nominee"];
      $contactId = $this->id($nominee);
      switch ($outcome) {
        case 'accept':
        case 'accept_then_withdraw':
          CRM_Elections_Helper_Process::accept($nominationId, $contactId, "Statement of $nominee for $position", "https://example.org/$nominee");
          if ($outcome === 'accept_then_withdraw') {
            CRM_Elections_Helper_Process::withdraw($nominationId, $contactId, 'Changed my mind');
          }
          break;

        case 'withdraw':
          CRM_Elections_Helper_Process::withdraw($nominationId, $contactId, 'Not this time');
          break;
      }
    }
  }

  /**
   * The ballot of every member, as ranks keyed by position name then candidate persona.
   *
   * Members are numbered 1..40 (voters V01..V30 then nominees N01..N10). The
   * pattern gives a clear winner in most positions, a tie for Treasurer, one
   * uncontested position, abstentions and partially filled ballots.
   *
   * @return array member persona => [position name => [candidate persona => rank]]
   */
  public function ballots() {
    $ballots = [];
    $k = 0;
    foreach ($this->members() as $persona => $contactId) {
      $k++;
      if (in_array($persona, ['V29', 'V30'], TRUE)) {
        // These two members never vote.
        continue;
      }
      $ballot = [];
      $first = [
        'President' => $k <= 22 ? 'N01' : 'N02',
        'Treasurer' => $k % 2 == 0 ? 'N04' : 'N05',
        'Council Member 1' => $k <= 25 ? 'N06' : 'N01',
        'Council Member 2' => $k % 3 != 0 ? 'N02' : 'N06',
        'Council Member 3' => $k <= 30 ? 'N05' : 'N03',
      ];
      foreach ($first as $position => $firstChoice) {
        $candidates = self::CANDIDATES[$position];
        $others = array_values(array_diff($candidates, [$firstChoice]));
        $ballot[$position] = [$firstChoice => 1];
        // Every seventh member only ranks their first choice.
        if ($k % 7 != 0) {
          foreach ($others as $i => $other) {
            $ballot[$position][$other] = $i + 2;
          }
        }
      }
      // Uncontested position: every fifth member leaves it blank.
      if ($k % 5 != 0) {
        $ballot['Secretary'] = ['N03' => 1];
      }
      $ballots[$persona] = $ballot;
    }
    return $ballots;
  }

  /**
   * Members vote, each as themselves.
   */
  public function castBallots() {
    foreach ($this->ballots() as $persona => $ballot) {
      CRM_Elections_Helper_Process::vote($this->electionId, $this->id($persona), $this->ranksForApi($ballot));
    }
  }

  /**
   * Translate a ballot to candidate nomination ids.
   *
   * @param array $ballot position name => [candidate persona => rank]
   *
   * @return array nomination id => rank
   */
  public function ranksForApi(array $ballot) {
    $ranks = [];
    foreach ($ballot as $position => $positionRanks) {
      foreach ($positionRanks as $candidate => $rank) {
        $ranks[$this->nominations["$position|$candidate"]] = $rank;
      }
    }
    return $ranks;
  }

  public function generateResults() {
    $election = civicrm_api3('Election', 'getsingle', ['id' => $this->electionId]);
    (new CRM_Elections_Helper_Results())->generateResult($this->electionId, $election['anonymize_votes']);
  }

  /**
   * Candidates actually standing in a position, from the database.
   *
   * @param string $position
   *
   * @return array persona keys, sorted
   */
  public function standingCandidates($position) {
    $nominations = civicrm_api3('ElectionNomination', 'get', [
      'election_position_id' => $this->positions[$position],
      'is_eligible_candidate' => 1,
      'has_accepted_nomination' => 1,
      'options' => ['limit' => 0],
    ]);
    $byContact = array_flip($this->personas);
    $personas = [];
    foreach ($nominations['values'] as $nomination) {
      $personas[] = $byContact[$nomination['member_nominee']];
    }
    sort($personas);
    return $personas;
  }

}
