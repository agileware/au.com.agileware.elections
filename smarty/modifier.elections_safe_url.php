<?php

/**
 * Smarty modifier: a web address that is safe to use as a link, or an empty string.
 *
 * Only http and https addresses pass, so a `javascript:` or `data:` address stored in a field is never
 * turned into a link.
 *
 * @param string|null $url
 *
 * @return string
 */
function smarty_modifier_elections_safe_url($url) {
  return CRM_Elections_Helper_Utils::safeHttpUrl($url);
}
