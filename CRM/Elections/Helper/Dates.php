<?php
use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_Helper_Dates {

  /**
   * Compare two dates.
   *
   * @param $dateFirst
   * @param $dateSecond
   * @return int Return 0 if both are equals, 1 if the second date is greater and -1 if first date is greater.
   */
  public static function compare($dateFirst, $dateSecond, $dateFormat = 'Y-m-d H:i:s') {
    $dateFirst = DateTime::createFromFormat($dateFormat, $dateFirst);
    $dateSecond = DateTime::createFromFormat($dateFormat, $dateSecond);
    if ($dateFirst == $dateSecond) {
      return 0;
    }
    if ($dateFirst > $dateSecond) {
      return -1;
    }
    return 1;
  }

}
