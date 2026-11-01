<?php
use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_Helper_TimeZone {

  /**
   * The name of the site time zone, for example Australia/Sydney. Election dates are stored and shown in it.
   *
   * @param string $timeZone
   *   A time zone name to use in place of the site's.
   *
   * @return string
   */
  public static function getTimeZoneLabel($timeZone = '') {
    return $timeZone !== '' && $timeZone !== NULL ? (string) $timeZone : date_default_timezone_get();
  }

  /**
   * The one line a page says about its times, in place of a time zone beside each date.
   *
   * @return string
   *   For example "Times are shown in Australia/Sydney."
   */
  public static function getTimeZoneLine() {
    return E::ts('Times are shown in %1.', [1 => self::getTimeZoneLabel()]);
  }

}
