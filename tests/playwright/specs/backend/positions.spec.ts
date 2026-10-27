import { test, expect, civiAdminUrl, expectNoPhpOutput } from '../../fixtures/base';
import { civiApi4 } from '../../fixtures/civi';
import { addPosition, createElection, nm, positionNames, removeRunData, startElection } from '../../fixtures/data';

test.afterAll(() => removeRunData());

const listUrl = (electionId: number) => civiAdminUrl('civicrm/elections/positions', { reset: 1, eid: electionId });

test.describe('Election positions (SearchKit list)', () => {
  test('shows the positions in order and the old address still works', async ({ adminPage }) => {
    const id = createElection(nm('Positions list'));
    addPosition(id, 'Alpha', 1);
    addPosition(id, 'Beta', 2, 2);
    await adminPage.goto(listUrl(id));
    await expect(adminPage.getByRole('heading', { name: 'Election Positions' })).toBeVisible();
    const rows = adminPage.locator('tbody tr');
    await expect(rows).toHaveCount(2);
    await expect(rows.nth(0)).toContainText('Alpha');
    await expect(rows.nth(1)).toContainText('Beta');
    await expect(rows.nth(0).locator('.crm-draggable')).toBeVisible();
    await expect(adminPage.getByRole('link', { name: 'Add Position' })).toBeVisible();
    await expectNoPhpOutput(adminPage);
  });

  test('dragging a row changes the order and it is kept', async ({ adminPage }) => {
    const id = createElection(nm('Positions drag'));
    addPosition(id, 'Alpha', 1);
    addPosition(id, 'Beta', 2);
    addPosition(id, 'Gamma', 3);
    await adminPage.goto(listUrl(id));
    const rows = adminPage.locator('tbody tr');
    await expect(rows).toHaveCount(3);

    const handle = rows.nth(2).locator('.crm-draggable');
    const from = (await handle.boundingBox())!;
    const top = (await rows.nth(0).boundingBox())!;
    await adminPage.mouse.move(from.x + 5, from.y + 5);
    await adminPage.mouse.down();
    // Move up in small steps so the list re-orders under the pointer as a person would drag it.
    for (let y = from.y; y > top.y - 30; y -= 10) {
      await adminPage.mouse.move(from.x + 5, y, { steps: 2 });
      await adminPage.waitForTimeout(40);
    }
    await adminPage.mouse.up();

    await expect.poll(() => positionNames(id), { timeout: 15000 }).toEqual(['Gamma', 'Alpha', 'Beta']);
    await adminPage.reload();
    await expect(adminPage.locator('tbody tr').first()).toContainText('Gamma');
  });

  test('a position name can be edited in place', async ({ adminPage }) => {
    const id = createElection(nm('Positions edit'));
    addPosition(id, 'Old name', 1);
    await adminPage.goto(listUrl(id));
    const row = adminPage.locator('tbody tr').first();
    await row.locator('[data-field-name="name"] .crm-editable-enabled').click();
    await row.locator('input[type="text"]').fill('New name');
    await row.getByRole('button', { name: 'Save' }).click();
    await expect.poll(() => positionNames(id)).toEqual(['New name']);
  });

  test('a position can be duplicated and deleted', async ({ adminPage }) => {
    const id = createElection(nm('Positions copy'));
    addPosition(id, 'Chair', 1);
    await adminPage.goto(listUrl(id));
    await adminPage.locator('tbody tr', { hasText: 'Chair' }).getByRole('link', { name: 'Duplicate' }).click();
    await adminPage.locator('.ui-dialog').getByRole('button', { name: 'Duplicate' }).click();
    await expect(adminPage.locator('tbody tr', { hasText: 'Copy of Chair' })).toBeVisible();
    expect(positionNames(id)).toEqual(['Chair', 'Copy of Chair']);

    await adminPage.locator('tbody tr', { hasText: 'Copy of Chair' }).getByRole('link', { name: 'Delete' }).click();
    await adminPage.locator('.ui-dialog').getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('tbody tr', { hasText: 'Copy of Chair' })).toHaveCount(0);
    expect(positionNames(id)).toEqual(['Chair']);
  });

  test('positions cannot be changed once the election has started', async () => {
    // The rule is in the data layer, so inline edits, drag and drop and the API are all covered.
    const id = createElection(nm('Positions started'));
    const positionId = addPosition(id, 'Chair', 1);
    startElection(id);
    const failure = (fn: () => void) => {
      try {
        fn();
      }
      catch (e: any) {
        return String(e.stderr || e.message);
      }
      return '';
    };
    expect(failure(() => civiApi4('ElectionPosition.update', { values: { name: 'Changed' }, where: [['id', '=', positionId]] }))).toContain('once election has been started');
    expect(failure(() => civiApi4('ElectionPosition.update', { values: { sortorder: 9 }, where: [['id', '=', positionId]] }))).toContain('once election has been started');
    expect(failure(() => addPosition(id, 'Late', 2))).toContain('once election has been started');
    expect(failure(() => civiApi4('ElectionPosition.delete', { where: [['id', '=', positionId]] }))).toContain('once election has been started');
    expect(positionNames(id)).toEqual(['Chair']);
  });
});
