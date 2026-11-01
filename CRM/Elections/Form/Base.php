<?php

class CRM_Elections_Form_Base extends CRM_Core_Form {
  public $throwError = FALSE;

  /**
   * The session group where what a person typed is kept while the form is shown again after a refusal.
   */
  const KEPT_INPUT = 'electionsKeptInput';

  public function getTemplateFileName() {
    if ($this->throwError) {
      return 'CRM/Elections/Helper/Error.tpl';
    }
    return parent::getTemplateFileName();
  }

  /**
   * Go to a page once the form is done, unless the form is open in a popup, which closes
   * itself and lets the page behind it refresh.
   *
   * @param string $url
   */
  protected function finish($url) {
    if (CRM_Utils_Request::retrieve('snippet', 'String')) {
      return;
    }
    CRM_Utils_System::redirect($url);
  }

  /**
   * The page the person started from, which a form goes back to once it is saved: the address the form
   * carries (set from the page the person came from), otherwise the fallback.
   *
   * @param string $fallback
   *
   * @return string
   */
  protected function startedFrom($fallback) {
    $back = CRM_Elections_Helper_Utils::localUrlOrNull($this->exportValue('back'));
    return $back ?: $fallback;
  }

  /**
   * A refusal after the form was submitted: say why and show the same form again with what the person
   * typed. In a popup the message is shown over the list and the popup closes.
   *
   * @param string $message
   *   Why the change was refused, written for people.
   * @param string $title
   *   The title of the message, for example "Position not added".
   */
  protected function stayOnForm($message, $title = '') {
    CRM_Core_Session::setStatus($message, $title, 'error');
    if (CRM_Elections_Helper_Frame::isPopup()) {
      return;
    }
    $kept = [];
    foreach ($this->exportValues() as $name => $value) {
      if ($name !== 'qfKey' && $name !== 'entryURL' && $name[0] !== '_' && !is_object($value)) {
        $kept[$name] = $value;
      }
    }
    CRM_Core_Session::singleton()->set(static::class, $kept, self::KEPT_INPUT);
    $query = [];
    foreach (['reset', 'eid', 'epid', 'cid'] as $key) {
      $value = CRM_Utils_Request::retrieve($key, 'String');
      if ($value !== NULL && $value !== '') {
        $query[$key] = $value;
      }
    }
    CRM_Utils_System::redirect(CRM_Utils_System::url(CRM_Utils_System::currentPath(), http_build_query($query), FALSE, NULL, FALSE));
  }

  /**
   * Put back what the person typed before the refusal, once, when the form is shown again.
   *
   * @param array $defaults
   *
   * @return array
   */
  protected function restoreInput(array $defaults) {
    $session = CRM_Core_Session::singleton();
    $kept = $session->get(static::class, self::KEPT_INPUT);
    if (is_array($kept)) {
      $session->set(static::class, NULL, self::KEPT_INPUT);
      return array_merge($defaults, $kept);
    }
    return $defaults;
  }

  public function buildQuickForm() {
    $this->assign('siteTimeZone', CRM_Elections_Helper_TimeZone::getTimeZoneLabel());
    parent::buildQuickForm();
  }

}
