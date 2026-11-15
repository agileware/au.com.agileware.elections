#!/usr/bin/env bash
# Builds a DDEV test site for the Elections extension from scratch, next to the extension repository.
#
#   create-site.sh <wordpress|drupal|joomla> <site-name> [civicrm-version]
#
# Run it from the workspace folder that contains the extension repository (au.com.agileware.elections/):
#
#   cd <workspace>
#   au.com.agileware.elections/developer/ddev-kit/create-site.sh wordpress elections-wp
#
# civicrm-version is a full version (6.19.5), a series (6.19 = the newest 6.19.x) or "latest" (the newest
# stable release). The default is the 6.19 series. All code comes from public sources. See README.md.
#
# Optional environment variables:
#   WP_VERSION          WordPress version for "wp core download" (default: the latest release)
#   DRUPAL_CONSTRAINT   composer constraint for drupal/recommended-project (default: ^11)
#   JOOMLA_VERSION      Joomla 5 release to download from GitHub (default: 5.4.10)
#   SKIP_PLAYWRIGHT=1   do not run npm install and download Chromium for the browser tests
#
# Safe to run again: a step that finished is skipped (they are listed in <site>/.ddev/.kit-steps). It never
# prints a password; the administrator password is generated and written to <site>/.ddev/.site-credentials.

set -euo pipefail

DEFAULT_SERIES="6.19"
MIN_DDEV="1.24.0"
EXT_KEY="au.com.agileware.elections"
EXT_REPO_NAME="au.com.agileware.elections"
JOOMLA_VERSION="${JOOMLA_VERSION:-5.4.10}"
DRUPAL_CONSTRAINT="${DRUPAL_CONSTRAINT:-^11}"

say()  { printf '\n==> %s\n' "$*"; }
note() { printf '    %s\n' "$*"; }
warn() { printf 'WARNING: %s\n' "$*" >&2; }
die()  { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

usage() {
  cat <<'EOF'
Usage: create-site.sh <wordpress|drupal|joomla> <site-name> [civicrm-version]

  cms              wordpress, drupal (Drupal 11) or joomla (Joomla 5)
  site-name        letters, digits and hyphens; the site is created in ./<site-name> and served
                   at https://<site-name>.ddev.site
  civicrm-version  6.19.5 (exact), 6.19 (newest 6.19.x) or latest; default 6.19

Run it from the folder that contains the au.com.agileware.elections repository.
EOF
}

# ---------------------------------------------------------------- arguments

if [ "${1:-}" = "-h" ] || [ "${1:-}" = "--help" ]; then usage; exit 0; fi
[ $# -ge 2 ] && [ $# -le 3 ] || { usage >&2; exit 2; }

CMS="$1"
NAME="$2"
CIVI_WANT="${3:-$DEFAULT_SERIES}"

case "$CMS" in
  wordpress) TYPE="wordpress"; DOCROOT="httpdocs"; CV_CMD="cv" ;;
  drupal)    TYPE="drupal11";  DOCROOT="web";      CV_CMD="cv" ;;
  joomla)    TYPE="joomla";    DOCROOT="";         CV_CMD="jcv" ;;
  *) usage >&2; die "unknown CMS '$CMS' (use wordpress, drupal or joomla)" ;;
esac
[[ "$NAME" =~ ^[a-z][a-z0-9-]*$ ]] || die "site name '$NAME' must start with a letter and use only lower case letters, digits and hyphens"
if ! [[ "$CIVI_WANT" =~ ^[0-9]+\.[0-9]+(\.[0-9]+)?$ || "$CIVI_WANT" = "latest" || "$CIVI_WANT" = "stable" ]]; then
  die "CiviCRM version '$CIVI_WANT' must look like 6.19.5, 6.19 or latest"
fi

KIT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FILES_DIR="$KIT_DIR/files"
WORKSPACE="$(pwd -P)"
EXT_REPO="$WORKSPACE/$EXT_REPO_NAME"
SITE="$WORKSPACE/$NAME"

