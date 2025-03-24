<?php
use CRM_Elections_ExtensionUtil as E;

return [
  'name' => 'ElectionResult',
  'table' => 'civicrm_election_result',
  'class' => 'CRM_Elections_DAO_ElectionResult',
  'getInfo' => fn() => [
    'title' => E::ts('Election Result'),
    'title_plural' => E::ts('Election Results'),
    'description' => E::ts('FIXME'),
    'log' => TRUE,
    'add' => '5.3',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique ElectionResult ID'),
      'add' => '5.3',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'rank' => [
      'title' => E::ts('Rank'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Text',
      'description' => E::ts('Rank of a member for particular position.'),
      'add' => '5.3',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'election_position_id' => [
      'title' => E::ts('Election Position ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to ElectionPosition for which this result is added.'),
      'add' => '5.3',
      'entity_reference' => [
        'entity' => 'ElectionPosition',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'election_nomination_id' => [
      'title' => E::ts('Election Nomination ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to ElectionNomination for which this rank is added.'),
      'add' => '5.3',
      'entity_reference' => [
        'entity' => 'ElectionNomination',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
  ],
];
