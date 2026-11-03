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
          'select' => ['id', 'election_id', 'name', 'position_type:label', 'quantity', 'write_ins_per_seat', 'description', 'sortorder'],
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
          'description' => E::ts('One row for each position or question in this election, in the order shown on nominations and the ballot. Drag a row to change the order, or select a name, seats, write-ins per seat (0 to 10, 0 means off) or description to edit it, until the election is locked: active, with nominations started.'),
          'noResultsText' => E::ts('There are no positions yet. Select Add Position to add one.'),
          'sort' => [['sortorder', 'ASC']],
          'draggable' => 'sortorder',
          'limit' => 50,
          'pager' => ['show_count' => TRUE],
          'placeholder' => 5,
          'columns' => [
            ['type' => 'field', 'key' => 'name', 'dataType' => 'String', 'label' => E::ts('Position'), 'sortable' => FALSE, 'editable' => TRUE],
            ['type' => 'field', 'key' => 'position_type:label', 'dataType' => 'String', 'label' => E::ts('Position type'), 'sortable' => FALSE],
            ['type' => 'field', 'key' => 'quantity', 'dataType' => 'Integer', 'label' => E::ts('Seats'), 'sortable' => FALSE, 'editable' => TRUE],
            ['type' => 'field', 'key' => 'write_ins_per_seat', 'dataType' => 'Integer', 'label' => E::ts('Write-ins per seat'), 'sortable' => FALSE, 'editable' => TRUE],
            ['type' => 'field', 'key' => 'description', 'dataType' => 'Text', 'label' => E::ts('Description'), 'sortable' => FALSE, 'editable' => TRUE],
            [
              'size' => 'btn-sm',
              'links' => [
                ['path' => 'civicrm/elections/positions/create?eid=[election_id]&epid=[id]', 'entity' => '', 'action' => '', 'join' => '', 'target' => '', 'icon' => 'fa-pencil', 'text' => E::ts('Edit'), 'style' => 'default', 'task' => '', 'conditions' => []],
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
  [
    'name' => 'SavedSearch_ElectionPositions_SearchDisplay_ElectionPositions_Locked',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'ElectionPositions_Locked',
        'label' => E::ts('Election Positions (election active)'),
        'saved_search_id.name' => 'ElectionPositions',
        'type' => 'table',
        'settings' => [
          'description' => E::ts('One row for each position or question in this election. They cannot be changed because the election is locked: it is active and nominations have started.'),
          'noResultsText' => E::ts('This election has no positions.'),
          'sort' => [['sortorder', 'ASC']],
          'limit' => 50,
          'pager' => ['show_count' => TRUE],
          'placeholder' => 5,
          'columns' => [
            ['type' => 'field', 'key' => 'name', 'dataType' => 'String', 'label' => E::ts('Position'), 'sortable' => FALSE],
            ['type' => 'field', 'key' => 'position_type:label', 'dataType' => 'String', 'label' => E::ts('Position type'), 'sortable' => FALSE],
            ['type' => 'field', 'key' => 'quantity', 'dataType' => 'Integer', 'label' => E::ts('Seats'), 'sortable' => FALSE],
            ['type' => 'field', 'key' => 'write_ins_per_seat', 'dataType' => 'Integer', 'label' => E::ts('Write-ins per seat'), 'sortable' => FALSE],
            ['type' => 'field', 'key' => 'description', 'dataType' => 'Text', 'label' => E::ts('Description'), 'sortable' => FALSE],
          ],
          'actions' => FALSE,
          'classes' => ['table', 'table-striped'],
        ],
      ],
      'match' => ['saved_search_id', 'name'],
    ],
  ],
];
