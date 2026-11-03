<?php
// The Angular module that completes the SearchKit lists (the table caption, a card layout on a phone, names for the
// row buttons, the way back to the page the person came from, the time zone line, moving positions from the
// keyboard). A list asks for it with the attribute election-list-table on its table; Afform then loads the module.
return [
  'ext' => 'au.com.agileware.elections',
  'js' => ['ang/electionsLists.js'],
  'requires' => [],
  'basePages' => [],
  'exports' => [
    'election-list-table' => 'A',
  ],
  'settingsFactory' => ['CRM_Elections_Helper_ListPage', 'angularSettings'],
];
