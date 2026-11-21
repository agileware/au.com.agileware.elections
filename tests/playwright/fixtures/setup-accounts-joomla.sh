#!/usr/bin/env bash
# Creates the test accounts the specs log in with on a Joomla 5 site (run inside the DDEV web container
# from the site root): an administrator, an Elections manager, an organiser without View All Contacts, a person
# who manages candidates only and a voter. Joomla sets CiviCRM's permissions per user group, so joomla-test-groups.php makes a group for each
# role first. Safe to re-run. Passwords are generated here, at setup, into .generated/test-passwords.json
# (git-ignored; never committed).
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
USERS_JSON="$SCRIPT_DIR/test-users.json"
PASSWORDS_JSON="$SCRIPT_DIR/.generated/test-passwords.json"
php "$SCRIPT_DIR/generate-passwords.php" "$USERS_JSON" "$PASSWORDS_JSON"
password() { php -r 'echo json_decode(file_get_contents($argv[1]), true)[$argv[2]];' "$PASSWORDS_JSON" "$1"; }
get() { php -r 'echo json_decode(file_get_contents($argv[1]), true)[$argv[2]][$argv[3]];' "$USERS_JSON" "$1" "$2"; }
account() {
  local key=$1 group=$2 name
  name=$(get "$key" username)
  if php cli/joomla.php user:list 2>/dev/null | grep -q " $name "; then
    php cli/joomla.php user:reset-password --username="$name" --password="$(password "$key")" -n >/dev/null
    php cli/joomla.php user:addtogroup --username="$name" --group="$group" -n >/dev/null 2>&1 || true
  else
    php cli/joomla.php user:add --username="$name" --name="$3" --password="$(password "$key")" --email="$(get "$key" email)" --usergroup="$group" -n >/dev/null
  fi
}

php "$SCRIPT_DIR/joomla-test-groups.php"
account admin "Super Users" "Elections Admin (test)"
account manager "Elections Manager (test)" "Elections Manager (test)"
account limited "Elections Organiser without contacts (test)" "Elections Organiser (test)"
account candidates "Elections Candidates Manager (test)" "Elections Candidates Manager (test)"
account member "Elections Voter (test)" "Elections Voter (test)"
echo "Joomla test accounts ready"
