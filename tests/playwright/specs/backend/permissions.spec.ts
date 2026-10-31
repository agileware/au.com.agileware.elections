import { test, expect, civiAdminUrl, expectNoPhpOutput } from '../../fixtures/base';
import { CMS } from '../../fixtures/cms';

/**
 * What an organiser needs to see the groups on the election form (docs/roles_and_permissions.md).
 */
test.describe('organiser permissions', () => {
  test('an organiser with the documented permissions can choose groups', async ({ managerPage }) => {
    await managerPage.goto(civiAdminUrl('civicrm/elections/create', { reset: 1 }));
    await managerPage.getByRole('tab', { name: 'Participation' }).click();
    await expect(managerPage.locator('.election-groups-hidden')).toHaveCount(0);
    await managerPage.locator('#s2id_allowed_groups').click();
    await managerPage.keyboard.type('Administrators');
    await expect(managerPage.locator('.select2-results .select2-result-label', { hasText: 'Administrators' })).toBeVisible();
    await expectNoPhpOutput(managerPage);
  });

  test('an organiser without View All Contacts is told why no groups are listed', async ({ limitedPage }) => {
    test.skip(CMS === 'joomla', 'Joomla has no limited organiser session: its access levels are set in Joomla');
    await limitedPage.goto(civiAdminUrl('civicrm/elections/create', { reset: 1 }));
    await limitedPage.getByRole('tab', { name: 'Participation' }).click();
    const notice = limitedPage.locator('.election-groups-hidden');
    await expect(notice).toBeVisible();
    await expect(notice).toContainText('You cannot see any groups');
    await expect(notice).toContainText('View All Contacts');
    await expectNoPhpOutput(limitedPage);
  });
});
