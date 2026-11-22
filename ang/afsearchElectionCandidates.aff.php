<?php
use CRM_Elections_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Candidates'),
  'icon' => 'fa-users',
  'server_route' => 'civicrm/elections/candidate-list',
  // An administrator, or a person who manages candidates only (both roles also have access CiviCRM).
  'permission' => ['administer Elections', 'manage Election candidates'],
  'permission_operator' => 'OR',
  'search_displays' => [
    'ElectionCandidates.ElectionCandidates_Table_1',
    'ElectionCandidatesElections.ElectionCandidatesElections_Table_1',
  ],
];
