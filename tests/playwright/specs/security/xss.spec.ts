import { test, expect } from '@playwright/test';
import { advanceCouncil, civiEval, civiSql, councilNominationIds, moveToStage, reseedCouncil } from '../../fixtures/civi';
import { council, personaUrl } from '../../fixtures/personas';
import { frontUrl } from '../../fixtures/cms';

/**
 * Every place a person or an administrator can put text that other people then see (names, nicknames,
 * photo addresses, statements, reasons for a nomination, election and position names and descriptions)
 * is filled with script and markup, and every page of the election is loaded as other people. Nothing
 * may run, and nothing may be turned into page elements.
 */
test.describe.configure({ mode: 'serial', retries: 0 });

const PAYLOADS = [
  `<img src=x onerror="window.__xss='img'">`,
  `"><script>window.__xss='script'</script>`,
  `<svg onload="window.__xss='svg'">`,
];

test('script and markup in any field are shown as text, never run', async ({ browser }) => {
  test.setTimeout(15 * 60 * 1000);
  reseedCouncil();
  const data = council();
  advanceCouncil('fill-nominations');
  moveToStage('advertise');
  advanceCouncil('fill-responses');
  moveToStage('voting');
  advanceCouncil('fill-ballots');
  const ids = councilNominationIds(data.election_id);

  // Names and photo addresses of people (a member can edit their own name through a profile).
  for (const [i, persona] of ['N01', 'N02', 'N03', 'N04', 'N05', 'N06', 'V03', 'V29'].entries()) {
    const cid = data.links[persona].cid;
    const first = PAYLOADS[i % PAYLOADS.length];
    civiSql('UPDATE civicrm_contact SET first_name = %1, nick_name = %2, display_name = %3, image_URL = %4 WHERE id = %5', {
      1: first, 2: PAYLOADS[(i + 1) % PAYLOADS.length], 3: `${first} Payload ${persona}`, 4: `x" onerror="window.__xss='photo'`, 5: cid,
    });
  }
  // Statements, reasons and the election's and positions' own text.
  civiSql('UPDATE civicrm_election_nomination SET comments = %1, rejection_comments = %1, statement_url = %2', { 1: PAYLOADS[0], 2: `javascript:window.__xss='url'` });
  civiSql('UPDATE civicrm_election_nomination_seconder SET description = %1', { 1: PAYLOADS[1] });
  civiSql('UPDATE civicrm_election SET name = %1, description = %2 WHERE id = %3', { 1: `Council ${PAYLOADS[2]}`, 2: PAYLOADS[0], 3: data.election_id });
  civiSql('UPDATE civicrm_election_position SET description = %1', { 1: PAYLOADS[1] });
  civiEval(`Civi::settings()->set('elections_name_format', 'first_nick_last');`);

  const problems: string[] = [];
  async function visit(label: string, persona: string, civiPath: string, params: Record<string, string | number> = {}) {
    const context = await browser.newContext();
    const page = await context.newPage();
    page.on('dialog', (d) => { problems.push(`${label}: a dialog opened: ${d.message()}`); d.dismiss(); });
    await page.goto(personaUrl(persona, civiPath, params));
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(300);
    const found = await page.evaluate(() => {
      const out: string[] = [];
      if ((window as any).__xss) out.push(`script ran (${(window as any).__xss})`);
      const content = document.querySelector('.crm-container') || document.body;
      if (content.querySelector('[onerror], [onload], img[src^="x"]')) out.push('markup from a field became an element');
      content.querySelectorAll('script').forEach((s) => { if ((s.textContent || '').includes('__xss')) out.push('script element from a field'); });
      content.querySelectorAll('a[href]').forEach((a) => { if (/^\s*javascript:/i.test(a.getAttribute('href') || '')) out.push('javascript: link'); });
      return out;
    });
    for (const f of found) problems.push(`${label}: ${f}`);
    await context.close();
  }

  const nomination = ids['President|N01'];
  await visit('election page (voting)', 'V03', 'civicrm/elections/view');
  await visit('ballot', 'V29', 'civicrm/elections/vote');
  await visit('candidate page', 'V03', 'civicrm/elections/candidate', { enid: nomination });
  await visit('edit statement', 'N01', 'civicrm/elections/candidate/statement', { enid: nomination });
  await visit('election page as a nominee', 'N01', 'civicrm/elections/view');
  await visit('summary', 'V03', 'civicrm/elections/summary');
  moveToStage('advertise');
  await visit('candidates list', 'V03', 'civicrm/elections/view');
  await visit('accept form', 'N01', 'civicrm/elections/nominations/accept', { enid: nomination });
  await visit('withdraw form', 'N02', 'civicrm/elections/nominations/reject', { enid: ids['President|N02'] });
  moveToStage('nominations');
  await visit('nominations (nominate form)', 'V03', 'civicrm/elections/nominations/create');
  await visit('nominations (election page)', 'V03', 'civicrm/elections/view');
  moveToStage('results');
  advanceCouncil('generate');
  await visit('results', 'V03', 'civicrm/elections/view');
  await visit('summary after results', 'V03', 'civicrm/elections/summary');

  // The address of the page that shows one election arrives in the request on the election list.
  const context = await browser.newContext();
  const page = await context.newPage();
  for (const evaction of [`javascript:window.__xss='evaction'`, `https://evil.example/steal`, `//evil.example/steal`, `/safe/page" onmouseover="window.__xss='attr`]) {
    await page.goto(frontUrl('civicrm/elections', { evaction }));
    await page.waitForLoadState('networkidle');
    const hrefs = await page.locator('.crm-elections-list-block a.election-btn').evaluateAll((els) => els.map((e) => e.getAttribute('href') || ''));
    for (const href of hrefs) {
      if (/javascript:|evil\.example|onmouseover/i.test(href)) problems.push(`election list: unsafe link from evaction: ${href}`);
    }
    if (await page.evaluate(() => (window as any).__xss)) problems.push('election list: script ran from evaction');
  }
  await context.close();

  civiEval(`Civi::settings()->revert('elections_name_format');`);
  reseedCouncil();
  expect(problems, 'script or markup from a field reached a page').toEqual([]);
});
