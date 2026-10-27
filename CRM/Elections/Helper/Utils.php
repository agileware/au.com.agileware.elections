<?php

use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_Helper_Utils {

  /**
   * An address on this website, or NULL.
   *
   * Used for addresses that arrive in a request (for example the page that shows one election),
   * so they cannot send a person to another site or carry a `javascript:` address.
   *
   * @param string|null $url
   *   A path starting with one slash, or a full http(s) address on this site.
   *
   * @return string|null
   */
  public static function localUrlOrNull($url) {
    $url = trim((string) $url);
    if ($url === '' || preg_match('/[\x00-\x20\\\\]/', $url) || strpos($url, '//') === 0) {
      return NULL;
    }
    if ($url[0] === '/') {
      return $url;
    }
    $parts = parse_url($url);
    if (!$parts || empty($parts['host']) || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], TRUE)) {
      return NULL;
    }
    $site = parse_url((string) CRM_Utils_System::baseCMSURL());
    if (empty($site['host']) || strtolower($site['host']) !== strtolower($parts['host'])) {
      return NULL;
    }
    return $url;
  }

  /**
   * A web address that is safe to use as a link: http or https only, otherwise an empty string.
   *
   * @param string|null $url
   *
   * @return string
   */
  public static function safeHttpUrl($url) {
    $url = trim((string) $url);
    if ($url === '' || preg_match('/[\\x00-\\x20]/', $url)) {
      return '';
    }
    $parts = parse_url($url);
    if (!$parts || empty($parts['host']) || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], TRUE)) {
      return '';
    }
    return $url;
  }

  /**
   * A picture address that is safe to put in an image tag: http(s) or a path, never another scheme.
   *
   * @param string|null $url
   * @param string $fallback
   *
   * @return string
   */
  public static function safeImageUrl($url, $fallback) {
    $url = trim((string) $url);
    if ($url === '' || preg_match('/[\x00-\x20]/', $url)) {
      return $fallback;
    }
    if ($url[0] === '/' && strpos($url, '//') !== 0) {
      return $url;
    }
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], TRUE) ? $url : $fallback;
  }

  public static function replaceSingleProfilePic(&$contact, $imageKey, $contactIdKey) {
    $profilePics = self::getCandidatePictures([$contact[$contactIdKey]]);
    $contact[$imageKey] = $profilePics[$contact[$contactIdKey]];
  }

  public static function getCandidatePictures($contactIds) {
    if (Civi::settings()->get('elections_image_source') == 'CMS') {
      return self::getCMSProfilePictures($contactIds);
    }

    return self::getCiviCRMContactImage($contactIds);
  }

  static function getCiviCRMContactImage($contactIds) {
    $profilePictures = [];
    if (empty($contactIds)) {
      return $profilePictures;
    }

    $cmsMatches = civicrm_api3('Contact', 'get', [
      'sequential' => 1,
      'return' => ['id', 'image_URL'],
      'id' => ['IN' => $contactIds],
      'options' => ['limit' => 0],
    ]);

    $cmsMatches = $cmsMatches['values'];


    $defaultImage = Civi::resources()->getUrl('au.com.agileware.elections', 'images/default_profile.jpg');

    foreach ($cmsMatches as $cmsMatch) {
      $profilePictures[$cmsMatch['contact_id']] = self::safeImageUrl($cmsMatch['image_URL'] ?? '', $defaultImage);
    }

    return $profilePictures;
  }


  private static function getCMSProfilePictures($contactIds) {
    $profilePictures = [];
    if (empty($contactIds)) {
      return $profilePictures;
    }
    $cmsMatches = civicrm_api3('UFMatch', 'get', [
      'sequential' => TRUE,
      'return'     => ['uf_id', 'contact_id'],
      'contact_id' => ['IN' => $contactIds],
      'options' => ['limit' => 0],
    ]);

    $defaultEmails = civicrm_api3('Contact', 'get', [
      'return' => ['email'],
      'id' => ['IN' => $contactIds],
      'options' => ['limit' => 0],
    ]);

    $defaultEmails = $defaultEmails['values'];

    $cmsMatches = $cmsMatches['values'];

    foreach ($contactIds as $contactId) {
      $contactEmail = (!empty($defaultEmails[$contactId]['email'])) ? $defaultEmails[$contactId]['email'] : '';
      $profilePictures[$contactId] = self::getGravatarUrlFromEmailId($contactEmail);
    }

    switch (CRM_Core_Config::singleton()->userFramework) {
      case 'WordPress':
        foreach ($cmsMatches as $cmsMatch) {
          $profilePictures[$cmsMatch['contact_id']] = get_avatar_url($cmsMatch['uf_id'], [
            'size' => 300,
          ]);
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
    return 'https://2.gravatar.com/avatar/' . self::getEmailHashForGravatar($emailId) . '?s=300&d=mm&r=g';
  }
}
