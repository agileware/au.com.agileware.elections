<?php
use CRM_Elections_ExtensionUtil as E;

class CRM_Elections_BAO_ElectionNomination extends CRM_Elections_DAO_ElectionNomination {

  /**
   * Create a new ElectionNomination based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Elections_DAO_ElectionNomination|NULL
   *
  public static function create($params) {
    $className = 'CRM_Elections_DAO_ElectionNomination';
    $entityName = 'ElectionNomination';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  } */

}
