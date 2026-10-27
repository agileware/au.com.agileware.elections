import { test, expect, type Page } from '@playwright/test';
import { advanceCouncil, councilNominationIds, moveToStage, reseedCouncil } from '../../fixtures/civi';
import { council, personaUrl } from '../../fixtures/personas';
import { expectNoPhpOutput } from '../../fixtures/base';

/**
 * Every page of the election process, stage by stage, on the CMS's default theme (CMS=wordpress|drupal|joomla)
 * at phone, tablet and desktop width: the page must not scroll sideways, nothing may overflow its card,
 * and every election button must have its text centred and readable.
 */
test.describe.configure({ mode: 'serial', retries: 0 });

const WIDTHS = [375, 768, 1280];

async function checkPage(page: Page, label: string) {
  await expectNoPhpOutput(page);
  const result = await page.evaluate(() => {
    const problems: string[] = [];
    const doc = document.documentElement;
    if (doc.scrollWidth > doc.clientWidth + 1) problems.push(`page scrolls sideways (${doc.scrollWidth} > ${doc.clientWidth})`);
    const root = document.querySelector('.crm-container') as HTMLElement | null;
    if (root) {
      const box = root.getBoundingClientRect();
      root.querySelectorAll<HTMLElement>('img, select, textarea, input[type=text], .election-btn').forEach((el) => {
        const r = el.getBoundingClientRect();
        if (r.width && (r.right > box.right + 1 || r.left < box.left - 1)) problems.push(`${el.tagName.toLowerCase()}.${el.className} sticks out of the election area`);
      });
    }
    document.querySelectorAll<HTMLElement>('.election-btn, .crm-election-form-block-container button.crm-button').forEach((b) => {
      const text = (b.textContent || '').trim();
      const r = b.getBoundingClientRect();
      const range = document.createRange();
      range.selectNodeContents(b);
      const t = range.getBoundingClientRect();
      const cs = getComputedStyle(b);
      const dx = Math.abs((t.left + t.right) / 2 - (r.left + r.right) / 2);
      const dy = Math.abs((t.top + t.bottom) / 2 - (r.top + r.bottom) / 2);
      // Buttons with an icon are not centred on the text alone, so allow for it.
      if (!b.querySelector('i') && dx > 3) problems.push(`button "${text}" text is ${dx.toFixed(1)}px off centre horizontally`);
      if (dy > 3) problems.push(`button "${text}" text is ${dy.toFixed(1)}px off centre vertically`);
      if (r.height < 36) problems.push(`button "${text}" is only ${r.height.toFixed(0)}px high`);
      if (cs.backgroundColor === cs.color) problems.push(`button "${text}" text is the same colour as its background`);
    });
    return problems;
  });
  expect(result, label).toEqual([]);
}

test('election pages fit and buttons are centred at every width', async ({ browser }) => {
  test.setTimeout(20 * 60 * 1000);
  reseedCouncil();

  async function visit(label: string, persona: string, civiPath: string, params: Record<string, string | number> = {}) {
    for (const width of WIDTHS) {
      const context = await browser.newContext({ viewport: { width, height: 900 }, ignoreHTTPSErrors: true });
      const page = await context.newPage();
      await page.goto(personaUrl(persona, civiPath, params));
      await page.waitForLoadState('networkidle');
      await checkPage(page, `${label} at ${width}px`);
      await context.close();
    }
  }

  await visit('nominations: election page', 'V01', 'civicrm/elections/view');
  await visit('nominations: nominate form', 'V01', 'civicrm/elections/nominations/create');
  await visit('nominations: outsider refused', 'OUT', 'civicrm/elections/nominations/create');
  advanceCouncil('fill-nominations');
  await visit('nominations: with nominations', 'V03', 'civicrm/elections/view');
  const ids = councilNominationIds(council().election_id);
  await visit('nominations: second form', 'V12', 'civicrm/elections/nominations/create', { enid: ids['President|N08'] });

  moveToStage('advertise');
  await visit('advertise: nominee page', 'N01', 'civicrm/elections/view');
  await visit('advertise: accept form', 'N01', 'civicrm/elections/nominations/accept', { enid: ids['President|N01'] });
  await visit('advertise: withdraw form', 'N03', 'civicrm/elections/nominations/reject', { enid: ids['President|N03'] });
  advanceCouncil('fill-responses');
  await visit('advertise: candidates', 'V05', 'civicrm/elections/view');
  await visit('advertise: candidate page', 'V05', 'civicrm/elections/candidate', { enid: ids['President|N01'] });

  moveToStage('voting');
  await visit('voting: election page', 'V29', 'civicrm/elections/view');
  await visit('voting: ballot', 'V29', 'civicrm/elections/vote');
  advanceCouncil('fill-ballots');
  await visit('voting: after voting', 'V01', 'civicrm/elections/view');
  await visit('voting: outsider refused', 'OUT', 'civicrm/elections/vote');

  moveToStage('results');
  advanceCouncil('generate');
  await visit('results: election page', 'V05', 'civicrm/elections/view');
  await visit('results: summary', 'V05', 'civicrm/elections/summary');

  reseedCouncil();
});
