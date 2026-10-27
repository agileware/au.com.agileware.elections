<?php

class CRM_Elections_Form_Base extends CRM_Core_Form {
  public $throwError = FALSE;

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

  public function buildQuickForm() {
    $this->assign('siteTimeZone', CRM_Elections_Helper_TimeZone::getTimeZoneLabel());
    parent::buildQuickForm();
  }

}
