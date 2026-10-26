<?php

/**
 * Per-election display options: photos, statements, nominators, name format.
 *
 * @group headless
 */
class CRM_Elections_DisplayOptionsTest extends api_v3_ElectionBaseTestCase {

  public function testNewElectionKeepsPre2Behaviour() {
    $election = findElectionById($this->createElection(['is_visible' => 1, 'is_deleted' => 0])['id']);
    $this->assertTrue($election->showPhotos);
    $this->assertTrue($election->showNominators);
    $this->assertFalse($election->showStatements);
    $this->assertSame('none', $election->statementDisplay);
  }

  public function testOptionsRoundTripThroughTheApi() {
    $created = $this->createElection([
      'is_visible' => 0,
      'is_deleted' => 0,
      'show_photos' => 0,
      'show_statements' => 'inline',
      'show_nominators' => 0,
      'name_format' => CRM_Elections_Helper_Candidate::NAME_NICK_LAST,
    ]);
    $election = findElectionById($created['id']);
    $this->assertFalse($election->showPhotos);
    $this->assertFalse($election->showNominators);
    $this->assertTrue($election->showStatements);
    $this->assertSame('inline', $election->statementDisplay);
    $this->assertSame(CRM_Elections_Helper_Candidate::NAME_NICK_LAST, $election->name_format);
  }

  public function testApiRejectsUnknownStatementOption() {
    $dates = $this->getElectionDates();
    $this->modifyDatesInString($dates);
    $this->callAPIFailure('Election', 'create', $dates + [
      'name' => 'Bad option',
      'show_statements' => 'bogus',
      'is_visible' => 0,
      'is_deleted' => 0,
      'allowed_groups' => $this->groupCreate(),
    ]);
  }

  public function testUnknownStoredStatementOptionFallsBackToNone() {
    $created = $this->createElection(['is_visible' => 0, 'is_deleted' => 0]);
    CRM_Core_DAO::executeQuery("UPDATE civicrm_election SET show_statements = 'bogus' WHERE id = %1", [1 => [$created['id'], 'Integer']]);
    $this->assertSame('none', findElectionById($created['id'])->statementDisplay);
  }

  public function testNullOptionsMeanDefaults() {
    // API callers that post back NULLs must not turn features off.
    $created = $this->createElection(['is_visible' => 0, 'is_deleted' => 0]);
    CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET show_photos = NULL, show_nominators = NULL, show_statements = NULL WHERE id = %1', [1 => [$created['id'], 'Integer']]);
    $election = findElectionById($created['id']);
    $this->assertTrue($election->showPhotos);
    $this->assertTrue($election->showNominators);
    $this->assertSame('none', $election->statementDisplay);
  }

  public function testElectionNameFormatOverridesSiteSetting() {
    $none = $this->createElection(['is_visible' => 0, 'is_deleted' => 0]);
    $own = $this->createElection(['is_visible' => 0, 'is_deleted' => 0, 'name_format' => CRM_Elections_Helper_Candidate::NAME_FIRST_NICK_LAST]);
    Civi::settings()->set('elections_name_format', CRM_Elections_Helper_Candidate::NAME_NICK_LAST);
    try {
      $this->assertSame(CRM_Elections_Helper_Candidate::NAME_NICK_LAST, CRM_Elections_Helper_Candidate::getNameFormatForElection($none['id']));
      $this->assertSame(CRM_Elections_Helper_Candidate::NAME_FIRST_NICK_LAST, CRM_Elections_Helper_Candidate::getNameFormatForElection($own['id']));
      $position = $this->createElectionPosition($own);
      $this->assertSame(CRM_Elections_Helper_Candidate::NAME_FIRST_NICK_LAST, CRM_Elections_Helper_Candidate::getNameFormatForPosition($position['id']));
    }
    finally {
      Civi::settings()->revert('elections_name_format');
    }
  }

  public function statementUrlProvider() {
    return [
      'empty' => ['', TRUE],
      'null' => [NULL, TRUE],
      'https' => ['https://example.org/cv.pdf', TRUE],
      'http with query' => ['http://example.org/a?b=c&d=e', TRUE],
      'javascript' => ['javascript:alert(1)', FALSE],
      'data' => ['data:text/html;base64,PHNjcmlwdD4=', FALSE],
      'no scheme' => ['example.org/cv', FALSE],
      'ftp' => ['ftp://example.org/cv', FALSE],
      'text' => ['not a url', FALSE],
    ];
  }

  /**
   * @dataProvider statementUrlProvider
   */
  public function testStatementUrlValidation($url, $expected) {
    $this->assertSame($expected, CRM_Elections_Helper_Candidate::isValidStatementUrl($url));
  }

  public function testNominationStoresStatementAndLink() {
    $nomination = $this->createNomination();
    $updated = $this->callAPISuccess('ElectionNomination', 'create', [
      'id' => $nomination['nomination']['id'],
      'comments' => 'My statement',
      'statement_url' => 'https://example.org/cv',
    ]);
    $loaded = $this->callAPISuccessGetSingle('ElectionNomination', ['id' => $updated['id'], 'return' => ['comments', 'statement_url']]);
    $this->assertSame('My statement', $loaded['comments']);
    $this->assertSame('https://example.org/cv', $loaded['statement_url']);
  }

}
