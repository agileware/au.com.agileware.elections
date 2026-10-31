import { test, expect, civiAdminUrl } from '../../fixtures/base';
import { advanceCouncil, civiApi4, civiSql, reseedCouncil } from '../../fixtures/civi';
import { council, personaUrl } from '../../fixtures/personas';

/**
 * The look of the results page: a logo, header and footer text, and a celebration for a few days after the
 * results are published. Set on the Results page form (which works while the election is running) and shown to
 * people on a personal link.
 */
test.describe.configure({ mode: 'serial', retries: 0 });

// A 4 x 4 PNG.
const PNG = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAQAAAAECAIAAAAmkwkpAAAAEUlEQVR4nGN4sEABjhiI4wAAsFIaAdpB+EoAAAAASUVORK5CYII=', 'base64');

let electionId = 0;

const setResultDate = (sql: string) => civiSql(`UPDATE civicrm_election SET result_date = ${sql} WHERE id = ${electionId}`);

test.beforeAll(() => {
  reseedCouncil();
  electionId = council().election_id;
  for (const step of ['fill-nominations', 'to-advertise', 'fill-responses', 'to-voting', 'fill-ballots', 'to-results', 'generate']) {
    advanceCouncil(step);
  }
});

test.afterAll(() => {
  reseedCouncil();
});

test('the Results page form works on a running election and saves the logo, header and footer', async ({ adminPage }) => {
  await adminPage.goto(civiAdminUrl('civicrm/elections/results-display', { reset: 1, eid: electionId }));
  await expect(adminPage.getByRole('heading', { name: /Results page for/ })).toBeVisible();
  await adminPage.getByLabel('Header text').fill('<b>Congratulations</b> to our new council');
  await adminPage.getByLabel('Footer text').fill('Thank you for voting.\nSee you at the AGM.');
  await adminPage.locator('input[name="results_logo"]').setInputFiles({ name: 'logo.png', mimeType: 'image/png', buffer: PNG });
  // A logo needs a description.
  await adminPage.getByRole('button', { name: 'Save' }).click();
  await expect(adminPage.getByText('Describe the logo for people who cannot see it.').first()).toBeVisible();
  await adminPage.locator('input[name="results_logo"]').setInputFiles({ name: 'logo.png', mimeType: 'image/png', buffer: PNG });
  await adminPage.getByLabel('Logo description').fill('Council crest');
  await adminPage.getByRole('button', { name: 'Save' }).click();
  await expect(adminPage.getByText('The results page has been saved.')).toBeVisible();
  const [row] = civiApi4<any[]>('Election.get', { select: ['results_header_text', 'results_logo_file_id', 'results_logo_alt'], where: [['id', '=', electionId]] });
  expect(row.results_header_text).toBe('<b>Congratulations</b> to our new council');
  expect(row.results_logo_file_id).toBeTruthy();
  expect(row.results_logo_alt).toBe('Council crest');
  civiApi4('Election.update', { values: { results_footer_text: 'Thank you for voting.\nSee you at the AGM.' }, where: [['id', '=', electionId]] });
});

test('a refused picture is explained', async ({ adminPage }) => {
  await adminPage.goto(civiAdminUrl('civicrm/elections/results-display', { reset: 1, eid: electionId }));
  await adminPage.locator('input[name="results_logo"]').setInputFiles({ name: 'logo.svg', mimeType: 'image/svg+xml', buffer: Buffer.from('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>') });
  await adminPage.getByLabel('Logo description').fill('A picture');
  await adminPage.getByRole('button', { name: 'Save' }).click();
  await expect(adminPage.getByText('The logo must be a PNG, JPEG, GIF or WebP picture.').first()).toBeVisible();
});

test('people on a personal link see the logo, header and footer as plain text', async ({ personaGoto }) => {
  const page = await personaGoto('V03', 'civicrm/elections/view');
  const header = page.locator('.election-results-header');
  await expect(header).toBeVisible();
  await expect(header.locator('.election-results-header-text')).toContainText('<b>Congratulations</b> to our new council');
  await expect(header.locator('b')).toHaveCount(0);
  const logo = header.locator('img.election-results-logo');
  await expect(logo).toHaveAttribute('alt', 'Council crest');
  expect(await logo.evaluate((img: HTMLImageElement) => img.complete && img.naturalWidth > 0)).toBe(true);
  await expect(page.locator('.election-results-footer')).toContainText('Thank you for voting.');
  await expect(page.locator('.election-results-footer')).toContainText('See you at the AGM.');
  // The summary page carries the same header and footer.
  const summary = await personaGoto('V03', 'civicrm/elections/summary');
  await expect(summary.locator('.election-results-header-text')).toBeVisible();
  await expect(summary.locator('.election-results-footer')).toBeVisible();
  await expect(summary.locator('canvas.election-celebration-canvas')).toHaveCount(0);
});

