import { test, expect, civiAdminUrl, expectNoPhpOutput } from '../../fixtures/base';
import { civiApi4, civiEval } from '../../fixtures/civi';
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
    await expect(adminPage.getByRole('link', { name: 'Edit Election' })).toBeVisible();
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
    // Settings cannot be edited once an election is running: the button stays in its place, disabled, with the reason.
    await expect(adminPage.getByRole('link', { name: 'Edit Election' })).toHaveCount(0);
    const edit = adminPage.locator('.election-action-bar .election-action-disabled');
    await expect(edit).toHaveAttribute('aria-disabled', 'true');
    await expect(edit).toContainText('Edit Election');
    await expect(edit).toContainText('cannot be edited');
    await expectNoPhpOutput(adminPage);
  });

  test('the page has a breadcrumb, back buttons first and the actions in one row, with no Duplicate', async ({ adminPage }) => {
    const name = nm('Overview frame');
    const id = createElection(name);
    await adminPage.goto(overviewUrl(id));
    // Above the heading: Manage Elections is a link, the election is the current page.
    const crumbs = adminPage.locator('.crm-container .breadcrumb');
    await expect(crumbs.getByRole('link', { name: 'Manage Elections' })).toBeVisible();
    await expect(crumbs.locator('[aria-current="page"]')).toHaveText(name);
    // The heading is CiviCRM's on WordPress and Joomla, and the theme's page title (an h1 in the main area) on Drupal.
    await expect(adminPage.locator('.crm-title .title, h2.title, main h1').first()).toContainText(name);
    // The row: Back, Back to Manage Elections, Edit Election first of the actions, then the rest in order.
    const labels = (await adminPage.locator('.election-action-bar > .button').allInnerTexts()).map((t) => t.replace(/\s+/g, ' ').trim());
    expect(labels).toEqual(['Back', 'Back to Manage Elections', 'Edit Election', 'Positions', 'Candidates', 'Results Page', 'Audit Log', 'View as a Voter', 'Activate']);
    await expect(adminPage.getByRole('link', { name: 'Duplicate' })).toHaveCount(0);
    // One row on a wide screen (it wraps on a narrow one): every button sits at the same height.
    await adminPage.setViewportSize({ width: 1600, height: 900 });
    // A Drupal or Joomla theme sets the width of the content column (Olivero is about 800 px wide), so the row
    // is only expected to stay in one line where all the buttons fit in the width the theme gives it.
    const row = await adminPage.locator('.election-action-bar').evaluate((bar) => {
      const buttons = Array.from(bar.querySelectorAll(':scope > .button'));
      const gap = parseFloat(getComputedStyle(bar).columnGap) || 0;
      const needed = buttons.reduce((sum, b) => sum + b.getBoundingClientRect().width, 0) + gap * (buttons.length - 1);
      return {
        fits: needed <= bar.clientWidth,
        tops: buttons.map((b) => Math.round(b.getBoundingClientRect().top)),
      };
    });
    if (row.fits) {
      expect(new Set(row.tops).size).toBe(1);
    }
    else {
      // Wrapped: still in order, each row below the one before.
      expect([...row.tops].sort((a, b) => a - b)).toEqual(row.tops);
    }
    // The action bar is above the first block, not at the bottom.
    const bar = await adminPage.locator('.election-action-bar').boundingBox();
    const grid = await adminPage.locator('.election-overview-grid').boundingBox();
    expect(bar!.y).toBeLessThan(grid!.y);
    await expectNoPhpOutput(adminPage);
  });

  test('the blocks have headings in order, one time zone line and labelled tables', async ({ adminPage }) => {
    const id = createElection(nm('Overview structure'));
    await adminPage.goto(overviewUrl(id));
    await expect(adminPage.locator('.crm-election-overview h3')).toHaveCount(0);
    await expect(adminPage.locator('.crm-election-overview h2').first()).toBeVisible();
    await expect(adminPage.getByText(/^Times are shown in /)).toHaveCount(1);
    await expect(adminPage.locator('.election-counts th[scope="row"]')).toHaveCount(5);
    await expect(adminPage.locator('.election-counts')).toContainText('Nominees with enough nominations');
    await expect(adminPage.locator('.crm-election-overview')).not.toContainText('Eligible candidates');
    // The icons are hidden from assistive technology without also being given a role.
    await expect(adminPage.locator('.election-action-bar .crm-i[role]')).toHaveCount(0);
  });

  test('numbers of people are worded for one and for many', async ({ adminPage }) => {
    const { groupId, ids } = createMembers(['Jo']);
    const id = createElection(nm('Overview plurals'), { groupId });
    const chair = addPosition(id, 'Chair', 1);
    addNomination(chair, ids.Jo, { accepted: true });
    startElection(id);
    civiApi4('Election.update', { values: { nomination_groups: String(groupId) }, where: [['id', '=', id]] });
    await adminPage.goto(overviewUrl(id));
    await expect(adminPage.locator('.election-overview-roles')).toContainText('1 person may vote');
    await expect(adminPage.locator('.election-overview-roles')).toContainText('1 person may nominate and second');
  });

  test('the audit log shows readable labels and the whole fingerprint above the table, with a Copy button', async ({ adminPage }) => {
    const id = createElection(nm('Overview audit'));
    civiEval(`CRM_Elections_Helper_Audit::log(${id}, 'election_changed', []); CRM_Elections_Helper_Audit::log(${id}, 'votes_anonymised', []);`);
    await adminPage.goto(overviewUrl(id));
    const table = adminPage.locator('.election-audit-recent');
    await expect(table.locator('tbody tr').first()).toContainText('Votes anonymised');
    await expect(table).toContainText('Election changed');
    await expect(table).not.toContainText('election_changed');
    const fingerprint = adminPage.locator('#election-fingerprint');
    const text = (await fingerprint.textContent())!.trim();
    expect(text).toMatch(/^[0-9a-f]{64}$/);
    await expect(adminPage.locator('.election-overview-audit')).not.toContainText('...');
    const above = await fingerprint.boundingBox();
    const below = await table.boundingBox();
    expect(above!.y).toBeLessThan(below!.y);
    await expect(adminPage.locator('.election-overview-audit .description').first()).toContainText('Entries cannot be changed or removed');
    await adminPage.getByRole('button', { name: 'Copy' }).click();
    await expect(adminPage.locator('#election-copy-status')).toHaveText(/Copied|Select the fingerprint/);
  });

  test('the manage list links to the overview and a member cannot open it', async ({ adminPage, memberPage, allowedProblems }) => {
    const id = createElection(nm('Overview link'));
    await adminPage.goto(civiAdminUrl('civicrm/elections/manage', { reset: 1 }));
    await adminPage.locator('tr', { hasText: nm('Overview link') }).getByRole('link', { name: nm('Overview link'), exact: true }).click();
    await expect(adminPage.locator('.election-stage')).toBeVisible();

    allowedProblems.push(/403/);
    await memberPage.goto(overviewUrl(id));
    await expect(memberPage.locator('.election-stage')).toHaveCount(0);
  });
});
