import { test, expect, civiAdminUrl } from '../fixtures/base';
import type { Locator, Page } from '@playwright/test';
import { advanceCouncil, civiApi4, civiEval, moveToStage, reseedCouncil } from '../fixtures/civi';
import { personaUrl } from '../fixtures/personas';
import { addPosition, createElection, removeRunData } from '../fixtures/data';
import fs from 'node:fs';
import users from '../fixtures/test-users.json';
import path from 'node:path';

/**
 * The pictures of the user guide (docs/images/), taken from the real screens, so they can be made again after a
 * screen changes:
 *
 *   BASE_URL=http://localhost npm run docs-images        (WordPress; the guide's pictures are of WordPress)
 *
 * Pictures are written straight into docs/images/<folder>/<nn>.png of the extension the tool runs from (on a DDEV site,
 * the deployed copy: copy them back to the repository).
 * This is a tool, not a test: it is not in specs/ and the suite never runs it. The control each step is about is
 * marked with an outline. People on the front end are personas on personal links (the address bar is never in the
 * picture); the back end is the test administrator's session.
 */
test.describe.configure({ mode: 'serial', retries: 0 });

// Straight into the guide's folder (Playwright empties test-results at the start of every run).
const OUT = path.resolve(__dirname, '../../../docs/images');
const WIDTH = 1024;
// The back end is wider: below about 1200 px the Manage Elections table scrolls in its own box, hiding the row buttons.
const ADMIN_WIDTH = 1280;

/** The control the next picture is about: the picture keeps it in view. */
let marked: Locator | null = null;

/** Outline the control a step is about. */
async function mark(target: Locator) {
  marked = target.first();
  await target.first().scrollIntoViewIfNeeded();
  await target.first().evaluate((el) => {
    (el as HTMLElement).style.outline = '3px solid #e8590c';
    (el as HTMLElement).style.outlineOffset = '3px';
  });
}

/**
 * A picture of the election's part of the page (CiviCRM's container, or a dialog), at most 900 px high from its top,
 * or from the marked control when that is lower down.
 */
async function shot(page: Page, file: string, options: { region?: Locator; focus?: Locator; height?: number; include?: Locator } = {}) {
  const region = (options.region ?? page.locator('.crm-container:visible').first());
  await region.waitFor();
  await page.waitForLoadState('networkidle').catch(() => {});
  // WordPress's own notices (for example "WordPress 7.1.5 is available!") are about the test site, not the guide.
  await page.addStyleTag({ content: '.update-nag, #wpbody-content > .notice { display: none !important; }' });
  await page.waitForTimeout(300);
  // Boxes are measured from the window, the full-page picture from the top of the page: add the scroll.
  const scroll = await page.evaluate(() => ({ x: window.scrollX, y: window.scrollY }));
  const onPage = (b: { x: number; y: number; width: number; height: number }) => ({ ...b, x: b.x + scroll.x, y: b.y + scroll.y });
  const box = onPage((await region.boundingBox())!);
  const maxHeight = options.height ?? 900;
  let top = box.y;
  const focus = options.focus ?? marked;
  marked = null;
  if (focus) {
    const f = onPage((await focus.first().boundingBox())!);
    if (f.y + f.height > top + maxHeight) top = Math.max(box.y, f.y - 200);
  }
  let height = Math.min(maxHeight, box.y + box.height - top);
  let left = box.x;
  let width = box.width;
  // Something outside the region that the picture is about, such as CiviCRM's pop-up message at the top right.
  if (options.include) {
    const extra = onPage((await options.include.first().boundingBox())!);
    const newTop = Math.min(top, extra.y - 10);
    height += top - newTop;
    top = newTop;
    const right = Math.max(left + width, extra.x + extra.width + 10);
    left = Math.min(left, extra.x - 10);
    width = right - left;
  }
  const out = path.join(OUT, file);
  fs.mkdirSync(path.dirname(out), { recursive: true });
  await page.screenshot({ path: out, clip: { x: left, y: top, width, height }, fullPage: true });
}

