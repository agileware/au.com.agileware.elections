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
 * @property string $comments
 * @property string $rejection_comments
 * @property bool|string $is_eligible_candidate
 * @property bool|string $has_accepted_nomination
 * @property bool|string $has_rejected_nomination
 * @property string $created_at
 * @property string $updated_at
 * @property string $member_nominee
 * @property string $election_position_id
 */
class CRM_Elections_DAO_ElectionNomination extends CRM_Elections_DAO_Base {

  /**
   * Required by older versions of CiviCRM (<5.74).
   * @var string
   */
  public static $_tableName = 'civicrm_election_nomination';

}