if [ -z "$DOCROOT" ]; then WEBROOT_C="/var/www/html"; else WEBROOT_C="/var/www/html/$DOCROOT"; fi
EXT_DIR_C="$WEBROOT_C/CRM/extensions"
EXT_C="$EXT_DIR_C/$EXT_KEY"

# ---------------------------------------------------------------- prerequisites

say "Checking prerequisites"
[ -f "$EXT_REPO/info.xml" ] || die "no extension repository at $EXT_REPO: run this from the folder that contains $EXT_REPO_NAME/"
for tool in ddev docker curl rsync openssl sed grep sort; do
  command -v "$tool" >/dev/null 2>&1 || die "'$tool' is required but was not found on the PATH"
done
docker info >/dev/null 2>&1 || die "Docker is not running (start Docker Desktop, Colima or your Docker engine)"

DDEV_VERSION="$(ddev --version | sed -n 's/.*version v\{0,1\}\([0-9][0-9.]*\).*/\1/p' | sed -n 1p)"
[ -n "$DDEV_VERSION" ] || die "could not read the DDEV version from 'ddev --version'"
if [ "$(printf '%s\n%s\n' "$MIN_DDEV" "$DDEV_VERSION" | sort -V | sed -n 1p)" != "$MIN_DDEV" ]; then
  die "DDEV $MIN_DDEV or newer is needed (found $DDEV_VERSION)"
fi
note "DDEV $DDEV_VERSION, Docker running, extension repository found"

curl -fsSL -m 30 -o /dev/null -r 0-0 https://download.civicrm.org/cv/cv.phar \
  || die "cannot reach download.civicrm.org: check the network connection"

# A new site must not clash with a DDEV project of the same name that lives elsewhere.
if [ ! -d "$SITE" ] && ddev describe "$NAME" >/dev/null 2>&1; then
  die "a DDEV project named '$NAME' already exists in another folder: choose another name"
fi
if [ -f "$SITE/.ddev/config.yaml" ]; then
  have_type="$(sed -n 's/^type: *//p' "$SITE/.ddev/config.yaml" | tr -d '"' | sed -n 1p)"
  [ "$have_type" = "$TYPE" ] || die "$SITE is a '$have_type' site, not '$TYPE'"
fi

# ---------------------------------------------------------------- versions

