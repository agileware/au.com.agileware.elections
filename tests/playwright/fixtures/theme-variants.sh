#!/usr/bin/env bash
# Switch the default theme of a test site to one of its colour variations, so the election pages can be
# checked against more than the out-of-the-box colours. Run inside the DDEV web container from the
# site's root. Variant "default" puts the site back as it was.
#
#   WordPress (Twenty Twenty-Five style variations): default evening noon dusk afternoon twilight morning sunrise midnight
#   Drupal (Olivero primary colour):                 default gold green purple
#   Joomla (Cassiopeia colour scheme):               default alternative
#   CiviCRM (Riverlea dark mode, any CMS):           light dark inherit   (inherit follows the browser)
#
#   theme-variants.sh wordpress evening      (run from httpdocs)
#   theme-variants.sh drupal gold            (run from the project root)
#   theme-variants.sh joomla alternative     (run from the Joomla root)
#   theme-variants.sh civicrm dark           (from the site root; CIVI_CV=jcv on the Joomla site)
set -euo pipefail
CMS=${1:?cms}
VARIANT=${2:?variant}

case "$CMS" in
  wordpress)
    TMP=$(mktemp /tmp/variant-XXXXXX.php)
    cat > "$TMP" <<'PHP'
<?php
$variant = getenv('VARIANT');
$theme = wp_get_theme();
$data = ['version' => 3, 'isGlobalStylesUserThemeJSON' => true];
if ($variant !== 'default') {
  $files = glob($theme->get_stylesheet_directory() . '/styles/*' . $variant . '.json');
  if (!$files) { fwrite(STDERR, "No variation $variant\n"); exit(1); }
  $json = json_decode(file_get_contents($files[0]), TRUE);
  foreach (['settings', 'styles'] as $key) { if (isset($json[$key])) { $data[$key] = $json[$key]; } }
}
// One styles record for the theme, tagged with the theme (wp_theme), which is how WordPress finds it. Before 2.2
// this script added a record each run with the tag left off (tax_input is dropped when no user is logged in, as in
// wp-cli), so WordPress never read it and the variation never showed. Records for this theme, and untagged ones
// left by that script, are removed first: this is for test sites only.
$stylesheet = $theme->get_stylesheet();
foreach (get_posts(['post_type' => 'wp_global_styles', 'post_status' => 'any', 'numberposts' => -1]) as $old) {
  $terms = wp_get_object_terms($old->ID, 'wp_theme', ['fields' => 'names']);
  if (!$terms || in_array($stylesheet, $terms, TRUE)) {
    wp_delete_post($old->ID, TRUE);
  }
}
$id = wp_insert_post(['post_type' => 'wp_global_styles', 'post_status' => 'publish', 'post_title' => 'Custom Styles', 'post_name' => 'wp-global-styles-' . urlencode($stylesheet), 'post_content' => wp_slash(wp_json_encode($data))], TRUE);
if (is_wp_error($id)) { fwrite(STDERR, $id->get_error_message() . "\n"); exit(1); }
wp_set_object_terms($id, $stylesheet, 'wp_theme');
WP_Theme_JSON_Resolver::clean_cached_data();
wp_clean_theme_json_cache();
$base = wp_get_global_settings(['color', 'palette', 'theme']);
$base = array_column(is_array($base) ? $base : [], 'color', 'slug')['base'] ?? '?';
echo "WordPress style variation: $variant (base colour $base)\n";
PHP
    VARIANT=$VARIANT wp eval-file "$TMP"
    rm -f "$TMP"
    ;;
  drupal)
    case "$VARIANT" in
      default) COLOR='#1b9ae4' ;;
      gold) COLOR='#e6a800' ;;
      green) COLOR='#2e7d32' ;;
      purple) COLOR='#4a148c' ;;
      *) echo "Unknown Drupal variant $VARIANT"; exit 1 ;;
    esac
    vendor/bin/drush config:set olivero.settings base_primary_color "$COLOR" -y >/dev/null
    vendor/bin/drush cr >/dev/null
    echo "Drupal Olivero primary colour: $COLOR ($VARIANT)"
    ;;
  civicrm)
    case "$VARIANT" in
      light|dark|inherit) ;;
      *) echo "Unknown CiviCRM dark mode $VARIANT (light, dark or inherit)"; exit 1 ;;
    esac
    CV=${CIVI_CV:-cv}
    $CV api4 Setting.set +v riverlea_dark_mode_frontend="$VARIANT" +v riverlea_dark_mode_backend="$VARIANT" >/dev/null
    $CV flush >/dev/null
    echo "CiviCRM (Riverlea) dark mode: $VARIANT"
    ;;
  joomla)
    case "$VARIANT" in
      default) SCHEME=colors_standard ;;
      alternative) SCHEME=colors_alternative ;;
      *) echo "Unknown Joomla variant $VARIANT"; exit 1 ;;
    esac
    mysql -udb -pdb db -e "UPDATE jos_template_styles SET params = JSON_SET(params, '$.colorName', '$SCHEME') WHERE template = 'cassiopeia'"
    echo "Joomla Cassiopeia colour scheme: $SCHEME"
    ;;
  *) echo "Unknown CMS $CMS"; exit 1 ;;
esac
