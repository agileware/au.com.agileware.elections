<?php
use CRM_Elections_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_ElectionResults',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'ElectionResults',
        'label' => E::ts('Election Results'),
        'api_entity' => 'ElectionResult',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'election_position_id.election_id',
            'election_position_id.name',
            'election_position_id.sortorder',
            'rank',
            'election_nomination_id',
            'election_nomination_id.member_nominee.display_name',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
        'description' => E::ts('The calculated result for each position of one election, chosen at runtime with the eid parameter. Shows the final ranking only, never how people voted.'),
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'SavedSearch_ElectionResults_SearchDisplay_ElectionResults_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'ElectionResults_Table_1',
        'label' => E::ts('Election Results'),
        'saved_search_id.name' => 'ElectionResults',
        'type' => 'table',
        'settings' => [
          'description' => E::ts('The ranking for each position after the votes were counted. The number of seats for a position says how many of the top ranks win.'),
          'sort' => [['election_position_id.sortorder', 'ASC'], ['rank', 'ASC']],
          'limit' => 100,
          'pager' => [],
          'placeholder' => 5,
          'columns' => [
            ['type' => 'field', 'key' => 'election_position_id.name', 'dataType' => 'String', 'label' => E::ts('Position'), 'sortable' => TRUE],
            ['type' => 'field', 'key' => 'rank', 'dataType' => 'Integer', 'label' => E::ts('Rank'), 'sortable' => TRUE],
            [
              'type' => 'field',
              'key' => 'election_nomination_id.member_nominee.display_name',
              'dataType' => 'String',
              'label' => E::ts('Candidate'),
              'sortable' => TRUE,
              'link' => ['path' => 'civicrm/elections/candidate?eid=[election_position_id.election_id]&enid=[election_nomination_id]', 'entity' => '', 'action' => '', 'join' => '', 'target' => ''],
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
