#!/usr/bin/env bash
# Creates the test accounts the back end specs log in with on a Drupal 11 site (run inside the
# DDEV web container from the project root): an administrator, an Elections manager and a member.
# Safe to re-run. Passwords come from test-users.json (test values for the local test site only).
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
USERS_JSON="$SCRIPT_DIR/test-users.json"
DRUSH=${DRUSH:-vendor/bin/drush}
get() { php -r 'echo json_decode(file_get_contents($argv[1]), true)[$argv[2]][$argv[3]];' "$USERS_JSON" "$1" "$2"; }

role() {
  $DRUSH role:create "$1" "$2" >/dev/null 2>&1 || true
}
perms() {
  local role=$1; shift
  for p in "$@"; do $DRUSH role:perm:add "$role" "$p" >/dev/null; done
}
account() {
  local key=$1 role=$2
  local name; name=$(get "$key" username)
  if $DRUSH user:information "$name" >/dev/null 2>&1; then
    $DRUSH user:password "$name" "$(get "$key" password)" >/dev/null
  else
    $DRUSH user:create "$name" --mail="$(get "$key" email)" --password="$(get "$key" password)" >/dev/null
  fi
  $DRUSH user:role:add "$role" "$name" >/dev/null
}

role elections_manager "Elections Manager (test)"
perms elections_manager "access CiviCRM" "administer Elections" "view Elections" "access AJAX API"
role elections_member "Elections Member (test)"
perms elections_member "view Elections" "access AJAX API" "access uploaded files" "profile view" "profile edit"

account admin administrator
account manager elections_manager
account member elections_member
echo "Drupal test accounts ready"
