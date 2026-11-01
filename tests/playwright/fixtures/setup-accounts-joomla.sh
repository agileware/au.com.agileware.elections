#!/usr/bin/env bash
# Creates the test administrator the back end specs log in with on a Joomla 5 site (run inside the
# DDEV web container from the site root). Safe to re-run. The password comes from test-users.json
# (a test value for the local test site only).
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
USERS_JSON="$SCRIPT_DIR/test-users.json"
get() { php -r 'echo json_decode(file_get_contents($argv[1]), true)[$argv[2]][$argv[3]];' "$USERS_JSON" "$1" "$2"; }
NAME=$(get admin username)
if php cli/joomla.php user:list 2>/dev/null | grep -q " $NAME "; then
  php cli/joomla.php user:reset-password --username="$NAME" --password="$(get admin password)" -n >/dev/null
else
  php cli/joomla.php user:add --username="$NAME" --name="Elections Admin (test)" --password="$(get admin password)" --email="$(get admin email)" --usergroup="Super Users" -n >/dev/null
fi
echo "Joomla test administrator ready"
