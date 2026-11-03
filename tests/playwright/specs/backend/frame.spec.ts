import { test, expect, civiAdminUrl, expectNoPhpOutput } from '../../fixtures/base';
import { civiEval } from '../../fixtures/civi';
import { CMS } from '../../fixtures/cms';
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
      const crumbs = adminPage.locator('.crm-container .breadcrumb');
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
    // Scoped to CiviCRM's own breadcrumb: a Drupal or Joomla theme draws a breadcrumb of its own above the page.
    const crumbs = adminPage.locator('.crm-container .breadcrumb');
    await expect(crumbs.locator('[aria-current="page"]')).toHaveText('Manage Elections');
    if (CMS === 'joomla') {
      // Joomla's page template draws every step of the trail as a link: the page itself is a link to the top
      // of the page, with its name marked as the current one.
      await expect(crumbs.getByRole('link')).toHaveCount(1);
      await expect(crumbs.getByRole('link')).toHaveAttribute('href', '#');
    }
    else {
      await expect(crumbs.getByRole('link')).toHaveCount(0);
    }
  });

  test('every list has Back and Back to Manage Elections on the left; Add is at the right end only where the list has one', async ({ adminPage }) => {
    const id = createElection(nm('Frame bars'));
    addPosition(id, 'Chair', 1);
    const pages: Array<[string, string[]]> = [
      ['civicrm/elections/position-list', ['Back', 'Back to Manage Elections', 'Add Position']],
      ['civicrm/elections/candidate-list', ['Back', 'Back to Manage Elections', 'Add Candidate']],
      ['civicrm/elections/result-list', ['Back', 'Back to Manage Elections']],
      ['civicrm/elections/audit-log', ['Back', 'Back to Manage Elections']],
    ];
    for (const [route, labels] of pages) {
      await adminPage.goto(civiAdminUrl(route, { reset: 1 }) + `#/?eid=${id}`);
      const bar = adminPage.locator('.election-list-actions');
      await expect(bar.getByRole('link')).toHaveText(labels);
      // The old wording is gone.
      await expect(adminPage.getByText('Back to the election')).toHaveCount(0);
    }
  });

  test('Back returns to the page the person came from and falls back to the Election Overview', async ({ adminPage }) => {
    const id = createElection(nm('Frame back'));
    addPosition(id, 'Chair', 1);
    addPosition(id, 'Deputy', 2);

    // Nothing to go back to: the Election Overview.
    await adminPage.goto(civiAdminUrl('civicrm/elections/result-list', { reset: 1 }) + `#/?eid=${id}`);
    await expect(adminPage.getByRole('link', { name: 'Back', exact: true })).toHaveAttribute('href', new RegExp(`overview.*eid(=|%3D)${id}`));

    // From Manage Elections (through the position count): back to Manage Elections.
    await adminPage.goto(civiAdminUrl('civicrm/elections/manage', { reset: 1 }));
    await adminPage.locator('tr', { hasText: nm('Frame back') }).getByRole('link', { name: '2', exact: true }).click();
    await expect(adminPage.locator('.election-list-actions')).toBeVisible();
    await adminPage.getByRole('link', { name: 'Back', exact: true }).click();
    await expect(adminPage.locator('tr', { hasText: nm('Frame back') })).toBeVisible();
    await expect(adminPage.getByRole('link', { name: 'Add Election' })).toBeVisible();
  });

  test('each list has a caption and column headings with scope', async ({ adminPage }) => {
    const id = createElection(nm('Frame tables'));
    addPosition(id, 'Chair', 1);
    const pages: Array<[string, string, string]> = [
      ['civicrm/elections/manage', '', 'Elections'],
      ['civicrm/elections/position-list', `#/?eid=${id}`, 'Positions'],
      ['civicrm/elections/candidate-list', `#/?eid=${id}`, 'Candidates'],
      ['civicrm/elections/result-list', `#/?eid=${id}`, 'Results'],
      ['civicrm/elections/audit-log', `#/?eid=${id}`, 'Audit Log'],
    ];
    for (const [route, hash, caption] of pages) {
      await adminPage.goto(civiAdminUrl(route, { reset: 1 }) + hash);
      const table = adminPage.locator('crm-search-display-table table');
      await expect(table.locator('caption')).toHaveText(caption);
      const headings = await table.locator('thead th').count();
      expect(headings).toBeGreaterThan(0);
      await expect(table.locator('thead th[scope="col"]')).toHaveCount(headings);
    }
  });

  test('the audit log shows words and not stored values, and says which time zone the times are in', async ({ adminPage }) => {
    const id = createElection(nm('Frame audit'));
    civiEval(`CRM_Elections_Helper_Audit::log(${id}, 'election_changed', ['changes' => ['voting_end_date' => ['from' => 'a', 'to' => 'b']]]); CRM_Elections_Helper_Audit::log(${id}, 'results_generated', ['ballots' => 12]); CRM_Elections_Helper_Audit::log(${id}, 'vote_removed', ['rows' => 1]); CRM_Elections_Helper_Audit::log(${id}, 'votes_anonymised', []);`);
    await adminPage.goto(civiAdminUrl('civicrm/elections/audit-log', { reset: 1 }) + `#/?eid=${id}`);
    const table = adminPage.locator('crm-search-display-table table');
    await expect(table.locator('tbody tr', { hasText: 'Election changed' }).first()).toContainText('Fields: voting end date');
    await expect(table.locator('tbody tr', { hasText: 'Results generated' })).toContainText('Ballots counted: 12');
    await expect(table.locator('tbody tr', { hasText: 'Vote removed' })).toContainText('Votes removed: 1');
    await expect(table.locator('tbody tr', { hasText: 'Votes anonymised' })).toBeVisible();
    await expect(table).not.toContainText(/election_changed|results_generated|vote_removed|votes_anonymised|\{|"changes"/);
    await expect(adminPage.locator('.election-time-zone-note')).toContainText(/^Times are shown in .+\.$/);
    await expect(adminPage.getByText('fingerprint of the entry before it')).toBeVisible();
    await expect(adminPage.getByText('a hash of')).toHaveCount(0);
  });

  test('on a phone each row becomes a card with a label for every value and nothing scrolls sideways', async ({ adminPage }) => {
    const id = createElection(nm('Frame cards'));
    addPosition(id, 'Chair', 1);
    await adminPage.setViewportSize({ width: 375, height: 800 });
    await adminPage.goto(civiAdminUrl('civicrm/elections/manage', { reset: 1 }));
    const row = adminPage.locator('tr', { hasText: nm('Frame cards') });
    await expect(row).toBeVisible();
    const stage = row.locator('td[data-label="Stage"]');
    await expect(stage).toHaveCount(1);
    expect(await stage.evaluate((cell) => getComputedStyle(cell, '::before').content)).toContain('Stage');
    expect(await stage.evaluate((cell) => getComputedStyle(cell).display)).toBe('block');
    const widths = await adminPage.locator('crm-search-display-table').evaluate((host) => ({ host: host.scrollWidth, page: document.documentElement.clientWidth }));
    expect(widths.host).toBeLessThanOrEqual(widths.page);
    // The row buttons are at least 44 px high.
    const edit = await row.getByRole('link', { name: /^Edit\b/ }).boundingBox();
    expect(edit!.height).toBeGreaterThanOrEqual(44);
  });

  test('Back from an Edit Position form returns to the same election\'s positions, and editing again stays in that election', async ({ adminPage }) => {
    const name = nm('Frame back');
    const id = createElection(name);
    addPosition(id, 'Chair', 1);
    addPosition(id, 'Treasurer', 2);
    // Another election with a position of its own: it must never appear in this election's list.
    const other = createElection(nm('Frame other'));
    addPosition(other, 'Other chair', 1);

    await adminPage.goto(civiAdminUrl('civicrm/elections/overview', { reset: 1, eid: id }));
    await adminPage.getByRole('link', { name: 'Positions', exact: true }).click();
    await expect(adminPage.getByRole('link', { name: 'Edit Chair', exact: true })).toBeVisible();
    await adminPage.getByRole('link', { name: 'Edit Chair', exact: true }).click();
    await expect(adminPage.getByRole('heading', { name: 'Edit Position' })).toBeVisible();
    await adminPage.getByRole('link', { name: 'Back', exact: true }).click();

    // The list is of this election (the election is in the address, though the referrer had none).
    await expect(adminPage).toHaveURL(new RegExp(`eid=${id}`));
    await expect(adminPage.getByRole('link', { name: 'Edit Chair', exact: true })).toBeVisible();
    await expect(adminPage.getByText('Other chair')).toHaveCount(0);
    await expect(adminPage.locator('.crm-container .breadcrumb').getByRole('link', { name })).toBeVisible();
    // Editing again opens the same election's position.
    await adminPage.getByRole('link', { name: 'Edit Treasurer', exact: true }).click();
    await expect(adminPage.getByRole('heading', { name: 'Edit Position' })).toBeVisible();
    await expect(adminPage.locator('.crm-container .breadcrumb').getByRole('link', { name })).toBeVisible();
  });

  test('a list opened with no election says so instead of showing every election', async ({ adminPage }) => {
    await adminPage.goto(civiAdminUrl('civicrm/elections/position-list', { reset: 1 }));
    await expect(adminPage.locator('.election-no-election')).toContainText('belongs to one election');
    await expect(adminPage.locator('.election-list-table')).toBeHidden();
  });

  test('a list opened with the election only in the query finds it', async ({ adminPage }) => {
    const name = nm('Frame query');
    const id = createElection(name);
    addPosition(id, 'Chair', 1);
    await adminPage.goto(civiAdminUrl('civicrm/elections/position-list', { reset: 1, eid: id }));
    await expect(adminPage.getByRole('link', { name: 'Edit Chair', exact: true })).toBeVisible();
    await expect(adminPage.locator('.election-no-election')).toHaveCount(0);
  });

  test('Submit and the back buttons are the same size', async ({ adminPage }) => {
    const id = createElection(nm('Frame buttons'));
    await adminPage.goto(civiAdminUrl('civicrm/elections/candidates/add', { reset: 1, eid: id }));
    const sizes = await adminPage.locator('.crm-submit-buttons .crm-button, .crm-submit-buttons a.button, .crm-submit-buttons button').evaluateAll((els) => els.map((e) => {
      const box = e.getBoundingClientRect();
      const style = getComputedStyle(e);
      return { text: (e.textContent || '').trim(), height: Math.round(box.height), font: style.fontSize, weight: style.fontWeight };
    }));
    expect(sizes.length).toBeGreaterThanOrEqual(3);
    for (const size of sizes) {
      expect(size.height, size.text).toBe(sizes[0].height);
      expect(size.font, size.text).toBe(sizes[0].font);
      expect(size.weight, size.text).toBe(sizes[0].weight);
    }
  });
});
