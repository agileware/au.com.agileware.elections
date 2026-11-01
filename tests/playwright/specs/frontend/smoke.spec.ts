import { test, expect } from '../../fixtures/base';
import { frontUrl } from '../../fixtures/cms';
import { personaUrl } from '../../fixtures/personas';

/**
 * Front end smoke tests: a voter and an outsider using personal links, at the
 * nominations stage of the seeded council election. The base fixture fails any test
 * that shows PHP output, a console error or a failed election AJAX request.
 */
test.describe('election front end (nominations stage)', () => {
  test('a voter sees the election, its six roles and the nomination button', async ({ personaGoto }) => {
    const page = await personaGoto('V01', 'civicrm/elections/view');
    await expect(page.getByText(/You are taking part as Voter 01\./)).toBeVisible();
    await expect(page.getByText(/Nominations close on/)).toBeVisible();
    await expect(page.getByRole('link', { name: 'Nominate', exact: true })).toBeVisible();
    for (const role of ['President', 'Secretary', 'Treasurer', 'Council Member 1', 'Council Member 2', 'Council Member 3']) {
      await expect(page.getByRole('heading', { name: `Nominations for ${role}` })).toBeVisible();
    }
  });

  test('an outsider with a valid link cannot nominate and is told why, with the 403 status', async ({ persona, allowedProblems }) => {
    // The refusal is a page with the reason in plain words, sent as HTTP 403 on every CMS.
    allowedProblems.push(/403/);
    const page = await persona('OUT');
    const response = await page.goto(personaUrl('OUT', 'civicrm/elections/nominations/create'));
    expect(response?.status()).toBe(403);
    await expect(page.getByText('You are not in a group that can take part in this election. Ask the organisers if you think you should be.')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Nominate' })).toHaveCount(0);
    // One message, a quiet way out to the list, and no code or stack on the screen.
    await expect(page.locator('.crm-container .messages')).toHaveCount(1);
    await expect(page.getByRole('link', { name: 'View Elections' })).toHaveClass(/election-btn-quiet/);
    await expect(page.locator('body')).not.toContainText(/Exception|Stack trace|Return home|Return to Elections/);
  });

  test('an election that does not exist is not found, with the 404 status', async ({ persona, allowedProblems }) => {
    allowedProblems.push(/404/);
    const page = await persona('V01');
    const response = await page.goto(personaUrl('V01', 'civicrm/elections/view', { eid: 987654 }));
    expect(response?.status()).toBe(404);
    await expect(page.getByText('This election could not be found.')).toBeVisible();
    await expect(page.getByRole('link', { name: 'View Elections' })).toBeVisible();
    await expect(page.locator('body')).not.toContainText(/not authori[sz]ed|Exception|Stack trace/i);
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
