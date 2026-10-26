<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * Helper for presenting candidates (nominees) consistently on every screen.
 */
class CRM_Elections_Helper_Candidate {

  const NAME_DISPLAY = 'display_name';

  const NAME_FIRST_NICK_LAST = 'first_nick_last';

  const NAME_NICK_LAST = 'nick_last';

  /**
   * Nomination API fields needed to build a candidate name in any format.
   */
  const NAME_RETURN_FIELDS = [
    'member_nominee.display_name',
    'member_nominee.first_name',
    'member_nominee.last_name',
    'member_nominee.nick_name',
  ];

  /**
   * Options for the name format setting.
   *
   * @return array
   */
  public static function getNameFormats() {
    return [
      self::NAME_DISPLAY => E::ts('Display name (Edward Smith)'),
      self::NAME_FIRST_NICK_LAST => E::ts('First "Nickname" Last (Edward "Butch" Smith)'),
      self::NAME_NICK_LAST => E::ts('Nickname Last (Butch Smith)'),
    ];
  }

  /**
   * The name format currently configured for the site.
   *
   * @return string
   */
  public static function getNameFormat() {
    $format = Civi::settings()->get('elections_name_format');
    return array_key_exists($format ?? '', self::getNameFormats()) ? $format : self::NAME_DISPLAY;
  }

  /**
   * Build the name to show for a candidate.
   *
   * Falls back to the display name whenever the parts needed by the format are
   * missing, for example organisations or contacts without a nickname.
   *
   * @param string|null $displayName
   * @param string|null $firstName
   * @param string|null $lastName
   * @param string|null $nickName
   * @param string|null $format
   *   One of the NAME_* constants, defaults to the configured format.
   *
   * @return string
   */
  public static function formatName($displayName, $firstName, $lastName, $nickName, $format = NULL) {
    $displayName = trim((string) $displayName);
    $firstName = trim((string) $firstName);
    $lastName = trim((string) $lastName);
    $nickName = trim((string) $nickName);
    $format = $format ?? self::getNameFormat();

    if ($nickName === '' || $format === self::NAME_DISPLAY) {
      return $displayName;
    }

    switch ($format) {
      case self::NAME_FIRST_NICK_LAST:
        if ($firstName === '' && $lastName === '') {
          return $displayName;
        }
        // A nickname which is just the first name adds nothing.
        if (mb_strtolower($nickName) === mb_strtolower($firstName)) {
          return trim($firstName . ' ' . $lastName);
        }
        return trim($firstName . ' "' . $nickName . '" ' . $lastName);

      case self::NAME_NICK_LAST:
        return trim($nickName . ' ' . $lastName);
    }

    return $displayName;
  }

  /**
   * Replace `member_nominee.display_name` on a nomination with the formatted name.
   *
   * The nomination must have been loaded with NAME_RETURN_FIELDS.
   *
   * @param array $nomination
   * @param string|null $format
   */
  public static function applyNameFormat(array &$nomination, $format = NULL) {
    if (!isset($nomination['member_nominee.display_name'])) {
      return;
    }
    $nomination['member_nominee.display_name'] = self::formatName(
      $nomination['member_nominee.display_name'],
      $nomination['member_nominee.first_name'] ?? NULL,
      $nomination['member_nominee.last_name'] ?? NULL,
      $nomination['member_nominee.nick_name'] ?? NULL,
      $format
    );
  }

  /**
   * Apply the name format to a list of nominations.
   *
   * @param array $nominations
   * @param string|null $format
   */
  public static function applyNameFormatToAll(array &$nominations, $format = NULL) {
    foreach ($nominations as &$nomination) {
      self::applyNameFormat($nomination, $format);
    }
  }

}
