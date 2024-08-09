<?php

use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_Helper_Utils {

  public static function replaceSingleProfilePic(&$contact, $imageKey, $contactIdKey) {
    $profilePics = self::getCandidatePictures(array($contact[$contactIdKey]));
    $contact[$imageKey] = $profilePics[$contact[$contactIdKey]];
  }

  public static function getCandidatePictures($contactIds) {
    if (Civi::settings()->get('elections_image_source') == 'CMS') {
      return self::getCMSProfilePictures($contactIds);
    }

    return self::getCiviCRMContactImage($contactIds);
  }

  static function getCiviCRMContactImage($contactIds) {
    $profilePictures = array();
    if (count($contactIds) == 0) {
      return $profilePictures;
    }

    $cmsMatches = civicrm_api3('Contact', 'get', [
      'sequential' => 1,
      'return' => ["id", "image_URL"],
      'id' => ['IN' => $contactIds],
      'options' => ['limit' => 0],
    ]);

    $cmsMatches = $cmsMatches["values"];


    $defaultImage = Civi::resources()->getUrl('au.com.agileware.elections', 'images/default_profile.jpg');

    foreach ($cmsMatches as $cmsMatch) {
      $profilePictures[$cmsMatch['contact_id']] = $cmsMatch['image_URL'] !== "" ? $cmsMatch['image_URL'] : $defaultImage;
    }

    return $profilePictures;
  }


  private static function getCMSProfilePictures($contactIds) {
    $profilePictures = array();
    if (count($contactIds) == 0) {
      return $profilePictures;
    }
    $cmsMatches = civicrm_api3('UFMatch', 'get', [
      'sequential' => TRUE,
      'return'     => ["uf_id", "contact_id"],
      'contact_id' => ['IN' => $contactIds],
      'options' => ['limit' => 0],
    ]);

    $defaultEmails = civicrm_api3('Contact', 'get', [
      'return' => ["email"],
      'id' => ['IN' => $contactIds],
      'options' => ['limit' => 0],
    ]);

    $defaultEmails = $defaultEmails['values'];

    $cmsMatches = $cmsMatches['values'];

    foreach ($contactIds as $contactId) {
      $contactEmail = (isset($defaultEmails[$contactId]['email'])) ? $defaultEmails[$contactId]['email'] : "";
      $profilePictures[$contactId] = self::getGravatarUrlFromEmailId($contactEmail);
    }

    switch (CRM_Core_Config::singleton()->userFramework) {
      case 'WordPress':
        foreach ($cmsMatches as $cmsMatch) {
          $profilePictures[$cmsMatch['contact_id']] = get_avatar_url($cmsMatch['uf_id'], array(
            'size' => 300,
          ));
        }
        break;
      case 'Drupal8':
        foreach ($cmsMatches as $cmsMatch) {
          $user = \Drupal\user\Entity\User::load($cmsMatch['uf_id']);
          if (!$user->user_picture->isEmpty()) {
            $profilePictures[$cmsMatch['contact_id']] = $user->user_picture->entity->createFileUrl();
          };
        }
        break;
      default:
        // Handle other cases here
        break;
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
