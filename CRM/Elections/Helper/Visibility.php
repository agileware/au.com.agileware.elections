<?php

/**
 * Finds out whether the person using a form can see the groups and people the form lists.
 *
 * CiviCRM only shows a person the groups and contacts that its permissions (View All Contacts or an
 * ACL) allow. When someone who runs elections lacks that, the group fields are silently empty, so the
 * forms say why.
 */
class CRM_Elections_Helper_Visibility {

  /**
   * TRUE when groups exist but the logged in person cannot see any of them.
   *
   * @return bool
   */
  public static function groupsHidden() {
    try {
      $all = \Civi\Api4\Group::get(FALSE)->addWhere('is_active', '=', TRUE)->addWhere('is_hidden', '=', FALSE)->selectRowCount()->execute()->count();
      if (!$all) {
        return FALSE;
      }
      return !\Civi\Api4\Group::get(TRUE)->addWhere('is_active', '=', TRUE)->addWhere('is_hidden', '=', FALSE)->selectRowCount()->execute()->count();
    }
    catch (\Throwable $e) {
      return TRUE;
    }
  }

  /**
   * TRUE when people are in the given groups but the logged in person cannot see any of them.
   *
   * @param int[] $groupIds
   *
   * @return bool
   */
  public static function contactsHidden(array $groupIds) {
    if (!$groupIds) {
      return FALSE;
    }
    try {
      $query = function ($check) use ($groupIds) {
        return \Civi\Api4\Contact::get($check)->addWhere('groups', 'IN', $groupIds)->addWhere('is_deleted', '=', FALSE)->selectRowCount()->execute()->count();
      };
      return $query(FALSE) > 0 && $query(TRUE) === 0;
    }
    catch (\Throwable $e) {
      return TRUE;
    }
  }

}
