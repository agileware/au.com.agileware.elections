import { test, expect, type Browser, type Page } from '@playwright/test';
import { advanceCouncil, civiApi4, civiSql, councilNominationIds, moveToStage, reseedCouncil } from '../../fixtures/civi';
import { council, personaUrl } from '../../fixtures/personas';
import { frontUrl } from '../../fixtures/cms';

/**
 * What a person could try with the election's own pages and requests: links that do not belong to them,
 * requests for the programming interface, two requests sent at once, a form kept open past the stage it
 * belonged to, and a form changed before it is sent. The election must not change.
 */
test.describe.configure({ mode: 'serial', retries: 0 });

let ids: Record<string, number> = {};

test.beforeAll(() => {
  reseedCouncil();
});

test.afterAll(() => {
  reseedCouncil();
});

async function open(browser: Browser, persona: string, civiPath: string, params: Record<string, string | number> = {}) {
  const context = await browser.newContext();
  const page = await context.newPage();
  const response = await page.goto(personaUrl(persona, civiPath, params));
  await page.waitForLoadState('networkidle');
  return { context, page, response };
}

/** A fresh election taken to the voting stage with its candidates standing (no ballots yet). */
function freshElectionAtVoting() {
  reseedCouncil();
  advanceCouncil('fill-nominations');
  moveToStage('advertise');
  advanceCouncil('fill-responses');
  moveToStage('voting');
  ids = councilNominationIds(council().election_id);
}

/** Open the review window for the filled-in ballot (the vote is only sent when it is confirmed). */
async function review(page: Page) {
  await page.getByRole('button', { name: 'Vote', exact: true }).click();
  const dialog = page.getByRole('dialog', { name: 'Check your vote' });
  await expect(dialog).toBeVisible();
  return dialog;
}

const rowsFor = (cid: number) => civiApi4<Array<{ id: number }>>('ElectionVote.get', { select: ['id'], where: [['member_id', '=', cid]], limit: 0 }).length;
const nominations = () => civiApi4<Array<{ id: number }>>('ElectionNomination.get', { select: ['id'], where: [['election_position_id.election_id', '=', council().election_id]], limit: 0 }).length;
const seconders = () => civiApi4<Array<{ id: number }>>('ElectionNominationSeconder.get', { select: ['id'], where: [['election_nomination_id.election_position_id.election_id', '=', council().election_id]], limit: 0 }).length;

test('a link that does not belong to the person is refused', async ({ browser }) => {
  const data = council();
  const link = (cid: number, cs: string) => frontUrl('civicrm/elections/view', { eid: data.election_id, cid, cs });
  const attempts = [
    ['the checksum of another person', link(data.links.V01.cid, data.links.V02.cs)],
    ['a checksum cut short', link(data.links.V01.cid, data.links.V01.cs.slice(0, 20))],
    ['a made-up checksum', link(data.links.V01.cid, 'f'.repeat(32) + '_1893456000_720')],
    ['another contact id with this checksum', link(data.links.V02.cid, data.links.V01.cs)],
  ];
  for (const [label, url] of attempts) {
    const context = await browser.newContext();
    const page = await context.newPage();
    await page.goto(url);
    await expect(page.locator('body'), label).not.toContainText('Welcome,');
    await expect(page.locator('.crm-elections-action-block .election-btn'), label).toHaveCount(0);
    await context.close();
  }
});

test('the programming interface gives nothing to a person using a personal link', async ({ browser }) => {
  const data = council();
  advanceCouncil('fill-nominations');
  moveToStage('advertise');
  advanceCouncil('fill-responses');
  moveToStage('voting');
  advanceCouncil('fill-ballots');
  const context = await browser.newContext();
  const page = await context.newPage();
  for (const entity of ['ElectionVote', 'ElectionResult', 'ElectionNomination', 'ElectionNominationSeconder', 'Election', 'ElectionPosition', 'ElectionAudit']) {
    for (const action of ['get', 'getlist']) {
      const url = frontUrl('civicrm/ajax/rest', { entity, action, json: 1, sequential: 1, cid: data.links.V01.cid, cs: data.links.V01.cs });
      const response = await page.request.get(url);
      const body = await response.text();
      expect(body, `${entity}.${action}`).not.toMatch(/"rank"|"member_id"|"member_nominee"|"election_nomination_id"/);
    }
  }
  const api4 = await page.request.post(frontUrl('civicrm/ajax/api4/ElectionVote/get', { cid: data.links.V01.cid, cs: data.links.V01.cs }), { form: { params: JSON.stringify({ select: ['rank'] }) } });
  expect(await api4.text()).not.toMatch(/"rank"/);
  await context.close();
});

