<?php
/**
 * Helpers shared by the scripts in this folder (loaded with require_once by each of them).
 *
 * Everything the scripts read from the environment starts with ELECTIONS_SEED_, except ELECTION and STAGE
 * (see move-election.php). An empty variable counts as not set.
 */

/**
 * The folder of these scripts. __DIR__ is correct here because this file is included, whatever
 * `cv scr` does with __DIR__ in the script it runs.
 */
function seed_dir(): string {
  return __DIR__;
}

/**
 * A setting from the environment: ELECTIONS_SEED_<NAME>, or the default.
 */
function seed_env(string $name, $default = NULL) {
  $value = getenv('ELECTIONS_SEED_' . $name);
  return ($value === FALSE || $value === '') ? $default : $value;
}

/**
 * The folder for what the scripts generate (git-ignored): the record of what was seeded, personal links,
 * logins. Created when needed.
 */
function seed_generated_dir(): string {
  $dir = seed_dir() . '/.generated';
  if (!is_dir($dir)) {
    mkdir($dir, 0700, TRUE);
  }
  return $dir;
}

/**
 * The JSON file in which seed.php records the contact and election ids it made, and from which links.php
 * and wordpress-logins.php read them. Set ELECTIONS_SEED_STATE_FILE to keep it elsewhere.
 */
function seed_state_file(): string {
  return seed_env('STATE_FILE') ?: seed_generated_dir() . '/seeded.json';
}

/**
 * What seed.php recorded, or an error and exit when it has not run.
 */
function seed_read_state(): array {
  $file = seed_state_file();
  $state = is_file($file) ? json_decode(file_get_contents($file), TRUE) : NULL;
  if (!is_array($state) || empty($state['beekeepers']) || empty($state['elections'])) {
    fwrite(STDERR, "No record of a seed run at $file. Run seed.php first (or set ELECTIONS_SEED_STATE_FILE).\n");
    exit(1);
  }
  return $state;
}

/**
 * Write a file that holds something private (a password or a personal link) readable by its owner only.
 */
function seed_write_private_file(string $path, string $contents): void {
  $dir = dirname($path);
  if (!is_dir($dir)) {
    mkdir($dir, 0700, TRUE);
  }
  file_put_contents($path, $contents);
  chmod($path, 0600);
}