async function adminAt(page: Page, civiPath: string, params: Record<string, string | number> = {}, hash = '') {
  await page.setViewportSize({ width: ADMIN_WIDTH, height: 900 });
  await page.goto(civiAdminUrl(civiPath, { reset: 1, ...params }) + hash);
  await page.waitForLoadState('networkidle');
  // A list's Filters panel opens by default and would fill the picture: close it.
  const filters = page.locator('details.af-container[open] > summary');
  if (await filters.count()) {
    await filters.first().click();
  }
}

/** The election the back end pictures show, made for them and deleted afterwards. */
let shownElection = 0;
/** The site's WordPress time zone before the pictures, put back afterwards. */
let previousTimeZone = '';

test.beforeAll(() => {
  // Elections left by earlier test runs (their names start with "PW ") would show in the lists.
  removeRunData('PW %');
  // The pages say "Times are shown in ..." and show the times in it: use the time zone of the guide's readers, not
  // the UTC of a new site. WordPress only (the tool is for WordPress).
  previousTimeZone = civiEval("echo get_option('timezone_string');").trim();
  civiEval("update_option('timezone_string', 'Australia/Sydney');");
});

test.afterAll(() => {
  civiEval(`update_option('timezone_string', ${JSON.stringify(previousTimeZone)});`);
  if (shownElection) {
    civiApi4('Election.delete', { where: [['id', '=', shownElection]] });
  }
  reseedCouncil();
});

test('the pictures of the back end', async ({ adminPage: page }) => {
  test.setTimeout(10 * 60 * 1000);
  const electionName = 'Annual General Meeting 2027';
  const id = createElection(electionName);
  shownElection = id;
  const president = addPosition(id, 'President', 1);
  addPosition(id, 'Treasurer', 2);
  addPosition(id, 'Committee member', 3, 3);

  // Create an election: Manage Elections, Add Election, the Details tab, the Dates tab.
  await adminAt(page, 'civicrm/elections/manage');
  await mark(page.getByRole('link', { name: 'Add Election' }));
  await shot(page, 'admin_election/01.png', { height: 620 });
  await adminAt(page, 'civicrm/elections/create');
  await shot(page, 'admin_election/02.png', { height: 620 });
  await page.locator('#name').fill('Annual General Meeting 2028');
  await page.locator('#description').fill('The committee for the year ahead: President, Treasurer and three committee members.');
  await mark(page.locator('#name'));
  await shot(page, 'admin_election/03.png', { height: 620 });
  await page.getByRole('tab', { name: 'Dates' }).click();
  await page.locator('#quick_cycle').selectOption('2-3');
  await page.evaluate(() => {
    // @ts-ignore CRM is CiviCRM's global
    CRM.$('input[name="quick_start"]').val('2028-03-02 09:00:00').trigger('change');
  });
  await page.locator('#election-quick-fill').click();
  await mark(page.locator('#election-quick-fill'));
  await shot(page, 'admin_election/04.png');

  // Positions: the list with Add Position, then the form filled in step by step.
  await adminAt(page, 'civicrm/elections/position-list', {}, `#/?eid=${id}`);
  await page.locator('crm-search-display-table tbody tr').first().waitFor();
  await mark(page.getByRole('link', { name: 'Add Position' }));
  await shot(page, 'admin_add/01.png', { height: 620 });
  await adminAt(page, 'civicrm/elections/positions/create', { eid: id });
  await page.getByLabel(/^\s*Name\s*\*?\s*$/).fill('Secretary');
  await mark(page.getByLabel(/^\s*Name\s*\*?\s*$/));
  await shot(page, 'admin_add/02.png', { height: 620 });
  await page.getByLabel(/^\s*Seats\s*\*?\s*$/).fill('1');
  await mark(page.getByLabel(/^\s*Seats\s*\*?\s*$/));
  await shot(page, 'admin_add/03.png', { height: 620 });
  await page.getByLabel('Description').fill('Keeps the minutes and the records of the association.');
  await mark(page.getByLabel('Description'));
  await shot(page, 'admin_add/04.png', { focus: page.getByLabel('Description') });

  // Edit a position: Edit on its row, then the saved form.
  await adminAt(page, 'civicrm/elections/position-list', {}, `#/?eid=${id}`);
  const presidentRow = page.locator('crm-search-display-table tbody tr', { hasText: 'President' }).first();
  await presidentRow.waitFor();
  await mark(presidentRow.getByRole('link', { name: /^Edit/ }));
  await shot(page, 'admin_edit/01.png', { height: 620 });
  await adminAt(page, 'civicrm/elections/positions/create', { eid: id, epid: president });
  await page.getByLabel('Description').fill('Chairs the meetings and speaks for the association.');
  await page.getByRole('button', { name: 'Submit' }).click();
  // CiviCRM shows "Position saved." in a pop-up at the top right, above the list: keep it in the picture.
  const saved = page.locator('#crm-notification-container .ui-notify-message', { hasText: 'Position saved.' });
  await expect(saved).toBeVisible();
  await shot(page, 'admin_edit/02.png', { height: 620, include: saved });

  // Edit an election: Edit on its row in Manage Elections.
  await adminAt(page, 'civicrm/elections/manage');
  const row = page.locator('crm-search-display-table tbody tr', { hasText: electionName }).first();
  await row.waitFor();
  await mark(row.getByRole('link', { name: /^Edit/ }));
  await shot(page, 'admin_edit/03.png', { focus: row, height: 620 });

  // Delete a position: the Positions list, then the Delete Position dialog.
  await adminAt(page, 'civicrm/elections/position-list', {}, `#/?eid=${id}`);
  const treasurerRow = page.locator('crm-search-display-table tbody tr', { hasText: 'Treasurer' }).first();
  await treasurerRow.waitFor();
  await mark(treasurerRow.getByRole('link', { name: /^Delete/ }));
  await shot(page, 'admin_delete/01.png', { height: 620 });
  await treasurerRow.getByRole('link', { name: /^Delete/ }).click();
  const positionDialog = page.locator('.ui-dialog:visible').first();
  await positionDialog.waitFor();
  await page.waitForLoadState('networkidle');
  await shot(page, 'admin_delete/02.png', { region: positionDialog });
  await positionDialog.getByRole('button', { name: 'Cancel' }).click();

  // Delete an election: the Delete Election dialog from its row.
  await adminAt(page, 'civicrm/elections/manage');
  const again = page.locator('crm-search-display-table tbody tr', { hasText: electionName }).first();
  await again.getByRole('link', { name: /^Delete/ }).click();
  const electionDialog = page.locator('.ui-dialog:visible').first();
  await electionDialog.waitFor();
  await page.waitForLoadState('networkidle');
  await shot(page, 'admin_delete/03.png', { region: electionDialog });
  await electionDialog.getByRole('button', { name: 'Cancel' }).click();
});

