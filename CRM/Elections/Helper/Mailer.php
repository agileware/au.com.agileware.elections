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
