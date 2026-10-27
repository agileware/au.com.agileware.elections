<?php
use CRM_Elections_ExtensionUtil as E;

/**
 * Message templates for the emails the extension sends. Each workflow has an editable default and a
 * reserved original, which is how CiviCRM lets people edit a template and reset it later.
 */
$templates = [
  'elections_nominee_invited' => E::ts('Elections - Nominee invited to respond'),
  'elections_write_in_invited' => E::ts('Elections - Person written in by a voter'),
  'elections_write_in_reminder' => E::ts('Elections - Reminder to a person written in'),
  'elections_write_in_closed' => E::ts('Elections - Voting closed before a person written in answered'),
];

$entities = [
  [
    'name' => 'OptionGroup_msg_tpl_workflow_elections',
    'entity' => 'OptionGroup',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'checkPermissions' => FALSE,
      'values' => [
        'name' => 'msg_tpl_workflow_elections',
        'title' => E::ts('Message Template Workflow for Elections'),
        'description' => E::ts('Message templates used by the Elections extension.'),
        'is_reserved' => TRUE,
        'is_active' => TRUE,
      ],
      'match' => ['name'],
    ],
  ],
];

$weight = 0;
foreach ($templates as $workflow => $title) {
  $weight++;
  $entities[] = [
    'name' => 'OptionValue_' . $workflow,
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'checkPermissions' => FALSE,
      'values' => [
        'option_group_id.name' => 'msg_tpl_workflow_elections',
        'name' => $workflow,
        'label' => $title,
        'value' => $weight,
        'weight' => $weight,
        'is_reserved' => TRUE,
        'is_active' => TRUE,
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ];
  $files = [];
  foreach (['subject', 'text', 'html'] as $part) {
    $files[$part] = file_get_contents(__DIR__ . '/../msg_tpl/' . $workflow . '_' . $part . '.tpl');
  }
  foreach ([TRUE, FALSE] as $reserved) {
    $entities[] = [
      'name' => $workflow . ($reserved ? '_reserved' : ''),
      'entity' => 'MessageTemplate',
      'cleanup' => 'unused',
      'update' => 'never',
      'params' => [
        'version' => 4,
        'checkPermissions' => FALSE,
        'match' => ['workflow_name', 'is_reserved'],
        'values' => [
          'msg_title' => $title,
          'msg_subject' => trim($files['subject']),
          'msg_text' => $files['text'],
          'msg_html' => $files['html'],
          'is_default' => !$reserved,
          'is_reserved' => $reserved,
          'is_active' => TRUE,
          'workflow_name' => $workflow,
        ],
      ],
    ];
  }
}

return $entities;
