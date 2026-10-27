<?php
use CRM_Elections_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_ElectionAudit',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'ElectionAudit',
        'label' => E::ts('Election Audit Log'),
        'api_entity' => 'ElectionAudit',
        'api_params' => [
          'version' => 4,
          'select' => ['id', 'election_id', 'created_date', 'contact_id.display_name', 'action', 'details'],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
        'description' => E::ts('What was changed in one election, who changed it and when. Chosen at runtime with the eid parameter. Never shows how anyone voted.'),
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'SavedSearch_ElectionAudit_SearchDisplay_ElectionAudit_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'ElectionAudit_Table_1',
        'label' => E::ts('Election Audit Log'),
        'saved_search_id.name' => 'ElectionAudit',
        'type' => 'table',
        'settings' => [
          'description' => E::ts('Entries cannot be changed or removed. Each carries a hash of the entry before it, which is checked on the election overview.'),
          'sort' => [['id', 'DESC']],
          'limit' => 50,
          'pager' => ['show_count' => TRUE],
          'placeholder' => 5,
          'columns' => [
            ['type' => 'field', 'key' => 'created_date', 'dataType' => 'Timestamp', 'label' => E::ts('When'), 'sortable' => TRUE],
            ['type' => 'field', 'key' => 'contact_id.display_name', 'dataType' => 'String', 'label' => E::ts('Who'), 'sortable' => TRUE, 'empty_value' => E::ts('System')],
            ['type' => 'field', 'key' => 'action', 'dataType' => 'String', 'label' => E::ts('What'), 'sortable' => TRUE],
            ['type' => 'field', 'key' => 'details', 'dataType' => 'Text', 'label' => E::ts('Details'), 'sortable' => FALSE],
          ],
          'actions' => FALSE,
          'classes' => ['table', 'table-striped'],
        ],
      ],
      'match' => ['saved_search_id', 'name'],
    ],
  ],
];
