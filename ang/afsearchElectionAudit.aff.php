<?php
use CRM_Elections_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Election Audit Log'),
  'icon' => 'fa-shield',
  'server_route' => 'civicrm/elections/audit-log',
  'permission' => ['access CiviCRM', 'administer Elections'],
  'permission_operator' => 'AND',
  'search_displays' => [
    'ElectionAudit.ElectionAudit_Table_1',
  ],
];
