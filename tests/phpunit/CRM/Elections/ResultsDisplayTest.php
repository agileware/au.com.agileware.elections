<?php

use CRM_Elections_Helper_ResultsDisplay as Display;

/**
 * The look of the results page: logo, header text, footer text, and the celebration shown for a few days.
 *
 * @group headless
 */
class CRM_Elections_ResultsDisplayTest extends api_v3_ElectionBaseTestCase {

  private $created = [];

  public function setUp(): void {
    parent::setUp();
    Display::$acceptAnyFile = TRUE;
  }

  public function tearDown(): void {
    Display::$acceptAnyFile = FALSE;
    foreach ($this->created as $path) {
      @unlink($path);
    }
    parent::tearDown();
  }

  /**
   * A real picture, as a temporary file in the shape of an entry of $_FILES.
   */
  private function upload($type = 'png', $width = 40, $height = 20, $append = '') {
    $image = imagecreatetruecolor($width, $height);
    imagefill($image, 0, 0, imagecolorallocate($image, 200, 120, 20));
    $path = tempnam(sys_get_temp_dir(), 'elogo') . '.' . $type;
    $type === 'jpg' ? imagejpeg($image, $path) : ($type === 'gif' ? imagegif($image, $path) : imagepng($image, $path));
    imagedestroy($image);
    file_put_contents($path, $append, FILE_APPEND);
    $this->created[] = $path;
    return ['name' => 'logo.' . $type, 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'size' => filesize($path)];
  }

  private function election($values = []) {
    $election = $this->createElection(['is_visible' => 0, 'is_deleted' => 0] + $values);
    return (int) $election['id'];
  }

  private function loaded($id) {
    return findElectionById($id);
  }

  // ------------------------------------------------------------------ what is entered

  public function testLimitsOnTextAndDays() {
    $this->assertTrue(Display::validateForm(['results_header_text' => str_repeat('a', 200), 'results_footer_text' => str_repeat('b', 1000), 'celebration_days' => '30', 'celebration_effect' => 'fireworks']));
    $errors = Display::validateForm(['results_header_text' => str_repeat('a', 201), 'results_footer_text' => str_repeat('b', 1001), 'celebration_days' => '31', 'celebration_effect' => 'nope']);
    $this->assertEqualsCanonicalizing(['results_header_text', 'results_footer_text', 'celebration_days', 'celebration_effect'], array_keys($errors));
    foreach (['0', '-1', 'x', '2.5'] as $days) {
      $this->assertArrayHasKey('celebration_days', Display::validateForm(['celebration_days' => $days]), "Days $days");
    }
    $this->assertTrue(Display::validateForm(['celebration_days' => '']), 'Empty uses the default');
  }

  public function testTextIsEscapedOnceWhenShown() {
    $id = $this->election();
    \Civi\Api4\Election::update(FALSE)->addWhere('id', '=', $id)->setValues(['results_header_text' => '<b>Well</b> done', 'results_footer_text' => "Tom & Jerry <script>alert(1)</script>"])->execute();
    $display = Display::getDisplay($this->loaded($id), 'eid=' . $id);
    $this->assertSame('<b>Well</b> done', $display['header_text'], 'What was typed, ready to be escaped once by the page');
    $this->assertSame('Tom & Jerry <script>alert(1)</script>', $display['footer_text']);
    $this->assertSame('<b>Well</b> done', Display::getDefaults($this->loaded($id))['results_header_text'], 'The form shows what was typed');
  }

  public function testValuesToSave() {
    $values = Display::getValuesToSave(['results_header_text' => ' Well done ', 'results_footer_text' => '', 'celebration_enabled' => 1, 'celebration_effect' => 'balloons', 'celebration_days' => '7']);
    $this->assertSame('Well done', $values['results_header_text']);
    $this->assertNull($values['results_footer_text']);
    $this->assertSame(7, $values['celebration_days']);
    $this->assertSame(Display::DAYS_DEFAULT, Display::getValuesToSave([])['celebration_days']);
    $this->assertSame(0, Display::getValuesToSave([])['celebration_enabled']);
  }

  // --------------------------------------------------------------------------- the logo

  public function testALogoIsStoredAgainAndDescribed() {
    $id = $this->election();
    $junk = '<?php echo "hidden"; ?>';
    Display::saveLogo($id, ['results_logo_alt' => 'Our hive logo'], $this->upload('png', 40, 20, $junk));
    $election = $this->loaded($id);
    $this->assertNotEmpty($election->results_logo_file_id);
    $this->assertSame('Our hive logo', $election->results_logo_alt);
    $path = Display::logoPath($election->results_logo_file_id);
    $this->assertNotNull($path);
    $this->created[] = $path;
    $this->assertStringNotContainsString('hidden', file_get_contents($path), 'What was hidden after the picture is gone');
    $this->assertSame('image/png', getimagesize($path)['mime']);
    $this->assertSame('image/png', CRM_Core_DAO::singleValueQuery('SELECT mime_type FROM civicrm_file WHERE id = %1', [1 => [$election->results_logo_file_id, 'Integer']]));
  }

