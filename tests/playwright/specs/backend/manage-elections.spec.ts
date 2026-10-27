import { test, expect, civiAdminUrl, expectNoPhpOutput } from '../../fixtures/base';
import { civiApi4 } from '../../fixtures/civi';
import { addPosition, createElection, isActive, nm, positionNames, removeRunData, RUN } from '../../fixtures/data';

test.afterAll(() => removeRunData());

test.describe('Manage Elections (SearchKit list)', () => {
  test('lists every election with its dates and position count', async ({ adminPage }) => {
    const id = createElection(nm('Manage list'));
    addPosition(id, 'Chair', 1);
    addPosition(id, 'Deputy', 2);
    await adminPage.goto(civiAdminUrl('civicrm/elections/manage', { reset: 1 }));
    const row = adminPage.locator('tr', { hasText: nm('Manage list') });
    await expect(row).toBeVisible();
    await expect(row.getByRole('link', { name: '2', exact: true })).toBeVisible();
    await expect(row.getByText('No', { exact: true })).toBeVisible();
    await expect(row.getByRole('link', { name: 'Edit' })).toBeVisible();
    await expect(adminPage.getByRole('link', { name: 'Create Election' })).toBeVisible();
    await expectNoPhpOutput(adminPage);
  });

  test('an election with no positions cannot be activated', async ({ adminPage }) => {
    const id = createElection(nm('Needs positions'));
    await adminPage.goto(civiAdminUrl('civicrm/elections/manage', { reset: 1 }));
    const row = adminPage.locator('tr', { hasText: nm('Needs positions') });
    await row.getByRole('link', { name: 'Activate' }).click();
    const dialog = adminPage.locator('.ui-dialog');
    await dialog.getByRole('button', { name: 'Activate Election' }).click();
    await expect(adminPage.getByText('At least one Position must be defined before the Election can be active.')).toBeVisible();
    await expect(adminPage.locator('tr', { hasText: nm('Needs positions') }).getByText('No', { exact: true })).toBeVisible();
    expect(isActive(id)).toBe(false);
  });

  test('an election with a position can be activated and deactivated from the list', async ({ adminPage }) => {
    const id = createElection(nm('Toggle me'));
    addPosition(id, 'Chair', 1);
    await adminPage.goto(civiAdminUrl('civicrm/elections/manage', { reset: 1 }));
    const row = adminPage.locator('tr', { hasText: nm('Toggle me') });

    await row.getByRole('link', { name: 'Activate' }).click();
    await adminPage.locator('.ui-dialog').getByRole('button', { name: 'Activate Election' }).click();
    await expect(row.getByText('Yes', { exact: true })).toBeVisible();
    expect(isActive(id)).toBe(true);

    await row.getByRole('link', { name: 'Deactivate' }).click();
    await adminPage.locator('.ui-dialog').getByRole('button', { name: 'Deactivate Election' }).click();
    await expect(row.getByText('No', { exact: true })).toBeVisible();
    expect(isActive(id)).toBe(false);
  });

  test('inline editing cannot activate an election with no positions either', async ({ adminPage }) => {
    // The rule lives in the database layer, so it holds for SearchKit inline edits and the API too.
    const id = createElection(nm('Inline rule'));
    let message = '';
    try {
      civiApi4('Election.update', { values: { is_visible: true }, where: [['id', '=', id]] });
    }
    catch (e: any) {
      message = String(e.stderr || e.message);
    }
    expect(message).toContain('At least one Position');
    expect(isActive(id)).toBe(false);
  });

  test('an election can be duplicated with its positions', async ({ adminPage }) => {
    const id = createElection(nm('Original'));
    addPosition(id, 'Chair', 1);
    addPosition(id, 'Treasurer', 2);
    await adminPage.goto(civiAdminUrl('civicrm/elections/manage', { reset: 1 }));
    await adminPage.locator('tr', { hasText: nm('Original') }).getByRole('link', { name: 'Duplicate' }).click();
    const dialog = adminPage.locator('.ui-dialog');
    await expect(dialog.getByLabel('Name of the copy')).toHaveValue(`Copy of ${nm('Original')}`);
    await dialog.getByLabel('Name of the copy').fill(nm('Copy'));
    await dialog.getByRole('button', { name: 'Duplicate' }).click();
    await expect(adminPage.locator('tr', { hasText: nm('Copy') })).toBeVisible();

    const copies = civiApi4<Array<{ id: number; is_visible: boolean }>>('Election.get', { select: ['id', 'is_visible'], where: [['name', '=', nm('Copy')]] });
    expect(copies).toHaveLength(1);
    expect(copies[0].is_visible).toBe(false);
    const positions = civiApi4<Array<{ name: string }>>('ElectionPosition.get', { select: ['name'], where: [['election_id', '=', copies[0].id]], orderBy: { sortorder: 'ASC' } });
    expect(positions.map((p) => p.name)).toEqual(['Chair', 'Treasurer']);
  });
});
