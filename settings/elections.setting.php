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
];
