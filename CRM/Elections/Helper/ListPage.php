<?php

/**
 * What the script of the SearchKit lists (ang/electionsLists.js) needs from the server.
 */
class CRM_Elections_Helper_ListPage {

  /**
   * The settings of the Angular module electionsLists, read in the browser as CRM.electionsLists.
   *
   * @return array
   *   timeZone: the site time zone, shown once above the lists that have dates.
   */
  public static function angularSettings(): array {
    return [
      'timeZone' => CRM_Elections_Helper_TimeZone::getTimeZoneLabel(),
    ];
  }

}
