<?php

namespace Civi\Api4;

/**
 * Election Positions.
 *
 * Only users with the "administer Elections" permission can use this entity
 * through APIv4. Participants use the election pages, which apply their own
 * access rules.
 *
 * Positions are sortable: CiviCRM keeps each election's positions in their own places when one is
 * moved (newer CiviCRM versions, seen on 6.19, refuse drag and drop in SearchKit otherwise).
 *
 * @searchable secondary
 * @orderBy sortorder
 * @groupWeightsBy election_id
 * @since 2.0
 * @package au.com.agileware.elections
 */
class ElectionPosition extends Generic\DAOEntity {
  use Generic\Traits\SortableEntity;

  public static function create($checkPermissions = TRUE) {
    return (new Action\ElectionPosition\Create(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  public static function update($checkPermissions = TRUE) {
    return (new Action\ElectionPosition\Update(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  public static function save($checkPermissions = TRUE) {
    return (new Action\ElectionPosition\Save(static::getEntityName(), __FUNCTION__))
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
