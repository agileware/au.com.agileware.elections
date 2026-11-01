<?php
use CRM_Elections_ExtensionUtil as E;

return [
  'name' => 'ElectionOption',
  'table' => 'civicrm_election_option',
  'class' => 'CRM_Elections_DAO_ElectionOption',
  'getInfo' => fn() => [
    'title' => E::ts('Election Option'),
    'title_plural' => E::ts('Election Options'),
    'description' => E::ts('A choice on a referendum question: yes, no or abstain, with the wording the election uses. Never a candidate.'),
    'log' => TRUE,
    'add' => '2.0',
  ],
  'getIndices' => fn() => [
    'UI_election_option_position_kind' => [
      'fields' => ['election_position_id' => TRUE, 'kind' => TRUE],
      'unique' => TRUE,
      'add' => '2.0',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique ElectionOption ID'),
      'add' => '2.0',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'election_position_id' => [
      'title' => E::ts('Election Position ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to the referendum question this is a choice of.'),
      'add' => '2.0',
      'entity_reference' => [
        'entity' => 'ElectionPosition',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'kind' => [
      'title' => E::ts('Kind'),
      'sql_type' => 'varchar(16)',
      'input_type' => 'Text',
      'description' => E::ts('yes, no or abstain.'),
      'add' => '2.0',
    ],
    'label' => [
      'title' => E::ts('Label'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => E::ts('The wording shown to voters, for example For or Approve.'),
      'add' => '2.0',
    ],
    'weight' => [
      'title' => E::ts('Order'),
      'sql_type' => 'int',
      'input_type' => 'Number',
      'description' => E::ts('Order on the ballot.'),
      'add' => '2.0',
      'default' => 0,
    ],
    'created_at' => [
      'title' => E::ts('Created At'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'description' => E::ts('Date on which the option was created.'),
      'add' => '2.0',
      'default' => 'CURRENT_TIMESTAMP',
      'usage' => ['export'],
    ],
  ],
];
