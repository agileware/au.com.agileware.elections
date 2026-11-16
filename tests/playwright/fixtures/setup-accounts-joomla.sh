#!/usr/bin/env bash
# Creates the test administrator the back end specs log in with on a Joomla 5 site (run inside the
# DDEV web container from the site root). Safe to re-run. The password is generated here, at setup,
# into .generated/test-passwords.json (git-ignored; never committed).
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
USERS_JSON="$SCRIPT_DIR/test-users.json"
PASSWORDS_JSON="$SCRIPT_DIR/.generated/test-passwords.json"
php "$SCRIPT_DIR/generate-passwords.php" "$USERS_JSON" "$PASSWORDS_JSON"
password() { php -r 'echo json_decode(file_get_contents($argv[1]), true)[$argv[2]];' "$PASSWORDS_JSON" "$1"; }
get() { php -r 'echo json_decode(file_get_contents($argv[1]), true)[$argv[2]][$argv[3]];' "$USERS_JSON" "$1" "$2"; }
NAME=$(get admin username)
if php cli/joomla.php user:list 2>/dev/null | grep -q " $NAME "; then
  php cli/joomla.php user:reset-password --username="$NAME" --password="$(password admin)" -n >/dev/null
else
  php cli/joomla.php user:add --username="$NAME" --name="Elections Admin (test)" --password="$(password admin)" --email="$(get admin email)" --usergroup="Super Users" -n >/dev/null
fi
echo "Joomla test administrator ready"
