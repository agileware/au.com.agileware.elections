<?php
use CRM_Elections_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_ManageElections',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'ManageElections',
        'label' => E::ts('Manage Elections'),
        'api_entity' => 'Election',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'name',
            'is_visible',
            'stage:label',
            'is_locked',
            'lock_reason',
            'nomination_start_date',
            'voting_start_date',
            'voting_end_date',
            'result_date',
            'COUNT(Election_ElectionPosition_election_id_01.id) AS COUNT_Election_ElectionPosition_election_id_01_id',
          ],
          'orderBy' => [],
          'where' => [
            ['is_deleted', 'IS EMPTY'],
          ],
          'groupBy' => ['id'],
          'join' => [
            [
              'ElectionPosition AS Election_ElectionPosition_election_id_01',
              'LEFT',
              ['id', '=', 'Election_ElectionPosition_election_id_01.election_id'],
            ],
          ],
          'having' => [],
        ],
        'description' => E::ts('Every election that has not been deleted, with its dates, its stage, how many positions it has and whether it is active and locked.'),
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'SavedSearch_ManageElections_SearchDisplay_ManageElections_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'ManageElections_Table_1',
        'label' => E::ts('Manage Elections'),
        'saved_search_id.name' => 'ManageElections',
        'type' => 'table',
        'settings' => [
          'description' => E::ts('One row for each election that has not been deleted. An election is locked once it is active and nominations have started: it cannot be edited or deleted, and its positions cannot be changed.'),
          'noResultsText' => E::ts('No elections found. Clear the filters, or select Add Election to create one.'),
          'sort' => [['nomination_start_date', 'DESC']],
          'limit' => 50,
          'pager' => ['show_count' => TRUE],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'name',
              'dataType' => 'String',
              'label' => E::ts('Election'),
              'sortable' => TRUE,
              'link' => ['path' => 'civicrm/elections/overview?eid=[id]', 'entity' => '', 'action' => '', 'join' => '', 'target' => ''],
            ],
            [
              'type' => 'field',
              'key' => 'is_visible',
              'dataType' => 'Boolean',
              'label' => E::ts('Active'),
              'sortable' => TRUE,
            ],
            ['type' => 'field', 'key' => 'stage:label', 'dataType' => 'String', 'label' => E::ts('Stage'), 'sortable' => FALSE],
            ['type' => 'field', 'key' => 'nomination_start_date', 'dataType' => 'Timestamp', 'label' => E::ts('Nominations open'), 'sortable' => TRUE],
            ['type' => 'field', 'key' => 'voting_start_date', 'dataType' => 'Timestamp', 'label' => E::ts('Voting opens'), 'sortable' => TRUE],
            ['type' => 'field', 'key' => 'voting_end_date', 'dataType' => 'Timestamp', 'label' => E::ts('Voting closes'), 'sortable' => TRUE],
            ['type' => 'field', 'key' => 'result_date', 'dataType' => 'Timestamp', 'label' => E::ts('Results published'), 'sortable' => TRUE],
            [
              'type' => 'field',
              'key' => 'COUNT_Election_ElectionPosition_election_id_01_id',
              'dataType' => 'Integer',
              'label' => E::ts('Positions'),
              'sortable' => TRUE,
              'link' => ['path' => 'civicrm/elections/positions?eid=[id]', 'entity' => '', 'action' => '', 'join' => '', 'target' => ''],
            ],
            ['type' => 'field', 'key' => 'lock_reason', 'dataType' => 'String', 'label' => E::ts('Edit and delete'), 'sortable' => FALSE],
            [
              'size' => 'btn-sm',
              'links' => [
                ['path' => 'civicrm/elections/create?eid=[id]', 'entity' => '', 'action' => '', 'join' => '', 'target' => '', 'icon' => 'fa-pencil', 'text' => E::ts('Edit'), 'style' => 'default', 'task' => '', 'conditions' => [['is_locked', '=', FALSE]]],
                ['path' => 'civicrm/elections/duplicate?eid=[id]', 'entity' => '', 'action' => '', 'join' => '', 'target' => 'crm-popup', 'icon' => 'fa-copy', 'text' => E::ts('Duplicate'), 'style' => 'default', 'task' => '', 'conditions' => []],
                ['path' => 'civicrm/elections/visibility?eid=[id]', 'entity' => '', 'action' => '', 'join' => '', 'target' => 'crm-popup', 'icon' => 'fa-toggle-on', 'text' => E::ts('Activate'), 'style' => 'default', 'task' => '', 'conditions' => [['is_visible', '=', FALSE]]],
                ['path' => 'civicrm/elections/visibility?eid=[id]', 'entity' => '', 'action' => '', 'join' => '', 'target' => 'crm-popup', 'icon' => 'fa-toggle-off', 'text' => E::ts('Deactivate'), 'style' => 'default', 'task' => '', 'conditions' => [['is_visible', '=', TRUE]]],
                ['path' => 'civicrm/elections/delete?eid=[id]', 'entity' => '', 'action' => '', 'join' => '', 'target' => 'crm-popup', 'icon' => 'fa-trash', 'text' => E::ts('Delete'), 'style' => 'danger', 'task' => '', 'conditions' => [['is_locked', '=', FALSE]]],
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
