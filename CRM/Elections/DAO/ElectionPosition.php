<?php

/**
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 *
 * Generated from /var/www/civicrm530/sites/default/files/civicrm/ext/au.com.agileware.elections/xml/schema/CRM/Elections/ElectionPosition.xml
 * DO NOT EDIT.  Generated by CRM_Core_CodeGen
 * (GenCodeChecksum:f6002bcd62e60dd90cb389996691e88d)
 */

/**
 * Database access object for the ElectionPosition entity.
 */
class CRM_Elections_DAO_ElectionPosition extends CRM_Core_DAO {

  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  static $_tableName = 'civicrm_election_position';

  /**
   * Should CiviCRM log any modifications to this table in the civicrm_log table.
   *
   * @var bool
   */
  static $_log = TRUE;

  /**
   * Unique ElectionPosition ID
   *
   * @var int unsigned
   */
  public $id;

  /**
   * Name of the position.
   *
   * @var string
   */
  public $name;

  /**
   * Quantity of a this position.
   *
   * @var int unsigned
   */
  public $quantity;

  /**
   * Quantity of a this position.
   *
   * @var int unsigned
   */
  public $sortorder;


  /**
   * @var text
   */
  public $description;

  /**
   * Date on which election position created.
   *
   * @var timestamp
   */
  public $created_at;

  /**
   * Date on which election position was updated.
   *
   * @var timestamp
   */
  public $updated_at;

  /**
   * FK to Election
   *
   * @var int unsigned
   */
  public $election_id;

  /**
   * FK to Contact who created particular position
   *
   * @var int unsigned
   */
  public $created_by;