test('the logo is served only when the results are published', async ({ personaGoto, allowedProblems }) => {
  allowedProblems.push(/404/);
  const page = await personaGoto('V03', 'civicrm/elections/view');
  const src = await page.locator('img.election-results-logo').getAttribute('src');
  expect(src).toBeTruthy();
  expect((await page.request.get(src!)).status()).toBe(200);
  setResultDate("DATE_ADD(NOW(), INTERVAL 2 DAY)");
  try {
    const hidden = await page.request.get(src!);
    expect(hidden.status()).toBe(404);
  }
  finally {
    setResultDate('DATE_SUB(NOW(), INTERVAL 1 HOUR)');
  }
});

test.describe('celebration', () => {
  test.beforeAll(() => {
    civiApi4('Election.update', { values: { celebration_enabled: true, celebration_effect: 'confetti', celebration_days: 5 }, where: [['id', '=', electionId]] });
    setResultDate('DATE_SUB(NOW(), INTERVAL 1 DAY)');
  });

  test('plays every time the page is opened within the days, and can be skipped', async ({ persona }) => {
    for (let visit = 0; visit < 2; visit++) {
      const page = await persona('V04');
      await page.goto(personaUrl('V04', 'civicrm/elections/view'));
      const canvas = page.locator('canvas.election-celebration-canvas');
      await expect(canvas).toBeVisible();
      await expect(canvas).toHaveAttribute('aria-hidden', 'true');
      // It never blocks clicks on the page.
      expect(await canvas.evaluate((el) => getComputedStyle(el).pointerEvents)).toBe('none');
      await page.getByRole('button', { name: 'Skip the celebration' }).click();
      await expect(canvas).toHaveCount(0);
      await page.context().close();
    }
  });

  test('stops by itself and shows nothing once the days are over', async ({ persona }) => {
    setResultDate('DATE_SUB(NOW(), INTERVAL 6 DAY)');
    try {
      const page = await persona('V05');
      await page.goto(personaUrl('V05', 'civicrm/elections/view'));
      await expect(page.locator('.election-results-header')).toBeVisible();
      await page.waitForTimeout(800);
      await expect(page.locator('canvas.election-celebration-canvas')).toHaveCount(0);
      expect(await page.evaluate(() => typeof (window as any).ElectionCelebrate)).toBe('undefined');
    }
    finally {
      setResultDate('DATE_SUB(NOW(), INTERVAL 1 DAY)');
    }
  });

  test('shows a short message instead when the device asks for reduced motion', async ({ persona }) => {
    const page = await persona('V06');
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.goto(personaUrl('V06', 'civicrm/elections/view'));
    await expect(page.locator('.election-celebration-banner')).toBeVisible();
    await expect(page.locator('canvas.election-celebration-canvas')).toHaveCount(0);
  });

  test('every effect can be drawn without errors', async ({ persona }) => {
    const page = await persona('V07');
    await page.goto(personaUrl('V07', 'civicrm/elections/view'));
    await page.getByRole('button', { name: 'Skip the celebration' }).click();
    for (const effect of ['confetti', 'fireworks', 'balloons', 'sparkle', 'ticker']) {
      const shown = await page.evaluate((name) => (window as any).ElectionCelebrate.play(name), effect);
      expect(shown, effect).toBe(true);
      await page.waitForTimeout(700);
      await expect(page.locator('canvas.election-celebration-canvas')).toHaveCount(1);
      await page.keyboard.press('Escape');
      await expect(page.locator('canvas.election-celebration-canvas')).toHaveCount(0);
    }
  });

  test('is off when the election does not celebrate', async ({ persona }) => {
    civiApi4('Election.update', { values: { celebration_enabled: false }, where: [['id', '=', electionId]] });
    try {
      const page = await persona('V08');
      await page.goto(personaUrl('V08', 'civicrm/elections/view'));
      await expect(page.locator('.election-results-header')).toBeVisible();
      await page.waitForTimeout(800);
      await expect(page.locator('canvas.election-celebration-canvas')).toHaveCount(0);
    }
    finally {
      civiApi4('Election.update', { values: { celebration_enabled: true }, where: [['id', '=', electionId]] });
    }
  });
});

test('the form previews the effect and only asks for the options when it is on', async ({ adminPage }) => {
  await adminPage.goto(civiAdminUrl('civicrm/elections/create', { reset: 1 }));
  await adminPage.getByRole('tab', { name: 'Display' }).click();
  const toggle = adminPage.getByLabel('Display celebration effects on the Election results page');
  await toggle.uncheck();
  await expect(adminPage.getByLabel('Celebration effect', { exact: true })).toBeHidden();
  await toggle.check();
  await expect(adminPage.getByLabel('Celebration effect', { exact: true })).toBeVisible();
  await expect(adminPage.getByLabel('Celebration lasts (days)')).toHaveValue('5');
  await adminPage.getByRole('button', { name: 'Preview' }).click();
  await expect(adminPage.locator('canvas.election-celebration-canvas')).toBeVisible();
  await adminPage.getByRole('button', { name: 'Skip the celebration' }).click();
  await expect(adminPage.locator('canvas.election-celebration-canvas')).toHaveCount(0);
  // Days outside 1 to 30 are explained.
  await adminPage.getByLabel('Celebration lasts (days)').fill('45');
  await adminPage.getByRole('button', { name: 'Submit' }).click();
  await expect(adminPage.getByText('Enter a whole number of days from 1 to 30.').first()).toBeVisible();
});
