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

  public function buildQuickForm() {
    hideNonRequiredItemsOnPage($this);
    $this->eId = retrieveElectionIdFromUrl($this);
    if ($this->eId == -1) {
      return;
    }
    $this->election = findElectionById($this->eId);

    // Election is not visible
    if ( !$this->election->isVisible ) {
      throwAccessDeniedPage($this);
      return;
    }

    // Logged in but not allowed to vote
    if ( !empty( \CRM_Core_Session::getLoggedInContactID() ) && !isLoggedInMemberAllowedToVote( $this->eId ) ) {
      throwNonMemberAccessDenied($this);
      return;
    }

    // Already voted
    if ( !isMemberAllowedToReVote( $this->eId ) && hasLoggedInUserAlreadyVoted( $this->eId ) ) {
      throwAccessDeniedException( $this, 'You have already voted in this election.' );
    }

    // Check if there are positions or candidates
    $this->assign('election', $this->election); // Assign the election from class to the template (QuickForms)
    if ( !$this->findElectionPositions() || !$this->findElectionCandidates() || $this->throwExceptionIfNoCandidates() ) {
      return;
    }

    if ( $this->election->isVotingEnded ) {
      throwAccessDeniedException($this, 'Voting is closed on ' . CRM_Utils_Date::customFormat($this->election->voting_end_date));
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
    }

    $this->assignFormElements();

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Vote'),
        'isDefault' => TRUE,
      ],
    ]);

    CRM_Utils_System::setTitle('You are Voting for ' . $this->election->name);

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
      throwAccessDeniedException($this, 'Candidates are not available in election for voting.');
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
    $this->assign('positions', $this->electionPositions);
    $this->addElement('hidden', 'eid', $this->eId);
    $elements = $this->getFormElementsByCandidates();
    foreach ($elements as $element) {
      $this->add('select2', $element['key'], $element['name'], $this->getVoteSelectionByPosition($element['position_id']), FALSE);
    }
  }

  private function findElectionPositions() {
    $this->electionPositions = civicrm_api3('ElectionPosition', 'get', [
      'election_id' => $this->eId,
      'options'     => ['sort' => 'sortorder ASC'],
    ]);

    if ($this->electionPositions['count'] == 0) {
      throwAccessDeniedException($this, 'Positions are not added in election for voting.');
      return FALSE;
    }

    $this->electionPositions = $this->electionPositions['values'];
    return TRUE;
  }

  private function findElectionCandidates() {
    $this->electionCandidates = civicrm_api3('ElectionNomination', 'get', [
      'is_eligible_candidate' => 1,
      'has_accepted_nomination' => 1,
      'return' => ['id', 'member_nominee', 'member_nominee.display_name', 'member_nominee.image_URL', 'election_position_id'],
      'election_position_id' => ['IN' => array_column($this->electionPositions, 'id')],
      'options' => ['limit' => 0],
    ]);

    if ($this->electionCandidates == 0) {
      throwAccessDeniedException($this, 'Nominations are not added in election for voting.');
      return FALSE;
    }

    $this->electionCandidates = $this->electionCandidates['values'];
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

    foreach ($elements as $element) {
      $key = $element['key'];
      if (array_key_exists($key, $values)) {

        if (!array_key_exists($element['position_id'], $voteRanksByPosition)) {
          $voteRanksByPosition[$element['position_id']] = [];
        }
        $value = $values[$key];
        if ($value != '' && !CRM_Utils_Rule::positiveInteger($value)) {
          $this->_errors[$key] = 'Rank must be a valid positive integer.';
        }

        // Similar ranks to different candidates are not allowed.
        if (!in_array($value, $voteRanksByPosition[$element['position_id']])) {
          $voteRanksByPosition[$element['position_id']][] = $value;
        }
        elseif ($value != '') {
          $this->_errors[$key] = 'Rank must be different for each candidate.';
        }

      }
    }

    foreach ($voteRanksByPosition as $positionId => $ranksByPosition) {
      if (array_filter($ranksByPosition)) {
        $ranks = array_values($ranksByPosition);
        if (!in_array(1, $ranks)) {
          $this->_errors['_qf_default'] = 'For each position, You may or may not assign a number to candidates. But if you are assigning numbers for a position, you must assign number 1 to at least one candidate';
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
        $rank = $values[$key];
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

      CRM_Core_Session::setStatus('You have successfully voted as ' . $contact['display_name'] . ' in the election.', '', 'success');
    } else {
      CRM_Core_Session::setStatus('You have successfully voted in the election.', '', 'success');
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
      'subject'  => 'Voted in an election : ' . $this->election->name,
    ]);
  }

}
