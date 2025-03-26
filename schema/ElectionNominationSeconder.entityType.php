<?php
use CRM_Elections_ExtensionUtil as E;

return [
  'name' => 'ElectionNominationSeconder',
  'table' => 'civicrm_election_nomination_seconder',
  'class' => 'CRM_Elections_DAO_ElectionNominationSeconder',
  'getInfo' => fn() => [
    'title' => E::ts('Election Nomination Seconder'),
    'title_plural' => E::ts('Election Nomination Seconders'),
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
      'description' => E::ts('Unique ElectionNominationSeconder ID'),
      'add' => '5.3',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'description' => [
      'title' => E::ts('Description'),
      'sql_type' => 'text',
      'input_type' => 'Text',
      'add' => '5.3',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'member_nominator' => [
      'title' => E::ts('Member Nominator'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to Contact who nominated member_nominee for particular position.'),
      'add' => '5.3',
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'election_nomination_id' => [
      'title' => E::ts('Election Nomination ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to ElectionNomination for which this seconder is added.'),
      'add' => '5.3',
      'entity_reference' => [
        'entity' => 'ElectionNomination',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
  ],
];
