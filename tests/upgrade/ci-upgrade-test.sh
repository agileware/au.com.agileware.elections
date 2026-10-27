#!/usr/bin/env bash
# Upgrade test for GitHub Actions: install the old version, seed it, upgrade to this commit, check.
#
# Run by ci-workflows' civicrm-extension-upgrade-tests.yml inside the WordPress container, as www-data. It is
# given EXTENSION_KEY, EXTENSION_DIR (where the extension is installed from, holding the OLD version) and
# NEW_VERSION_DIR (this commit, read-only). The same seed and checks run by upgrade-from-v1.sh on DDEV.
set -uo pipefail

: "${EXTENSION_KEY:?}" "${EXTENSION_DIR:?}" "${NEW_VERSION_DIR:?}"
CV=${CV:-cv}
cd "$EXTENSION_DIR" || exit 1   # cv finds the site from here

echo "== installing the old version"
$CV flush >/dev/null 2>&1
$CV ext:install "$EXTENSION_KEY" 2>&1 | tail -2
$CV ext:list -L 2>&1 | grep "$EXTENSION_KEY"

echo "== seeding with the old version"
SEED_LOG=$(mktemp)
$CV scr "$NEW_VERSION_DIR/tests/upgrade/seed-v1.php" > "$SEED_LOG" 2>&1
grep -v '^SNAPSHOT_JSON:' "$SEED_LOG" | tail -25
SNAPSHOT=$(grep '^SNAPSHOT_JSON:' "$SEED_LOG" | head -1 | sed 's/^SNAPSHOT_JSON://')
if [ -z "$SNAPSHOT" ]; then
  echo "no snapshot: seeding failed"
  exit 1
fi

echo "== upgrading to this commit"
# The old code is replaced by the new, like unpacking a new release over the old one.
find "$EXTENSION_DIR" -mindepth 1 -delete
cp -a "$NEW_VERSION_DIR"/. "$EXTENSION_DIR"/
mkdir -p "$EXTENSION_DIR/tests/upgrade/.generated"
printf '%s' "$SNAPSHOT" > "$EXTENSION_DIR/tests/upgrade/.generated/v1-snapshot.json"
$CV ext:upgrade-db 2>&1 | tail -3
$CV flush >/dev/null 2>&1

echo "== checking"
UPGRADE_DIR="$EXTENSION_DIR/tests/upgrade" $CV scr "$EXTENSION_DIR/tests/upgrade/verify-v2.php" 2>&1
