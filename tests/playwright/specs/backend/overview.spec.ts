import { test, expect, civiAdminUrl, expectNoPhpOutput } from '../../fixtures/base';
import { addNomination, addPosition, createElection, createMembers, nm, removeRunData, removeRunMembers, startElection } from '../../fixtures/data';

test.afterAll(() => {
  removeRunData();
  removeRunMembers();
});

const overviewUrl = (id: number) => civiAdminUrl('civicrm/elections/overview', { reset: 1, eid: id });

test.describe('Election overview', () => {
  test('an inactive election with no positions says what to do first', async ({ adminPage }) => {
    const id = createElection(nm('Overview new'));
    await adminPage.goto(overviewUrl(id));
    await expect(adminPage.locator('.election-stage')).toHaveText('Inactive');
    await expect(adminPage.locator('.election-next-step')).toContainText('Add at least one position');
    await expect(adminPage.locator('.election-count-positions')).toHaveText('0');
    await expect(adminPage.getByRole('link', { name: 'Edit settings' })).toBeVisible();
    await expectNoPhpOutput(adminPage);
  });

  test('a running election shows its stage, timeline and numbers', async ({ adminPage }) => {
    const { groupId, ids } = createMembers(['Hal', 'Ivy']);
    const id = createElection(nm('Overview live'), { groupId });
    const chair = addPosition(id, 'Chair', 1);
    addNomination(chair, ids.Hal, { accepted: true });
    addNomination(chair, ids.Ivy, { accepted: false });
    startElection(id);
    await adminPage.goto(overviewUrl(id));
    await expect(adminPage.locator('.election-stage')).toHaveText('Nominations open');
    await expect(adminPage.locator('.election-count-positions')).toHaveText('1');
    await expect(adminPage.locator('.election-count-nominations')).toHaveText('2');
    await expect(adminPage.locator('.election-count-candidates')).toHaveText('1');
    await expect(adminPage.locator('.election-timeline li')).toHaveCount(8);
    await expect(adminPage.locator('.election-timeline-passed').first()).toBeVisible();
    // Settings cannot be edited once an election is running.
    await expect(adminPage.getByRole('link', { name: 'Edit settings' })).toHaveCount(0);
    await expectNoPhpOutput(adminPage);
  });

  test('the manage list links to the overview and a member cannot open it', async ({ adminPage, memberPage, allowedProblems }) => {
    const id = createElection(nm('Overview link'));
    await adminPage.goto(civiAdminUrl('civicrm/elections/manage', { reset: 1 }));
    await adminPage.locator('tr', { hasText: nm('Overview link') }).getByRole('link', { name: nm('Overview link') }).click();
    await expect(adminPage.locator('.election-stage')).toBeVisible();

    allowedProblems.push(/403/);
    await memberPage.goto(overviewUrl(id));
    await expect(memberPage.locator('.election-stage')).toHaveCount(0);
  });
});
