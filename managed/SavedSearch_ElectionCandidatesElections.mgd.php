<?php
use CRM_Elections_ExtensionUtil as E;

// The elections a person who manages candidates chooses from (the Election Candidates page, without an election).
return [
  [
    'name' => 'SavedSearch_ElectionCandidatesElections',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'ElectionCandidatesElections',
        'label' => E::ts('Elections for candidates'),
        'api_entity' => 'Election',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'name',
            'stage:label',
            'nomination_start_date',
            'voting_start_date',
            'voting_end_date',
          ],
          'orderBy' => [],
          'where' => [
            ['is_deleted', 'IS EMPTY'],
          ],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
        'description' => E::ts('Every election that has not been deleted, with its stage and dates, for choosing whose candidates to manage.'),
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'SavedSearch_ElectionCandidatesElections_SearchDisplay_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'ElectionCandidatesElections_Table_1',
        'label' => E::ts('Elections for candidates'),
        'saved_search_id.name' => 'ElectionCandidatesElections',
        'type' => 'table',
        'settings' => [
          'description' => E::ts('Choose an election to see its candidates and add one.'),
          'noResultsText' => E::ts('No elections found.'),
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
              'link' => ['path' => 'civicrm/elections/candidate-list?reset=1&eid=[id]', 'entity' => '', 'action' => '', 'join' => '', 'target' => ''],
            ],
            [
              'type' => 'field',
              'key' => 'stage:label',
              'dataType' => 'String',
              'label' => E::ts('Stage'),
              'sortable' => FALSE,
            ],
            [
              'type' => 'field',
              'key' => 'nomination_start_date',
              'dataType' => 'Timestamp',
              'label' => E::ts('Nominations open'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'voting_start_date',
              'dataType' => 'Timestamp',
              'label' => E::ts('Voting opens'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'voting_end_date',
              'dataType' => 'Timestamp',
              'label' => E::ts('Voting closes'),
              'sortable' => TRUE,
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
