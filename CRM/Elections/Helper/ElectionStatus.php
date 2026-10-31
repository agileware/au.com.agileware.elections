<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * What the election page says about where the election is and what the person looking at it can do now.
 *
 * Kept apart from the page so the wording and the rules can be tested without drawing a page.
 */
class CRM_Elections_Helper_ElectionStatus {

  /**
   * The stages as a list for the stage stepper.
   *
   * @param CRM_Elections_BAO_Election $election
   *
   * @return array[]
   *   Each with key, label, from, to (a date or NULL) and state: passed, current or upcoming.
   */
  public static function steps($election) {
    $stage = $election->getStage()['key'];
    // How many steps are behind us, and which one is under way (if any).
    $position = [
      'upcoming' => [0, NULL],
      'nominations' => [0, 0],
      'advertise' => [1, 1],
      'voting' => [2, 2],
      'closed' => [3, NULL],
      'results' => [3, 3],
      'inactive' => [0, NULL],
      'deleted' => [0, NULL],
    ][$stage] ?? [0, NULL];
    $steps = [
      ['key' => 'nominations', 'label' => E::ts('Nominations'), 'from' => $election->nomination_start_date, 'to' => $election->nomination_end_date],
      ['key' => 'candidates', 'label' => E::ts('Candidates'), 'from' => $election->advertise_candidates_date, 'to' => NULL],
      ['key' => 'voting', 'label' => E::ts('Voting'), 'from' => $election->voting_start_date, 'to' => $election->voting_end_date],
      ['key' => 'results', 'label' => E::ts('Results'), 'from' => $election->result_date, 'to' => NULL],
    ];
    foreach ($steps as $index => &$step) {
      $step['state'] = $position[1] === $index ? 'current' : ($index < $position[0] ? 'passed' : 'upcoming');
      $step['stateLabel'] = ['current' => E::ts('current stage'), 'passed' => E::ts('finished'), 'upcoming' => E::ts('not started')][$step['state']];
    }
    return $steps;
  }

