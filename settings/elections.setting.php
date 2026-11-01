<?php

use CRM_Elections_ExtensionUtil as E;

return [
  'elections_image_source' => [
    'name' => 'elections_image_source',
    'type' => 'String',
    'default' => 'CMS',
    'html_type' => 'select',
    'add' => '1.0',
    'title' => E::ts('Image source'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => E::ts('Where candidate photos come from. CMS uses the picture of the website account, and Contact image uses the image on the CiviCRM contact.'),
    'settings_pages' => [
      'elections' => [
        'weight' => 15,
      ],
    ],
    'options' => [
      'CMS' => E::ts('CMS'),
      'Contact' => E::ts('Contact image'),
    ],
  ],
  'elections_name_format' => [
    'name' => 'elections_name_format',
    'type' => 'String',
    'default' => 'display_name',
    'html_type' => 'select',
    'add' => '2.0',
    'title' => E::ts('Candidate name format'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => E::ts('How the name of a candidate is shown on nominations, ballots and results. Nicknames are only shown for people who have one.'),
    'settings_pages' => [
      'elections' => [
        'weight' => 20,
      ],
    ],
    'pseudoconstant' => [
      'callback' => 'CRM_Elections_Helper_Candidate::getNameFormats',
    ],
  ],
];
