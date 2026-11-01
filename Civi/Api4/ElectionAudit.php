<?php

namespace Civi\Api4;

/**
 * Election audit log.
 *
 * Read only: entries are written by the extension itself and cannot be created, changed or deleted
 * through the API. Each entry carries a hash of the one before it, so a change made directly in the
 * database is found when the chain is checked.
 *
 * @searchable secondary
 * @since 2.0
 * @package au.com.agileware.elections
 */
class ElectionAudit extends Generic\DAOEntity {

  /**
   * @return array
   */
  public static function permissions(): array {
    return [
      'meta' => ['access CiviCRM'],
      'default' => ['administer Elections'],
    ];
  }

  public static function create($checkPermissions = TRUE) {
    throw new \CRM_Core_Exception(ts('The audit log cannot be changed.'));
  }

  public static function update($checkPermissions = TRUE) {
    throw new \CRM_Core_Exception(ts('The audit log cannot be changed.'));
  }

  public static function save($checkPermissions = TRUE) {
    throw new \CRM_Core_Exception(ts('The audit log cannot be changed.'));
  }

  public static function delete($checkPermissions = TRUE) {
    throw new \CRM_Core_Exception(ts('The audit log cannot be changed.'));
  }

  public static function replace($checkPermissions = TRUE) {
    throw new \CRM_Core_Exception(ts('The audit log cannot be changed.'));
  }

}
