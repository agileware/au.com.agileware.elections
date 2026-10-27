<?php

use CRM_Elections_Test_CouncilFixture as Council;

/**
 * Emails about nominations: sent when the election has them switched on, to the right person, with a link that
 * works for them, never breaking the action that caused them.
 *
 * @group headless
 */
class CRM_Elections_MailerTest extends api_v3_ElectionBaseTestCase {

  public function setUp(): void {
    parent::setUp();
    // The message templates come with the extension (a test database made earlier may not have them yet).
    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();
    // Send mail to a database table instead of out.
    Civi::settings()->set('mailing_backend', ['outBound_option' => CRM_Mailing_Config::OUTBOUND_OPTION_REDIRECT_TO_DB]);
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_mailing_spool');
  }

  public function tearDown(): void {
    Civi::settings()->revert('mailing_backend');
    parent::tearDown();
  }

  private function mails() {
    return CRM_Core_DAO::executeQuery('SELECT recipient_email, headers, body FROM civicrm_mailing_spool ORDER BY id')->fetchAll();
  }

  private function nominate(Council $fixture, $nominee, array $nominators, $position = 'President') {
    foreach ($nominators as $nominator) {
      CRM_Elections_Helper_Process::nominate($fixture->electionId, $fixture->positions[$position], $fixture->id($nominee), $fixture->id($nominator), 'reason');
    }
  }

  private function email($fixture, $persona, $address) {
    civicrm_api3('Email', 'create', ['contact_id' => $fixture->id($persona), 'email' => $address, 'is_primary' => 1, 'location_type_id' => 1]);
  }

  private function switchEmails(Council $fixture, $on) {
    civicrm_api3('Election', 'create', ['send_emails' => $on] + civicrm_api3('Election', 'getsingle', ['id' => $fixture->electionId]));
  }

  public function testANomineeIsEmailedWhenTheyHaveEnoughNominations() {
    $fixture = Council::create();
    $this->switchEmails($fixture, 1);
    $this->email($fixture, 'N01', 'n01.mailer@example.org');
    $this->nominate($fixture, 'N01', ['V01']);
    $this->assertCount(0, $this->mails(), 'One of the two nominations needed: nothing to respond to yet');
    $this->nominate($fixture, 'N01', ['V02']);
    $mails = $this->mails();
    $this->assertCount(1, $mails);
    $this->assertStringContainsString('n01.mailer@example.org', $mails[0]['recipient_email']);
    $this->assertStringContainsString('nominated for President', $mails[0]['headers'] . $mails[0]['body']);
    $nomination = $fixture->nominations['President|N01'] ?? CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_election_nomination WHERE election_position_id = %1 AND member_nominee = %2', [1 => [$fixture->positions['President'], 'Integer'], 2 => [$fixture->id('N01'), 'Integer']]);
    $body = $mails[0]['body'];
    $this->assertStringContainsString('enid=' . $nomination, $body);
    $this->assertStringContainsString('cs=', $body, 'The election allows access with a personal link');
    $this->nominate($fixture, 'N01', ['V03']);
    $this->assertCount(1, $this->mails(), 'A further nominator does not send it again');
  }

  public function testNothingIsSentWhenEmailsAreOff() {
    $fixture = Council::create();
    $this->email($fixture, 'N01', 'n01.mailer@example.org');
    $this->nominate($fixture, 'N01', ['V01', 'V02']);
    $this->assertCount(0, $this->mails());
  }

  public function testNoLinkChecksumWhenThePersonMustLogIn() {
    $fixture = Council::create(['allow_checksum_access' => 0]);
    $this->switchEmails($fixture, 1);
    $this->email($fixture, 'N01', 'n01.mailer@example.org');
    $this->nominate($fixture, 'N01', ['V01', 'V02']);
    $body = $this->mails()[0]['body'];
    $this->assertStringContainsString('enid=', $body);
    $this->assertStringNotContainsString('cs=', $body);
  }

  public function testPeopleWhoDoNotWantEmailAreSkippedAndTheNominationStillWorks() {
    $fixture = Council::create();
    $this->switchEmails($fixture, 1);
    $this->email($fixture, 'N01', 'n01.mailer@example.org');
    civicrm_api3('Contact', 'create', ['id' => $fixture->id('N01'), 'do_not_email' => 1]);
    $this->nominate($fixture, 'N01', ['V01', 'V02']);
    $this->assertCount(0, $this->mails());
    $this->assertSame(1, (int) CRM_Core_DAO::singleValueQuery('SELECT is_eligible_candidate FROM civicrm_election_nomination WHERE member_nominee = %1', [1 => [$fixture->id('N01'), 'Integer']]));
  }

  public function testNamesInTheEmailAreEscaped() {
    $fixture = Council::create();
    $this->switchEmails($fixture, 1);
    $this->email($fixture, 'N01', 'n01.mailer@example.org');
    civicrm_api3('Election', 'create', ['name' => 'Vote <b>now</b>'] + civicrm_api3('Election', 'getsingle', ['id' => $fixture->electionId]));
    $this->nominate($fixture, 'N01', ['V01', 'V02']);
    $body = $this->mails()[0]['body'];
    $this->assertStringNotContainsString('<b>now</b>', $body);
    $this->assertStringContainsString('&lt;b&gt;now&lt;/b&gt;', $body);
  }

  public function testAnAdministratorAddingAnUnacceptedCandidateInvitesThem() {
    $fixture = Council::create();
    $this->switchEmails($fixture, 1);
    $this->email($fixture, 'N02', 'n02.mailer@example.org');
    CRM_Elections_BAO_ElectionNomination::addCandidate($fixture->positions['President'], $fixture->id('N02'), FALSE);
    $this->assertCount(1, $this->mails());
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_mailing_spool');
    CRM_Elections_BAO_ElectionNomination::addCandidate($fixture->positions['Treasurer'], $fixture->id('N02'), TRUE);
    $this->assertCount(0, $this->mails(), 'Already accepted: nothing to respond to');
  }

}
