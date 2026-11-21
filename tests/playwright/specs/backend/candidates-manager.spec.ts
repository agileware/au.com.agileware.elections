import { test, expect, civiAdminUrl, expectNoPhpOutput } from '../../fixtures/base';
import { civiApi4 } from '../../fixtures/civi';
import { CMS } from '../../fixtures/cms';
import { addNomination, addPosition, createElection, createMembers, nm, nominationFlags, removeRunData, removeRunMembers } from '../../fixtures/data';

/**
 * A person with "manage Election candidates" and not "administer Elections" (a returning officer, say): they open
 * Election Candidates, choose an election, add a candidate and edit statements, and nothing else in the back end.
 */
test.afterAll(() => {
  removeRunData();
  removeRunMembers();
});

const home = () => civiAdminUrl('civicrm/elections/candidate-list', { reset: 1 });

test.describe('a person who manages candidates only', () => {
  test('chooses an election from Election Candidates and adds a candidate to it', async ({ candidatesPage: page }) => {
    const { groupId, ids } = createMembers(['Rona']);
    const name = nm('Cand only');
    const election = createElection(name, { groupId });
    addPosition(election, 'Chair', 1);

    await page.goto(home());
    await expect(page.getByText('Choose an election to see its candidates and add one.')).toBeVisible();
    await page.getByRole('link', { name, exact: true }).click();
    await expect(page.locator('crm-search-display-table[search-name="ElectionCandidates"]')).toBeVisible();
    // Their way back is Election Candidates; they cannot open the overview or Manage Elections.
    await expect(page.getByRole('link', { name: 'Back to Election Candidates' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Back to Manage Elections' })).toBeHidden();
    await expect(page.locator('.election-list-actions .election-back')).toBeHidden();

    await page.getByRole('link', { name: 'Add Candidate' }).click();
    // The breadcrumb starts at Election Candidates and the election leads to its candidates, not the overview.
    // CiviCRM's own breadcrumb: a Drupal or Joomla theme draws one of its own above the page.
    const crumbs = page.locator('.crm-container .breadcrumb');
    await expect(crumbs).toContainText('Election Candidates');
    await expect(crumbs).not.toContainText('Manage Elections');
    await page.getByLabel('Position').selectOption({ label: 'Chair' });
    await page.locator('#s2id_contact, .crm-form-entityref').first().click();
    await page.locator('.select2-input:visible').fill('Rona');
    await page.locator('.select2-result-label', { hasText: 'Rona' }).first().click();
    await page.getByLabel(/^\s*Statement\s*\*?\s*$/).fill('Added by the returning officer');
    await page.getByRole('button', { name: 'Submit' }).click();
    await expect(page.locator('crm-search-display-table tbody tr', { hasText: 'Rona' })).toBeVisible();
    const [nomination] = civiApi4<Array<{ id: number }>>('ElectionNomination.get', { select: ['id'], where: [['member_nominee', '=', ids.Rona]] });
    expect(nominationFlags(nomination.id)).toMatchObject({ has_accepted_nomination: true, comments: 'Added by the returning officer' });
    await expectNoPhpOutput(page);
  });

  test('edits a statement with Edit Statement', async ({ candidatesPage: page }) => {
    const { groupId, ids } = createMembers(['Sol']);
    const election = createElection(nm('Cand only edit'), { groupId });
    const nomination = addNomination(addPosition(election, 'Chair', 1), ids.Sol, { accepted: true, statement: 'Old words' });
    await page.goto(civiAdminUrl('civicrm/elections/candidate/statement', { reset: 1, enid: nomination, eid: election }));
    await page.getByLabel(/^\s*Statement\s*\*?\s*$/).fill('Corrected words');
    await page.getByRole('button', { name: 'Submit' }).click();
    await expect.poll(() => nominationFlags(nomination).comments).toBe('Corrected words');
  });

  test('is refused the screens that set up or change elections', async ({ candidatesPage: page, allowedProblems }) => {
    allowedProblems.push(/403/);
    if (CMS === 'joomla') {
      // On Joomla CiviCRM's own refusal page answers HTTP 500 (upstream-issues 004); the page itself is a refusal.
      allowedProblems.push(/HTTP 500|status of 500/);
    }
    const election = createElection(nm('Cand only refused'));
    for (const [civiPath, params] of [
      ['civicrm/elections/manage', {}],
      ['civicrm/elections/create', {}],
      ['civicrm/elections/overview', { eid: election }],
      ['civicrm/elections/position-list', { eid: election }],
      ['civicrm/elections/results-display', { eid: election }],
    ] as Array<[string, Record<string, number>]>) {
      await page.goto(civiAdminUrl(civiPath, { reset: 1, ...params }));
      // CiviCRM's own refusal, not an error page (Afform pages needed their permissions on the route for that).
      await expect(page.locator('body'), civiPath).toContainText(/do not have permission|do not have access|not allowed to access|not authori[sz]ed|access denied/i);
      await expect(page.locator('body'), civiPath).not.toContainText('not found in api results');
      await expect(page.getByRole('button', { name: 'Submit' }), civiPath).toHaveCount(0);
      await expect(page.getByRole('link', { name: /^(Add Election|Add Position|Edit|Delete)$/ }), civiPath).toHaveCount(0);
    }
  });
});
