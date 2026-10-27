#!/bin/bash
# Upgrade test: install version 1.3.2 of the extension on a DDEV site, seed it, upgrade to this branch, check.
#
#   tests/upgrade/upgrade-from-v1.sh <ddev project dir> <docroot> <cv command>
#   e.g. tests/upgrade/upgrade-from-v1.sh ../elections2 httpdocs cv
#
# The site's database is saved first and put back at the end, so the site is as it was.
set -u
SITE=$(cd "$1" && pwd); DOCROOT=$2; CV=$3
EXT_DIR=$(cd "$(dirname "$0")/../.." && pwd)
V1=$(mktemp -d)
BACKUP="$V1/before.sql.gz"
git -C "$EXT_DIR" archive master | tar -x -C "$V1"
status=1
cd "$SITE" || exit 1
echo "== saving the database"; ddev export-db --file="$BACKUP" >/dev/null 2>&1 || { echo "could not save the database"; exit 1; }
ext_path="$DOCROOT/CRM/extensions/au.com.agileware.elections"
[ "$DOCROOT" = "." ] && ext_path="CRM/extensions/au.com.agileware.elections"

echo "== removing the current version and installing 1.3.2"
ddev exec "cd /var/www/html/$DOCROOT && $CV ext:uninstall au.com.agileware.elections" >/dev/null 2>&1
rm -rf "$ext_path"; mkdir -p "$ext_path"
rsync -a "$V1/" "$ext_path/" --exclude before.sql.gz
ddev exec "cd /var/www/html/$DOCROOT && $CV flush >/dev/null 2>&1; $CV ext:install au.com.agileware.elections 2>&1 | tail -2; $CV flush >/dev/null 2>&1; $CV ext:list -L 2>&1 | grep elections"

echo "== seeding with version 1.3.2"
mkdir -p "$ext_path/tests/upgrade"
cp "$EXT_DIR/tests/upgrade/seed-v1.php" "$ext_path/tests/upgrade/seed-v1.php"
SEED_LOG=$(mktemp)
ddev exec "cd /var/www/html/$DOCROOT && $CV scr /var/www/html/$ext_path/tests/upgrade/seed-v1.php 2>&1" > "$SEED_LOG"
grep -v '^SNAPSHOT_JSON:' "$SEED_LOG" | tail -25
SNAP=$(mktemp)
grep '^SNAPSHOT_JSON:' "$SEED_LOG" | head -1 | sed 's/^SNAPSHOT_JSON://' > "$SNAP"
[ -s "$SNAP" ] || echo "no snapshot: seeding failed"

echo "== upgrading to this branch"
ddev deploy-elections >/dev/null 2>&1
mkdir -p "$ext_path/tests/upgrade/.generated"; cp "$SNAP" "$ext_path/tests/upgrade/.generated/v1-snapshot.json"
ddev exec "cd /var/www/html/$DOCROOT && $CV ext:upgrade-db 2>&1 | tail -3; $CV flush >/dev/null 2>&1"

echo "== checking"
ddev exec "cd /var/www/html/$DOCROOT && UPGRADE_DIR=/var/www/html/$ext_path/tests/upgrade $CV scr /var/www/html/$ext_path/tests/upgrade/verify-v2.php 2>&1"
status=$?

echo "== putting the database back"
ddev import-db --file="$BACKUP" >/dev/null 2>&1 && echo "restored" || echo "COULD NOT RESTORE: $BACKUP"
ddev deploy-elections >/dev/null 2>&1
rm -rf "$V1" "$SNAP" "$SEED_LOG"
exit $status
