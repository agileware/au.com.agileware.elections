<?php

class CRM_Elections_Form_Base extends CRM_Core_Form {
  public $thorwError = FALSE;

  public function getTemplateFileName() {
    if ($this->thorwError) {
      return 'CRM/Elections/Helper/Error.tpl';
    }
    return parent::getTemplateFileName();
  }

  public function buildQuickForm() {
    $this->assign('siteTimeZone', CRM_Elections_Helper_TimeZone::getTimeZoneLabel());
    parent::buildQuickForm();
  }

}
