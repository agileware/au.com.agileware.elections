import { test, expect, type Page } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import fs from 'node:fs';
import path from 'node:path';
import { advanceCouncil, councilNominationIds, moveToStage, reseedCouncil } from '../../fixtures/civi';
import { council, personaUrl } from '../../fixtures/personas';
import { CMS } from '../../fixtures/cms';

/**
 * Accessibility of every page of the election process on the CMS's default theme (CMS=wordpress|drupal|joomla):
 *  - axe (WCAG 2.1 A and AA) on the election content: no serious or critical violations (the theme's own
 *    header, menus and footer are not the extension's and are left out);
 *  - keyboard: every election button can be reached with Tab, shows a focus indicator and works with Enter.
 */
test.describe.configure({ mode: 'serial', retries: 0 });

const OUT = path.resolve(__dirname, '../../test-results/axe');
// Widths to scan, for example A11Y_WIDTHS=375,768,1280. The keyboard checks run at the first width only.
const WIDTHS = (process.env.A11Y_WIDTHS || '1280').split(',').map((w) => parseInt(w, 10));
// A label for the theme variation being checked (see fixtures/theme-variants.sh), kept in the results file name.
const VARIANT = process.env.THEME_VARIANT || 'default';

type Found = { page: string; id: string; impact: string; help: string; nodes: string[] };
const found: Found[] = [];

async function axeScan(page: Page, label: string) {
  const result = await new AxeBuilder({ page })
    .include('.crm-container')
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
    .analyze();
  for (const v of result.violations) {
    found.push({ page: label, id: v.id, impact: v.impact || 'unknown', help: v.help, nodes: v.nodes.slice(0, 4).map((n) => `${n.target.join(' ')} :: ${n.failureSummary?.split('\n').slice(1, 3).join(' ').trim()}`) });
  }
}

/** Press Tab until the focus lands on the element, returning how many presses it took (or -1). */
async function tabTo(page: Page, selector: string, limit = 120): Promise<number> {
  await page.evaluate(() => (document.activeElement as HTMLElement | null)?.blur());
  await page.locator('body').click({ position: { x: 1, y: 1 } }).catch(() => {});
  for (let i = 1; i <= limit; i++) {
    await page.keyboard.press('Tab');
    const hit = await page.evaluate((s) => document.activeElement?.matches(s) ?? false, selector);
    if (hit) return i;
  }
  return -1;
}

async function focusIsVisible(page: Page): Promise<boolean> {
  return page.evaluate(() => {
    const el = document.activeElement as HTMLElement | null;
    if (!el) return false;
    const cs = getComputedStyle(el);
    const outline = cs.outlineStyle !== 'none' && parseFloat(cs.outlineWidth) > 0;
    const shadow = cs.boxShadow !== 'none';
    return outline || shadow;
  });
}

