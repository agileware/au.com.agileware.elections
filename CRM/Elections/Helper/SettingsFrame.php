<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * The frame of the Elections Settings page (plan/20-ui-standards.md, sections 3.5 and 3.7). The page is CiviCRM's
 * own generic settings form, so it keeps CiviCRM's own breadcrumb (Administer, System Settings) and gets the
 * rest here: a line saying what the settings are for, and the footer of every edit form: Submit, Back, Back to
 * Manage Elections.
 *
 * Nothing here is required for the settings to work. Whatever CiviCRM does not allow is left as CiviCRM draws it
 * (its own Save and Cancel).
 */
class CRM_Elections_Helper_SettingsFrame {

  const PATH = 'civicrm/admin/setting/elections';

  /**
   * Called from hook_civicrm_buildForm for every form.
   *
   * @param string $formName
   * @param \CRM_Core_Form $form
   */
  public static function buildForm($formName, &$form): void {
    if (!($form instanceof CRM_Admin_Form_Generic) || CRM_Utils_System::currentPath() !== self::PATH) {
      return;
    }
    CRM_Core_Region::instance('crm-setting-form-elections-top')->add([
      'markup' => '<p class="description election-settings-description">' . htmlspecialchars(E::ts('These settings apply to every election.'), ENT_QUOTES, 'UTF-8') . '</p>',
    ]);
    try {
      self::replaceButtons($form);
    }
    catch (\Throwable $e) {
      // CiviCRM's own Save and Cancel stay.
      Civi::log()->warning('Elections Settings footer not changed: ' . $e->getMessage());
    }
  }

  /**
   * Submit in place of Save, then Back and Back to Manage Elections in place of Cancel.
   *
   * @param \CRM_Core_Form $form
   */
  private static function replaceButtons($form): void {
    $group = $form->getElement('buttons');
    $elements = [];
    foreach ($group->getElements() as $element) {
      if (strpos($element->getName(), '_cancel') !== FALSE) {
        // Back takes its place.
        continue;
      }
      if (strpos($element->getName(), '_next') !== FALSE && method_exists($element, 'setContent')) {
        $element->setContent(CRM_Core_Page::crmIcon('fa-check') . ' ' . E::ts('Submit'));
      }
      $elements[] = $element;
    }
    $back = CRM_Elections_Helper_Frame::backUrl(CRM_Utils_System::url('civicrm/admin', 'reset=1', FALSE, NULL, FALSE));
    $links = '<a class="button election-action-quiet election-back" href="' . htmlspecialchars($back, ENT_QUOTES, 'UTF-8') . '"><span><i class="crm-i fa-arrow-left" aria-hidden="true"></i> ' . htmlspecialchars(E::ts('Back'), ENT_QUOTES, 'UTF-8') . '</span></a> '
      . '<a class="button election-action-quiet election-back-manage" href="' . htmlspecialchars(CRM_Elections_Helper_Frame::managerUrl(), ENT_QUOTES, 'UTF-8') . '"><span><i class="crm-i fa-arrow-left" aria-hidden="true"></i> ' . htmlspecialchars(E::ts('Back to Manage Elections'), ENT_QUOTES, 'UTF-8') . '</span></a>';
    // The footer template prints the elements of the buttons group whose names start with _qf_.
    $elements[] = $form->createElement('static', '_qf_' . $form->getAttribute('name') . '_back', NULL, $links);
    $group->setElements($elements);
  }

}
