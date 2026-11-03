<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Elections_Form_VoteCandidates extends CRM_Elections_Form_Base {

  private $eId = 0;
  private $cid = NULL;
  private $cs = NULL;
  private $election = NULL;
  private $electionPositions = [];
  private $electionCandidates = [];
  private $method = CRM_Elections_VotingMethod::IRV;

  public function buildQuickForm() {
    hideNonRequiredItemsOnPage($this);
    $this->eId = retrieveElectionIdFromUrl($this);
    if ($this->eId == -1) {
      return;
    }
    $this->election = findElectionById($this->eId);
    CRM_Elections_Helper_Utils::assignBackToElection($this, $this->eId, $this->cid, $this->cs);
    $this->method = CRM_Elections_VotingMethod::normalise($this->election->voting_method);

    // Election is not visible
    if ( !$this->election->isVisible ) {
      throwAccessDeniedPage($this);
      return;
    }

    // User is not logged in, and the election does not allow checksum access
    if ( empty( \CRM_Core_Session::getLoggedInContactID() ) && !filter_var($this->election->allow_checksum_access, FILTER_VALIDATE_BOOL) ) {
      throwAccessDeniedPage($this);
      return;
    }

    // Not allowed to take part (logged in, or using a personal link)
    if ( !isLoggedInMemberAllowedToVote( $this->eId ) ) {
      throwNonMemberAccessDenied($this);
      return;
    }

    // Already voted, and the election does not let people change their vote
    $hasVoted = hasLoggedInUserAlreadyVoted($this->eId);
    if ( !isMemberAllowedToReVote( $this->eId ) && $hasVoted ) {
      throwAccessDeniedException($this, E::ts('You have already voted in this election, and it does not let people change their vote.'), [
        'return_button_text' => E::ts('Back to the election'),
        'return_button_action' => CRM_Elections_Helper_Utils::backToElectionUrl($this->eId, $this->cid, $this->cs),
      ]);
      return;
    }
    // Only a voter who may change an earlier vote is told that this one replaces it.
    $this->assign('replacesVote', $hasVoted);

    // Check if there are positions or candidates
    $this->assign('election', $this->election); // Assign the election from class to the template (QuickForms)
    if ( !$this->findElectionPositions() || !$this->findElectionCandidates() || $this->throwExceptionIfNoCandidates() ) {
      return;
    }

    if ( $this->election->isVotingEnded ) {
      throwAccessDeniedException($this, E::ts('Voting closed on %1.', [1 => CRM_Utils_Date::customFormat($this->election->voting_end_date)]), [
        'errorKind' => 'status',
        'return_button_text' => E::ts('Back to the election'),
        'return_button_action' => CRM_Elections_Helper_Utils::backToElectionUrl($this->eId, $this->cid, $this->cs),
      ]);
      return;
    }

    if ( $this->cid && $this->cs ) {
      // Expose to Smarty
      $contact = \Civi\Api4\Contact::get(FALSE)
                                    ->addSelect('email_primary', 'display_name')
                                    ->addWhere('id', '=', $this->cid)
                                    ->execute()
                                    ->first();

      $this->assign( 'checksum_authenticated', $contact );

      $login_url = getLoginPageURL(\CRM_Utils_System::currentPath());
      $this->assign( 'login_url', sprintf( '%s?eid=%s', $login_url, $this->eId ) );
    }

    $this->assignFormElements();

    $this->addButtons([
      [
        'type' => 'submit',
        // Without JavaScript the button sends the vote straight away, so it says so; with JavaScript the ballot
        // changes it to Review Your Vote (js/election-ballot.js) and the vote is sent from the review window.
        'name' => E::ts('Submit Vote'),
        'isDefault' => TRUE,
      ],
    ]);

    CRM_Utils_System::setTitle(E::ts('Ballot for %1', [1 => $this->election->name]));

    Civi::resources()
      ->addVars('electionsBallot', [
        'method' => $this->method,
        'writeInRank' => E::ts('Give the person you wrote in a rank, or clear the name.'),
        'writtenIn' => E::ts('%1 (written in)'),
        'tooMany' => E::ts('You can choose no more than %1.'),
        'chosen' => E::ts('%1 of %2 chosen.'),
        'chosenOne' => E::ts('Chosen: %1.'),
        'duplicate' => E::ts('Rank %1 is given to more than one candidate.'),
        'needFirst' => E::ts('Give your first choice the rank 1.'),
        'counted' => E::ts('%1 of %2 candidates ranked.'),
        'review' => E::ts('Review Your Vote'),
        'reviewTitle' => E::ts('Review Your Vote'),
        'reviewIntro' => E::ts('This is how your vote will be recorded. Nothing is saved until you select Submit Vote.'),
        'positionTitle' => E::ts('Vote for %1'),
        'noRank' => E::ts('You have not ranked anyone for %1.'),
        'noChoice' => E::ts('You have not chosen anyone for %1.'),
        'noAnswer' => E::ts('You have not answered %1.'),
        'rankedRow' => E::ts('%1, rank %2'),
        'back' => E::ts('Go Back and Change'),
        'confirm' => E::ts('Submit Vote'),
      ])
      ->addScriptFile(E::LONG_NAME, 'js/election-ballot.js', 100, 'page-footer');

    parent::buildQuickForm();
  }

  public function setDefaultValues() {
    // A ballot that was refused after it was sent comes back as the person filled it in.
    return CRM_Elections_Helper_Utils::recallInput('ballot');
  }

  private function throwExceptionIfNoCandidates() {
    $hasCandidatesInOnePosition = FALSE;
    foreach ($this->electionPositions as $electionPosition) {
      if (!empty($electionPosition['candidates']) || $this->isQuestion($electionPosition)) {
        // A question can be voted on without any candidates in the election.
        $hasCandidatesInOnePosition = TRUE;
        break;
      }
    }

    if (!$hasCandidatesInOnePosition) {
      throwAccessDeniedException($this, E::ts('There are no candidates to vote for yet.'), [
        'errorKind' => 'status',
        'return_button_text' => E::ts('Back to the election'),
        'return_button_action' => CRM_Elections_Helper_Utils::backToElectionUrl($this->eId, $this->cid, $this->cs),
      ]);
      return TRUE;
    }
    return FALSE;
  }

  private function isQuestion(array $position) {
    return ($position['position_type'] ?? '') === CRM_Elections_BAO_ElectionPosition::TYPE_REFERENDUM;
  }

  /**
   * The choice on each referendum question: a set of radio buttons, which can be cleared to leave a question unanswered.
   */
  private function addQuestionElements() {
    foreach ($this->electionPositions as $positionId => $position) {
      if (!$this->isQuestion($position)) {
        continue;
      }
      $options = [];
      foreach (CRM_Elections_BAO_ElectionPosition::getOptions($positionId) as $option) {
        $options[$option['id']] = htmlspecialchars((string) $option['label'], ENT_QUOTES, 'UTF-8');
      }
      $this->addRadio("option_{$positionId}", htmlspecialchars((string) $position['name'], ENT_QUOTES, 'UTF-8'), $options, ['allowClear' => TRUE], '', FALSE);
      $this->electionPositions[$positionId]['isQuestion'] = TRUE;
      $this->electionPositions[$positionId]['questionKey'] = "option_{$positionId}";
    }
  }

  /**
   * The choices the voter made on referendum questions.
   *
   * @param array $values
   *
   * @return array[]
   */
  private function getOptionVotes(array $values) {
    $choices = [];
    foreach ($this->electionPositions as $positionId => $position) {
      if ($this->isQuestion($position) && !empty($values["option_{$positionId}"])) {
        $choices[] = ['position_id' => (int) $positionId, 'option_id' => (int) $values["option_{$positionId}"]];
      }
    }
    return $choices;
  }

  private function getFormElementsByCandidates() {
    $elements = [];
    foreach ($this->electionCandidates as $candidate) {
      $candidateKey = $this->getCandidateKey($candidate);
      $elements[] = [
        'key' => $candidateKey,
        'nominationId' => $candidate['id'],
        'name' => $candidate['member_nominee.display_name'],
        'position_id' => $candidate['election_position_id'],
      ];
    }
    return $elements;
  }

  private function getCandidateKey($candidate) {
    return 'candidate_' . $candidate['id'];
  }

  private function getVoteSelectionByPosition($positionId) {
    $totalCandidates = count($this->electionPositions[$positionId]['candidates']);
    $options = [];
    for ($i = 1; $i <= $totalCandidates; $i++) {
      $options[] = [
        'text' => $i,
        'id'   => $i,
      ];
    }
    return $options;
  }

  private function assignFormElements() {
    CRM_Elections_BAO_ElectionResult::updateCandidateProfilePictures($this->electionPositions);
    foreach ($this->electionPositions as $positionId => $position) {
      $this->electionPositions[$positionId]['candidates'] = $position['candidates'] ?? [];
      $position = $this->electionPositions[$positionId];
      $this->electionPositions[$positionId]['writeInPlaces'] = $this->isQuestion($position) ? 0 : CRM_Elections_Helper_WriteIn::places($position);
      $this->electionPositions[$positionId]['prompt'] = $this->isQuestion($position) ? $position['name'] : CRM_Elections_VotingMethod::positionPrompt($this->method, $position['name'], count($position['candidates'] ?? []), $position['quantity'] ?? 1);
      $this->electionPositions[$positionId]['seats'] = max(1, (int) ($position['quantity'] ?? 1));
    }
    $this->addWriteInElements();
    $this->addQuestionElements();
    $this->assign('positions', $this->electionPositions);
    $this->assign('votingMethod', $this->method);
    $hasQuestions = FALSE;
    $hasPositions = FALSE;
    foreach ($this->electionPositions as $position) {
      if ($this->isQuestion($position)) {
        $hasQuestions = TRUE;
      }
      else {
        $hasPositions = TRUE;
      }
    }
    $this->assign('instructions', CRM_Elections_VotingMethod::ballotInstructions($this->method, $hasPositions, $hasQuestions));
    $this->addElement('hidden', 'eid', $this->eId);
    if ( $this->cid && $this->cs ) {
      $this->addElement('hidden', 'cid', $this->cid);
      $this->addElement('hidden', 'cs', $this->cs);
    }
    $elements = $this->getFormElementsByCandidates();
    foreach ($elements as $element) {
      if ($this->method === CRM_Elections_VotingMethod::PLURALITY) {
        $this->addElement('checkbox', $element['key'], htmlspecialchars((string) $element['name'], ENT_QUOTES, 'UTF-8'));
        continue;
      }
      $this->add('select2', $element['key'], htmlspecialchars((string) $element['name'], ENT_QUOTES, 'UTF-8'), $this->getVoteSelectionByPosition($element['position_id']), FALSE, ['placeholder' => E::ts('Select a rank')]);
    }
  }

  /**
   * The places on the ballot where a voter can write a person in, for each position that allows it: a person
   * picker, and a rank when the election is counted by ranked choice.
   */
  private function addWriteInElements() {
    foreach ($this->electionPositions as $positionId => $position) {
      $places = (int) $position['writeInPlaces'];
      $rows = [];
      for ($n = 1; $n <= $places; $n++) {
        $row = ['person' => "writein_{$positionId}_{$n}", 'rank' => "writeinrank_{$positionId}_{$n}", 'number' => $n];
        $this->addEntityRef($row['person'], $places > 1 ? E::ts('Write someone in (place %1)', [1 => $n]) : E::ts('Write someone in'), [
          'entity' => 'ElectionNominee',
          'placeholder' => E::ts('Type at least 3 letters of a name'),
          'api' => ['election_id' => $this->election->id, 'write_in' => 1],
        ], FALSE);
        if ($this->method !== CRM_Elections_VotingMethod::PLURALITY) {
          $options = [];
          for ($i = 1; $i <= count($position['candidates']) + $places; $i++) {
            $options[] = ['text' => $i, 'id' => $i];
          }
          $this->add('select2', $row['rank'], $places > 1 ? E::ts('Rank for place %1', [1 => $n]) : E::ts('Rank'), $options, FALSE, ['placeholder' => E::ts('Select a rank')]);
        }
        $rows[] = $row;
      }
      $this->electionPositions[$positionId]['writeInRows'] = $rows;
    }
  }

  /**
   * The people the voter wrote in: position, person and rank (ticks have rank 1).
   *
   * @param array $values
   *
   * @return array[]
   */
  private function getWriteIns(array $values) {
    $writeIns = [];
    foreach ($this->electionPositions as $positionId => $position) {
      foreach ($position['writeInRows'] ?? [] as $row) {
        $contactId = $values[$row['person']] ?? '';
        if ($contactId === '' || $contactId === NULL) {
          continue;
        }
        $rank = ($this->method === CRM_Elections_VotingMethod::PLURALITY) ? 1 : ($values[$row['rank']] ?? '');
        $writeIns[] = ['position_id' => (int) $positionId, 'contact_id' => $contactId, 'rank' => $rank, 'row' => $row];
      }
    }
    return $writeIns;
  }

  private function findElectionPositions() {
    $this->electionPositions = civicrm_api3('ElectionPosition', 'get', [
      'election_id' => $this->eId,
      'options'     => ['sort' => 'sortorder ASC'],
    ]);

    if ($this->electionPositions['count'] == 0) {
      throwAccessDeniedException($this, E::ts('There are no positions to vote on yet.'), [
        'errorKind' => 'status',
        'return_button_text' => E::ts('Back to the election'),
        'return_button_action' => CRM_Elections_Helper_Utils::backToElectionUrl($this->eId, $this->cid, $this->cs),
      ]);
      return FALSE;
    }

    $this->electionPositions = $this->electionPositions['values'];
    return TRUE;
  }

  private function findElectionCandidates() {
    $this->electionCandidates = civicrm_api3('ElectionNomination', 'get', [
      'is_eligible_candidate' => 1,
      'has_accepted_nomination' => 1,
      'return' => array_merge(['id', 'member_nominee', 'member_nominee.image_URL', 'election_position_id', 'comments', 'statement_url'], CRM_Elections_Helper_Candidate::NAME_RETURN_FIELDS),
      'election_position_id' => ['IN' => array_column($this->electionPositions, 'id')],
      'options' => ['limit' => 0],
    ]);

    if ($this->electionCandidates == 0) {
      throwAccessDeniedException($this, E::ts('There are no candidates to vote for yet.'), [
        'errorKind' => 'status',
        'return_button_text' => E::ts('Back to the election'),
        'return_button_action' => CRM_Elections_Helper_Utils::backToElectionUrl($this->eId, $this->cid, $this->cs),
      ]);
      return FALSE;
    }

    $this->electionCandidates = $this->electionCandidates['values'];
    CRM_Elections_Helper_Candidate::applyNameFormatToAll($this->electionCandidates, CRM_Elections_Helper_Candidate::getNameFormatForElection($this->election->id));
    $this->mapCandidatesWithPosition();
    return TRUE;
  }

  private function mapCandidatesWithPosition() {
    foreach ($this->electionCandidates as $electionCandidate) {
      $positionId = $electionCandidate['election_position_id'];
      if (!array_key_exists('candidates', $this->electionPositions[$positionId])) {
        $this->electionPositions[$positionId]['candidates'] = [];
      }
      $this->electionPositions[$positionId]['candidates'][] = $electionCandidate;
    }

    foreach ($this->electionPositions as $index => $electionPosition) {
      if (isset($this->electionPositions[$index]['candidates'])) {
        shuffle($this->electionPositions[$index]['candidates']);
      }
    }
  }

  public function validate() {
    $elements = $this->getFormElementsByCandidates();
    $values = $this->exportValues();
    $voteRanksByPosition = [];

    $writeIns = $this->getWriteIns($values);
    foreach ($writeIns as $writeIn) {
      if (!CRM_Utils_Rule::positiveInteger($writeIn['contact_id'])) {
        $this->_errors[$writeIn['row']['person']] = E::ts('Choose a person from the list.');
      }
      elseif ($this->method !== CRM_Elections_VotingMethod::PLURALITY && !CRM_Utils_Rule::positiveInteger($writeIn['rank'])) {
        $this->_errors[$writeIn['row']['rank']] = E::ts('Give the person you wrote in a rank, or clear the name.');
      }
    }

    if ($this->method === CRM_Elections_VotingMethod::PLURALITY) {
      $ticks = [];
      foreach ($elements as $element) {
        if (!empty($values[$element['key']])) {
          $ticks[$element['position_id']] = ($ticks[$element['position_id']] ?? 0) + 1;
        }
      }
      foreach ($writeIns as $writeIn) {
        $ticks[$writeIn['position_id']] = ($ticks[$writeIn['position_id']] ?? 0) + 1;
      }
      foreach ($ticks as $positionId => $count) {
        $seats = max(1, (int) ($this->electionPositions[$positionId]['quantity'] ?? 1));
        if ($count > $seats) {
          $this->_errors['_qf_default'] = E::ts('For %1 you can choose no more than %2.', [1 => $this->electionPositions[$positionId]['name'], 2 => $seats]);
        }
      }
      return parent::validate();
    }

    foreach ($elements as $element) {
      $key = $element['key'];
      if (array_key_exists($key, $values)) {

        if (!array_key_exists($element['position_id'], $voteRanksByPosition)) {
          $voteRanksByPosition[$element['position_id']] = [];
        }
        $value = $values[$key];
        if ($value != '' && !CRM_Utils_Rule::positiveInteger($value)) {
          $this->_errors[$key] = E::ts('Choose a rank from the list.');
        }

        // Similar ranks to different candidates are not allowed.
        if (!in_array($value, $voteRanksByPosition[$element['position_id']])) {
          $voteRanksByPosition[$element['position_id']][] = $value;
        }
        elseif ($value != '') {
          $this->_errors[$key] = E::ts('Each candidate needs a different rank.');
        }

      }
    }

    foreach ($writeIns as $writeIn) {
      $rank = $writeIn['rank'];
      if (CRM_Utils_Rule::positiveInteger($rank)) {
        if (in_array($rank, $voteRanksByPosition[$writeIn['position_id']] ?? [])) {
          $this->_errors[$writeIn['row']['rank']] = E::ts('Each candidate needs a different rank.');
        }
        $voteRanksByPosition[$writeIn['position_id']][] = $rank;
      }
    }

    foreach ($voteRanksByPosition as $positionId => $ranksByPosition) {
      if (array_filter($ranksByPosition)) {
        $ranks = array_values($ranksByPosition);
        if (!in_array(1, $ranks)) {
          $this->_errors['_qf_default'] = E::ts('For %1, give your first choice the rank 1.', [1 => $this->electionPositions[$positionId]['name']]);
        }
      }
    }

    return parent::validate();
  }

  public function preProcess() {
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive');
    $cs = CRM_Utils_Request::retrieve('cs', 'String');

    // Only store these if the user is not logged in. Otherwise we want to
    // defer to the logged in contact.
    if ( empty( \CRM_Core_Session::getLoggedInContactID() ) && $cid && $cs ) {
      $this->cid = $cid;
      $this->cs = $cs;
    }
  }

  public function postProcess() {
    $values = $this->exportValues();
    $elements = $this->getFormElementsByCandidates();

    $member_cid = \CRM_Core_Session::getLoggedInContactID();

    if ( !$member_cid && $this->cid && $this->cs ) {
      $member_cid = $this->cid;
    }

    $addVotesParams = [
      'election_id' => $this->election->id,
      'member_id'   => $member_cid,
      'votes'       => [],
    ];
    foreach ($elements as $element) {
      $key = $element['key'];
      if (array_key_exists($key, $values)) {
        $nominationId = $element['nominationId'];
        $rank = ($this->method === CRM_Elections_VotingMethod::PLURALITY) ? (empty($values[$key]) ? '' : 1) : $values[$key];
        if ($rank != '') {
          $addVotesParams['votes'][] = [
            'election_nomination_id' => $nominationId,
            'rank'                   => $rank,
          ];
        }
      }
    }

    $addVotesParams['option_votes'] = $this->getOptionVotes($values);
    $addVotesParams['write_ins'] = array_map(fn($w) => ['position_id' => $w['position_id'], 'contact_id' => (int) $w['contact_id'], 'rank' => (int) $w['rank']], $this->getWriteIns($values));

    try {
      civicrm_api3('ElectionVote', 'addvotes', $addVotesParams);
    }
    catch (CRM_Core_Exception $e) {
      // Something the form could not know (for example a person who has declined since the page opened).
      CRM_Core_Session::setStatus($e->getMessage(), E::ts('Your vote was not saved'), 'error');
      // The ballot comes back with the choices the person made, so they can fix it and send it again.
      CRM_Elections_Helper_Utils::rememberInput('ballot', array_filter($values, fn($key) => preg_match('/^(candidate|writein|writeinrank|option)_/', (string) $key), ARRAY_FILTER_USE_KEY));
      $back = Civi::url('current://civicrm/elections/vote')->addQuery(['eid' => $this->eId]);
      if ($this->cid && $this->cs) {
        $back->addQuery(['cid' => $this->cid, 'cs' => $this->cs]);
      }
      CRM_Utils_System::redirect($back);
    }

    $this->createVoteActivity([
      'member_cid' => $member_cid,
    ]);

    // The election page then says when, and who the person is acting as.
    CRM_Core_Session::setStatus(E::ts('Vote recorded.'), '', 'success');

    parent::postProcess();

    // Redirect back to the main election info view
    $redirectUrl = Civi::url('current://civicrm/elections/view');
    $redirectUrl->addQuery(['eid' => $this->eId]);

    // Conditionally add contact ID and checksum
    if ( $this->cid && $this->cs ) {
        $redirectUrl->addQuery(['cid' => $this->cid]);
        $redirectUrl->addQuery(['cs' => $this->cs]);
    }
    CRM_Utils_System::redirect( $redirectUrl );
  }

  private function createVoteActivity($params) {
    $currentDateTime = (new DateTime())->format('Y-m-d H:i:s');
    civicrm_api3('Activity', 'create', [
      'source_contact_id' => $params['member_cid'],
      'activity_type_id' => 'Vote',
      'activity_date_time' => $currentDateTime,
      'status_id' => 'Completed',
      'assignee_id' => $params['member_cid'],
      'target_id' => $params['member_cid'],
      'source_record_id' => $this->eId,
      'subject'  => E::ts('Voted in an election : %1', [1 => $this->election->name]),
    ]);
  }

}
