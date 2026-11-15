<?php
// The CiviCRM package for Joomla leaves out psr/log and relies on the copy Joomla loads inside the web
// application. cv runs outside Joomla, so make Joomla's copy available.
spl_autoload_register(function ($class) {
  if (strpos($class, 'Psr\\Log\\') === 0) {
    $file = '/var/www/html/libraries/vendor/psr/log/src/' . str_replace('\\', '/', substr($class, 8)) . '.php';
    if (is_file($file)) {
      require_once $file;
    }
  }
});
