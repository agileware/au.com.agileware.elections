import { test, expect, civiAdminUrl, expectNoPhpOutput } from '../../fixtures/base';
import { createElection, nm, positionNames, removeRunData } from '../../fixtures/data';

test.afterAll(() => {
  removeRunData();
});

/**
 * Back end smoke tests: administering elections. The back end is for administrators
 * only - members take part through the front end.
 */
test.describe('election administration', () => {
  test('the administrator sees the elections list with the seeded election', async ({ adminPage }) => {
    await adminPage.goto(civiAdminUrl('civicrm/elections/manage', { reset: 1 }));
    await expect(adminPage.getByRole('heading', { name: 'Manage Elections' })).toBeVisible();
    await expect(adminPage.getByRole('link', { name: 'Add Election' })).toBeVisible();
    await expect(adminPage.getByText('Playwright Council Election')).toBeVisible();
    await expectNoPhpOutput(adminPage);
  });

  test('the create election form offers the display options', async ({ adminPage }) => {
    await adminPage.goto(civiAdminUrl('civicrm/elections/create', { reset: 1 }));
    await adminPage.getByRole('tab', { name: 'Display' }).click();
    await expect(adminPage.getByText('Choose what voters see about candidates in this election.')).toBeVisible();
    await expect(adminPage.getByLabel('Show candidate photos')).toBeChecked();
    await expect(adminPage.getByLabel('Show candidate statements')).toBeVisible();
    await expect(adminPage.getByLabel('Candidate name format')).toBeVisible();
    await expectNoPhpOutput(adminPage);
  });

  test('an organiser with the documented permissions can open the list', async ({ managerPage }) => {
    await managerPage.goto(civiAdminUrl('civicrm/elections/manage', { reset: 1 }));
    await expect(managerPage.getByRole('link', { name: 'Add Election' })).toBeVisible();
    await expectNoPhpOutput(managerPage);
  });

  test('a member cannot reach the administration screens', async ({ memberPage, allowedProblems }) => {
    allowedProblems.push(/403/);
    await memberPage.goto(civiAdminUrl('civicrm/elections/create', { reset: 1 }));
    await expect(memberPage.getByRole('button', { name: 'Submit' })).toHaveCount(0);
  });

  test('the position form is labelled as the standard says and saves with a short confirmation', async ({ adminPage }) => {
    const id = createElection(nm('Smoke position'));
    await adminPage.goto(civiAdminUrl('civicrm/elections/positions/create', { reset: 1, eid: id }));
    await expect(adminPage.getByText('Fields marked * are required.')).toBeVisible();
    for (const label of ['Name', 'Position type', 'Seats', 'Order on the ballot', 'Write-ins per seat', 'Description']) {
      await expect(adminPage.getByLabel(label, { exact: true }), label).toBeVisible();
    }
    await expect(adminPage.getByLabel('Position type').locator('option')).toHaveText(['Position', 'Question']);
    await adminPage.getByLabel('Position type').selectOption('referendum');
    for (const label of ['Wording for Yes', 'Wording for No', 'Wording for Abstain', 'Passes when', 'Quorum (% of voters)', 'Resolution text']) {
      await expect(adminPage.getByLabel(label), label).toBeVisible();
    }
    await adminPage.getByLabel('Position type').selectOption('election');
    // Submit, then the two quiet back links, in that order.
    const footer = adminPage.locator('.crm-submit-buttons');
    await expect(footer.getByRole('button', { name: 'Submit' })).toBeVisible();
    await expect(footer.getByRole('link', { name: 'Back', exact: true })).toBeVisible();
    await expect(footer.getByRole('link', { name: 'Back to Manage Elections' })).toBeVisible();

    await adminPage.getByLabel('Seats').fill('0');
    await adminPage.getByLabel('Name', { exact: true }).fill('Smoke Chair');
    await adminPage.getByLabel('Order on the ballot').fill('1');
    await adminPage.getByRole('button', { name: 'Submit' }).click();
    await expect(adminPage.getByText('Seats must be a whole number of 1 or more.')).toBeVisible();
    await adminPage.getByLabel('Seats').fill('1');
    await adminPage.getByRole('button', { name: 'Submit' }).click();
    await expect(adminPage.getByText('Position added.')).toBeVisible();
    expect(positionNames(id)).toEqual(['Smoke Chair']);
    await expectNoPhpOutput(adminPage);
  });

  test('Add Candidate says why it cannot be used when the election has no positions', async ({ adminPage }) => {
    const id = createElection(nm('Smoke candidate'));
    await adminPage.goto(civiAdminUrl('civicrm/elections/candidates/add', { reset: 1, eid: id }));
    await expect(adminPage.getByText('There are no positions to add a candidate to.')).toBeVisible();
    await expect(adminPage.getByRole('link', { name: 'Add a position first.' })).toBeVisible();
    for (const label of ['Position', 'Candidate', 'Mark as accepted', 'Statement']) {
      await expect(adminPage.getByLabel(label, { exact: true }), label).toBeVisible();
    }
    await expect(adminPage.getByLabel('Position').locator('option').first()).toHaveText('Select a position');
    await expect(adminPage.locator('.crm-submit-buttons').getByRole('button', { name: 'Submit' })).toBeVisible();
    await expectNoPhpOutput(adminPage);
  });
});
