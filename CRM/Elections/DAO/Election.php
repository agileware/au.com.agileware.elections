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
 * @property string $description
 * @property string $visibility_start_date
 * @property string $visibility_end_date
 * @property string $nomination_start_date
 * @property string $nomination_end_date
 * @property string $advertise_candidates_date
 * @property string $voting_start_date
 * @property string $voting_end_date
 * @property string $result_date
 * @property string $result_status
 * @property bool|string $is_deleted
 * @property bool|string $is_visible
 * @property bool|string $has_results_generated
 * @property bool|string $anonymize_votes
 * @property string $required_nominations
 * @property bool|string $allow_revote
 * @property string $allowed_groups
 * @property string $created_at
 * @property string $updated_at
 * @property string $created_by
 */
class CRM_Elections_DAO_Election extends CRM_Elections_DAO_Base {

  /**
   * Required by older versions of CiviCRM (<5.74).
   * @var string
   */
  public static $_tableName = 'civicrm_election';

}
