/**
 * Which CMS the site under test runs on, and how it addresses CiviCRM pages.
 *
 *   CMS=wordpress (default)  /?page=CiviCRM&q=civicrm/elections/view&eid=1
 *   CMS=drupal               /civicrm/elections/view?eid=1
 *   CMS=joomla               /index.php?option=com_civicrm&task=civicrm/elections/view&eid=1
 *
 * CIVI_CV names the `cv` command for the site (the Joomla dev site uses `jcv`).
 */
export type Cms = 'wordpress' | 'drupal' | 'joomla';

export const CMS: Cms = (process.env.CMS as Cms) || 'wordpress';

export const CV = process.env.CIVI_CV || 'cv';

/** Address of a CiviCRM page a member sees (the front end). */
export function frontUrl(civiPath: string, params: Record<string, string | number> = {}): string {
  const query = new URLSearchParams();
  switch (CMS) {
    case 'drupal':
      for (const [k, v] of Object.entries(params)) query.set(k, String(v));
      return `/${civiPath}?${query.toString()}`;
    case 'joomla':
      query.set('option', 'com_civicrm');
      query.set('task', civiPath);
      for (const [k, v] of Object.entries(params)) query.set(k, String(v));
      return `/index.php?${query.toString()}`;
    default:
      query.set('page', 'CiviCRM');
      query.set('q', civiPath);
      for (const [k, v] of Object.entries(params)) query.set(k, String(v));
      return `/?${query.toString()}`;
  }
}
