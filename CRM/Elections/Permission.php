<?php

class CRM_Elections_Permission {

  /**
   * Custom access callback function specified in xml/Menu/Elections.xml.
   *
   * This function implements the core logic:
   * 1. Checks if the current user has the 'view elections' permission.
   * 2. If not, it checks for a valid contact ID (cid) and checksum (cs) in the URL.
   *
   * @return bool
   * TRUE if access is granted, FALSE otherwise.
   */
  public static function check() {
    // Check for the explicit CiviCRM permission first.
    // Always grant access to logged-in users with the right role.
    if (CRM_Core_Permission::check('view elections')) {
      return TRUE;
    }

    // Allows unauthenticated users with a checksum to view the page.
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive');
    $checksum = CRM_Utils_Request::retrieve('cs', 'String');

    // If either cid or cs are missing from the URL, deny access.
    if (!$contactId || !$checksum) {
      return FALSE;
    }

    // Validate the checksum
    $results = \Civi\Api4\Contact::validateChecksum(FALSE)
                                    ->setContactId($contactId)
                                    ->setChecksum($checksum)
                                    ->execute()
                                    ->first();

    if ( $results['valid'] ) {
      return TRUE;
    }

    return FALSE;
  }

}
