#!/usr/bin/env bash
# Switch the default theme of a test site to one of its colour variations, so the election pages can be
# checked against more than the out-of-the-box colours. Run inside the DDEV web container from the
# site's root. Variant "default" puts the site back as it was.
#
#   WordPress (Twenty Twenty-Five style variations): default evening noon dusk afternoon twilight morning sunrise midnight
#   Drupal (Olivero primary colour):                 default gold green purple
#   Joomla (Cassiopeia colour scheme):               default alternative
#
#   theme-variants.sh wordpress evening      (run from httpdocs)
#   theme-variants.sh drupal gold            (run from the project root)
#   theme-variants.sh joomla alternative     (run from the Joomla root)
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
$post = WP_Theme_JSON_Resolver::get_user_data_from_wp_global_styles($theme, TRUE);
$id = $post['ID'] ?? 0;
$content = wp_slash(wp_json_encode($data));
if ($id) { wp_update_post(['ID' => $id, 'post_content' => $content]); }
else { wp_insert_post(['post_type' => 'wp_global_styles', 'post_status' => 'publish', 'post_title' => 'Custom Styles', 'post_name' => 'wp-global-styles-' . $theme->get_stylesheet(), 'post_content' => $content, 'tax_input' => ['wp_theme' => [$theme->get_stylesheet()]]]); }
echo "WordPress style variation: $variant\n";
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
