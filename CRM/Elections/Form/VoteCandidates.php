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

    // Already voted
    if ( !isMemberAllowedToReVote( $this->eId ) && hasLoggedInUserAlreadyVoted( $this->eId ) ) {
      throwAccessDeniedException( $this, E::ts('You have already voted in this election.') );
    }

    // Check if there are positions or candidates
    $this->assign('election', $this->election); // Assign the election from class to the template (QuickForms)
    if ( !$this->findElectionPositions() || !$this->findElectionCandidates() || $this->throwExceptionIfNoCandidates() ) {
      return;
    }

    if ( $this->election->isVotingEnded ) {
      throwAccessDeniedException($this, E::ts('Voting is closed on %1', [1 => CRM_Utils_Date::customFormat($this->election->voting_end_date)]));
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
        'name' => E::ts('Vote'),
        'isDefault' => TRUE,
      ],
    ]);

    CRM_Utils_System::setTitle(E::ts('You are Voting for %1', [1 => $this->election->name]));

    Civi::resources()
      ->addVars('electionsBallot', [
        'method' => $this->method,
        'tooMany' => E::ts('You can choose no more than %1.'),
        'chosen' => E::ts('%1 of %2 chosen.'),
        'chosenOne' => E::ts('Chosen: %1.'),
        'duplicate' => E::ts('Rank %1 is given to more than one candidate.'),
        'needFirst' => E::ts('Give your first choice the rank 1.'),
        'counted' => E::ts('%1 of %2 candidates ranked.'),
        'reviewTitle' => E::ts('Check your vote'),
        'reviewIntro' => E::ts('This is how your vote will be recorded. Nothing is saved until you confirm.'),
        'positionTitle' => E::ts('Vote for %1'),
        'noPreference' => E::ts('No preference given for: %1.'),
        'rank' => E::ts('rank %1'),
        'back' => E::ts('Go back and change'),
        'confirm' => E::ts('Confirm my vote'),
      ])
      ->addScriptFile(E::LONG_NAME, 'js/election-ballot.js', 100, 'page-footer');

    parent::buildQuickForm();
  }

  private function throwExceptionIfNoCandidates() {
    $hasCandidatesInOnePosition = FALSE;
    foreach ($this->electionPositions as $electionPosition) {
      if (!empty($electionPosition['candidates'])) {
        $hasCandidatesInOnePosition = TRUE;
        break;
      }
    }

    if (!$hasCandidatesInOnePosition) {
      throwAccessDeniedException($this, E::ts('Candidates are not available in election for voting.'));
      return TRUE;
    }
    return FALSE;
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
      $this->electionPositions[$positionId]['prompt'] = CRM_Elections_VotingMethod::positionPrompt($this->method, $position['name'], count($position['candidates'] ?? []), $position['quantity'] ?? 1);
      $this->electionPositions[$positionId]['seats'] = max(1, (int) ($position['quantity'] ?? 1));
    }
    $this->assign('positions', $this->electionPositions);
    $this->assign('votingMethod', $this->method);
    $this->assign('instructions', CRM_Elections_VotingMethod::instructions($this->method));
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
      $this->add('select2', $element['key'], htmlspecialchars((string) $element['name'], ENT_QUOTES, 'UTF-8'), $this->getVoteSelectionByPosition($element['position_id']), FALSE, ['placeholder' => E::ts('- Rank -')]);
    }
  }

  private function findElectionPositions() {
    $this->electionPositions = civicrm_api3('ElectionPosition', 'get', [
      'election_id' => $this->eId,
      'options'     => ['sort' => 'sortorder ASC'],
    ]);

    if ($this->electionPositions['count'] == 0) {
      throwAccessDeniedException($this, E::ts('Positions are not added in election for voting.'));
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
      throwAccessDeniedException($this, E::ts('Nominations are not added in election for voting.'));
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

    if ($this->method === CRM_Elections_VotingMethod::PLURALITY) {
      $ticks = [];
      foreach ($elements as $element) {
        if (!empty($values[$element['key']])) {
          $ticks[$element['position_id']] = ($ticks[$element['position_id']] ?? 0) + 1;
        }
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
          $this->_errors[$key] = E::ts('Rank must be a valid positive integer.');
        }

        // Similar ranks to different candidates are not allowed.
        if (!in_array($value, $voteRanksByPosition[$element['position_id']])) {
          $voteRanksByPosition[$element['position_id']][] = $value;
        }
        elseif ($value != '') {
          $this->_errors[$key] = E::ts('Rank must be different for each candidate.');
        }

      }
    }

    foreach ($voteRanksByPosition as $positionId => $ranksByPosition) {
      if (array_filter($ranksByPosition)) {
        $ranks = array_values($ranksByPosition);
        if (!in_array(1, $ranks)) {
          $this->_errors['_qf_default'] = E::ts('For each position, you may or may not assign a number to candidates. But if you are assigning numbers for a position, you must assign number 1 to at least one candidate.');
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

    civicrm_api3('ElectionVote', 'addvotes', $addVotesParams);

    $this->createVoteActivity([
      'member_cid' => $member_cid,
    ]);

    if ( $this->cid && $this->cs ) {
      // User is validated by cs and cid, not logged in.
      // Indicate the voting was done as the validated user.
      $contact = \Civi\Api4\Contact::get(FALSE)
                                    ->addSelect('display_name')
                                    ->addWhere('id', '=', $this->cid)
                                    ->execute()
                                    ->first();

      CRM_Core_Session::setStatus(E::ts('You have successfully voted as %1 in the election.', [1 => $contact['display_name']]), '', 'success');
    } else {
      CRM_Core_Session::setStatus(E::ts('You have successfully voted in the election.'), '', 'success');
    }

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
