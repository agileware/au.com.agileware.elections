<?php
use CRM_Elections_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Election Positions'),
  'icon' => 'fa-list-ol',
  'server_route' => 'civicrm/elections/position-list',
  'permission' => ['access CiviCRM', 'administer Elections'],
  'permission_operator' => 'AND',
  'search_displays' => [
    'ElectionPositions.ElectionPositions_Table_1',
  ],
];
