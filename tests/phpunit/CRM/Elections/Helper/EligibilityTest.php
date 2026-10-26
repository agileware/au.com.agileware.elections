<?php

/**
 * @group headless
 */
class CRM_Elections_Helper_EligibilityTest extends api_v3_ElectionBaseTestCase {

  public function testContactInDirectGroup() {
    $groupId = $this->groupCreate();
    $member = $this->individualCreate();
    $outsider = $this->individualCreate();
    $this->callAPISuccess('GroupContact', 'create', ['group_id' => $groupId, 'contact_id' => $member]);

    $this->assertTrue(CRM_Elections_Helper_Eligibility::isContactInGroups($member, $groupId));
    $this->assertTrue(CRM_Elections_Helper_Eligibility::isContactInGroups($member, [$groupId + 1000, $groupId]));
    $this->assertTrue(CRM_Elections_Helper_Eligibility::isContactInGroups($member, "$groupId,"));
    $this->assertFalse(CRM_Elections_Helper_Eligibility::isContactInGroups($outsider, $groupId));
  }

  public function testNoContactOrNoGroupsIsNotEligible() {
    $groupId = $this->groupCreate();
    $this->assertFalse(CRM_Elections_Helper_Eligibility::isContactInGroups(NULL, $groupId));
    $this->assertFalse(CRM_Elections_Helper_Eligibility::isContactInGroups($this->individualCreate(), NULL));
    $this->assertFalse(CRM_Elections_Helper_Eligibility::isContactInGroups($this->individualCreate(), ''));
  }

  public function testCanVoteUsesElectionGroups() {
    $election = $this->createElection(['is_visible' => 1, 'is_deleted' => 0]);
    $member = $this->individualCreate();
    $outsider = $this->individualCreate();
    $this->addMembersInElectionGroup($member, $election);

    $this->assertTrue(CRM_Elections_Helper_Eligibility::canVote($election['id'], $member));
    $this->assertTrue(CRM_Elections_Helper_Eligibility::canNominate($election['id'], $member));
    $this->assertFalse(CRM_Elections_Helper_Eligibility::canVote($election['id'], $outsider));
    // No logged in user in tests and no contact supplied.
    $this->assertFalse(CRM_Elections_Helper_Eligibility::canVote($election['id']));
  }

  public function testHasVotedRequiresAContact() {
    $election = $this->createElection(['is_visible' => 1, 'is_deleted' => 0]);
    $this->assertFalse(CRM_Elections_Helper_Eligibility::hasVoted($election['id']));
    $this->assertNull(CRM_Elections_Helper_Eligibility::getVoteDate($election['id']));
  }

  public function testApiV4EntitiesRequireAdministerElections() {
    $this->assertSame(0, \Civi\Api4\Election::get(FALSE)->selectRowCount()->execute()->countMatched());
    $permissions = \Civi\Api4\ElectionVote::permissions();
    $this->assertSame(['administer Elections'], $permissions['default']);
  }

}
