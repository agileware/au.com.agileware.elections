<?php

namespace Civi\Api4;

/**
 * Elections.
 *
 * Only users with the "administer Elections" permission can use this entity
 * through APIv4. Participants use the election pages, which apply their own
 * access rules.
 *
 * @searchable secondary
 * @since 2.0
 * @package au.com.agileware.elections
 */
class Election extends Generic\DAOEntity {

  /**
   * Copy an election and its positions as a new, inactive election.
   *
   * @param bool $checkPermissions
   *
   * @return \Civi\Api4\Action\Election\Duplicate
   */
  public static function duplicate($checkPermissions = TRUE) {
    return (new Action\Election\Duplicate(static::class, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return array
   */
  public static function permissions(): array {
    return [
      'meta' => ['access CiviCRM'],
      'default' => ['administer Elections'],
    ];
  }

}
