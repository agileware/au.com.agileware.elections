<?php

/**
 * @group headless
 */
class CRM_Elections_Helper_CandidateTest extends api_v3_ElectionBaseTestCase {

  /**
   * @return array
   */
  public static function nameProvider() {
    $display = CRM_Elections_Helper_Candidate::NAME_DISPLAY;
    $firstNickLast = CRM_Elections_Helper_Candidate::NAME_FIRST_NICK_LAST;
    $nickLast = CRM_Elections_Helper_Candidate::NAME_NICK_LAST;
    return [
      'display name ignores nickname' => ['Robert Irwin', 'Robert', 'Irwin', 'Rob', $display, 'Robert Irwin'],
      'first nick last' => ['Robert Irwin', 'Robert', 'Irwin', 'Rob', $firstNickLast, 'Robert "Rob" Irwin'],
      'nick last' => ['Robert Irwin', 'Robert', 'Irwin', 'Rob', $nickLast, 'Rob Irwin'],
      'no nickname falls back' => ['Robert Irwin', 'Robert', 'Irwin', '', $firstNickLast, 'Robert Irwin'],
      'null nickname falls back' => ['Robert Irwin', 'Robert', 'Irwin', NULL, $nickLast, 'Robert Irwin'],
      'whitespace nickname falls back' => ['Robert Irwin', 'Robert', 'Irwin', '  ', $firstNickLast, 'Robert Irwin'],
      'nickname same as first name' => ['Heather Jones', 'Heather', 'Jones', 'heather', $firstNickLast, 'Heather Jones'],
      'organisation without name parts' => ['Acme Pty Ltd', '', '', 'Acme', $firstNickLast, 'Acme Pty Ltd'],
      'nick only last name missing' => ['Rob', '', '', 'Rob', $nickLast, 'Rob'],
      'unicode nickname' => ['Zoë Müller', 'Zoë', 'Müller', 'Zozo', $firstNickLast, 'Zoë "Zozo" Müller'],
    ];
  }

  /**
   * @dataProvider nameProvider
   */
  public function testFormatName($display, $first, $last, $nick, $format, $expected) {
    $this->assertSame($expected, CRM_Elections_Helper_Candidate::formatName($display, $first, $last, $nick, $format));
  }

  public function testDefaultFormatIsDisplayName() {
    $this->assertSame(CRM_Elections_Helper_Candidate::NAME_DISPLAY, CRM_Elections_Helper_Candidate::getNameFormat());
    $this->assertSame('Robert Irwin', CRM_Elections_Helper_Candidate::formatName('Robert Irwin', 'Robert', 'Irwin', 'Rob'));
  }

  public function testConfiguredFormatIsUsed() {
    Civi::settings()->set('elections_name_format', CRM_Elections_Helper_Candidate::NAME_FIRST_NICK_LAST);
    $this->assertSame('Robert "Rob" Irwin', CRM_Elections_Helper_Candidate::formatName('Robert Irwin', 'Robert', 'Irwin', 'Rob'));
    Civi::settings()->set('elections_name_format', 'garbage');
    $this->assertSame(CRM_Elections_Helper_Candidate::NAME_DISPLAY, CRM_Elections_Helper_Candidate::getNameFormat());
    Civi::settings()->revert('elections_name_format');
  }

  public function testApplyNameFormatToNominations() {
    $nominations = [
      1 => [
        'member_nominee.display_name' => 'Robert Irwin',
        'member_nominee.first_name' => 'Robert',
        'member_nominee.last_name' => 'Irwin',
        'member_nominee.nick_name' => 'Rob',
      ],
      2 => [
        'member_nominee.display_name' => 'Jane Doe',
        'member_nominee.first_name' => 'Jane',
        'member_nominee.last_name' => 'Doe',
        'member_nominee.nick_name' => '',
      ],
      3 => ['id' => 3],
    ];
    CRM_Elections_Helper_Candidate::applyNameFormatToAll($nominations, CRM_Elections_Helper_Candidate::NAME_FIRST_NICK_LAST);
    $this->assertSame('Robert "Rob" Irwin', $nominations[1]['member_nominee.display_name']);
    $this->assertSame('Jane Doe', $nominations[2]['member_nominee.display_name']);
    $this->assertArrayNotHasKey('member_nominee.display_name', $nominations[3]);
  }

  public function testNicknameIsLoadedForNominations() {
    $contactId = $this->individualCreate([
      'first_name' => 'Robert',
      'last_name' => 'Irwin',
      'nick_name' => 'Rob',
    ]);
    $election = $this->createElection(['is_deleted' => 0, 'is_visible' => 1]);
    $position = $this->createElectionPosition($election);
    $this->editElectionByState(api_v3_ElectionBaseTestCase::$ELECTION_NOMINATION_IN_PROGRESS, $election);
    $nomination = $this->callAPISuccess('ElectionNomination', 'create', [
      'member_nominee' => $contactId,
      'election_position_id' => $position['id'],
    ]);

    // The join fields must resolve through the API for a real nomination.
    $loaded = $this->callAPISuccess('ElectionNomination', 'getsingle', [
      'id' => $nomination['id'],
      'return' => CRM_Elections_Helper_Candidate::NAME_RETURN_FIELDS,
    ]);
    CRM_Elections_Helper_Candidate::applyNameFormat($loaded, CRM_Elections_Helper_Candidate::NAME_FIRST_NICK_LAST);
    $this->assertSame('Robert "Rob" Irwin', $loaded['member_nominee.display_name']);
  }

}