resolve_civicrm_version() {
  local want="$1" series found
  if [[ "$want" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    printf '%s' "$want"
  elif [ "$want" = "latest" ] || [ "$want" = "stable" ]; then
    found="$(curl -fsS -m 30 https://latest.civicrm.org/stable.php | tr -d '[:space:]')" || found=""
    [[ "$found" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || return 1
    printf '%s' "$found"
  else
    series="$want"
    found="$(curl -fsS -m 30 'https://latest.civicrm.org/stable.php?format=json' \
      | grep -o "\"version\":\"${series//./\\.}\\.[0-9][0-9]*\"" \
      | sed 's/.*:"\(.*\)"/\1/' | sort -V | sed -n '$p')" || found=""
    [ -n "$found" ] || return 1
    printf '%s' "$found"
  fi
}

CIVI_VERSION="$(resolve_civicrm_version "$CIVI_WANT")" \
  || die "could not find a CiviCRM release for '$CIVI_WANT' (see https://latest.civicrm.org/stable.php?format=json)"
note "CMS: $CMS ($TYPE), site: $NAME, CiviCRM $CIVI_VERSION"

case "$CMS" in
  joomla) JOOMLA_ZIP_URL="https://github.com/joomla/joomla-cms/releases/download/${JOOMLA_VERSION}/Joomla_${JOOMLA_VERSION}-Stable-Full_Package.zip" ;;
esac

# ---------------------------------------------------------------- helpers

CRED_FILE="$SITE/.ddev/.site-credentials"
STEPS_FILE="$SITE/.ddev/.kit-steps"

step_done() { [ -f "$STEPS_FILE" ] && grep -qx "$1" "$STEPS_FILE"; }
mark_done() { printf '%s\n' "$1" >> "$STEPS_FILE"; }
run_step() {
  local name="$1" title="$2"
  shift 2
  if step_done "$name"; then
    say "$title (finished earlier, skipped)"
  else
    say "$title"
    "$@"
    mark_done "$name"
  fi
}

# Runs a command string inside the web container (the same form the maintainers' commands use).
dexec() { ddev exec "$1"; }
# Same, for a string that holds a password: -q keeps ddev from printing the command if it fails.
dexec_secret() { ddev exec -q "$1"; }
dexec_ok() { ddev exec "$1" >/dev/null 2>&1; }

gen_password() {
  # 24 random letters and digits plus a fixed tail so every password policy (length, case, digit) is met.
  local body
  body="$(openssl rand -base64 48 | tr -dc 'A-Za-z0-9' | cut -c1-24)"
  [ "${#body}" -eq 24 ] || die "could not generate a password"
  printf '%s-Aa1' "$body"
}

cred_get() { sed -n "s/^$1=//p" "$CRED_FILE" | sed -n 1p; }

civicrm_installed() { dexec_ok "mysql -udb -pdb db -e 'SELECT 1 FROM civicrm_domain LIMIT 1'"; }

# ---------------------------------------------------------------- 1. project files

copy_kit_files() {
  mkdir -p "$SITE/.ddev/web-build" "$SITE/.ddev/commands/host" "$SITE/.ddev/homeadditions/bin"
  cp "$FILES_DIR/.ddev/web-build/Dockerfile.playwright" "$SITE/.ddev/web-build/Dockerfile.playwright"
  cp "$FILES_DIR/.ddev/config.playwright.yaml" "$SITE/.ddev/config.playwright.yaml"
  cp "$FILES_DIR/.ddev/commands/host/deploy-elections" "$SITE/.ddev/commands/host/deploy-elections"
  chmod +x "$SITE/.ddev/commands/host/deploy-elections"
  local f
  for f in cv phpunit civicrm-dev-settings wp-config-localhost .gitignore; do
    cp "$FILES_DIR/.ddev/homeadditions/bin/$f" "$SITE/.ddev/homeadditions/bin/$f"
  done
  chmod +x "$SITE/.ddev/homeadditions/bin/cv" "$SITE/.ddev/homeadditions/bin/phpunit" "$SITE/.ddev/homeadditions/bin/civicrm-dev-settings" "$SITE/.ddev/homeadditions/bin/wp-config-localhost"
  if [ "$CMS" = "joomla" ]; then
    cp "$FILES_DIR/.ddev/homeadditions/bin/jcv" "$SITE/.ddev/homeadditions/bin/jcv"
    chmod +x "$SITE/.ddev/homeadditions/bin/jcv"
    cp "$FILES_DIR/.ddev/homeadditions/joomla-prepend.php" "$SITE/.ddev/homeadditions/joomla-prepend.php"
  fi
}

download_phar() {
  local url="$1" target="$2"
  if [ -s "$target" ]; then return 0; fi
  note "downloading $url"
  curl -fsSL --retry 3 -m 300 -o "$target.part" "$url" || { rm -f "$target.part"; die "download failed: $url"; }
  mv "$target.part" "$target"
  chmod +x "$target"
}

write_cv_json() {
  # ~/.cv.json in the container: where the headless tests (and jcv) find their database.
  # root/root is DDEV's own default for the local database container; the site database user is db/db.
  local settings dsn
  case "$CMS" in
    wordpress) settings="/var/www/html/httpdocs/wp-content/uploads/civicrm/civicrm.settings.php"; dsn="mysql://root:root@db:3306/civicrm_tests_dev?new_link=true" ;;
    drupal)    settings="/var/www/html/web/sites/default/civicrm.settings.php";                   dsn="mysql://root:root@db:3306/civicrm_tests_dev?new_link=true" ;;
    joomla)    settings="/var/www/html/administrator/components/com_civicrm/civicrm.settings.php"; dsn="mysql://db:db@db:3306/db?new_link=true" ;;
  esac
  cat > "$SITE/.ddev/homeadditions/.cv.json" <<EOF
{
  "sites": {
    "$settings": {
      "TEST_DB_DSN": "$dsn"
    }
  }
}
EOF
}

