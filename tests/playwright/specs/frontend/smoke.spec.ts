import { test, expect } from '../../fixtures/base';
import { frontUrl } from '../../fixtures/cms';

/**
 * Front end smoke tests: a voter and an outsider using personal links, at the
 * nominations stage of the seeded council election. The base fixture fails any test
 * that shows PHP output, a console error or a failed election AJAX request.
 */
test.describe('election front end (nominations stage)', () => {
  test('a voter sees the election, its six roles and the nomination button', async ({ personaGoto }) => {
    const page = await personaGoto('V01', 'civicrm/elections/view');
    await expect(page.getByText('Welcome, Voter 01.')).toBeVisible();
    await expect(page.getByText(/Nominations are open until/)).toBeVisible();
    await expect(page.getByRole('link', { name: 'Submit Nomination' })).toBeVisible();
    for (const role of ['President', 'Secretary', 'Treasurer', 'Council Member 1', 'Council Member 2', 'Council Member 3']) {
      await expect(page.getByRole('heading', { name: `Nominations for ${role}` })).toBeVisible();
    }
  });

  test('an outsider with a valid link cannot nominate', async ({ personaGoto }) => {
    const page = await personaGoto('OUT', 'civicrm/elections/nominations/create');
    await expect(page.getByText('You may only see this page if you can take part in this election.')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Nominate' })).toHaveCount(0);
  });

  test('a link with a wrong checksum is refused', async ({ persona, allowedProblems }) => {
    // A refused page is reported as HTTP 403 or, by CiviCRM's fatal error page, 500: Joomla always, and WordPress
    // and Drupal from CiviCRM 6.19 (its unhandled exception handler now sets 500 after the 403).
    allowedProblems.push(/403|500/);
    const page = await persona('V01');
    const response = await page.goto(frontUrl('civicrm/elections/view', { eid: 1, cid: 1, cs: 'wrong' }));
    await expect(page.locator('body')).not.toContainText('Welcome,');
    await expect(page.locator('body')).toContainText(/do not have permission|not authorized|access denied/i);
    expect([200, 403, 500]).toContain(response?.status());
  });
});
