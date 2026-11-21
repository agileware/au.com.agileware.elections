import { test, expect, type Page } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import fs from 'node:fs';
import path from 'node:path';
import { advanceCouncil, councilNominationIds, moveToStage, reseedCouncil } from '../../fixtures/civi';
import { council, personaUrl } from '../../fixtures/personas';
import { CMS } from '../../fixtures/cms';
import { expectNoPhpOutput } from '../../fixtures/base';

/**
 * One walk through every page of the election process, stage by stage, on the CMS's default theme
 * (CMS=wordpress|drupal|joomla), with two checks on each page (2.2 merged what were the layout and accessibility specs,
 * which walked the same pages one after the other):
 *  - layout, at phone, tablet and desktop width: no PHP output, the page does not scroll sideways, nothing sticks out
 *    of the election area, and every election button has its text centred and readable and is at least 36px high;
 *  - accessibility: axe (WCAG 2.1 A and AA) on the election content, no serious or critical violations (the theme's
 *    own header, menus and footer are not the extension's and are left out), and by keyboard every election button
 *    can be reached with Tab, shows a focus indicator and works with Enter.
 * Every problem is collected and reported at the end, so one does not hide the others.
 *
 * WALK_CHECKS=layout or a11y runs one of the two (default: both). A11Y_WIDTHS (for example 375,768,1280) sets the
 * widths axe scans, default 1280; the keyboard checks run at the first. THEME_VARIANT labels the results file of a
 * theme variation (fixtures/theme-variants.sh). FORCED_COLORS=active checks the pages as Windows high contrast shows
 * them (the browser's forced colours): the keyboard checks then also prove the focus indicator survives, and axe's
 * contrast rule is left out, because the browser draws the colours.
 */
// One retry: a blip would otherwise lose the whole walk. A retry starts again from a reseeded election.
test.describe.configure({ mode: 'serial', retries: 1 });

const CHECKS = process.env.WALK_CHECKS || 'all';
const LAYOUT = CHECKS === 'all' || CHECKS === 'layout';
const A11Y = CHECKS === 'all' || CHECKS === 'a11y';
const LAYOUT_WIDTHS = [375, 768, 1280];
const OUT = path.resolve(__dirname, '../../test-results/axe');
const A11Y_WIDTHS = (process.env.A11Y_WIDTHS || '1280').split(',').map((w) => parseInt(w, 10));
const VARIANT = process.env.THEME_VARIANT || 'default';
const FORCED = process.env.FORCED_COLORS === 'active' ? 'active' : 'none';

/** Layout problems on the page (none when it fits): see the header for what is checked. */
async function layoutProblems(page: Page): Promise<string[]> {
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
      // The text a person sees: not the words only a screen reader gets (they are clipped to 1px but their text overflows).
      const visible: Range[] = [];
      const walker = document.createTreeWalker(b, NodeFilter.SHOW_TEXT);
      for (let node = walker.nextNode(); node; node = walker.nextNode()) {
        if (!(node.textContent || '').trim() || (node.parentElement && node.parentElement.closest('.election-visually-hidden'))) continue;
        const piece = document.createRange();
        piece.selectNodeContents(node);
        visible.push(piece);
      }
      const rects = visible.map((v) => v.getBoundingClientRect());
      const t = rects.length
        ? { left: Math.min(...rects.map((x) => x.left)), right: Math.max(...rects.map((x) => x.right)), top: Math.min(...rects.map((x) => x.top)), bottom: Math.max(...rects.map((x) => x.bottom)) }
        : { left: r.left, right: r.right, top: r.top, bottom: r.bottom };
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
  return result;
}


type Found = { page: string; id: string; impact: string; help: string; nodes: string[] };
const found: Found[] = [];

async function axeScan(page: Page, label: string) {
  let builder = new AxeBuilder({ page })
    .include('.crm-container')
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']);
  if (FORCED === 'active') {
    // The browser draws forced colours itself; axe measures the page's own colours, which are not shown.
    builder = builder.disableRules(['color-contrast']);
  }
  const result = await builder.analyze();
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


test('every election page fits and is accessible, stage by stage', async ({ browser }) => {
  test.setTimeout(30 * 60 * 1000);
  reseedCouncil();
  const layout: string[] = [];
  const keyboardProblems: string[] = [];
  const widths = [...new Set([...(LAYOUT ? LAYOUT_WIDTHS : []), ...(A11Y ? A11Y_WIDTHS : [])])].sort((a, b) => a - b);

  async function visit(label: string, persona: string, civiPath: string, params: Record<string, string | number> = {}, keyboard?: (page: Page) => Promise<void>) {
    let keyboardDone = false;
    for (const width of widths) {
      const context = await browser.newContext({ viewport: { width, height: 900 }, ignoreHTTPSErrors: true, forcedColors: FORCED });
      const page = await context.newPage();
      await page.goto(personaUrl(persona, civiPath, params));
      await page.waitForLoadState('networkidle');
      await expectNoPhpOutput(page);
      if (LAYOUT && LAYOUT_WIDTHS.includes(width)) {
        layout.push(...(await layoutProblems(page)).map((p) => `${label} at ${width}px: ${p}`));
      }
      if (A11Y && A11Y_WIDTHS.includes(width)) {
        await axeScan(page, A11Y_WIDTHS.length > 1 ? `${label} @${width}px` : label);
        if (keyboard && !keyboardDone && width === A11Y_WIDTHS[0]) {
          keyboardDone = true;
          await keyboard(page).catch((e) => keyboardProblems.push(`${label}: ${e.message.split('\n')[0]}`));
        }
      }
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
  await visit('nominations: election list', 'V01', 'civicrm/elections', {}, buttonsByKeyboard('election list'));
  await visit('nominations: nominate form', 'V01', 'civicrm/elections/nominations/create');
  await visit('nominations: outsider refused', 'OUT', 'civicrm/elections/nominations/create');
  await visit('nominations: unknown election', 'V01', 'civicrm/elections/view', { eid: 987654 });
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

  const blocking = found.filter((f) => f.impact === 'serious' || f.impact === 'critical');
  if (A11Y) {
    fs.mkdirSync(OUT, { recursive: true });
    fs.writeFileSync(path.join(OUT, `${CMS}${VARIANT === 'default' ? '' : '-' + VARIANT}${FORCED === 'active' ? '-forced' : ''}.json`), JSON.stringify({ axe: found, keyboard: keyboardProblems }, null, 2));
  }
  expect(layout, `layout problems on ${CMS}`).toEqual([]);
  const summary = [...blocking.map((f) => `${f.impact} ${f.id} on ${f.page}: ${f.help} [${f.nodes[0]}]`), ...keyboardProblems];
  expect(summary, `accessibility problems on ${CMS} (all findings in test-results/axe/${CMS}.json)`).toEqual([]);
});
