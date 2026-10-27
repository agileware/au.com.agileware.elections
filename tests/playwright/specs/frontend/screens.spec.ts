import { test } from '../../fixtures/base';
import { advanceCouncil, councilNominationIds, moveToStage, reseedCouncil } from '../../fixtures/civi';
import { council } from '../../fixtures/personas';
import { CMS } from '../../fixtures/cms';
import fs from 'node:fs';
import path from 'node:path';

/**
 * Screenshots of every page of the election process, stage by stage, at desktop, tablet and phone
 * width, for the CMS under test (CMS=wordpress|drupal|joomla). Used to check the front end on each
 * CMS's default theme. Only runs when SCREENS is set, for example:
 *
 *   SCREENS=1 CMS=drupal BASE_URL=http://localhost npx playwright test screens
 *
 * Pictures go to test-results/screens/<cms>/.
 */
test.skip(!process.env.SCREENS, 'Set SCREENS=1 to take screenshots of the election pages');
test.describe.configure({ mode: 'serial', retries: 0 });

const WIDTHS = [{ name: 'desktop', width: 1280, height: 900 }, { name: 'tablet', width: 768, height: 1000 }, { name: 'phone', width: 375, height: 800 }];
const OUT = path.resolve(__dirname, '../../test-results/screens', CMS);

test.beforeAll(() => {
  fs.mkdirSync(OUT, { recursive: true });
  reseedCouncil();
});

test('walk the whole election and take pictures', async ({ browser }) => {
  test.setTimeout(20 * 60 * 1000);
  const { personaUrl } = await import('../../fixtures/personas');

  async function shoot(stage: string, label: string, persona: string, civiPath: string, params: Record<string, string | number> = {}) {
    for (const size of WIDTHS) {
      const context = await browser.newContext({ viewport: { width: size.width, height: size.height } });
      const page = await context.newPage();
      await page.goto(personaUrl(persona, civiPath, params));
      await page.waitForLoadState('networkidle');
      await page.screenshot({ path: path.join(OUT, `${stage}-${label}-${size.name}.png`), fullPage: true });
      await context.close();
    }
  }

  let ids: Record<string, number> = {};

  // Nominations
  await shoot('1-nominations', 'election-voter', 'V01', 'civicrm/elections/view');
  await shoot('1-nominations', 'nominate-form', 'V01', 'civicrm/elections/nominations/create');
  await shoot('1-nominations', 'refused-outsider', 'OUT', 'civicrm/elections/nominations/create');
  advanceCouncil('fill-nominations');
  await shoot('1-nominations', 'election-with-nominations', 'V03', 'civicrm/elections/view');
  ids = councilNominationIds(council().election_id);
  await shoot('1-nominations', 'second-form', 'V12', 'civicrm/elections/nominations/create', { enid: ids['President|N08'] });

  // Advertise: nominees accept or withdraw
  moveToStage('advertise');
  await shoot('2-advertise', 'election-nominee', 'N01', 'civicrm/elections/view');
  await shoot('2-advertise', 'accept-form', 'N01', 'civicrm/elections/nominations/accept', { enid: ids['President|N01'] });
  await shoot('2-advertise', 'withdraw-form', 'N03', 'civicrm/elections/nominations/reject', { enid: ids['President|N03'] });
  advanceCouncil('fill-responses');
  await shoot('2-advertise', 'candidates-voter', 'V05', 'civicrm/elections/view');
  await shoot('2-advertise', 'candidate-page', 'V05', 'civicrm/elections/candidate', { enid: ids['President|N01'] });

  // Voting
  moveToStage('voting');
  await shoot('3-voting', 'election-voter', 'V29', 'civicrm/elections/view');
  await shoot('3-voting', 'ballot', 'V29', 'civicrm/elections/vote');
  advanceCouncil('fill-ballots');
  await shoot('3-voting', 'election-voted', 'V01', 'civicrm/elections/view');
  await shoot('3-voting', 'refused-vote-outsider', 'OUT', 'civicrm/elections/vote');

  // Results
  moveToStage('results');
  advanceCouncil('generate');
  await shoot('4-results', 'results', 'V05', 'civicrm/elections/view');
  await shoot('4-results', 'summary', 'V05', 'civicrm/elections/summary');

  // Leave the election at the start for the other specs.
  reseedCouncil();
});
