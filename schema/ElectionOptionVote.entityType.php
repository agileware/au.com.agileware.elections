<?php
use CRM_Elections_ExtensionUtil as E;

return [
  'name' => 'ElectionOptionVote',
  'table' => 'civicrm_election_option_vote',
  'class' => 'CRM_Elections_DAO_ElectionOptionVote',
  'getInfo' => fn() => [
    'title' => E::ts('Election Option Vote'),
    'title_plural' => E::ts('Election Option Votes'),
    'description' => E::ts('A choice made on a referendum question. Part of the ballot, with the same secrecy as the votes for candidates.'),
    'log' => TRUE,
    'add' => '2.0',
  ],
  'getIndices' => fn() => [
    // One choice for each member and question, however the request arrives. Anonymised rows have no
    // member, and any number of NULLs are allowed.
    'UI_election_option_vote_member_position' => [
      'fields' => ['member_id' => TRUE, 'election_position_id' => TRUE],
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
      'description' => E::ts('Unique ElectionOptionVote ID'),
      'add' => '2.0',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'election_option_id' => [
      'title' => E::ts('Election Option ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to the option chosen.'),
      'add' => '2.0',
      'entity_reference' => [
        'entity' => 'ElectionOption',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'election_position_id' => [
      'title' => E::ts('Election Position ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to the question answered.'),
      'add' => '2.0',
      'entity_reference' => [
        'entity' => 'ElectionPosition',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'member_id' => [
      'title' => E::ts('Member ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to Contact who made this choice.'),
      'add' => '2.0',
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'created_at' => [
      'title' => E::ts('Created At'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'description' => E::ts('Date on which the choice was made.'),
      'add' => '2.0',
      'default' => 'CURRENT_TIMESTAMP',
      'usage' => ['export'],
    ],
  ],
];
