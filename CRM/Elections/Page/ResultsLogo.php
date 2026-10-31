<?php

use GuzzleHttp\Psr7\Response;

/**
 * Sends the logo of an election's results page. CiviCRM's own file address needs a logged in person, which
 * people using a personal link are not, so the logo has its own address. It serves one picture: the logo of the
 * election in the address, and only once the results are published (administrators can always see it).
 */
class CRM_Elections_Page_ResultsLogo extends CRM_Core_Page {

  public function run() {
    $electionId = (int) CRM_Utils_Request::retrieve('eid', 'Positive');
    $election = $electionId ? findElectionById($electionId, FALSE) : NULL;
    $found = $election && !empty($election->name) && !$election->is_deleted && $election->results_logo_file_id;
    $path = $found ? CRM_Elections_Helper_ResultsDisplay::logoPath($election->results_logo_file_id) : NULL;
    $type = $path ? CRM_Core_DAO::singleValueQuery('SELECT mime_type FROM civicrm_file WHERE id = %1', [1 => [(int) $election->results_logo_file_id, 'Integer']]) : NULL;
    if (!$path || !in_array($type, array_keys(CRM_Elections_Helper_ResultsDisplay::LOGO_TYPES), TRUE)
      || (!isElectionAdmin() && !($election->isVisible && $election->isResultsOut))) {
      CRM_Utils_System::sendResponse(new Response(404, ['Content-Type' => 'text/plain'], 'Not found'));
      return;
    }
    CRM_Utils_System::sendResponse(new Response(200, [
      'Content-Type' => $type,
      'X-Content-Type-Options' => 'nosniff',
      'Content-Security-Policy' => "default-src 'none'; sandbox",
      'Cache-Control' => 'private, max-age=3600',
    ], file_get_contents($path)));
  }

}
