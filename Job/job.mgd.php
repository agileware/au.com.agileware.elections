<?php
// This file declares a managed database record of type "Job".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array(
    0 => array(
        'name' => E::ts('Elections Results Job'),
        'entity' => 'Job',
        'update' => 'never',
        'params' => array(
            'version' => 3,
            'name' => E::ts('Elections Results Job'),
            'description' => E::ts('Generate the scheduled election results.'),
            'api_entity' => 'Election',
            'api_action' => 'generateresults',
            'run_frequency' => 'Always',
        ),
    ),
);