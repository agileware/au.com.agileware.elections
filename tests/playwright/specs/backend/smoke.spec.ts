import { test, expect, civiAdminUrl, expectNoPhpOutput } from '../../fixtures/base';

/**
 * Back end smoke tests: administering elections. The back end is for administrators
 * only - members take part through the front end.
 */
test.describe('election administration', () => {
  test('the administrator sees the elections list with the seeded election', async ({ adminPage }) => {
    await adminPage.goto(civiAdminUrl('civicrm/elections/manage', { reset: 1 }));
    await expect(adminPage.getByRole('heading', { name: 'Manage Elections' })).toBeVisible();
    await expect(adminPage.getByRole('link', { name: 'Create Election' })).toBeVisible();
    await expect(adminPage.getByText('Playwright Council Election')).toBeVisible();
    await expectNoPhpOutput(adminPage);
  });

  test('the create election form offers the display options', async ({ adminPage }) => {
    await adminPage.goto(civiAdminUrl('civicrm/elections/create', { reset: 1 }));
    await adminPage.getByRole('tab', { name: 'Display' }).click();
    await expect(adminPage.getByText('Choose what voters see about candidates in this election.')).toBeVisible();
    await expect(adminPage.getByLabel('Show candidate photos')).toBeChecked();
    await expect(adminPage.getByLabel('Candidate statements')).toBeVisible();
    await expect(adminPage.getByLabel('Candidate name format')).toBeVisible();
    await expectNoPhpOutput(adminPage);
  });

  test('a manager with only the administer Elections permission can open the list', async ({ managerPage }) => {
    await managerPage.goto(civiAdminUrl('civicrm/elections/manage', { reset: 1 }));
    await expect(managerPage.getByRole('link', { name: 'Create Election' })).toBeVisible();
    await expectNoPhpOutput(managerPage);
  });

  test('a member cannot reach the administration screens', async ({ memberPage, allowedProblems }) => {
    allowedProblems.push(/403/);
    await memberPage.goto(civiAdminUrl('civicrm/elections/create', { reset: 1 }));
    await expect(memberPage.getByRole('button', { name: 'Submit' })).toHaveCount(0);
  });
});
