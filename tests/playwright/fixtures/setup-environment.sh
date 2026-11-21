#!/usr/bin/env bash
# Creates the WordPress roles, users and pages and the CiviCRM council election the
# Playwright suite needs.
#
# Assumes WordPress + CiviCRM + this extension are installed and enabled. Runs with
# `wp`/`cv`/`php` on the PATH (true inside the CI WordPress container and under
# `ddev exec`); set CIVI_EXEC_PREFIX to wrap each call when running from outside.
# Safe to re-run.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
EXEC_PREFIX=${CIVI_EXEC_PREFIX:-}

run() {
  $EXEC_PREFIX "$@"
}

json_get() {
  # $1 = json file, $2 = top-level key, $3 = nested key
  run php -r 'echo json_decode(file_get_contents($argv[1]), true)[$argv[2]][$argv[3]];' "$1" "$2" "$3"
}

USERS_JSON="$SCRIPT_DIR/test-users.json"
# Passwords are made here, at setup, and kept in a git-ignored file (never committed).
PASSWORDS_JSON="$SCRIPT_DIR/.generated/test-passwords.json"
run php "$SCRIPT_DIR/generate-passwords.php" "$USERS_JSON" "$PASSWORDS_JSON"
password_get() {
  run php -r 'echo json_decode(file_get_contents($argv[1]), true)[$argv[2]];' "$PASSWORDS_JSON" "$1"
}

echo "Creating WordPress roles..."
# CiviCRM's WordPress permission check lowercases the permission and replaces runs of
# non-alphanumerics with an underscore, so the capability names are the munged forms.
#
# manager: runs the elections with exactly the permissions documented in docs/roles_and_permissions.md
# for an organiser.
run wp role create elections_manager "Elections Manager (test)" --clone=subscriber >/dev/null 2>&1 || true
run wp cap add elections_manager access_civicrm administer_elections view_elections access_ajax_api view_all_contacts
# limited: an organiser without View All Contacts, who cannot see any CiviCRM groups. The election form
# tells them why.
run wp role create elections_limited "Elections Organiser without contacts (test)" --clone=subscriber >/dev/null 2>&1 || true
run wp cap add elections_limited access_civicrm administer_elections view_elections access_ajax_api
# member: takes part in an election while logged in.
# candidates: manages candidates only (Election Candidates, Add Candidate, Edit Statement), not elections.
run wp role create elections_candidates "Elections Candidates Manager (test)" --clone=subscriber >/dev/null 2>&1 || true
run wp cap add elections_candidates access_civicrm manage_election_candidates view_elections access_ajax_api view_all_contacts
run wp role create elections_member "Elections Voter (test)" --clone=subscriber >/dev/null 2>&1 || true
run wp cap add elections_member view_elections access_ajax_api access_uploaded_files profile_view profile_edit

echo "Creating WordPress users..."
for key in manager limited candidates member; do
  USERNAME=$(json_get "$USERS_JSON" "$key" username)
  PASSWORD=$(password_get "$key")
  EMAIL=$(json_get "$USERS_JSON" "$key" email)
  ROLE=$(json_get "$USERS_JSON" "$key" role)
  if run wp user get "$USERNAME" >/dev/null 2>&1; then
    run wp user update "$USERNAME" --user_pass="$PASSWORD" --role="$ROLE"
  else
    run wp user create "$USERNAME" "$EMAIL" --role="$ROLE" --user_pass="$PASSWORD"
  fi
done

echo "Creating the election pages (as documented in docs/setup.md)..."
INFO_SLUG=$(json_get "$USERS_JSON" pages infoSlug)
LISTING_SLUG=$(json_get "$USERS_JSON" pages listingSlug)
# Each short-code is in a Custom HTML block, as docs/setup.md tells site owners to insert it (and as the user guide's
# pictures show). Pages made before 2.2 held the short-code as classic content: they are put in the block.
html_block() { printf '<!-- wp:html -->\n%s\n<!-- /wp:html -->' "$1"; }
INFO_ID=$(run wp post list --post_type=page --name="$INFO_SLUG" --field=ID | head -n 1)
if [ -z "$INFO_ID" ]; then
  INFO_ID=$(run wp post create --post_type=page --post_title="Election" --post_name="$INFO_SLUG" --post_status=publish --porcelain \
    --post_content="$(html_block '[civicrm component="electioninfo" hijack="0"]')")
fi
LISTING_ID=$(run wp post list --post_type=page --name="$LISTING_SLUG" --field=ID | head -n 1)
if [ -z "$LISTING_ID" ]; then
  LISTING_ID=$(run wp post create --post_type=page --post_title="Current Elections" --post_name="$LISTING_SLUG" --post_status=publish --porcelain \
    --post_content="$(html_block "[civicrm component=\"elections\" hijack=\"0\" action=\"visible\" viewpageid=\"$INFO_ID\"]")")
fi
for PAGE_ID in "$INFO_ID" "$LISTING_ID"; do
  CONTENT=$(run wp post get "$PAGE_ID" --field=post_content)
  case "$CONTENT" in
    *'<!-- wp:html -->'*) ;;
    *) run wp post update "$PAGE_ID" --post_content="$(html_block "$CONTENT")" >/dev/null ;;
  esac
done

echo "Redirecting CiviCRM's outgoing email to the database (no mail server needed)..."
# Nominations and write-ins send email while the tests run. Redirecting to the database (option 5) keeps
# the tests independent of whether the site has a mail server; nothing is delivered.
run cv api4 Setting.set '{"values":{"mailing_backend":{"outBound_option":5}}}' >/dev/null

echo "Seeding the council election..."
run cv scr "$SCRIPT_DIR/seed-council.php"