  /**
   * Result status for this position.
   *
   * @var string
   */
  public $result_status;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->__table = 'civicrm_election_position';
    parent::__construct();
  }

  /**
   * Returns foreign keys and entity references.
   *
   * @return array
   *   [CRM_Core_Reference_Interface]
   */
  public static function getReferenceColumns() {
    if (!isset(Civi::$statics[__CLASS__]['links'])) {
      Civi::$statics[__CLASS__]['links'] = static ::createReferenceColumns(__CLASS__);
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'election_id', 'civicrm_election', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'created_by', 'civicrm_contact', 'id');
      CRM_Core_DAO_AllCoreTables::invoke(__CLASS__, 'links_callback', Civi::$statics[__CLASS__]['links']);
    }
    return Civi::$statics[__CLASS__]['links'];
  }

  /**
   * Returns all the column names of this table
   *
   * @return array
   */
  public static function &fields() {
    if (!isset(Civi::$statics[__CLASS__]['fields'])) {
      Civi::$statics[__CLASS__]['fields'] = [
        'id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'description' => ts('Unique Election Position ID'),
          'required' => TRUE,
          'table_name' => 'civicrm_election_position',
          'entity' => 'ElectionPosition',
          'bao' => 'CRM_Elections_DAO_ElectionPosition',
          'localizable' => 0,
        ],
        'name' => [
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Name'),
          'description' => ts('Name of the position.'),
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'import' => TRUE,
          'where' => 'civicrm_election_position.name',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => TRUE,
          'table_name' => 'civicrm_election_position',
          'entity' => 'ElectionPosition',
          'bao' => 'CRM_Elections_DAO_ElectionPosition',
          'localizable' => 0,
          'html' => [
            'type' => 'Text',
          ],
        ],
        'quantity' => [
          'name' => 'quantity',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Quantity'),
          'description' => ts('Quantity of a this position.'),
          'import' => TRUE,
          'where' => 'civicrm_election_position.quantity',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => TRUE,
          'table_name' => 'civicrm_election_position',
          'entity' => 'ElectionPosition',
          'bao' => 'CRM_Elections_DAO_ElectionPosition',
          'localizable' => 0,
          'html' => [
            'type' => 'Text',
          ],
        ],
        'sortorder' => [
          'name' => 'sortorder',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Order'),
          'description' => ts('Order of this position.'),
          'import' => TRUE,
          'where' => 'civicrm_election_position.sortorder',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => TRUE,
          'table_name' => 'civicrm_election_position',
          'entity' => 'ElectionPosition',
          'bao' => 'CRM_Elections_DAO_ElectionPosition',
          'localizable' => 0,
          'html' => [
              'type' => 'Text',
          ],
        ],
        'description' => [
          'name' => 'description',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Description'),
          'import' => TRUE,
          'where' => 'civicrm_election_position.description',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => TRUE,
          'table_name' => 'civicrm_election_position',
          'entity' => 'ElectionPosition',
          'bao' => 'CRM_Elections_DAO_ElectionPosition',
          'localizable' => 0,
          'html' => [
            'type' => 'Text',
          ],
        ],
        'created_at' => [
          'name' => 'created_at',
          'type' => CRM_Utils_Type::T_TIMESTAMP,
          'title' => ts('Created At'),
          'description' => ts('Date on which election position created.'),
          'export' => TRUE,
          'where' => 'civicrm_election_position.created_at',
          'headerPattern' => '',
          'dataPattern' => '',
          'default' => 'CURRENT_TIMESTAMP',
          'table_name' => 'civicrm_election_position',
          'entity' => 'ElectionPosition',
          'bao' => 'CRM_Elections_DAO_ElectionPosition',
          'localizable' => 0,
        ],
        'updated_at' => [
          'name' => 'updated_at',
          'type' => CRM_Utils_Type::T_TIMESTAMP,
          'title' => ts('Updated At'),
          'description' => ts('Date on which election position was updated.'),
          'required' => FALSE,
          'export' => TRUE,
          'where' => 'civicrm_election_position.updated_at',
          'headerPattern' => '',
          'dataPattern' => '',
          'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
          'table_name' => 'civicrm_election_position',
          'entity' => 'ElectionPosition',
          'bao' => 'CRM_Elections_DAO_ElectionPosition',
          'localizable' => 0,
        ],
        'election_id' => [
          'name' => 'election_id',
          'type' => CRM_Utils_Type::T_INT,
          'description' => ts('FK to Election'),
          'table_name' => 'civicrm_election_position',
          'entity' => 'ElectionPosition',
          'FKClassName' => 'CRM_Elections_DAO_Election',
          'title' => 'Election',
          'bao' => 'CRM_Elections_DAO_ElectionPosition',
          'localizable' => 0,
        ],
        'created_by' => [
          'name' => 'created_by',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Created By'),
          'description' => ts('FK to Contact who created particular position'),
          'table_name' => 'civicrm_election_position',
          'entity' => 'ElectionPosition',
          'FKClassName' => 'CRM_Contact_DAO_Contact',
          'bao' => 'CRM_Elections_DAO_ElectionPosition',
          'localizable' => 0,
        ],
        'result_status' => [
          'name' => 'result_status',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Result Status'),
          'description' => ts('Result status for this position.'),
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'import' => TRUE,
          'where' => 'civicrm_election_position.result_status',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => TRUE,
          'table_name' => 'civicrm_election_position',
          'entity' => 'ElectionPosition',
          'bao' => 'CRM_Elections_DAO_ElectionPosition',
          'localizable' => 0,
          'html' => [
            'type' => 'Text',
          ],
        ],
      ];
      CRM_Core_DAO_AllCoreTables::invoke(__CLASS__, 'fields_callback', Civi::$statics[__CLASS__]['fields']);
    }
    return Civi::$statics[__CLASS__]['fields'];
  }

  /**
   * Return a mapping from field-name to the corresponding key (as used in fields()).
   *
   * @return array
   *   Array(string $name => string $uniqueName).
   */
  public static function &fieldKeys() {
    if (!isset(Civi::$statics[__CLASS__]['fieldKeys'])) {
      Civi::$statics[__CLASS__]['fieldKeys'] = array_flip(CRM_Utils_Array::collect('name', self::fields()));
    }
    return Civi::$statics[__CLASS__]['fieldKeys'];
  }

  /**
   * Returns the names of this table
   *
   * @return string
   */
  public static function getTableName() {
    return self::$_tableName;
  }

  /**
   * Returns if this table needs to be logged
   *
   * @return bool
   */
  public function getLog() {
    return self::$_log;
  }

  /**
   * Returns the list of fields that can be imported
   *
   * @param bool $prefix
   *
   * @return array
   */
  public static function &import($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getImports(__CLASS__, 'election_position', $prefix, []);
    return $r;
  }

  /**
   * Returns the list of fields that can be exported
   *
   * @param bool $prefix
   *
   * @return array
   */
  public static function &export($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getExports(__CLASS__, 'election_position', $prefix, []);
    return $r;
  }

  /**
   * Returns the list of indices
   *
   * @param bool $localize
   *
   * @return array
   */
  public static function indices($localize = TRUE) {
    $indices = [];
    return ($localize && !empty($indices)) ? CRM_Core_DAO_AllCoreTables::multilingualize(__CLASS__, $indices) : $indices;
  }

}
