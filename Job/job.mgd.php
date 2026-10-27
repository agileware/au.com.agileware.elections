<?php
// This file declares a managed database record of type "Job".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return [
    0 => [
        'name' => 'Elections Results Job',
        'entity' => 'Job',
        'update' => 'never',
        'params' => [
            'version' => 3,
            'name' => 'Elections Results Job',
            'description' => 'Generate the scheduled election results.',
            'api_entity' => 'Election',
            'api_action' => 'generateresults',
            'run_frequency' => 'Always',
        ],
    ],
    1 => [
        'name' => 'Elections Write-in Reminders',
        'entity' => 'Job',
        'update' => 'never',
        'params' => [
            'version' => 3,
            'name' => 'Elections Write-in Reminders',
            'description' => 'Remind people written in by a voter, who have not answered, shortly before voting closes.',
            'api_entity' => 'Election',
            'api_action' => 'sendwriteinreminders',
            'run_frequency' => 'Hourly',
        ],
    ],
];