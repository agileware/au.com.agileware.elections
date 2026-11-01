import { test, expect } from '../../fixtures/base';
import { advanceCouncil, civiApi4, civiEval, reseedCouncil } from '../../fixtures/civi';
import { accept } from '../../fixtures/council-ui';
import { council } from '../../fixtures/personas';

/**
 * An election whose organisers choose the candidates: no nomination form on the website, candidates added by an
 * administrator are asked to accept, and then everything works as usual.
 */
test.describe.configure({ mode: 'serial', retries: 0 });

test.beforeAll(() => {
  reseedCouncil();
  civiEval(`civicrm_api3('Election', 'create', ['allow_online_nominations' => 0] + civicrm_api3('Election', 'getsingle', ['id' => ${council().election_id}]));`);
});

test.afterAll(() => {
  reseedCouncil();
});

test('there is no nomination form and the page says the organisers choose', async ({ personaGoto }) => {
  const page = await personaGoto('V01', 'civicrm/elections/view');
  await expect(page.getByText('chosen by the organisers').first()).toBeVisible();
  await expect(page.getByRole('link', { name: 'Submit Nomination' })).toHaveCount(0);
  const form = await personaGoto('V01', 'civicrm/elections/nominations/create');
  await expect(form.getByText('there are no online nominations')).toBeVisible();
});

test('a candidate added by the organisers is asked to accept and becomes a candidate', async ({ persona, personaGoto }) => {
  const data = council();
  civiEval(`CRM_Elections_BAO_ElectionNomination::addCandidate(${data.positions['President']}, ${data.links.N01.cid}, FALSE);`);
  advanceCouncil('to-advertise');
  const [nomination] = civiApi4<Array<{ id: number }>>('ElectionNomination.get', { select: ['id'], where: [['member_nominee', '=', data.links.N01.cid]] });
  const nominee = await personaGoto('N01', 'civicrm/elections/view');
  await expect(nominee.getByRole('link', { name: /Accept Nomination|Respond to Nomination/ }).first()).toBeVisible();
  const page = await persona('N01');
  await accept(page, 'N01', nomination.id, 'Chosen by the board', 'https://example.org/N01');
  await page.context().close();
  const rows = civiApi4<any[]>('ElectionNomination.get', { select: ['has_accepted_nomination'], where: [['id', '=', nomination.id]] });
  expect(rows[0].has_accepted_nomination).toBeTruthy();
});
