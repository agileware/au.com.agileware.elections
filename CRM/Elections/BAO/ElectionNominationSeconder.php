<?php
use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_BAO_ElectionNominationSeconder extends CRM_Elections_DAO_ElectionNominationSeconder {

  /**
   * Create a new ElectionNominationSeconder based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Elections_DAO_ElectionNominationSeconder|NULL
   *
  public static function create($params) {
    $className = 'CRM_Elections_DAO_ElectionNominationSeconder';
    $entityName = 'ElectionNominationSeconder';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  } */

}
