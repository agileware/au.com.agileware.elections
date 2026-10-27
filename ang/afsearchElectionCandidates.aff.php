<?php
use CRM_Elections_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Election Candidates'),
  'icon' => 'fa-users',
  'server_route' => 'civicrm/elections/candidate-list',
  'permission' => ['access CiviCRM', 'administer Elections'],
  'permission_operator' => 'AND',
  'search_displays' => [
    'ElectionCandidates.ElectionCandidates_Table_1',
  ],
];
