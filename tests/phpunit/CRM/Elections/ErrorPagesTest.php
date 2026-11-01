<?php

/**
 * The pages a person sees when they cannot see an election, or there is none: plain words, one message, one way
 * out, and no code or stack.
 *
 * @group headless
 */
class CRM_Elections_ErrorPagesTest extends api_v3_ElectionBaseTestCase {

  /**
   * A page or form as far as the error helpers are concerned: they set a flag and assign variables.
   */
  private function stub() {
    return new class() {
      public $throwError = FALSE;
      public $vars = [];

      public function assign($name, $value = NULL) {
        $this->vars[$name] = $value;
      }

      public function get($name) {
        return NULL;
      }

      public function set($name, $value) {
      }
    };
  }

  private function render(array $vars) {
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->pushScope($vars);
    try {
      return $smarty->fetch('CRM/Elections/Helper/Error.tpl');
    }
    finally {
      $smarty->popScope();
    }
  }

  public function testAnUnknownElectionIsNotFoundAndLinksToTheList() {
    $page = $this->stub();
    $this->assertNull(findElectionForPage($page, 987654321));
    $this->assertTrue($page->throwError);
    $this->assertSame('This election could not be found.', $page->vars['errormessage']);
    $this->assertSame('View Elections', $page->vars['return_button_text']);
    $this->assertStringContainsString('civicrm/elections', $page->vars['return_button_action']);
    $this->assertSame('error', $page->vars['errorKind']);

    $none = $this->stub();
    $this->assertNull(findElectionForPage($none, 0));
    $this->assertSame('This election could not be found.', $none->vars['errormessage']);
  }

  public function testAnElectionThatExistsIsFound() {
    $election = $this->createElection();
    $page = $this->stub();
    $found = findElectionForPage($page, (int) $election['id']);
    $this->assertSame((int) $election['id'], (int) $found->id);
    $this->assertFalse($page->throwError);
  }

  public function testAMissingElectionNumberIsExplained() {
    $page = $this->stub();
    unset($_REQUEST['eid'], $_GET['eid']);
    $this->assertSame(-1, retrieveElectionIdFromUrl($page));
    $this->assertSame('This page needs an election. Go to the list of elections and choose one.', $page->vars['errormessage']);
    $this->assertSame('View Elections', $page->vars['return_button_text']);
  }

  public function testARefusedPersonIsToldWhatToDoWithoutAnyCode() {
    $page = $this->stub();
    throwNonMemberAccessDenied($page);
    $this->assertTrue($page->throwError);
    $this->assertTrue($page->vars['nonmember']);
    $this->assertSame('You are not in a group that can take part in this election. Ask the organisers if you think you should be.', $page->vars['errormessage']);
    $this->assertSame('error', $page->vars['errorKind']);
    $html = $this->render($page->vars);
    $this->assertStringContainsString('messages error', $html);
    $this->assertStringNotContainsString('messages status', $html, 'One message, not a red and a green one');
    $this->assertStringContainsString('View Elections', $html);
    $this->assertDoesNotMatchRegularExpression('/Exception|Stack trace|#\d+ \//', $html);
  }

  public function testAPageThatIsNotARefusalIsAnInformationMessageWithTheCallersLink() {
    $page = $this->stub();
    throwAccessDeniedException($page, 'Voting closed on 12 Nov.', [
      'return_button_text' => 'Back to the election',
      'return_button_action' => 'https://example.org/election?eid=3&cid=5',
      'kind' => 'status',
    ]);
    $html = $this->render($page->vars);
    $this->assertStringContainsString('messages status', $html);
    $this->assertStringNotContainsString('messages error', $html);
    // The link text is printed as it is (it is already translated) and the address is escaped once.
    $this->assertStringContainsString('>Back to the election</a>', $html);
    $this->assertStringContainsString('href="https://example.org/election?eid=3&amp;cid=5"', $html);
    $this->assertStringContainsString('election-btn-quiet', $html, 'A way out is never the primary button');
  }

  public function testAnOlderCallThatAsksForNoLinkGetsNone() {
    $page = $this->stub();
    throwAccessDeniedException($page, 'Nothing to do here.', ['']);
    $this->assertSame('', $page->vars['return_button_text']);
    $this->assertStringNotContainsString('<a ', $this->render($page->vars));
  }

}
