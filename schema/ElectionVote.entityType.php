<?php
use CRM_Elections_ExtensionUtil as E;

return [
  'name' => 'ElectionVote',
  'table' => 'civicrm_election_vote',
  'class' => 'CRM_Elections_DAO_ElectionVote',
  'getInfo' => fn() => [
    'title' => E::ts('Election Vote'),
    'title_plural' => E::ts('Election Votes'),
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
      'description' => E::ts('Unique ElectionVote ID'),
      'add' => '5.3',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'rank' => [
      'title' => E::ts('Rank'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Text',
      'description' => E::ts('Rank of a nomination for particular position.'),
      'add' => '5.3',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'election_nomination_id' => [
      'title' => E::ts('Election Nomination ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to ElectionNomination for which this vote is counted.'),
      'add' => '5.3',
      'entity_reference' => [
        'entity' => 'ElectionNomination',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'member_id' => [
      'title' => E::ts('Member ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to Contact who added this vote.'),
      'add' => '5.3',
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'created_at' => [
      'title' => E::ts('Created At'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'description' => E::ts('Date on which vote has been added.'),
      'add' => '5.3',
      'default' => 'CURRENT_TIMESTAMP',
      'usage' => ['export'],
    ],
  ],
];
