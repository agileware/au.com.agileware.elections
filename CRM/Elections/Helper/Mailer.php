<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * Emails sent by the extension, built from editable message templates (Administer > Communications >
 * Message Templates, the Elections workflows). An email never blocks the action that caused it: if it
 * cannot be sent the reason is logged and the action goes ahead.
 */
class CRM_Elections_Helper_Mailer {

  /**
   * Tell a nominee they have enough nominations to stand and need to respond.
   *
   * @param int $nominationId
   *
   * @return bool
   *   TRUE when an email was sent.
   */
  public static function nomineeInvited($nominationId) {
    $nomination = civicrm_api3('ElectionNomination', 'getsingle', [
      'id' => $nominationId,
      'return' => ['member_nominee', 'member_nominee.display_name', 'election_position_id', 'election_position_id.name', 'election_position_id.election_id'],
    ]);
    $election = findElectionById($nomination['election_position_id.election_id']);
    if (empty($election->send_emails) || $election->is_deleted) {
      return FALSE;
    }
    $contactId = (int) $nomination['member_nominee'];
    return self::send('elections_nominee_invited', $election, $contactId, [
      'nomineeName' => $nomination['member_nominee.display_name'],
      'positionName' => $nomination['election_position_id.name'],
      'acceptUrl' => self::link($election, $contactId, 'civicrm/elections/nominations/accept', ['enid' => (int) $nominationId]),
    ]);
  }

  /**
   * Tell a person a voter has written them in, and that votes for them only count if they accept before
   * voting closes. Sent whatever the election's email setting: the person cannot respond otherwise.
   *
   * @param int $nominationId
   * @param string $workflow
   *   elections_write_in_invited, or elections_write_in_reminder.
   *
   * @return bool
   */
  public static function writeInInvited($nominationId, $workflow = 'elections_write_in_invited') {
    $nomination = civicrm_api3('ElectionNomination', 'getsingle', [
      'id' => $nominationId,
      'return' => ['member_nominee', 'member_nominee.display_name', 'is_write_in', 'election_position_id.name', 'election_position_id.election_id'],
    ]);
    if (empty($nomination['is_write_in'])) {
      return FALSE;
    }
    $election = findElectionById($nomination['election_position_id.election_id']);
    $contactId = (int) $nomination['member_nominee'];
    return self::send($workflow, $election, $contactId, [
      'nomineeName' => $nomination['member_nominee.display_name'],
      'positionName' => $nomination['election_position_id.name'],
      'acceptUrl' => self::link($election, $contactId, 'civicrm/elections/nominations/accept', ['enid' => (int) $nominationId]),
      'declineUrl' => self::link($election, $contactId, 'civicrm/elections/nominations/reject', ['enid' => (int) $nominationId]),
    ]);
  }

  /**
   * Remind the people written in who have not answered, when voting is about to close. Each is reminded once.
   *
   * @param int $hoursBeforeClose
   *
   * @return int
   *   How many reminders were sent.
   */
  public static function sendWriteInReminders($hoursBeforeClose = 24) {
    $now = date('Y-m-d H:i:s');
    $until = date('Y-m-d H:i:s', time() + $hoursBeforeClose * 3600);
    $dao = CRM_Core_DAO::executeQuery('
      SELECT n.id
      FROM civicrm_election_nomination n
      INNER JOIN civicrm_election_position p ON p.id = n.election_position_id
      INNER JOIN civicrm_election e ON e.id = p.election_id
      WHERE n.is_write_in = 1 AND IFNULL(n.has_accepted_nomination, 0) = 0 AND IFNULL(n.has_rejected_nomination, 0) = 0
        AND n.write_in_reminded_date IS NULL
        AND e.is_deleted = 0 AND e.is_visible = 1
        AND e.voting_start_date <= %1 AND e.voting_end_date > %1 AND e.voting_end_date <= %2
    ', [1 => [$now, 'String'], 2 => [$until, 'String']]);
    $sent = 0;
    while ($dao->fetch()) {
      // Marked first, so a failure cannot lead to the same person being reminded over and over.
      CRM_Core_DAO::executeQuery('UPDATE civicrm_election_nomination SET write_in_reminded_date = %2 WHERE id = %1', [1 => [(int) $dao->id, 'Integer'], 2 => [$now, 'String']]);
      if (self::writeInInvited((int) $dao->id, 'elections_write_in_reminder')) {
        $sent++;
      }
    }
    return $sent;
  }

  /**
   * A link to an election page for one person: with their personal checksum when the election allows
   * access without logging in, otherwise a plain link (they log in first).
   *
   * @param CRM_Elections_BAO_Election $election
   * @param int $contactId
   * @param string $path
   * @param array $query
   *
   * @return string
   */
  public static function link($election, $contactId, $path, array $query = []) {
    $query = ['eid' => (int) $election->id] + $query;
    if (filter_var($election->allow_checksum_access, FILTER_VALIDATE_BOOL)) {
      $query['cid'] = (int) $contactId;
      $query['cs'] = \Civi\Api4\Contact::getChecksum(FALSE)->setContactId((int) $contactId)->execute()->first()['checksum'];
    }
    return CRM_Utils_System::url($path, $query, TRUE, NULL, FALSE, TRUE);
  }

  /**
   * Send a message template to one contact.
   *
   * @param string $workflow
   * @param CRM_Elections_BAO_Election $election
   * @param int $contactId
   * @param array $vars
   *   Smarty variables for the template. The election's name and the contact's name are added.
   *
   * @return bool
   */
  public static function send($workflow, $election, $contactId, array $vars) {
    try {
      $contact = \Civi\Api4\Contact::get(FALSE)
        ->addSelect('display_name', 'do_not_email', 'is_deceased', 'is_deleted', 'email_primary.email', 'email_primary.on_hold')
        ->addWhere('id', '=', (int) $contactId)
        ->execute()
        ->first();
      if (!$contact || $contact['is_deleted'] || $contact['is_deceased'] || $contact['do_not_email'] || empty($contact['email_primary.email']) || !empty($contact['email_primary.on_hold'])) {
        return FALSE;
      }
      $vars += [
        'electionName' => $election->name,
        'contactName' => $contact['display_name'],
        'votingOpens' => CRM_Utils_Date::customFormat($election->voting_start_date),
        'votingCloses' => CRM_Utils_Date::customFormat($election->voting_end_date),
        'viewUrl' => self::link($election, $contactId, 'civicrm/elections/view'),
      ];
      [$sent] = CRM_Core_BAO_MessageTemplate::sendTemplate([
        'workflow' => $workflow,
        'tplParams' => $vars,
        'contactId' => (int) $contactId,
        'from' => CRM_Core_BAO_Domain::getFromEmail(),
        'toName' => $contact['display_name'],
        'toEmail' => $contact['email_primary.email'],
      ]);
      return (bool) $sent;
    }
    catch (\Throwable $e) {
      Civi::log()->error('Elections: email {workflow} to contact {contact} was not sent: {message}', ['workflow' => $workflow, 'contact' => $contactId, 'message' => $e->getMessage()]);
      return FALSE;
    }
  }

}
