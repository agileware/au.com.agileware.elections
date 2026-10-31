<?php

/**
 * The extension calls everyone who takes part "people" (voters, nominees, candidates, organisers). "Member" and
 * "membership" suggest that a CiviCRM Membership is needed, which is not the case, so they do not appear in
 * anything a person reads: screens, emails, help text, the documentation and the README. Database columns and
 * API parameters keep their names (member_id and so on) so that existing sites keep working, and are not
 * scanned.
 *
 * @group headless
 */
class CRM_Elections_WordingTest extends \PHPUnit\Framework\TestCase {

  /**
   * Sentences that may use the word, because they are explaining that no membership is needed.
   */
  const ALLOWED = [
    'No CiviCRM Membership is needed',
  ];

  private function root() {
    return dirname(__DIR__, 4);
  }

  private function files() {
    $root = $this->root();
    $files = [$root . '/README.md', $root . '/CONTRIBUTING.md', $root . '/elections.php'];
    foreach (['templates', 'msg_tpl', 'docs', 'ang', 'js', 'managed', 'settings', 'xml', 'CRM', 'Civi', 'api', 'Job'] as $dir) {
      $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $dir, FilesystemIterator::SKIP_DOTS));
      foreach ($iterator as $file) {
        if (preg_match('/\.(tpl|md|html|js|php|xml)$/', $file->getFilename()) && !preg_match('#/(DAO|vendor|node_modules)/#', $file->getPathname())) {
          $files[] = $file->getPathname();
        }
      }
    }
    return array_filter($files, 'file_exists');
  }

  /**
   * The text of a PHP file that people can read: what is passed to ts() and a few description fields.
   */
  private function phpStrings($code) {
    preg_match_all('/(?:E::ts|ts)\(\s*(\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*")/s', $code, $matches);
    return $matches[1];
  }

  public function testNothingAPersonReadsSaysMemberOrMembership() {
    $found = [];
    foreach ($this->files() as $file) {
      $relative = substr($file, strlen($this->root()) + 1);
      $contents = file_get_contents($file);
      $texts = [];
      if (substr($file, -4) === '.php') {
        $texts = $this->phpStrings($contents);
      }
      elseif (substr($file, -3) === '.js') {
        preg_match_all('/\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*"/', $contents, $m);
        $texts = $m[0];
      }
      elseif (substr($file, -4) === '.tpl' || substr($file, -5) === '.html') {
        // The words on the screen: what is inside {ts}...{/ts} and plain text, not Smarty variables or markup.
        $text = preg_replace('/\{ts[^}]*\}|\{\/ts\}/', '', $contents);
        $text = preg_replace('/\{[^}]*\}|<[^>]*>/', ' ', $text);
        $texts = [$text];
      }
      else {
        $texts = [$contents];
      }
      foreach ($texts as $text) {
        foreach (self::ALLOWED as $allowed) {
          $text = str_replace($allowed, '', $text);
        }
        if (preg_match_all('/[^\n]{0,50}\b(members?|membership|memberships)\b[^\n]{0,30}/i', $text, $hits)) {
          foreach ($hits[0] as $hit) {
            $found[] = $relative . ': ' . trim($hit);
          }
        }
      }
    }
    $this->assertSame([], $found, "Use \"people\" instead of \"member\":\n" . implode("\n", $found));
  }

}
