<?php
use CRM_Elections_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Manage Elections'),
  'icon' => 'fa-check-square-o',
  'server_route' => 'civicrm/elections/manage',
  'permission' => ['access CiviCRM', 'administer Elections'],
  'permission_operator' => 'AND',
  'search_displays' => [
    'ManageElections.ManageElections_Table_1',
  ],
];
