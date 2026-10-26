<?php

use CRM_Elections_ExtensionUtil as E;

return [
  'elections_image_source' => [
    'name' => 'elections_image_source',
    'type' => 'String',
    'default' => 'CMS',
    'html_type' => 'select',
    'add' => '1.0',
    'title' => E::ts('Image Source'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => E::ts('Select the source of the image to use for the candidate.'),
    'settings_pages' => [
      'elections' => [
        'weight' => 15,
      ],
    ],
    'options' => [
      'CMS' => E::ts('CMS'),
      'Contact' => E::ts('Contact Image'),
    ],
  ],
  'elections_name_format' => [
    'name' => 'elections_name_format',
    'type' => 'String',
    'default' => 'display_name',
    'html_type' => 'select',
    'add' => '2.0',
    'title' => E::ts('Candidate Name Format'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => E::ts('How the name of a candidate is shown on nominations, ballots and results. Nicknames are only shown for contacts that have one.'),
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
