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
    const rows = adminPage.locator('crm-search-display-table tbody tr');
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
    const rows = adminPage.locator('crm-search-display-table tbody tr');
    await expect(rows).toHaveCount(3);

    // Themes with a tall header (Drupal Olivero) put the table below the fold: bring the rows into view.
    await adminPage.waitForLoadState('networkidle');
    await rows.nth(1).evaluate((el) => el.scrollIntoView({ block: 'center' }));
    await adminPage.waitForTimeout(500); // let the layout settle (messages and placeholders above the table move it)
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
    await expect(adminPage.locator('crm-search-display-table tbody tr').first()).toContainText('Gamma');
  });

  test('a position name can be edited in place', async ({ adminPage }) => {
    const id = createElection(nm('Positions edit'));
    addPosition(id, 'Old name', 1);
    await adminPage.goto(listUrl(id));
    const row = adminPage.locator('crm-search-display-table tbody tr').first();
    await row.locator('[data-field-name="name"] .crm-editable-enabled').click();
    await row.locator('input[type="text"]').fill('New name');
    await row.getByRole('button', { name: 'Save' }).click();
    await expect.poll(() => positionNames(id)).toEqual(['New name']);
  });

  test('a position can be duplicated and deleted', async ({ adminPage }) => {
    const id = createElection(nm('Positions copy'));
    addPosition(id, 'Chair', 1);
    await adminPage.goto(listUrl(id));
    await adminPage.locator('crm-search-display-table tbody tr', { hasText: 'Chair' }).getByRole('link', { name: 'Duplicate' }).click();
    await adminPage.locator('.ui-dialog').getByRole('button', { name: 'Duplicate' }).click();
    await expect(adminPage.locator('crm-search-display-table tbody tr', { hasText: 'Copy of Chair' })).toBeVisible();
    expect(positionNames(id)).toEqual(['Chair', 'Copy of Chair']);

    // Themes with a tall header put the row near the page's own controls: bring it to the middle first.
    const copyRow = adminPage.locator('crm-search-display-table tbody tr', { hasText: 'Copy of Chair' });
    await copyRow.evaluate((el) => el.scrollIntoView({ block: 'center' }));
    await copyRow.getByRole('link', { name: 'Delete' }).click();
    await adminPage.locator('.ui-dialog').getByRole('button', { name: 'Delete' }).click();
    await expect(adminPage.locator('crm-search-display-table tbody tr', { hasText: 'Copy of Chair' })).toHaveCount(0);
    expect(positionNames(id)).toEqual(['Chair']);
  });

  test('positions cannot be changed once the election has started', async () => {
    // The rule is in the data layer, so inline edits, drag and drop and the API are all covered.
    const id = createElection(nm('Positions started'));
    const positionId = addPosition(id, 'Chair', 1);
    addPosition(id, 'Treasurer', 2);
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
    expect(failure(() => civiApi4('ElectionPosition.update', { values: { sortorder: 2 }, where: [['id', '=', positionId]] }))).toContain('once election has been started');
    expect(failure(() => addPosition(id, 'Late', 2))).toContain('once election has been started');
    expect(failure(() => civiApi4('ElectionPosition.delete', { where: [['id', '=', positionId]] }))).toContain('once election has been started');
    expect(positionNames(id)).toEqual(['Chair', 'Treasurer']);
  });
});

test.describe('Referendum questions', () => {
  test('a question is added with its wording, threshold and quorum, and the position fields give way to the question fields', async ({ adminPage }) => {
    const id = createElection(nm('Positions question'));
    await adminPage.goto(civiAdminUrl('civicrm/elections/positions/create', { reset: 1, eid: id }));
    await expect(adminPage.locator('#quantity')).toBeVisible();
    await expect(adminPage.locator('#yes_label')).toBeHidden();

    await adminPage.locator('#position_type').selectOption('referendum');
    await expect(adminPage.locator('#quantity')).toBeHidden();
    await expect(adminPage.locator('#yes_label')).toBeVisible();
    await adminPage.locator('#name').fill('Adopt the new constitution?');
    await adminPage.locator('#sortorder').fill('1');
    await adminPage.locator('#yes_label').fill('For');
    await adminPage.locator('#no_label').fill('Against');
    await adminPage.locator('#abstain_label').fill('Abstain');
    await adminPage.locator('#pass_threshold_choice').selectOption('66.67');
    await adminPage.locator('#quorum_percent').fill('40');
    await adminPage.locator('#resolution_text').fill('That the constitution be replaced.');
    await adminPage.getByRole('button', { name: 'Create' }).click();

    await expect.poll(() => civiApi4<any[]>('ElectionPosition.get', { select: ['position_type', 'pass_threshold', 'quorum_percent', 'resolution_text'], where: [['election_id', '=', id]] }).length).toBe(1);
    const [position] = civiApi4<any[]>('ElectionPosition.get', { select: ['id', 'position_type', 'pass_threshold', 'quorum_percent', 'resolution_text'], where: [['election_id', '=', id]] });
    expect(position.position_type).toBe('referendum');
    expect(Number(position.pass_threshold)).toBeCloseTo(66.67);
    expect(Number(position.quorum_percent)).toBe(40);
    const options = civiApi4<any[]>('ElectionOption.get', { select: ['kind', 'label'], where: [['election_position_id', '=', position.id]], orderBy: { weight: 'ASC' } });
    expect(options.map((o) => `${o.kind}:${o.label}`)).toEqual(['yes:For', 'no:Against', 'abstain:Abstain']);

    // The list says which row is a question.
    await adminPage.goto(listUrl(id));
    await expect(adminPage.locator('crm-search-display-table tbody tr').first()).toContainText('Referendum question');
    await expectNoPhpOutput(adminPage);
  });
});