ensure_gitignore() {
  # DDEV rewrites .ddev/.gitignore, so the site's own .gitignore carries these entries.
  local entry
  touch "$SITE/.gitignore"
  for entry in '.ddev/.site-credentials' '.ddev/.kit-steps' '.ddev/homeadditions/bin/*.phar'; do
    grep -qxF "$entry" "$SITE/.gitignore" || printf '%s\n' "$entry" >> "$SITE/.gitignore"
  done
}

ensure_credentials() {
  if [ ! -f "$CRED_FILE" ]; then
    local pw
    pw="$(gen_password)"
    ( umask 077
      {
        echo "# Test site only. Generated by create-site.sh. Never commit or share this file."
        echo "SITE_URL=https://$NAME.ddev.site"
        echo "ADMIN_USER=admin"
        echo "ADMIN_PASSWORD=$pw"
      } > "$CRED_FILE" )
  fi
  chmod 600 "$CRED_FILE"
}

prepare_files() {
  mkdir -p "$SITE/.ddev"
  ensure_credentials
  copy_kit_files
  download_phar "https://download.civicrm.org/cv/cv.phar" "$SITE/.ddev/homeadditions/bin/cv.phar"
  download_phar "https://phar.phpunit.de/phpunit-9.6.phar" "$SITE/.ddev/homeadditions/bin/phpunit.phar"
  write_cv_json
}

# ---------------------------------------------------------------- 2. DDEV project

configure_ddev() {
  # DDEV needs the docroot folder to exist for WordPress. Drupal's composer create-project makes web/ itself,
  # and Joomla's docroot is the project folder.
  [ "$CMS" != "wordpress" ] || mkdir -p "$SITE/$DOCROOT"
  local docroot_arg=""
  [ -z "$DOCROOT" ] || docroot_arg="--docroot=$DOCROOT"
  # $docroot_arg is deliberately unquoted: it is either empty or one option without spaces.
  # shellcheck disable=SC2086
  ( cd "$SITE" && ddev config --project-type="$TYPE" --project-name="$NAME" $docroot_arg \
      --php-version=8.3 --database=mariadb:11.8 --nodejs-version=24 --webserver-type=nginx-fpm --auto )
}

start_ddev() {
  # The first start builds the web image with the Chromium libraries (apt-get), which takes a few minutes.
  ( cd "$SITE" && ddev start )
}

# ---------------------------------------------------------------- 3. the CMS

install_wordpress() {
  local pw
  pw="$(cred_get ADMIN_PASSWORD)"
  dexec "cd '$WEBROOT_C' && { [ -f wp-load.php ] || wp core download ${WP_VERSION:+--version=$WP_VERSION}; }"
  # DDEV writes wp-config.php on start; create one only if it is missing.
  dexec "cd '$WEBROOT_C' && { [ -f wp-config.php ] || wp config create --dbname=db --dbuser=db --dbpass=db --dbhost=db --skip-check; }"
  dexec_secret "cd '$WEBROOT_C' && { wp core is-installed || wp core install --url='$SITE_URL' --title='Elections test site' --admin_user=admin --admin_password='$pw' --admin_email=admin@example.test --skip-email; }"
  dexec "cd '$WEBROOT_C' && wp rewrite structure '/%postname%/' --hard"
}

install_drupal() {
  local pw
  pw="$(cred_get ADMIN_PASSWORD)"
  if [ ! -f "$SITE/composer.json" ]; then
    ( cd "$SITE" && ddev composer create-project "drupal/recommended-project:$DRUPAL_CONSTRAINT" --no-interaction )
  fi
  # Settings CiviCRM's composer plugins need (no prompts): patching on, compile everything, assets in web/libraries.
  local plugin
  for plugin in civicrm/civicrm-asset-plugin civicrm/composer-compile-plugin civicrm/composer-downloads-plugin cweagans/composer-patches; do
    ( cd "$SITE" && ddev composer config --no-plugins "allow-plugins.$plugin" true )
  done
  ( cd "$SITE" && ddev composer config --json extra.enable-patching true \
      && ddev composer config extra.compile-mode all \
      && ddev composer config extra.civicrm-asset.path web/libraries/civicrm )
  # CiviCRM's code goes in before Drush, as the CiviCRM installation guide asks.
  if [ ! -d "$SITE/web/modules/contrib/civicrm" ]; then
    ( cd "$SITE" && ddev composer require "civicrm/civicrm-core:$CIVI_VERSION" "civicrm/civicrm-packages:$CIVI_VERSION" "civicrm/civicrm-drupal-8:$CIVI_VERSION" --no-interaction )
  fi
  if [ ! -x "$SITE/vendor/bin/drush" ]; then
    ( cd "$SITE" && ddev composer require drush/drush --no-interaction )
  fi
  if ! dexec_ok "cd /var/www/html && vendor/bin/drush status --field=bootstrap | grep -q Successful"; then
    dexec_secret "cd /var/www/html && vendor/bin/drush site:install standard --site-name='Elections test site' --account-name=admin --account-pass='$pw' --account-mail=admin@example.test -y"
  fi
}