test('the pictures of the front end', async ({ browser }) => {
  test.setTimeout(10 * 60 * 1000);
  reseedCouncil();
  const context = await browser.newContext({ viewport: { width: WIDTH, height: 900 } });
  const page = await context.newPage();
  const goto = async (persona: string, civiPath: string, params: Record<string, string | number> = {}) => {
    await page.goto(personaUrl(persona, civiPath, params));
    await page.waitForLoadState('networkidle');
  };

  // Nominate: the election list, the election page, the form, the confirmation.
  await goto('V01', 'civicrm/elections');
  await mark(page.getByRole('link', { name: /^Nominate/ }));
  await shot(page, 'user_nominate/01.png', { height: 620 });
  await goto('V01', 'civicrm/elections/view');
  await mark(page.locator('.election-next-action').getByRole('link', { name: 'Nominate', exact: true }));
  await shot(page, 'user_nominate/02.png', { height: 620 });
  await goto('V01', 'civicrm/elections/nominations/create');
  await page.locator('#s2id_position').click();
  await page.locator('.select2-result-label', { hasText: /^Secretary$/ }).click();
  await page.locator('#s2id_contact').click();
  await page.locator('.select2-input:visible').fill('Nominee 03');
  await page.locator('.select2-result-label', { hasText: '03, Nominee' }).first().click();
  await page.getByLabel('Reason for the nomination').fill('Organised and fair: kept our records in order for two years.');
  await mark(page.getByLabel('Reason for the nomination'));
  await shot(page, 'user_nominate/03.png', { height: 700 });
  await page.getByRole('button', { name: 'Nominate' }).click();
  await expect(page.getByText('Nomination submitted.')).toBeVisible();
  await shot(page, 'user_nominate/04.png', { height: 620 });

  // Vote: the list, the election page, the ballot (ranked by drag and drop and the buttons), the review.
  advanceCouncil('fill-nominations');
  moveToStage('advertise');
  advanceCouncil('fill-responses');
  moveToStage('voting');
  await goto('V28', 'civicrm/elections');
  await mark(page.getByRole('link', { name: /^Vote/ }));
  await shot(page, 'user_vote/01.png', { height: 620 });
  await goto('V28', 'civicrm/elections/view');
  await mark(page.locator('.election-next-action').getByRole('link', { name: 'Vote', exact: true }));
  await shot(page, 'user_vote/02.png', { height: 620 });
  await goto('V28', 'civicrm/elections/vote');
  const block = page.locator('.crm-vote-election-form-block.election-rank-enhanced').first();
  await block.getByRole('button', { name: /^Add to Ranking/ }).first().click();
  await block.getByRole('button', { name: /^Add to Ranking/ }).first().click();
  await mark(block.locator('.election-rank-handle').first());
  await shot(page, 'user_vote/03.png', { focus: block, height: 760 });
  await page.locator('.crm-election-form-block-container button.crm-button[type=submit]').click();
  const review = page.getByRole('dialog');
  await review.waitFor();
  await shot(page, 'user_vote/04.png', { region: review });
  await review.getByRole('button', { name: 'Go Back and Change' }).click();

  // Results: the list, the results, the summary.
  advanceCouncil('fill-ballots');
  moveToStage('results');
  advanceCouncil('generate');
  await goto('V05', 'civicrm/elections');
  await mark(page.getByRole('link', { name: /^View Results/ }));
  await shot(page, 'user_view_results/01.png', { height: 620 });
  await goto('V05', 'civicrm/elections/view');
  await mark(page.getByRole('link', { name: 'View Election Summary' }));
  await shot(page, 'user_view_results/02.png');
  await goto('V05', 'civicrm/elections/summary');
  await shot(page, 'user_view_results/03.png');
  await context.close();
});