  /**
   * One clear sentence, and the button to go with it, for the person looking at the election.
   *
   * @param CRM_Elections_BAO_Election $election
   * @param int|null $contactId
   *   The person looking (logged in, or identified by a personal link).
   * @param string $query
   *   The query string that keeps a personal link working, for example "eid=3&cid=5&cs=...".
   * @param array $state
   *   canNominate, canVote, hasVoted, voteDate, allowedToChangeVote.
   *
   * @return array
   *   type (info, action, done or closed), message, and actions: a list of label and url.
   */
  public static function nextAction($election, $contactId, $query, array $state) {
    $date = function ($value) {
      return CRM_Utils_Date::customFormat($value);
    };
    $link = function ($label, $path, $extra = '') use ($query) {
      return ['label' => $label, 'url' => CRM_Utils_System::url($path, $query . $extra)];
    };
    $stage = $election->getStage()['key'];

    if ($election->isResultsOut) {
      return ['type' => 'done', 'message' => E::ts('The votes have been counted. The results are below.'), 'actions' => []];
    }
    if ($election->isVotingEnded) {
      return ['type' => 'closed', 'message' => E::ts('Voting has closed. The results will be published on %1.', [1 => $date($election->result_date)]), 'actions' => []];
    }

    if ($election->isVotingStarted) {
      // Someone a voter has written in is asked to answer, whether or not they can vote themselves.
      $writtenIn = $contactId ? self::nominationsAwaitingAnswer($election->id, $contactId, TRUE) : [];
      if ($writtenIn) {
        return [
          'type' => 'action',
          'message' => E::ts('A voter has written you in for %1. Votes for you count only if you accept before voting closes on %2.', [1 => implode(', ', array_column($writtenIn, 'position')), 2 => $date($election->voting_end_date)]),
          'actions' => [$link(E::ts('Respond to Nomination'), 'civicrm/elections/nominations/accept', '&enid=' . (int) $writtenIn[0]['id'])],
        ];
      }
      if (!$state['canVote']) {
        return ['type' => 'closed', 'message' => E::ts('Voting is open until %1. Only people in this election\'s groups can vote in it.', [1 => $date($election->voting_end_date)]), 'actions' => []];
      }
      if ($state['hasVoted']) {
        $message = E::ts('You voted on %1.', [1 => $date($state['voteDate'])]);
        if ($state['allowedToChangeVote']) {
          return ['type' => 'done', 'message' => $message . ' ' . E::ts('You can change your vote until %1.', [1 => $date($election->voting_end_date)]), 'actions' => [$link(E::ts('Change Vote'), 'civicrm/elections/vote')]];
        }
        return ['type' => 'done', 'message' => $message . ' ' . E::ts('Thank you for voting.'), 'actions' => []];
      }
      return ['type' => 'action', 'message' => (CRM_Elections_VotingMethod::normalise($election->voting_method) === CRM_Elections_VotingMethod::PLURALITY ? E::ts('Voting is open until %1. Choose the candidates for each position to cast your vote.', [1 => $date($election->voting_end_date)]) : E::ts('Voting is open until %1. Rank the candidates for each position to cast your vote.', [1 => $date($election->voting_end_date)])), 'actions' => [$link(E::ts('Vote Now'), 'civicrm/elections/vote')]];
    }

    // Before voting: nominees who have been nominated are asked to respond.
    $pending = $contactId ? self::nominationsAwaitingAnswer($election->id, $contactId) : [];
    if ($pending) {
      $positions = implode(', ', array_column($pending, 'position'));
      $opens = $date($election->voting_start_date);
      return [
        'type' => 'action',
        'message' => E::ts('You have been nominated for %1. Please accept or withdraw before voting opens on %2.', [1 => $positions, 2 => $opens]),
        'actions' => [$link(E::ts('Respond to Nomination'), 'civicrm/elections/nominations/accept', '&enid=' . (int) $pending[0]['id'])],
      ];
    }

    $election->assignDisplayOptions();
    if ($election->isNominationsInProgress && !$election->onlineNominations) {
      return ['type' => 'info', 'message' => E::ts('The candidates for this election are chosen by the organisers. Anyone chosen is asked to accept, and the candidates are listed below once they are announced.'), 'actions' => []];
    }
    if ($election->isNominationsInProgress) {
      if ($state['canNominate']) {
        return ['type' => 'action', 'message' => E::ts('Nominations are open until %1. Nominate someone, or second a nomination that needs a second.', [1 => $date($election->nomination_end_date)]), 'actions' => [$link(E::ts('Submit Nomination'), 'civicrm/elections/nominations/create')]];
      }
      return ['type' => 'info', 'message' => E::ts('Nominations are open until %1. You are not able to nominate in this election.', [1 => $date($election->nomination_end_date)]), 'actions' => []];
    }
    if ($election->advertiseCandidatesStarted) {
      return ['type' => 'info', 'message' => E::ts('Nominations are closed. The candidates are listed below. Voting opens on %1.', [1 => $date($election->voting_start_date)]), 'actions' => []];
    }
    if ($election->isNominationsStarted) {
      return ['type' => 'info', 'message' => E::ts('Nominations are closed. The candidates will be listed from %1. Voting opens on %2.', [1 => $date($election->advertise_candidates_date), 2 => $date($election->voting_start_date)]), 'actions' => []];
    }
    return ['type' => 'info', 'message' => E::ts('Nominations open on %1. The positions are listed below.', [1 => $date($election->nomination_start_date)]), 'actions' => []];
  }

