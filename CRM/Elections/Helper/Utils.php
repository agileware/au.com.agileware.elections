<?php

class CRM_Elections_Helper_Utils {

  public static function replaceSingleProfilePic(&$contact, $imageKey, $contactIdKey) {
    $profilePics = self::getCMSProfilePictures(array($contact[$contactIdKey]));
    $contact[$imageKey] = $profilePics[$contact[$contactIdKey]];
  }

  public static function getCMSProfilePictures($contactIds) {
    $profilePictures = array();
    if (count($contactIds) == 0) {
      return $profilePictures;
    }
    $cmsMatches = civicrm_api3('UFMatch', 'get', [
      'sequential' => 1,
      'return'     => ["uf_id", "contact_id"],
      'contact_id' => ['IN' => $contactIds],
    ]);

    $defaultEmails = civicrm_api3('Contact', 'get', [
      'return' => ["email"],
      'id' => ['IN' => $contactIds],
    ]);

    $defaultEmails = $defaultEmails['values'];

    $cmsMatches = $cmsMatches['values'];

    foreach ($contactIds as $contactId) {
      $contactEmail = (isset($defaultEmails[$contactId]['email'])) ? $defaultEmails[$contactId]['email'] : "";
      $profilePictures[$contactId] = self::getGravatarUrlFromEmailId($contactEmail);
    }

    if (function_exists('add_filter')) {
      foreach ($cmsMatches as $cmsMatch) {
        $profilePictures[$cmsMatch['contact_id']] = get_avatar_url($cmsMatch['uf_id'], array(
          'size' => 300,
        ));
      }
    }

    return $profilePictures;
  }

  private static function getEmailHashForGravatar($emailId) {
    return md5(strtolower(trim($emailId)));
  }

  private static function getGravatarUrlFromEmailId($emailId) {
    return "https://2.gravatar.com/avatar/" . self::getEmailHashForGravatar($emailId) . "?s=300&d=mm&r=g";
  }

}
