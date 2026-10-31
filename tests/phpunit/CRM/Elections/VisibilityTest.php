<?php

/**
 * The election forms tell an organiser when CiviCRM hides the groups and people from them.
 *
 * @group headless
 */
class CRM_Elections_VisibilityTest extends api_v3_ElectionBaseTestCase {

  private function withPermissions(array $permissions, callable $check) {
    $permissionClass = CRM_Core_Config::singleton()->userPermissionClass;
    $original = $permissionClass->permissions ?? NULL;
    $permissionClass->permissions = $permissions;
    try {
      return $check();
    }
    finally {
      $permissionClass->permissions = $original;
    }
  }

  private function groupWithAPerson() {
    $group = civicrm_api3('Group', 'create', ['title' => 'Visible ' . uniqid(), 'name' => 'visible_' . uniqid()]);
    $contact = civicrm_api3('Contact', 'create', ['contact_type' => 'Individual', 'first_name' => 'Vis', 'last_name' => 'Ible' . uniqid()]);
    civicrm_api3('GroupContact', 'create', ['group_id' => $group['id'], 'contact_id' => $contact['id'], 'status' => 'Added']);
    return (int) $group['id'];
  }

  public function testAnOrganiserWithoutViewAllContactsCannotSeeGroupsAndIsTold() {
    $groupId = $this->groupWithAPerson();
    $organiser = ['access CiviCRM', 'administer Elections', 'access AJAX API'];
    $this->assertTrue($this->withPermissions($organiser, fn() => CRM_Elections_Helper_Visibility::groupsHidden()));
    $this->assertTrue($this->withPermissions($organiser, fn() => CRM_Elections_Helper_Visibility::contactsHidden([$groupId])));
  }

  public function testAnOrganiserWithViewAllContactsSeesGroupsAndPeople() {
    $groupId = $this->groupWithAPerson();
    $organiser = ['access CiviCRM', 'administer Elections', 'access AJAX API', 'view all contacts'];
    $this->assertFalse($this->withPermissions($organiser, fn() => CRM_Elections_Helper_Visibility::groupsHidden()));
    $this->assertFalse($this->withPermissions($organiser, fn() => CRM_Elections_Helper_Visibility::contactsHidden([$groupId])));
  }

  public function testNoNoticeWhenThereAreNoGroupsToSee() {
    $this->assertFalse(CRM_Elections_Helper_Visibility::contactsHidden([]));
  }

}
