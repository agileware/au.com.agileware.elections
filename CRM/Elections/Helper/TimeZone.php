<?php
use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_Helper_TimeZone {
  public static function getTimeZoneLabel($timeZone = '') {
    return 'GMT' . date('P');
  }

  public static function getTimeZoneConvertUrl() {
    return 'https://www.timeanddate.com/worldclock/converter.html';
  }

}
