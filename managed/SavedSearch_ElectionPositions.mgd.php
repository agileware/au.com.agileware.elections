<?php
use CRM_Elections_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_ElectionPositions',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'ElectionPositions',
        'label' => E::ts('Election Positions'),
        'api_entity' => 'ElectionPosition',
        'api_params' => [
          'version' => 4,
          'select' => ['id', 'election_id', 'name', 'quantity', 'description', 'sortorder'],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
        'description' => E::ts('The positions of one election, chosen at runtime with the eid parameter. Drag rows to change the order they are shown in.'),
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'SavedSearch_ElectionPositions_SearchDisplay_ElectionPositions_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'ElectionPositions_Table_1',
        'label' => E::ts('Election Positions'),
        'saved_search_id.name' => 'ElectionPositions',
        'type' => 'table',
        'settings' => [
          'description' => E::ts('Drag a row to change the order positions are shown in on nominations and the ballot. Select a name, seats or description to edit it. Positions can only be changed before the election starts.'),
          'sort' => [['sortorder', 'ASC']],
          'draggable' => 'sortorder',
          'limit' => 100,
          'pager' => FALSE,
          'placeholder' => 5,
          'columns' => [
            ['type' => 'field', 'key' => 'name', 'dataType' => 'String', 'label' => E::ts('Position'), 'sortable' => FALSE, 'editable' => TRUE],
            ['type' => 'field', 'key' => 'quantity', 'dataType' => 'Integer', 'label' => E::ts('Seats'), 'sortable' => FALSE, 'editable' => TRUE],
            ['type' => 'field', 'key' => 'description', 'dataType' => 'Text', 'label' => E::ts('Description'), 'sortable' => FALSE, 'editable' => TRUE],
            [
              'size' => 'btn-xs',
              'links' => [
                ['path' => 'civicrm/elections/positions/duplicate?epid=[id]', 'entity' => '', 'action' => '', 'join' => '', 'target' => 'crm-popup', 'icon' => 'fa-copy', 'text' => E::ts('Duplicate'), 'style' => 'default', 'task' => '', 'conditions' => []],
                ['path' => 'civicrm/elections/positions/delete?eid=[election_id]&epid=[id]', 'entity' => '', 'action' => '', 'join' => '', 'target' => 'crm-popup', 'icon' => 'fa-trash', 'text' => E::ts('Delete'), 'style' => 'danger', 'task' => '', 'conditions' => []],
              ],
              'type' => 'buttons',
              'alignment' => 'text-right',
            ],
          ],
          'actions' => FALSE,
          'classes' => ['table', 'table-striped'],
        ],
      ],
      'match' => ['saved_search_id', 'name'],
    ],
  ],
];
