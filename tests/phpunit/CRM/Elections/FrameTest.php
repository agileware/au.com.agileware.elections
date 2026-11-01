<?php

/**
 * The frame every back end page shares: the breadcrumb, where Back goes, and the readable audit log labels.
 *
 * @group headless
 */
class CRM_Elections_FrameTest extends api_v3_ElectionBaseTestCase {

  public function testEveryAuditActionHasAReadableLabel() {
    $this->assertSame('Election changed', CRM_Elections_Helper_Audit::actionLabel('election_changed'));
    $this->assertSame('Votes anonymised', CRM_Elections_Helper_Audit::actionLabel('votes_anonymised'));
    $this->assertSame('Write-ins summary', CRM_Elections_Helper_Audit::actionLabel('write_ins_summary'));
    // A kind of entry that is not listed never shows a code.
    $this->assertSame('Something new happened', CRM_Elections_Helper_Audit::actionLabel('something_new_happened'));
  }

  public function testTheBreadcrumbStartsAtManageElectionsAndEndsWithThePage() {
    $election = $this->createElection(['name' => 'Beekeepers <b>2027</b>']);
    $trail = CRM_Elections_Helper_Frame::trail((int) $election['id'], [], 'Positions');
    $this->assertSame('Manage Elections', $trail[0][0]);
    $this->assertNotNull($trail[0][1]);
    $this->assertSame('Beekeepers <b>2027</b>', $trail[1][0]);
    $this->assertStringContainsString('eid=' . $election['id'], $trail[1][1]);
    $this->assertSame(['Positions', NULL], $trail[2]);

    $markup = CRM_Elections_Helper_Frame::breadcrumbMarkup($trail);
    $this->assertCount(3, $markup);
    $this->assertStringContainsString('<a href=', $markup[0]);
    // Names are escaped, and the page itself is the current one and not a link.
    $this->assertStringContainsString('Beekeepers &lt;b&gt;2027&lt;/b&gt;', $markup[1]);
    $this->assertStringNotContainsString('<b>', $markup[1]);
    $this->assertSame('<span aria-current="page">Positions</span>', $markup[2]);
  }

  public function testTheBreadcrumbCanBeGivenAsLinksForTemplatesThatDrawEveryStepAsOne() {
    $election = $this->createElection(['name' => 'Beekeepers <b>2027</b>']);
    $trail = CRM_Elections_Helper_Frame::trail((int) $election['id'], [], 'Positions');
    $links = CRM_Elections_Helper_Frame::breadcrumbLinks($trail);
    $this->assertCount(3, $links);
    $this->assertSame(['url', 'title'], array_keys($links[0]));
    $this->assertSame('Beekeepers &lt;b&gt;2027&lt;/b&gt;', $links[1]['title']);
    $this->assertSame('<span aria-current="page">Positions</span>', $links[2]['title']);
  }

  public function testTheOverviewTrailHasTheElectionAsItsPage() {
    $election = $this->createElection();
    $trail = CRM_Elections_Helper_Frame::overviewTrail((int) $election['id']);
    $this->assertCount(2, $trail);
    $this->assertNull($trail[1][1]);
  }

  public function testBackGoesToThePageThePersonCameFromOnlyIfItIsOnThisSite() {
    $fallback = CRM_Elections_Helper_Frame::managerUrl();
    $oldReferer = $_SERVER['HTTP_REFERER'] ?? NULL;
    $oldUri = $_SERVER['REQUEST_URI'] ?? NULL;
    try {
      $_SERVER['REQUEST_URI'] = '/civicrm/elections/overview/?reset=1&eid=1';
      $_SERVER['HTTP_REFERER'] = '/civicrm/elections/positions/?reset=1&eid=1';
      $this->assertSame('/civicrm/elections/positions/?reset=1&eid=1', CRM_Elections_Helper_Frame::backUrl($fallback));
      // The page itself, another site, or script are never used.
      $_SERVER['HTTP_REFERER'] = '/civicrm/elections/overview/?reset=1&eid=1';
      $this->assertSame($fallback, CRM_Elections_Helper_Frame::backUrl($fallback));
      $_SERVER['HTTP_REFERER'] = 'https://evil.example.org/phish';
      $this->assertSame($fallback, CRM_Elections_Helper_Frame::backUrl($fallback));
      $_SERVER['HTTP_REFERER'] = 'javascript:alert(1)';
      $this->assertSame($fallback, CRM_Elections_Helper_Frame::backUrl($fallback));
    }
    finally {
      if ($oldReferer === NULL) {
        unset($_SERVER['HTTP_REFERER']);
      }
      else {
        $_SERVER['HTTP_REFERER'] = $oldReferer;
      }
      if ($oldUri === NULL) {
        unset($_SERVER['REQUEST_URI']);
      }
      else {
        $_SERVER['REQUEST_URI'] = $oldUri;
      }
    }
  }

}
