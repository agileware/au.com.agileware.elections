<?php

/**
 * A refusal that is written for the person who caused it ("This election is active, so ..."), as opposed to
 * a fault. CiviCRM 6.19 and later show an AJAX caller a generic "Sorry an error occurred" with an error id
 * for an exception unless it says its message is safe to show, so SearchKit actions (drag and drop, editing in
 * place) would otherwise lose the reason.
 */
class CRM_Elections_Exception extends CRM_Core_Exception {

  public function __construct($message, $error_code = 0, $errorData = [], $previous = NULL) {
    parent::__construct($message, $error_code, ((array) $errorData) + ['show_detailed_error' => TRUE], $previous);
  }

}