test('two ballots sent at the same moment leave one ballot', async ({ browser }) => {
  const data = council();
  ids = councilNominationIds(data.election_id);
  const cid = data.links.V29.cid;
  expect(rowsFor(cid)).toBe(0);
  const pages: Page[] = [];
  const contexts = [];
  for (let i = 0; i < 2; i++) {
    const opened = await open(browser, 'V29', 'civicrm/elections/vote');
    contexts.push(opened.context);
    pages.push(opened.page);
    await opened.page.evaluate((nominationId) => {
      (document.querySelector(`input[name="candidate_${nominationId}"]`) as HTMLInputElement).value = '1';
    }, ids['President|N01']);
  }
  // Both forms are filled in and reviewed; confirm them together.
  const dialogs = await Promise.all(pages.map((p) => review(p)));
  await Promise.all(dialogs.map((d) => d.getByRole('button', { name: 'Confirm my vote' }).click()));
  await Promise.all(pages.map((p) => p.waitForLoadState('networkidle')));
  expect(rowsFor(cid), 'one row for the one candidate ranked, however many requests arrived').toBe(1);
  for (const c of contexts) await c.close();
});

test('two nominations sent at the same moment count as one nominator', async ({ browser }) => {
  const data = council();
  moveToStage('nominations');
  civiSql('DELETE s FROM civicrm_election_nomination_seconder s INNER JOIN civicrm_election_nomination n ON n.id = s.election_nomination_id INNER JOIN civicrm_election_position p ON p.id = n.election_position_id WHERE p.election_id = %1', { 1: data.election_id });
  civiSql('DELETE n FROM civicrm_election_nomination n INNER JOIN civicrm_election_position p ON p.id = n.election_position_id WHERE p.election_id = %1', { 1: data.election_id });
  const before = { nominations: nominations(), seconders: seconders() };
  const pages: Page[] = [];
  const contexts = [];
  for (let i = 0; i < 2; i++) {
    const opened = await open(browser, 'V07', 'civicrm/elections/nominations/create');
    contexts.push(opened.context);
    pages.push(opened.page);
    await opened.page.evaluate(([position, contact]) => {
      // The position and nominee boxes hold the chosen ids.
      (document.querySelector('#position') as HTMLInputElement).value = String(position);
      (document.querySelector('#contact') as HTMLInputElement).value = String(contact);
    }, [data.positions['President'], data.links.N09.cid]);
  }
  await Promise.all(pages.map((p) => p.getByRole('button', { name: 'Nominate' }).click()));
  await Promise.all(pages.map((p) => p.waitForLoadState('networkidle')));
  expect(nominations() - before.nominations, 'one nomination').toBe(1);
  expect(seconders() - before.seconders, 'one nominator row').toBe(1);
  const [row] = civiApi4<Array<{ is_eligible_candidate: boolean }>>('ElectionNomination.get', { select: ['is_eligible_candidate'], where: [['member_nominee', '=', data.links.N09.cid], ['election_position_id.election_id', '=', data.election_id]] });
  expect(row.is_eligible_candidate, 'one nominator is not enough when two are required').toBe(false);
  for (const c of contexts) await c.close();
});