install_joomla() {
  local pw
  pw="$(cred_get ADMIN_PASSWORD)"
  if ! dexec_ok "[ -f /var/www/html/libraries/src/Version.php ]"; then
    note "downloading Joomla $JOOMLA_VERSION"
    dexec "cd /var/www/html && curl -fsSL --retry 3 -o /tmp/joomla.zip '$JOOMLA_ZIP_URL' && unzip -q -o /tmp/joomla.zip && rm -f /tmp/joomla.zip"
  fi
  if ! dexec_ok "[ -s /var/www/html/configuration.php ]"; then
    dexec_secret "cd /var/www/html && php installation/joomla.php install --site-name='Elections test site' --admin-user='Administrator' --admin-username=admin --admin-password='$pw' --admin-email=admin@example.test --db-type=mysql --db-encryption=0 --db-host=db --db-user=db --db-pass=db --db-name=db --db-prefix=jos_ --public-folder=''"
  fi
  # The command line installer leaves the installation folder; the finished site does not have it.
  dexec "rm -rf /var/www/html/installation"
}

# ---------------------------------------------------------------- 4. CiviCRM

install_civicrm_wordpress() {
  if ! dexec_ok "[ -f '$WEBROOT_C/wp-content/plugins/civicrm/civicrm.php' ]"; then
    dexec "cd '$WEBROOT_C' && wp plugin install 'https://download.civicrm.org/civicrm-$CIVI_VERSION-wordpress.zip'"
  fi
  if ! civicrm_installed; then
    dexec "cd '$WEBROOT_C' && cv core:install --url='$SITE_URL'"
  fi
  dexec "cd '$WEBROOT_C' && { wp plugin is-active civicrm || wp plugin activate civicrm; }"
  # The CiviCRM base page: election links and the browser tests rely on it (docs/setup.md).
  dexec "cd '$WEBROOT_C' && { [ -n \"\$(wp post list --post_type=page --name=civicrm --field=ID | sed -n 1p)\" ] || wp post create --post_type=page --post_title=CiviCRM --post_name=civicrm --post_status=publish; }"
  dexec "cd '$WEBROOT_C' && cv api4 Setting.set '{\"values\": {\"wpBasePage\": \"civicrm\"}}' && wp rewrite flush --hard"
}

install_civicrm_drupal() {
  # Drupal's settings folder is read only once the site is installed; cv writes civicrm.settings.php into it.
  dexec "chmod u+w /var/www/html/web/sites/default /var/www/html/web/sites/default/settings.php"
  if ! civicrm_installed; then
    dexec "cd '$WEBROOT_C' && cv core:install --url='$SITE_URL'"
  fi
  dexec "cd /var/www/html && vendor/bin/drush en civicrm -y && vendor/bin/drush cr"
}

install_civicrm_joomla() {
  if ! dexec_ok "[ -f /var/www/html/administrator/components/com_civicrm/civicrm.settings.php ]"; then
    local url="https://download.civicrm.org/civicrm-$CIVI_VERSION-joomla.zip"
    note "installing the CiviCRM package through Joomla's command line extension installer"
    dexec "cd /var/www/html && curl -fsSL --retry 3 -o tmp/civicrm-joomla.zip '$url' && { php -d memory_limit=-1 cli/joomla.php extension:install --path=/var/www/html/tmp/civicrm-joomla.zip; rc=\$?; rm -f tmp/civicrm-joomla.zip; exit \$rc; }" \
      || joomla_install_help
    dexec_ok "[ -f /var/www/html/administrator/components/com_civicrm/civicrm.settings.php ]" || joomla_install_help
  fi
}

