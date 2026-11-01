import { test, expect, civiAdminUrl, expectNoPhpOutput } from '../../fixtures/base';
import { addPosition, createElection, nm, removeRunData, removeRunMembers } from '../../fixtures/data';

test.afterAll(() => {
  removeRunData();
  removeRunMembers();
});

/**
 * Every full back end page has the same frame (plan/20-ui-standards.md, section 3): a breadcrumb above the
 * heading that starts at Manage Elections and goes through the election to the page.
 */
test.describe('Back end page frame', () => {
  test('the SearchKit lists show the election in their breadcrumb, not CiviCRM\'s menu trail', async ({ adminPage }) => {
    const name = nm('Frame lists');
    const id = createElection(name);
    addPosition(id, 'Chair', 1);
    const pages: Array<[string, string]> = [
      ['civicrm/elections/position-list', 'Positions'],
      ['civicrm/elections/candidate-list', 'Candidates'],
      ['civicrm/elections/result-list', 'Results'],
      ['civicrm/elections/audit-log', 'Audit Log'],
    ];
    for (const [route, label] of pages) {
      await adminPage.goto(civiAdminUrl(route, { reset: 1 }) + `#/?eid=${id}`);
      const crumbs = adminPage.locator('.breadcrumb');
      await expect(crumbs).toBeVisible();
      await expect(crumbs.getByRole('link', { name: 'Manage Elections' })).toBeVisible();
      await expect(crumbs.getByRole('link', { name })).toHaveAttribute('href', new RegExp(`overview.*eid=${id}`));
      await expect(crumbs.locator('[aria-current="page"]')).toHaveText(label);
      // CiviCRM's own trail (CiviCRM » Elections) is gone.
      await expect(crumbs).not.toContainText('CiviCRM');
    }
    await expectNoPhpOutput(adminPage);
  });

  test('Manage Elections is the root of the trail and is the current page', async ({ adminPage }) => {
    await adminPage.goto(civiAdminUrl('civicrm/elections/manage', { reset: 1 }));
    const crumbs = adminPage.locator('.breadcrumb');
    await expect(crumbs.locator('[aria-current="page"]')).toHaveText('Manage Elections');
    await expect(crumbs.getByRole('link')).toHaveCount(0);
  });
});
