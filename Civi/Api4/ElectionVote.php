<?php

namespace Civi\Api4;

/**
 * Election Votes.
 *
 * Only users with the "administer Elections" permission can use this entity
 * through APIv4. Participants use the election pages, which apply their own
 * access rules.
 *
 * @searchable secondary
 * @since 2.0
 * @package au.com.agileware.elections
 */
class ElectionVote extends Generic\DAOEntity {

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