joomla_install_help() {
  cat >&2 <<EOF

ERROR: CiviCRM did not install through Joomla's command line. (Not verified from scratch: see the kit README.)
Install the package by hand, then run this script again; it carries on from here:
  1. Download https://download.civicrm.org/civicrm-$CIVI_VERSION-joomla.zip
  2. Open https://$NAME.ddev.site/administrator/ (login: see $CRED_FILE)
  3. System > Install > Extensions > Upload Package File, choose the zip (use the -joomla-alt.zip file if Joomla reports missing zip support).
EOF
  exit 1
}

install_civicrm() {
  case "$CMS" in
    wordpress) install_civicrm_wordpress ;;
    drupal)    install_civicrm_drupal ;;
    joomla)    install_civicrm_joomla ;;
  esac
}

# ---------------------------------------------------------------- 5. CiviCRM settings, mail, test database

configure_civicrm_settings() {
  dexec "mkdir -p '$EXT_DIR_C'"
  case "$CMS" in
    wordpress)
      dexec "wp-config-localhost '$WEBROOT_C/wp-config.php'"
      dexec "civicrm-dev-settings '$WEBROOT_C/wp-content/uploads/civicrm/civicrm.settings.php' '$SITE_URL' '$EXT_DIR_C'"
      ;;
    drupal)
      dexec "civicrm-dev-settings '$WEBROOT_C/sites/default/civicrm.settings.php' '$SITE_URL' '$EXT_DIR_C'"
      ;;
    joomla)
      dexec "civicrm-dev-settings '/var/www/html/administrator/components/com_civicrm/civicrm.settings.php' '$SITE_URL' '$EXT_DIR_C' '/administrator/'"
      if dexec_ok "[ -f /var/www/html/components/com_civicrm/civicrm.settings.php ]"; then
        dexec "civicrm-dev-settings '/var/www/html/components/com_civicrm/civicrm.settings.php' '$SITE_URL' '$EXT_DIR_C' '/'"
      fi
      ;;
  esac
}

configure_mail() {
  # DDEV's Mailpit catches everything the site sends: nothing is delivered. Read it with: ddev launch -m
  dexec "cd '$WEBROOT_C' && $CV_CMD api4 Setting.set '{\"values\": {\"mailing_backend\": {\"outBound_option\": 0, \"smtpServer\": \"localhost\", \"smtpPort\": \"1025\", \"smtpAuth\": 0, \"sendmail_path\": \"\", \"sendmail_args\": \"\"}}}'"
}

create_test_database() {
  # PHPUnit's headless tests use their own copy of the site database (named in ~/.cv.json).
  dexec "mysql -uroot -proot -e 'CREATE DATABASE IF NOT EXISTS civicrm_tests_dev'"
  dexec "mysqldump -uroot -proot db | mysql -uroot -proot civicrm_tests_dev"
}

# ---------------------------------------------------------------- 6. deploy, browser tests

install_playwright() {
  local dir="$EXT_C/tests/playwright"
  if ! dexec_ok "[ -f '$dir/package.json' ]"; then
    warn "no tests/playwright folder in the deployed extension; skipping the browser test install"
    return 0
  fi
  # A transient download failure (for example a DNS error for cdn.playwright.dev) is not fatal: run it again.
  if ! dexec "cd '$dir' && npm install && npx playwright install chromium"; then
    warn "npm install or the Chromium download failed. Run it again later with:"
    warn "  ddev exec 'cd $dir && npm install && npx playwright install chromium'"
    return 1
  fi
}

# ================================================================ run

[ -d "$SITE" ] && say "Continuing with the existing site folder $SITE" || say "Creating $SITE"
mkdir -p "$SITE"
cd "$SITE"