test('the pictures of the set-up pages (WordPress block editor)', async ({ adminPage: page }) => {
  test.setTimeout(5 * 60 * 1000);
  const { infoSlug, listingSlug } = users.pages;
  for (const [slug, file] of [[infoSlug, 'setup/election-page.png'], [listingSlug, 'setup/election-listing-page.png']] as const) {
    await page.setViewportSize({ width: WIDTH, height: 700 });
    // The page's id from the REST API (the election page itself answers 404 without an election), then its editor.
    const found = await (await page.request.get(`/wp-json/wp/v2/pages?slug=${slug}&_fields=id`)).json();
    const id = found[0]?.id;
    expect(id, slug).toBeTruthy();
    await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);
    const editor = page.frameLocator('iframe[name="editor-canvas"]');
    // Close the welcome guide if WordPress shows it.
    await page.getByRole('button', { name: 'Close' }).first().click({ timeout: 5000 }).catch(() => {});
    const html = editor.locator('[data-type="core/html"]').first();
    await html.waitFor({ timeout: 30000 });
    // The editor keeps drawing after the block appears (a grey panel was caught half faded in): let it settle. Not
    // "network idle": the block editor keeps polling, so that wait never ends.
    await page.waitForTimeout(2500);
    // Not selected: a selected block's toolbar would cover the page title.
    const out = path.join(OUT, file);
    fs.mkdirSync(path.dirname(out), { recursive: true });
    const title = editor.locator('.editor-post-title, h1').first();
    const top = (await title.boundingBox())!;
    const bottom = (await html.boundingBox())!;
    await page.screenshot({ path: out, clip: { x: Math.min(top.x, bottom.x) - 20, y: top.y - 30, width: Math.max(top.width, bottom.width) + 40, height: bottom.y + bottom.height - top.y + 60 } });
  }
});
