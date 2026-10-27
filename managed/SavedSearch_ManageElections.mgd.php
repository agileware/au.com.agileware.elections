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
        'description' => E::ts('Every election that has not been deleted, with its dates, how many positions it has and whether it is active.'),
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
          'description' => E::ts('Use Activate or Deactivate to show or hide an election. An election needs at least one position before it can be active.'),
          'sort' => [['nomination_start_date', 'DESC']],
          'limit' => 50,
          'pager' => [],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'name',
              'dataType' => 'String',
              'label' => E::ts('Election'),
              'sortable' => TRUE,
              'link' => ['path' => 'civicrm/elections/view?eid=[id]', 'entity' => '', 'action' => '', 'join' => '', 'target' => ''],
            ],
            [
              'type' => 'field',
              'key' => 'is_visible',
              'dataType' => 'Boolean',
              'label' => E::ts('Active'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            ['type' => 'field', 'key' => 'nomination_start_date', 'dataType' => 'Timestamp', 'label' => E::ts('Nominations open'), 'sortable' => TRUE],
            ['type' => 'field', 'key' => 'voting_start_date', 'dataType' => 'Timestamp', 'label' => E::ts('Voting opens'), 'sortable' => TRUE],
            ['type' => 'field', 'key' => 'voting_end_date', 'dataType' => 'Timestamp', 'label' => E::ts('Voting closes'), 'sortable' => TRUE],
            ['type' => 'field', 'key' => 'result_date', 'dataType' => 'Timestamp', 'label' => E::ts('Results'), 'sortable' => TRUE],
            [
              'type' => 'field',
              'key' => 'COUNT_Election_ElectionPosition_election_id_01_id',
              'dataType' => 'Integer',
              'label' => E::ts('Positions'),
              'sortable' => TRUE,
              'link' => ['path' => 'civicrm/elections/position-list#/?eid=[id]', 'entity' => '', 'action' => '', 'join' => '', 'target' => ''],
            ],
            [
              'size' => 'btn-xs',
              'links' => [
                ['path' => 'civicrm/elections/visibility?eid=[id]', 'entity' => '', 'action' => '', 'join' => '', 'target' => 'crm-popup', 'icon' => 'fa-toggle-on', 'text' => E::ts('Activate'), 'style' => 'success', 'task' => '', 'conditions' => [['is_visible', '=', FALSE]]],
                ['path' => 'civicrm/elections/visibility?eid=[id]', 'entity' => '', 'action' => '', 'join' => '', 'target' => 'crm-popup', 'icon' => 'fa-toggle-off', 'text' => E::ts('Deactivate'), 'style' => 'warning', 'task' => '', 'conditions' => [['is_visible', '=', TRUE]]],
                ['path' => 'civicrm/elections/position-list#/?eid=[id]', 'entity' => '', 'action' => '', 'join' => '', 'target' => '', 'icon' => 'fa-list-ol', 'text' => E::ts('Positions'), 'style' => 'default', 'task' => '', 'conditions' => []],
                ['path' => 'civicrm/elections/candidate-list#/?eid=[id]', 'entity' => '', 'action' => '', 'join' => '', 'target' => '', 'icon' => 'fa-users', 'text' => E::ts('Candidates'), 'style' => 'default', 'task' => '', 'conditions' => []],
                ['path' => 'civicrm/elections/create?eid=[id]', 'entity' => '', 'action' => '', 'join' => '', 'target' => '', 'icon' => 'fa-pencil', 'text' => E::ts('Edit'), 'style' => 'default', 'task' => '', 'conditions' => []],
                ['path' => 'civicrm/elections/duplicate?eid=[id]', 'entity' => '', 'action' => '', 'join' => '', 'target' => 'crm-popup', 'icon' => 'fa-copy', 'text' => E::ts('Duplicate'), 'style' => 'default', 'task' => '', 'conditions' => []],
                ['path' => 'civicrm/elections/delete?eid=[id]', 'entity' => '', 'action' => '', 'join' => '', 'target' => 'crm-popup', 'icon' => 'fa-trash', 'text' => E::ts('Delete'), 'style' => 'danger', 'task' => '', 'conditions' => []],
              ],
              'type' => 'buttons',
              'alignment' => 'text-right',
            ],
          ],
          'actions' => FALSE,
          'classes' => ['table', 'table-striped'],
          'toolbar' => [
            ['path' => 'civicrm/elections/create', 'entity' => '', 'action' => '', 'join' => '', 'target' => '', 'text' => E::ts('Create Election'), 'icon' => 'fa-plus', 'style' => 'primary', 'task' => '', 'conditions' => []],
          ],
        ],
      ],
      'match' => ['saved_search_id', 'name'],
    ],
  ],
];
