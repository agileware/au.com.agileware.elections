<?php
use CRM_Elections_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Election Results'),
  'icon' => 'fa-trophy',
  'server_route' => 'civicrm/elections/result-list',
  'permission' => ['access CiviCRM', 'administer Elections'],
  'permission_operator' => 'AND',
  'search_displays' => [
    'ElectionResults.ElectionResults_Table_1',
  ],
];