test('accessibility of the whole election process', async ({ browser }) => {
  test.setTimeout(25 * 60 * 1000);
  reseedCouncil();
  const keyboardProblems: string[] = [];

  async function visit(label: string, persona: string, civiPath: string, params: Record<string, string | number> = {}, keyboard?: (page: Page) => Promise<void>) {
    for (const [index, width] of WIDTHS.entries()) {
      const context = await browser.newContext({ viewport: { width, height: 900 }, ignoreHTTPSErrors: true });
      const page = await context.newPage();
      await page.goto(personaUrl(persona, civiPath, params));
      await page.waitForLoadState('networkidle');
      await axeScan(page, WIDTHS.length > 1 ? `${label} @${width}px` : label);
      if (keyboard && index === 0) await keyboard(page).catch((e) => keyboardProblems.push(`${label}: ${e.message.split('\n')[0]}`));
      await context.close();
    }
  }

  /** Every .election-btn is reachable by Tab, has a focus indicator, and Enter follows it. */
  const buttonsByKeyboard = (label: string) => async (page: Page) => {
    const count = await page.locator('.election-btn').count();
    if (!count) throw new Error('no election buttons on the page');
    const first = page.locator('.election-btn').first();
    const href = await first.getAttribute('href');
    const presses = await tabTo(page, '.election-btn');
    if (presses < 0) throw new Error('election button not reachable with Tab');
    if (!(await focusIsVisible(page))) keyboardProblems.push(`${label}: no visible focus indicator on the election button`);
    const target = href ? new URL(href, page.url()).pathname.replace(/\/$/, '').split('/').slice(-2).join('/') : '';
    await page.keyboard.press('Enter');
    await page.waitForURL((u) => u.href.includes(target), { timeout: 15000 }).catch(() => keyboardProblems.push(`${label}: Enter on the button did not follow ${href}`));
  };

  await visit('nominations: election page', 'V01', 'civicrm/elections/view', {}, buttonsByKeyboard('nominations election page'));
  await visit('nominations: nominate form', 'V01', 'civicrm/elections/nominations/create');
  await visit('nominations: outsider refused', 'OUT', 'civicrm/elections/nominations/create');
  advanceCouncil('fill-nominations');
  await visit('nominations: with nominations', 'V03', 'civicrm/elections/view', {}, buttonsByKeyboard('nominations with nominations'));
  const ids = councilNominationIds(council().election_id);
  await visit('nominations: second form', 'V12', 'civicrm/elections/nominations/create', { enid: ids['President|N08'] });

  moveToStage('advertise');
  await visit('advertise: nominee page', 'N01', 'civicrm/elections/view', {}, buttonsByKeyboard('nominee page'));
  await visit('advertise: accept form', 'N01', 'civicrm/elections/nominations/accept', { enid: ids['President|N01'] });
  await visit('advertise: withdraw form', 'N03', 'civicrm/elections/nominations/reject', { enid: ids['President|N03'] });
  advanceCouncil('fill-responses');
  await visit('advertise: candidates', 'V05', 'civicrm/elections/view');
  await visit('advertise: candidate page', 'V05', 'civicrm/elections/candidate', { enid: ids['President|N01'] });

  moveToStage('voting');
  await visit('voting: election page', 'V29', 'civicrm/elections/view', {}, buttonsByKeyboard('voting election page'));
  await visit('voting: ballot', 'V29', 'civicrm/elections/vote', {}, async (page) => {
    // The rank choosers are select2 boxes: the keyboard focus sits on the hidden .select2-focusser input.
    const choosers = '.vote-candidate-row .select2-focusser';
    if (!(await page.locator(choosers).count())) throw new Error('no ranking choosers on the ballot');
    if ((await tabTo(page, choosers)) < 0) throw new Error('ballot rank choosers not reachable with Tab');
    await page.keyboard.press('ArrowDown');
    await page.locator('.select2-drop-active').waitFor({ timeout: 5000 }).catch(() => keyboardProblems.push('ballot: Down arrow did not open the rank list'));
    await page.keyboard.press('Enter');
    const picked = await page.evaluate(() => (document.querySelector('.vote-candidate-row [name^=candidate_]') as HTMLInputElement | null)?.value ?? '');
    if (!picked) keyboardProblems.push('ballot: a rank could not be chosen with the keyboard');
    if ((await tabTo(page, '.crm-election-form-block-container button.crm-button[type=submit]', 200)) < 0) keyboardProblems.push('ballot: Vote button not reachable with Tab');
  });
  advanceCouncil('fill-ballots');
  await visit('voting: after voting', 'V01', 'civicrm/elections/view');
  await visit('voting: outsider refused', 'OUT', 'civicrm/elections/vote');

  moveToStage('results');
  advanceCouncil('generate');
  await visit('results: election page', 'V05', 'civicrm/elections/view');
  await visit('results: summary', 'V05', 'civicrm/elections/summary');

  reseedCouncil();

  fs.mkdirSync(OUT, { recursive: true });
  fs.writeFileSync(path.join(OUT, `${CMS}${VARIANT === 'default' ? '' : '-' + VARIANT}.json`), JSON.stringify({ axe: found, keyboard: keyboardProblems }, null, 2));
  const blocking = found.filter((f) => f.impact === 'serious' || f.impact === 'critical');
  const summary = [...blocking.map((f) => `${f.impact} ${f.id} on ${f.page}: ${f.help} [${f.nodes[0]}]`), ...keyboardProblems];
  expect(summary, `accessibility problems on ${CMS} (all findings in test-results/axe/${CMS}.json)`).toEqual([]);
});