  /**
   * The person's own ballot, in the order they ranked, for each position. Only they see it.
   *
   * Empty once the votes are anonymised (the ballot no longer says whose it is).
   *
   * @param CRM_Elections_BAO_Election $election
   * @param int $contactId
   *
   * @return array[]
   *   Each with position and candidates: a list of names, first choice first.
   */
  public static function ownBallot($election, $contactId) {
    if (!$contactId) {
      return [];
    }
    $dao = CRM_Core_DAO::executeQuery('
      SELECT p.id AS position_id, p.name AS position, v.`rank` AS rank_given,
             c.display_name, c.first_name, c.last_name, c.nick_name
      FROM civicrm_election_vote v
      INNER JOIN civicrm_election_nomination n ON n.id = v.election_nomination_id
      INNER JOIN civicrm_election_position p ON p.id = n.election_position_id
      INNER JOIN civicrm_contact c ON c.id = n.member_nominee
      WHERE p.election_id = %1 AND v.member_id = %2
      ORDER BY p.sortorder, p.id, v.`rank`
    ', [1 => [(int) $election->id, 'Integer'], 2 => [(int) $contactId, 'Integer']]);
    $ballot = [];
    while ($dao->fetch()) {
      $ballot[$dao->position_id]['position'] = $dao->position;
      $ballot[$dao->position_id]['candidates'][] = CRM_Elections_Helper_Candidate::formatName($dao->display_name, $dao->first_name, $dao->last_name, $dao->nick_name, $election->name_format ?: NULL);
    }
    // The answer to each referendum question.
    $answers = CRM_Core_DAO::executeQuery('
      SELECT p.id AS position_id, p.name AS position, o.label
      FROM civicrm_election_option_vote ov
      INNER JOIN civicrm_election_option o ON o.id = ov.election_option_id
      INNER JOIN civicrm_election_position p ON p.id = ov.election_position_id
      WHERE p.election_id = %1 AND ov.member_id = %2
      ORDER BY p.sortorder, p.id
    ', [1 => [(int) $election->id, 'Integer'], 2 => [(int) $contactId, 'Integer']]);
    while ($answers->fetch()) {
      $ballot['q' . $answers->position_id] = ['position' => $answers->position, 'candidates' => [$answers->label]];
    }
    return array_values($ballot);
  }

  /**
   * The person's own nominations in this election, each with where it stands and what they can do about it.
   *
   * @param CRM_Elections_BAO_Election $election
   * @param int|null $contactId
   * @param string $query
   *   The query string that keeps a personal link working.
   *
   * @return array[]
   *   Each with position, state (waiting, respond, accepted or withdrawn), stateLabel and actions (label, url, quiet).
   */
  public static function ownNominations($election, $contactId, $query) {
    if (!$contactId) {
      return [];
    }
    $dao = CRM_Core_DAO::executeQuery('
      SELECT n.id, p.name AS position, n.is_eligible_candidate, n.has_accepted_nomination, n.has_rejected_nomination, n.is_write_in,
             (SELECT COUNT(DISTINCT s.member_nominator) FROM civicrm_election_nomination_seconder s WHERE s.election_nomination_id = n.id) AS nominators
      FROM civicrm_election_nomination n
      INNER JOIN civicrm_election_position p ON p.id = n.election_position_id
      WHERE p.election_id = %1 AND n.member_nominee = %2
      ORDER BY p.sortorder, n.id
    ', [1 => [(int) $election->id, 'Integer'], 2 => [(int) $contactId, 'Integer']]);
    $link = function ($label, $path, $id, $quiet = FALSE) use ($query) {
      return ['label' => $label, 'url' => CRM_Utils_System::url($path, $query . '&enid=' . (int) $id), 'quiet' => $quiet];
    };
    $required = (int) $election->required_nominations;
    $rows = [];
    while ($dao->fetch()) {
      $beforeVoting = !$election->isVotingStarted;
      $actions = [];
      if ($dao->has_rejected_nomination) {
        $state = 'withdrawn';
        $label = E::ts('Withdrawn');
      }
      elseif ($dao->is_eligible_candidate && $dao->has_accepted_nomination) {
        $state = 'accepted';
        $label = E::ts('Accepted: you are a candidate');
        if ($beforeVoting) {
          $actions[] = $link(E::ts('Edit statement'), 'civicrm/elections/candidate/statement', $dao->id);
          $actions[] = $link(E::ts('Withdraw Nomination'), 'civicrm/elections/nominations/reject', $dao->id, TRUE);
        }
      }
      elseif ($dao->is_eligible_candidate) {
        $state = 'respond';
        $label = $dao->is_write_in ? E::ts('A voter has written you in: waiting for your answer') : E::ts('Waiting for your answer');
        // A person written in can answer until voting closes.
        if ($beforeVoting || ($dao->is_write_in && !$election->isVotingEnded)) {
          $actions[] = $link(E::ts('Accept Nomination'), 'civicrm/elections/nominations/accept', $dao->id);
          $actions[] = $link(E::ts('Withdraw Nomination'), 'civicrm/elections/nominations/reject', $dao->id, TRUE);
        }
      }
      else {
        $state = 'waiting';
        $missing = max(1, $required - (int) $dao->nominators);
        $label = E::ts('Waiting for %1 more nominator', [1 => $missing, 'plural' => 'Waiting for %count more nominators', 'count' => $missing]);
        if ($beforeVoting) {
          $actions[] = $link(E::ts('Withdraw Nomination'), 'civicrm/elections/nominations/reject', $dao->id, TRUE);
        }
      }
      $rows[] = ['position' => $dao->position, 'state' => $state, 'stateLabel' => $label, 'actions' => $actions];
    }
    return $rows;
  }

  /**
   * Nominations of the person that have enough nominators and are waiting for their answer.
   *
   * @param int $electionId
   * @param int $contactId
   *
   * @return array[]
   *   Each with id and position.
   */
  public static function nominationsAwaitingAnswer($electionId, $contactId, $writeInsOnly = FALSE) {
    $dao = CRM_Core_DAO::executeQuery('
      SELECT n.id, p.name AS position
      FROM civicrm_election_nomination n
      INNER JOIN civicrm_election_position p ON p.id = n.election_position_id
      WHERE p.election_id = %1 AND n.member_nominee = %2' . ($writeInsOnly ? ' AND n.is_write_in = 1' : '') . '
        AND n.is_eligible_candidate = 1 AND IFNULL(n.has_accepted_nomination, 0) = 0 AND IFNULL(n.has_rejected_nomination, 0) = 0
      ORDER BY p.sortorder, n.id
    ', [1 => [(int) $electionId, 'Integer'], 2 => [(int) $contactId, 'Integer']]);
    $rows = [];
    while ($dao->fetch()) {
      $rows[] = ['id' => (int) $dao->id, 'position' => $dao->position];
    }
    return $rows;
  }

  /**
   * What one election's card in the list says: its stage, one line about the next date, and the button.
   *
   * @param array $election
   *   An election as the list builds it: dates and the flags hasNominationsStarted, isNominationsInProgress,
   *   advertiseCandidatesStarted, isVotingStarted, isVotingEnded, isResultsOut and isUserAllowedToVote.
   *
   * @return array
   *   stage (a key for styling), stageLabel, line (plain words with the date in them) and button (a label, or '').
   */
  public static function card(array $election) {
    $date = function ($value) {
      return CRM_Utils_Date::customFormat($value);
    };
    if (!empty($election['isResultsOut'])) {
      return ['stage' => 'results', 'stageLabel' => E::ts('Results published'), 'line' => E::ts('Results published on %1', [1 => $date($election['result_date'])]), 'button' => E::ts('View Election results')];
    }
    if (!empty($election['isVotingEnded'])) {
      return ['stage' => 'closed', 'stageLabel' => E::ts('Voting closed'), 'line' => E::ts('Election results will be available on %1', [1 => $date($election['result_date'])]), 'button' => E::ts('View the Candidates')];
    }
    if (!empty($election['isVotingStarted'])) {
      $card = ['stage' => 'voting', 'stageLabel' => E::ts('Voting open'), 'line' => E::ts('Voting will close on %1', [1 => $date($election['voting_end_date'])]), 'button' => E::ts('Vote in the Election')];
      if (empty($election['isUserAllowedToVote'])) {
        $card['button'] = '';
      }
      return $card;
    }
    if (!empty($election['advertiseCandidatesStarted'])) {
      return ['stage' => 'advertise', 'stageLabel' => E::ts('Candidates announced'), 'line' => E::ts('Voting will open on %1', [1 => $date($election['voting_start_date'])]), 'button' => E::ts('View the Candidates')];
    }
    if (!empty($election['isNominationsInProgress'])) {
      $online = !array_key_exists('allow_online_nominations', $election) || $election['allow_online_nominations'] === NULL || $election['allow_online_nominations'] === '' || !empty($election['allow_online_nominations']);
      return ['stage' => 'nominations', 'stageLabel' => E::ts('Nominations open'), 'line' => $online ? E::ts('Nominations will close on %1', [1 => $date($election['nomination_end_date'])]) : E::ts('The organisers are choosing the candidates'), 'button' => $online ? E::ts('View and submit Nominations') : E::ts('View the Nominations')];
    }
    if (!empty($election['hasNominationsStarted'])) {
      return ['stage' => 'advertise', 'stageLabel' => E::ts('Nominations closed'), 'line' => E::ts('Candidates will be advertised from %1', [1 => $date($election['advertise_candidates_date'])]), 'button' => E::ts('View the Nominations')];
    }
    return ['stage' => 'upcoming', 'stageLabel' => E::ts('Not started'), 'line' => E::ts('Nominations will open on %1', [1 => $date($election['nomination_start_date'])]), 'button' => ''];
  }

}
