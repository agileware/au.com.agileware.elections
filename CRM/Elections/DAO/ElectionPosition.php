<?php

/**
 * DAOs provide an OOP-style facade for reading and writing database records.
 *
 * DAOs are a primary source for metadata in older versions of CiviCRM (<5.74)
 * and are required for some subsystems (such as APIv3).
 *
 * This stub provides compatibility. It is not intended to be modified in a
 * substantive way. Property annotations may be added, but are not required.
 * @property string $id
 * @property string $name
 * @property string $quantity
 * @property string $sortorder
 * @property string $description
 * @property string $created_at
 * @property string $updated_at
 * @property string $election_id
 * @property string $created_by
 * @property string $result_status
 */
class CRM_Elections_DAO_ElectionPosition extends CRM_Elections_DAO_Base {

  /**
   * Required by older versions of CiviCRM (<5.74).
   * @var string
   */
  public static $_tableName = 'civicrm_election_position';

}
