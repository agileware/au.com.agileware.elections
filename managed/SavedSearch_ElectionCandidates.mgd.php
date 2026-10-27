<?php
use CRM_Elections_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_ElectionCandidates',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'ElectionCandidates',
        'label' => E::ts('Election Candidates'),
        'api_entity' => 'ElectionNomination',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'election_position_id.election_id',
            'election_position_id.name',
            'election_position_id.sortorder',
            'member_nominee.display_name',
            'COUNT(ElectionNomination_ElectionNominationSeconder_election_nomination_id_01.id) AS COUNT_ElectionNomination_ElectionNominationSeconder_election_nomination_id_01_id',
            'is_eligible_candidate',
            'has_accepted_nomination',
            'has_rejected_nomination',
            'comments',
            'statement_url',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => ['id'],
          'join' => [
            [
              'ElectionNominationSeconder AS ElectionNomination_ElectionNominationSeconder_election_nomination_id_01',
              'LEFT',
              ['id', '=', 'ElectionNomination_ElectionNominationSeconder_election_nomination_id_01.election_nomination_id'],
            ],
          ],
          'having' => [],
        ],
        'description' => E::ts('Everyone nominated in one election, chosen at runtime with the eid parameter, with whether they are standing and their statement.'),
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'SavedSearch_ElectionCandidates_SearchDisplay_ElectionCandidates_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'ElectionCandidates_Table_1',
        'label' => E::ts('Election Candidates'),
        'saved_search_id.name' => 'ElectionCandidates',
        'type' => 'table',
        'settings' => [
          'description' => E::ts('Select a statement or link to edit it. Who is standing can be changed until voting starts; statements can be corrected until voting ends.'),
          'sort' => [['election_position_id.sortorder', 'ASC'], ['member_nominee.display_name', 'ASC']],
          'limit' => 100,
          'pager' => [],
          'placeholder' => 5,
          'columns' => [
            ['type' => 'field', 'key' => 'election_position_id.name', 'dataType' => 'String', 'label' => E::ts('Position'), 'sortable' => TRUE],
            [
              'type' => 'field',
              'key' => 'member_nominee.display_name',
              'dataType' => 'String',
              'label' => E::ts('Candidate'),
              'sortable' => TRUE,
              'link' => ['path' => 'civicrm/elections/candidate?eid=[election_position_id.election_id]&enid=[id]', 'entity' => '', 'action' => '', 'join' => '', 'target' => ''],
            ],
            ['type' => 'field', 'key' => 'COUNT_ElectionNomination_ElectionNominationSeconder_election_nomination_id_01_id', 'dataType' => 'Integer', 'label' => E::ts('Nominators'), 'sortable' => TRUE],
            ['type' => 'field', 'key' => 'is_eligible_candidate', 'dataType' => 'Boolean', 'label' => E::ts('Eligible'), 'sortable' => TRUE, 'editable' => TRUE],
            ['type' => 'field', 'key' => 'has_accepted_nomination', 'dataType' => 'Boolean', 'label' => E::ts('Accepted'), 'sortable' => TRUE, 'editable' => TRUE],
            ['type' => 'field', 'key' => 'has_rejected_nomination', 'dataType' => 'Boolean', 'label' => E::ts('Withdrawn'), 'sortable' => TRUE, 'editable' => TRUE],
            ['type' => 'field', 'key' => 'comments', 'dataType' => 'Text', 'label' => E::ts('Statement'), 'sortable' => FALSE, 'editable' => TRUE],
            ['type' => 'field', 'key' => 'statement_url', 'dataType' => 'String', 'label' => E::ts('Statement link'), 'sortable' => FALSE, 'editable' => TRUE],
          ],
          'actions' => FALSE,
          'classes' => ['table', 'table-striped'],
        ],
      ],
      'match' => ['saved_search_id', 'name'],
    ],
  ],
];