  public function testReplacingAndRemovingTheLogoDeletesTheOldFile() {
    $id = $this->election();
    Display::saveLogo($id, ['results_logo_alt' => 'First'], $this->upload('png'));
    $first = (int) $this->loaded($id)->results_logo_file_id;
    $firstPath = Display::logoPath($first);
    Display::saveLogo($id, ['results_logo_alt' => 'Second'], $this->upload('jpg'));
    $second = (int) $this->loaded($id)->results_logo_file_id;
    $this->assertNotSame($first, $second);
    $this->assertFileDoesNotExist($firstPath);
    $this->assertSame(0, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_file WHERE id = %1', [1 => [$first, 'Integer']]));
    $secondPath = Display::logoPath($second);
    Display::saveLogo($id, ['results_remove_logo' => 1], NULL);
    $election = $this->loaded($id);
    $this->assertEmpty($election->results_logo_file_id);
    $this->assertEmpty($election->results_logo_alt);
    $this->assertFileDoesNotExist($secondPath);
    // Nothing sent and nothing to remove: nothing happens.
    Display::saveLogo($id, [], NULL);
    $this->assertEmpty($this->loaded($id)->results_logo_file_id);
  }

  public function testOnlyPicturesOfTheRightKindAndSizeAreAccepted() {
    $text = tempnam(sys_get_temp_dir(), 'elogo');
    file_put_contents($text, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
    $this->created[] = $text;
    foreach ([
      'an svg' => [['name' => 'a.svg', 'tmp_name' => $text, 'error' => UPLOAD_ERR_OK], 'PNG, JPEG, GIF or WebP'],
      'a text file called png' => [['name' => 'a.png', 'tmp_name' => $text, 'error' => UPLOAD_ERR_OK], 'PNG, JPEG, GIF or WebP'],
      'too many pixels' => [$this->upload('png', 4001, 10), 'pixels'],
      'a failed upload' => [['name' => 'a.png', 'tmp_name' => '', 'error' => UPLOAD_ERR_INI_SIZE], 'could not be uploaded'],
    ] as $case => [$file, $message]) {
      $problem = Display::checkUpload($file);
      $this->assertNotNull($problem, $case);
      $this->assertStringContainsString($message, $problem, $case);
    }
    $big = $this->upload('png');
    file_put_contents($big['tmp_name'], str_repeat('0', Display::LOGO_MAX_BYTES + 1), FILE_APPEND);
    $this->assertStringContainsString('MB', Display::checkUpload($big));
    $id = $this->election();
    try {
      Display::saveLogo($id, ['results_logo_alt' => 'x'], ['name' => 'a.png', 'tmp_name' => $text, 'error' => UPLOAD_ERR_OK]);
      $this->fail('A text file must not be stored');
    }
    catch (CRM_Core_Exception $e) {
      $this->assertEmpty($this->loaded($id)->results_logo_file_id);
    }
    Display::$acceptAnyFile = FALSE;
    $this->assertStringContainsString('could not be uploaded', Display::checkUpload($this->upload()), 'A file that was not sent in an upload is refused');
  }

  public function testALogoNeedsADescription() {
    $_FILES['results_logo'] = $this->upload();
    try {
      $errors = Display::validateForm(['results_logo_alt' => ' ']);
      $this->assertArrayHasKey('results_logo_alt', $errors);
      $this->assertTrue(Display::validateForm(['results_logo_alt' => 'Hive']));
    }
    finally {
      unset($_FILES['results_logo']);
    }
  }

  public function testTheLogoAddressNeedsTheLinkOfAPersonWithoutALogin() {
    $id = $this->election();
    $this->assertFalse(Display::getDisplay($this->loaded($id), 'eid=' . $id)['has_logo']);
    Display::saveLogo($id, ['results_logo_alt' => 'Hive'], $this->upload());
    $display = Display::getDisplay($this->loaded($id), "eid=$id&cs=abc&cid=7");
    $this->assertTrue($display['has_logo']);
    $this->assertStringContainsString('civicrm/elections/results-logo', $display['logo_url']);
    $this->assertStringContainsString("eid=$id", $display['logo_url']);
    $this->assertStringContainsString('cs=abc', $display['logo_url']);
    $this->assertSame('Hive', $display['logo_alt']);
  }

  public function testDuplicatingAnElectionCopiesTheLookAndGivesTheCopyItsOwnLogoFile() {
    $id = $this->election();
    Display::saveLogo($id, ['results_logo_alt' => 'Hive'], $this->upload());
    \Civi\Api4\Election::update(FALSE)->addWhere('id', '=', $id)->setValues(['results_header_text' => 'Well done', 'results_footer_text' => 'Thanks', 'celebration_enabled' => TRUE, 'celebration_effect' => 'sparkle', 'celebration_days' => 9])->execute();
    $copy = CRM_Elections_BAO_Election::duplicate($id, 'The copy');
    $copyElection = $this->loaded((int) $copy['id']);
    $this->assertSame('Well done', $copyElection->results_header_text);
    $this->assertSame('Thanks', $copyElection->results_footer_text);
    $this->assertSame('Hive', $copyElection->results_logo_alt);
    $this->assertSame('sparkle', $copyElection->celebration_effect);
    $this->assertSame(9, (int) $copyElection->celebration_days);
    $this->assertNotEmpty($copyElection->results_logo_file_id);
    $this->assertNotSame((int) $this->loaded($id)->results_logo_file_id, (int) $copyElection->results_logo_file_id);
    $this->created[] = Display::logoPath($copyElection->results_logo_file_id);
    $this->created[] = Display::logoPath($this->loaded($id)->results_logo_file_id);
  }

  public function testChangesAreWrittenToTheAuditLogEvenOnAStartedElection() {
    $id = $this->election();
    \Civi\Api4\Election::update(FALSE)->addWhere('id', '=', $id)->addValue('results_header_text', 'Well done')->execute();
    $details = CRM_Core_DAO::singleValueQuery('SELECT details FROM civicrm_election_audit WHERE election_id = %1 ORDER BY id DESC LIMIT 1', [1 => [$id, 'Integer']]);
    $this->assertStringContainsString('results_header_text', (string) $details);
  }

  // ----------------------------------------------------------------------- the celebration

  private function celebratingElection(array $values = []) {
    $published = strtotime('2031-03-10 09:00:00');
    $election = new CRM_Elections_BAO_Election();
    $election->id = 6;
    $election->celebration_enabled = 1;
    $election->celebration_effect = 'fireworks';
    $election->celebration_days = 5;
    $election->result_date = date('Y-m-d H:i:s', $published);
    $election->has_results_generated = 1;
    foreach ($values as $key => $value) {
      $election->$key = $value;
    }
    return [$election, $published];
  }

  public function testTheCelebrationLastsForTheDaysAfterThePublishedResultsAndThenStops() {
    [$election, $published] = $this->celebratingElection();
    $this->assertNull(Display::getCelebration($election, $published - 1), 'Not before the results are published');
    $this->assertSame('fireworks', Display::getCelebration($election, $published)['effect']);
    $this->assertSame('fireworks', Display::getCelebration($election, $published + 5 * 86400 - 1)['effect']);
    $this->assertNull(Display::getCelebration($election, $published + 5 * 86400), 'After five days there is none');
    [$election, $published] = $this->celebratingElection(['celebration_days' => 2]);
    $this->assertNotNull(Display::getCelebration($election, $published + 86400));
    $this->assertNull(Display::getCelebration($election, $published + 2 * 86400));
    $this->assertSame($published + 2 * 86400, Display::getCelebration($election, $published)['until']);
  }

  public function testNoCelebrationUnlessSwitchedOnAndTheResultsExist() {
    [$election, $published] = $this->celebratingElection(['celebration_enabled' => 0]);
    $this->assertNull(Display::getCelebration($election, $published + 10));
    [$election, $published] = $this->celebratingElection(['has_results_generated' => 0]);
    $this->assertNull(Display::getCelebration($election, $published + 10));
    [$election, $published] = $this->celebratingElection(['result_date' => NULL]);
    $this->assertNull(Display::getCelebration($election, $published + 10));
  }

  public function testTheDaysDefaultToFiveWhenNoneAreSet() {
    [$election, $published] = $this->celebratingElection(['celebration_days' => NULL]);
    $this->assertNotNull(Display::getCelebration($election, $published + 4 * 86400));
    $this->assertNull(Display::getCelebration($election, $published + 5 * 86400));
  }

  public function testSurpriseMePicksOneEffectAndKeepsToIt() {
    $seen = [];
    for ($id = 1; $id <= 10; $id++) {
      [$election, $published] = $this->celebratingElection(['id' => $id, 'celebration_effect' => 'surprise']);
      $effect = Display::getCelebration($election, $published + 1)['effect'];
      $this->assertContains($effect, ['confetti', 'fireworks', 'balloons', 'sparkle', 'ticker']);
      $this->assertSame($effect, Display::getCelebration($election, $published + 2)['effect'], 'The same each time');
      $seen[$effect] = TRUE;
    }
    $this->assertGreaterThan(1, count($seen), 'Different elections get different effects');
  }

}
