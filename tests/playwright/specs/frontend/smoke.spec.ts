import { test, expect } from '../../fixtures/base';
import { CMS, frontUrl } from '../../fixtures/cms';

/**
 * Front end smoke tests: a voter and an outsider using personal links, at the
 * nominations stage of the seeded council election. The base fixture fails any test
 * that shows PHP output, a console error or a failed election AJAX request.
 */
test.describe('election front end (nominations stage)', () => {
  test('a voter sees the election, its six roles and the nomination button', async ({ personaGoto }) => {
    const page = await personaGoto('V01', 'civicrm/elections/view');
    await expect(page.getByText('Welcome, Voter 01.')).toBeVisible();
    await expect(page.getByText('This Election is currently in the Nominations phase.')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Submit Nomination' })).toBeVisible();
    for (const role of ['President', 'Secretary', 'Treasurer', 'Council Member 1', 'Council Member 2', 'Council Member 3']) {
      await expect(page.getByRole('heading', { name: `Nominations for ${role}` })).toBeVisible();
    }
  });

  test('an outsider with a valid link cannot nominate', async ({ personaGoto }) => {
    const page = await personaGoto('OUT', 'civicrm/elections/nominations/create');
    await expect(page.getByText('You may only see this page if you are a member.')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Nominate' })).toHaveCount(0);
  });

  test('a link with a wrong checksum is refused', async ({ persona, allowedProblems }) => {
    // CiviCRM on Joomla reports a refused page as HTTP 500 (its fatal error page) where WordPress and Drupal send 403.
    allowedProblems.push(CMS === 'joomla' ? /403|500/ : /403/);
    const page = await persona('V01');
    const response = await page.goto(frontUrl('civicrm/elections/view', { eid: 1, cid: 1, cs: 'wrong' }));
    await expect(page.locator('body')).not.toContainText('Welcome,');
    await expect(page.locator('body')).toContainText(/do not have permission|not authorized|access denied/i);
    expect(CMS === 'joomla' ? [403, 500] : [200, 403]).toContain(response?.status());
  });
});
