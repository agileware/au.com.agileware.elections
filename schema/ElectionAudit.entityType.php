<?php
use CRM_Elections_ExtensionUtil as E;

return [
  'name' => 'ElectionAudit',
  'table' => 'civicrm_election_audit',
  'class' => 'CRM_Elections_DAO_ElectionAudit',
  'getInfo' => fn() => [
    'title' => E::ts('Election Audit Entry'),
    'title_plural' => E::ts('Election Audit Log'),
    'description' => E::ts('A tamper-evident record of what was changed in an election, who changed it and when. Each entry carries a hash of the one before it.'),
    'log' => FALSE,
    'add' => '2.0',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique audit entry ID'),
      'add' => '2.0',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'election_id' => [
      'title' => E::ts('Election ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => E::ts('The election the entry is about. Not a foreign key, so the record outlives the election.'),
      'add' => '2.0',
    ],
    'created_date' => [
      'title' => E::ts('Date'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'description' => E::ts('When the change happened.'),
      'add' => '2.0',
      'default' => 'CURRENT_TIMESTAMP',
    ],
    'contact_id' => [
      'title' => E::ts('Changed By'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => E::ts('The contact who made the change, if known. Never recorded for ballots.'),
      'add' => '2.0',
    ],
    'action' => [
      'title' => E::ts('Action'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => E::ts('What happened, for example election_changed or results_generated.'),
      'add' => '2.0',
    ],
    'details' => [
      'title' => E::ts('Details'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('JSON describing the change. Never contains how anyone voted.'),
      'add' => '2.0',
    ],
    'prev_hash' => [
      'title' => E::ts('Previous Hash'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => E::ts('Hash of the previous entry.'),
      'add' => '2.0',
    ],
    'entry_hash' => [
      'title' => E::ts('Hash'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => E::ts('Hash of this entry and the previous hash.'),
      'add' => '2.0',
    ],
  ],
  'getIndices' => fn() => [
    'index_election_id' => [
      'fields' => ['election_id' => TRUE],
      'add' => '2.0',
    ],
  ],
];