say "Writing the DDEV project files and downloading cv and phpunit"
prepare_files
run_step config "Configuring the DDEV project ($TYPE, PHP 8.3, MariaDB 11.8)" configure_ddev

say "Starting the site (the first start builds the web image and takes a few minutes)"
start_ddev

SITE_URL="$(ddev exec 'echo "$DDEV_PRIMARY_URL"' | tr -d '\r\n')"
SITE_URL="${SITE_URL%/}"
[ -n "$SITE_URL" ] || SITE_URL="https://$NAME.ddev.site"
note "site address: $SITE_URL"

case "$CMS" in
  wordpress) run_step cms "Installing WordPress" install_wordpress ;;
  drupal)    run_step cms "Installing Drupal 11, CiviCRM's code and Drush with composer" install_drupal ;;
  joomla)    run_step cms "Installing Joomla $JOOMLA_VERSION" install_joomla ;;
esac

ensure_gitignore

run_step civicrm "Installing CiviCRM $CIVI_VERSION" install_civicrm
run_step settings "Setting CiviCRM's custom extensions directory (CRM/extensions) and site address" configure_civicrm_settings
run_step mail "Sending CiviCRM's email to Mailpit" configure_mail
if [ "$CMS" != "joomla" ]; then
  run_step testdb "Creating the PHPUnit test database civicrm_tests_dev" create_test_database
fi

say "Deploying the extension (ddev deploy-elections)"
ddev deploy-elections
# Capture the list first: "grep -q" ends the pipe at the first match and, under pipefail, that fails the test.
EXT_LIST="$(dexec "cd '$WEBROOT_C' && $CV_CMD ext:list -L" 2>/dev/null || true)"
if printf '%s\n' "$EXT_LIST" | grep -q "$EXT_KEY"; then
  note "$EXT_KEY is installed from $EXT_C"
else
  warn "$EXT_KEY was not found in 'ext:list'. Check: ddev exec 'cd $WEBROOT_C && $CV_CMD ext:list -L'"
fi

if [ "${SKIP_PLAYWRIGHT:-}" = "1" ]; then
  say "Skipping the browser test install (SKIP_PLAYWRIGHT=1)"
else
  run_step playwright "Installing the browser test packages and Chromium (npm install)" install_playwright
fi

# ---------------------------------------------------------------- finish

say "Site ready: $SITE_URL"
note "Administrator: user 'admin'. The password is in $CRED_FILE (mode 600, git-ignored); it is not printed."
note "Mail: ddev launch -m   (Mailpit; nothing the site sends is delivered)"
note "Redeploy after changing the extension: cd $SITE && ddev deploy-elections"
echo
echo "Next, create the test accounts and data the browser tests need (from $SITE):"
case "$CMS" in
  wordpress)
    echo "  ddev exec 'cd $EXT_C/tests/playwright && bash fixtures/setup-environment.sh'"
    echo "  Run the tests as the administrator you just created (the password is read inside the container from the"
    echo "  credentials file, so it never appears on a command line):"
    echo "    ddev exec 'cd $EXT_C/tests/playwright && set -a && . /var/www/html/.ddev/.site-credentials && set +a && WP_ADMIN_USER=\$ADMIN_USER WP_ADMIN_PASS=\$ADMIN_PASSWORD BASE_URL=http://localhost npx playwright test'"
    ;;
  drupal)
    echo "  ddev exec 'cd /var/www/html && bash $EXT_C/tests/playwright/fixtures/setup-accounts-drupal.sh'"
    echo "  ddev exec 'cd $EXT_C/tests/playwright && CMS=drupal BASE_URL=http://localhost npx playwright test'"
    ;;
  joomla)
    echo "  ddev exec 'cd /var/www/html && bash $EXT_C/tests/playwright/fixtures/setup-accounts-joomla.sh'"
    echo "  ddev exec 'cd $EXT_C/tests/playwright && CMS=joomla CIVI_CV=jcv BASE_URL=http://localhost npx playwright test'"
    ;;
esac
echo "The suite seeds the council election itself when it starts. See developer/ddev-kit/README.md."
echo
echo "When you are done with the site: cd $SITE && ddev stop   (restart later with: ddev start $NAME)"