test('a form kept open past its stage does nothing', async ({ browser }) => {
  reseedCouncil();
  const data = council();
  // The nomination form, opened while nominations are open, sent after they closed.
  const nominate = await open(browser, 'V12', 'civicrm/elections/nominations/create');
  await nominate.page.evaluate(([position, contact]) => {
    (document.querySelector('#position') as HTMLInputElement).value = String(position);
    (document.querySelector('#contact') as HTMLInputElement).value = String(contact);
  }, [data.positions['Treasurer'], data.links.N05.cid]);
  const nominationsBefore = nominations();
  const seconderBefore = seconders();
  moveToStage('advertise');
  await nominate.page.getByRole('button', { name: 'Nominate' }).click();
  await nominate.page.waitForLoadState('networkidle');
  expect(nominations()).toBe(nominationsBefore);
  expect(seconders()).toBe(seconderBefore);
  await nominate.context.close();

  // The ballot, opened while voting is open, sent after it closed.
  freshElectionAtVoting();
  const ballot = await open(browser, 'V30', 'civicrm/elections/vote');
  await ballot.page.evaluate((nominationId) => {
    (document.querySelector(`input[name="candidate_${nominationId}"]`) as HTMLInputElement).value = '1';
  }, ids['President|N01']);
  moveToStage('results');
  await (await review(ballot.page)).getByRole('button', { name: 'Confirm my vote' }).click();
  await ballot.page.waitForLoadState('networkidle');
  expect(rowsFor(data.links.V30.cid), 'a ballot sent after voting closed is not counted').toBe(0);
  await ballot.context.close();
});

test('a ballot changed before it is sent cannot reach another election or another person', async ({ browser }) => {
  freshElectionAtVoting();
  const data = council();
  const { context, page } = await open(browser, 'V30', 'civicrm/elections/vote');
  // Rank a candidate that is not on the ballot: a nomination that was never accepted, and a made-up one.
  const notStanding = ids['Treasurer|N07'];
  await page.evaluate(([real, hidden]) => {
    (document.querySelector(`input[name="candidate_${real}"]`) as HTMLInputElement).value = '1';
    for (const id of [hidden, 999999]) {
      const extra = document.createElement('input');
      extra.type = 'hidden';
      extra.name = `candidate_${id}`;
      extra.value = '1';
      document.querySelector('form')!.appendChild(extra);
    }
    // Another voter's identity in the form must change nothing.
    for (const name of ['cid', 'member_id']) {
      const extra = document.createElement('input');
      extra.type = 'hidden';
      extra.name = name;
      extra.value = String(12345);
      document.querySelector('form')!.appendChild(extra);
    }
  }, [ids['President|N01'], notStanding]);
  await (await review(page)).getByRole('button', { name: 'Confirm my vote' }).click();
  await page.waitForLoadState('networkidle');
  const rows = civiApi4<Array<{ election_nomination_id: number; member_id: number }>>('ElectionVote.get', { select: ['election_nomination_id', 'member_id'], where: [['member_id', '=', data.links.V30.cid]], limit: 0 });
  expect(rows.map((r) => r.election_nomination_id).filter((n) => n !== ids['President|N01'])).toEqual([]);
  expect(civiApi4<Array<{ id: number }>>('ElectionVote.get', { select: ['id'], where: [['member_id', '=', 12345]], limit: 0 })).toHaveLength(0);
  await context.close();
});

test('election pages ask the browser not to pass the personal link on to other sites', async ({ browser }) => {
  reseedCouncil();
  for (const [civiPath, persona] of [['civicrm/elections/view', 'V01'], ['civicrm/elections/nominations/create', 'V01'], ['civicrm/elections/summary', 'V01']] as const) {
    const { context, page } = await open(browser, persona, civiPath);
    // The page itself carries the policy (a site's own headers may replace the one the extension sends).
    await expect(page.locator('meta[name="referrer"]'), civiPath).toHaveAttribute('content', 'same-origin');
    await context.close();
  }
});

test('a form sent without the key it was given is not accepted', async ({ browser }) => {
  const data = council();
  reseedCouncil();
  const { context, page } = await open(browser, 'V05', 'civicrm/elections/nominations/create');
  const action = await page.evaluate(() => (document.querySelector('form.CRM_Elections_Form_CreateElectionNomination, form') as HTMLFormElement).action);
  const before = { nominations: nominations(), seconders: seconders() };
  const fields = { position: String(data.positions['President']), contact: String(data.links.N02.cid), reason: 'forged', _qf_CreateElectionNomination_submit: 'Nominate' };
  // No form key, and a wrong one.
  for (const extra of [{}, { qfKey: 'not-the-key' }]) {
    await page.request.post(action, { form: { ...fields, ...extra } });
  }
  expect(nominations()).toBe(before.nominations);
  expect(seconders()).toBe(before.seconders);
  await context.close();
});
