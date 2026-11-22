<?php

namespace Civi\Api4;

/**
 * Election Nominations.
 *
 * Only users with the "administer Elections" permission can use this entity
 * through APIv4. Participants use the election pages, which apply their own
 * access rules.
 *
 * @searchable secondary
 * @since 2.0
 * @package au.com.agileware.elections
 */
class ElectionNomination extends Generic\DAOEntity {

  /**
   * @return array
   */
  public static function permissions(): array {
    return [
      'meta' => ['access CiviCRM'],
      // People who manage candidates only may read, for the candidates lists; every change needs administer Elections.
      'get' => [['administer Elections', 'manage Election candidates']],
      'default' => ['administer Elections'],
    ];
  }

}
