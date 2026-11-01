<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * The frame every full back end page shares (plan/20-ui-standards.md, section 3): the breadcrumb above the
 * heading, a Back button that returns to the page the person came from, and Back to Manage Elections.
 */
class CRM_Elections_Helper_Frame {

  const MANAGE_PATH = 'civicrm/elections/manage';

  const OVERVIEW_PATH = 'civicrm/elections/overview';

  /**
   * The SearchKit screens, by route, with the name each has in a breadcrumb and the heading above it.
   */
  public static function listScreens(): array {
    return [
      'civicrm/elections/position-list' => E::ts('Positions'),
      'civicrm/elections/position-list-active' => E::ts('Positions'),
      'civicrm/elections/candidate-list' => E::ts('Candidates'),
      'civicrm/elections/result-list' => E::ts('Results'),
      'civicrm/elections/audit-log' => E::ts('Audit Log'),
    ];
  }

  public static function managerUrl(): string {
    return CRM_Utils_System::url(self::MANAGE_PATH, 'reset=1', FALSE, NULL, FALSE);
  }

  public static function overviewUrl(int $electionId): string {
    return CRM_Utils_System::url(self::OVERVIEW_PATH, 'reset=1&eid=' . $electionId, FALSE, NULL, FALSE);
  }

  /**
   * Where Back goes: an address given on the request (a form that is shown again keeps it), otherwise the page
   * the person came from, otherwise the fallback. Only addresses on this site are used, and never the page itself.
   *
   * @param string $fallback
   *   An address on this site, used when there is nothing better.
   *
   * @return string
   */
  public static function backUrl(string $fallback): string {
    $candidates = [
      CRM_Utils_Request::retrieve('back', 'String'),
      $_SERVER['HTTP_REFERER'] ?? '',
    ];
    $here = self::pathAndQuery((string) ($_SERVER['REQUEST_URI'] ?? ''));
    foreach ($candidates as $candidate) {
      $url = CRM_Elections_Helper_Utils::localUrlOrNull($candidate);
      if ($url !== NULL && self::pathAndQuery($url) !== $here) {
        return $url;
      }
    }
    return $fallback;
  }

  private static function pathAndQuery(string $url): string {
    $parts = parse_url($url);
    return ($parts['path'] ?? '') . '?' . ($parts['query'] ?? '');
  }

  public static function electionName(int $electionId): ?string {
    if ($electionId <= 0) {
      return NULL;
    }
    $name = CRM_Core_DAO::getFieldValue('CRM_Elections_DAO_Election', $electionId, 'name');
    // CiviCRM's API stores < and > as &lt; and &gt;: turn them back so the name is escaped once, when it is shown.
    return $name === NULL || $name === FALSE || $name === '' ? NULL : CRM_Elections_Helper_ResultsDisplay::decode($name);
  }

  /**
   * The trail of a page, as a list of [label, address] pairs, the last one being the page itself (no address).
   *
   * @param int $electionId
   *   The election, or 0 for pages that are not about one election.
   * @param array $between
   *   Pages between the election and this one, each [label, address] (for example the Positions list).
   * @param string $page
   *   The name of this page.
   *
   * @return array
   */
  public static function trail(int $electionId, array $between, string $page): array {
    $trail = [[E::ts('Manage Elections'), self::managerUrl()]];
    $name = self::electionName($electionId);
    if ($name !== NULL) {
      $trail[] = [$name, self::overviewUrl($electionId)];
    }
    foreach ($between as $item) {
      $trail[] = $item;
    }
    $trail[] = [$page, NULL];
    return $trail;
  }

  /**
   * The trail of the election overview: the election's own name is the page.
   */
  public static function overviewTrail(int $electionId): array {
    $trail = [[E::ts('Manage Elections'), self::managerUrl()]];
    $trail[] = [self::electionName($electionId) ?? E::ts('Election Overview'), NULL];
    return $trail;
  }

  /**
   * The breadcrumb as CiviCRM draws it above the page heading: one piece of markup for each step, every
   * name escaped, the page itself marked as the current one and not a link.
   */
  public static function breadcrumbMarkup(array $trail): array {
    $markup = [];
    foreach ($trail as [$label, $url]) {
      $label = htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8');
      $markup[] = $url === NULL
        ? '<span aria-current="page">' . $label . '</span>'
        : '<a href="' . htmlspecialchars((string) $url, ENT_QUOTES, 'UTF-8') . '">' . $label . '</a>';
    }
    return $markup;
  }

  /**
   * Replace CiviCRM's own breadcrumb with the trail of this page.
   */
  public static function setBreadcrumb(array $trail): void {
    CRM_Core_Smarty::singleton()->assign('breadcrumb', self::breadcrumbMarkup($trail));
  }

  /**
   * Give a back end Smarty page or form its frame: the breadcrumb above the heading and the addresses for
   * Back and Back to Manage Elections.
   *
   * @param \CRM_Core_Page|\CRM_Core_Form $pageOrForm
   * @param array $trail
   * @param string $fallbackBack
   *   Where Back goes when there is no page to go back to (usually the page above in the trail).
   */
  public static function assign($pageOrForm, array $trail, string $fallbackBack): void {
    self::setBreadcrumb($trail);
    $pageOrForm->assign('backUrl', self::backUrl($fallbackBack));
    $pageOrForm->assign('managerUrl', self::managerUrl());
  }

  /**
   * The same, for a form: also keeps the address Back goes to in the form, so that a form shown again
   * after a mistake still goes back to the page the person came from.
   *
   * @param \CRM_Core_Form $form
   */
  public static function assignToForm($form, array $trail, string $fallbackBack): void {
    self::assign($form, $trail, $fallbackBack);
    $form->addElement('hidden', 'back', $form->get_template_vars('backUrl'));
  }

  /**
   * The breadcrumb of a SearchKit screen (an Afform page): the trail of the page, built from the election in
   * the request. Called for every page that runs; anything that is not one of ours is left alone.
   */
  public static function applyToListPage(): void {
    $path = CRM_Utils_System::currentPath();
    $screens = self::listScreens();
    if ($path === self::MANAGE_PATH) {
      self::setBreadcrumb([[E::ts('Manage Elections'), NULL]]);
      return;
    }
    if (!isset($screens[$path])) {
      return;
    }
    $electionId = (int) CRM_Utils_Request::retrieve('eid', 'Positive');
    self::setBreadcrumb(self::trail($electionId, [], $screens[$path]));
    // These screens read the election from the address after the #, which the server never sees, so a page opened
    // without it in the query gets its breadcrumb completed in the browser.
    if ($electionId <= 0) {
      Civi::resources()
        ->addScriptFile(E::LONG_NAME, 'js/election-breadcrumb.js', 110, 'page-footer')
        ->addVars('electionsFrame', [
          'manageUrl' => self::managerUrl(),
          'manageLabel' => E::ts('Manage Elections'),
          'overviewUrl' => CRM_Utils_System::url(self::OVERVIEW_PATH, 'reset=1&eid=', FALSE, NULL, FALSE),
          'pageLabel' => $screens[$path],
        ]);
    }
  }

}
