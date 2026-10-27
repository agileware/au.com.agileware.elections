import AxeBuilder from '@axe-core/playwright';
import { test, expect } from '../../fixtures/base';
import { advanceCouncil, civiApi4, civiEval, civiSql, councilNominationIds, reseedCouncil } from '../../fixtures/civi';
import { accept } from '../../fixtures/council-ui';
import { council, personaUrl } from '../../fixtures/personas';

/**
 * Write-ins: where a position allows it a voter can name a person who is not on the ballot. The person must
 * accept before voting closes; accepting puts them on the ballot for everyone who votes afterwards.
 */
test.describe.configure({ mode: 'serial', retries: 0 });

let ids: Record<string, number> = {};

test.beforeAll(() => {
  reseedCouncil();
  const data = council();
  advanceCouncil('fill-nominations');
  advanceCouncil('to-advertise');
  advanceCouncil('fill-responses');
  // President allows two write-ins for each seat.
  civiSql('UPDATE civicrm_election_position SET write_ins_per_seat = 2 WHERE id = %1', { 1: data.positions['President'] });
  advanceCouncil('to-voting');
  ids = councilNominationIds(data.election_id);
});

test.afterAll(() => {
  reseedCouncil();
});

test('only the position that allows write-ins shows the write-in places', async ({ personaGoto }) => {
  const page = await personaGoto('V01', 'civicrm/elections/vote');
  const places = page.locator('.election-writein');
  await expect(places).toHaveCount(1);
  await expect(places).toContainText('Write in a candidate');
  await expect(places).toContainText('accept before voting closes');
  // One place to start; the button adds the next.
  await expect(places.locator('.election-writein-row:visible')).toHaveCount(1);
  await page.getByRole('button', { name: 'Add another write-in' }).click();
  await expect(places.locator('.election-writein-row:visible')).toHaveCount(2);
  await expect(page.getByRole('button', { name: 'Add another write-in' })).toBeHidden();
  const result = await new AxeBuilder({ page }).include('.crm-container').withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze();
  expect(result.violations.filter((v) => ['serious', 'critical'].includes(v.impact || ''))).toEqual([]);
});

test('a voter finds a person by name, writes them in, and sees them in the review', async ({ persona }) => {
  const data = council();
  const page = await persona('V01');
  await page.goto(personaUrl('V01', 'civicrm/elections/vote'));
  await page.waitForLoadState('networkidle');

  // Too few letters: nothing is offered.
  await page.locator('.election-writein-row').first().locator('.election-writein-person .select2-choice').click();
  await page.locator('.select2-input:visible').fill('No');
  await expect(page.locator('.select2-result-label')).toHaveCount(0);
  await page.locator('.select2-input:visible').fill('Nominee');
  await page.locator('.select2-result-label', { hasText: '09, Nominee' }).first().click();

  // First choice among the listed candidates, the person written in second.
  await page.evaluate((r) => {
    for (const [id, rank] of Object.entries(r)) {
      (document.querySelector(`input[name="candidate_${id}"]`) as HTMLInputElement).value = String(rank);
    }
    (document.querySelector('input[name^="writeinrank_"]') as HTMLInputElement).value = '2';
  }, { [ids['President|N01']]: 1 });
  await page.getByRole('button', { name: 'Vote', exact: true }).click();
  const review = page.getByRole('dialog', { name: 'Check your vote' });
  await expect(review).toBeVisible();
  await expect(review).toContainText('(written in)');
  await review.getByRole('button', { name: 'Confirm my vote' }).click();
  await expect(page.getByText(/You have successfully voted/)).toBeVisible();
  await page.context().close();

  const writeIns = civiApi4<any[]>('ElectionNomination.get', {
    select: ['id', 'is_write_in', 'has_accepted_nomination', 'member_nominee'],
    where: [['election_position_id.election_id', '=', data.election_id], ['is_write_in', '=', true]],
    limit: 0,
  });
  expect(writeIns).toHaveLength(1);
  expect(writeIns[0].has_accepted_nomination).toBeFalsy();
  expect(writeIns[0].member_nominee).toBe(data.links.N09.cid);
});

test('the person written in is not on the ballot until they accept, then they are', async ({ persona, personaGoto }) => {
  const data = council();
  const before = await personaGoto('V02', 'civicrm/elections/vote');
  await expect(before.locator('.vote-candidate-row', { hasText: '09' })).toHaveCount(0);

  const [nomination] = civiApi4<Array<{ id: number }>>('ElectionNomination.get', { select: ['id'], where: [['is_write_in', '=', true], ['election_position_id.election_id', '=', data.election_id]] });
  const page = await persona('N09');
  await accept(page, 'N09', nomination.id, 'Happy to stand', 'https://example.org/N09');
  await page.context().close();

  const after = await personaGoto('V03', 'civicrm/elections/vote');
  await expect(after.locator('.vote-candidate-row', { hasText: '09' })).toHaveCount(1);
});

test('someone who declines cannot be written in again', async ({ persona }) => {
  const data = council();
  // N10 is written in by a script as a voter would, then declines.
  civiEval(`
    $election = findElectionById(${data.election_id});
    civicrm_api3('ElectionVote', 'addvotes', ['election_id' => ${data.election_id}, 'member_id' => ${data.links.V04.cid}, 'votes' => [['election_nomination_id' => ${ids['President|N01']}, 'rank' => 1]], 'write_ins' => [['position_id' => ${data.positions['President']}, 'contact_id' => ${data.links.N10.cid}, 'rank' => 2]]]);
  `);
  const [nomination] = civiApi4<Array<{ id: number }>>('ElectionNomination.get', { select: ['id'], where: [['is_write_in', '=', true], ['member_nominee', '=', data.links.N10.cid]] });
  const page = await persona('N10');
  await page.goto(personaUrl('N10', 'civicrm/elections/nominations/reject', { enid: nomination.id }));
  await page.getByLabel('Comments').fill('No thanks');
  await page.getByRole('button', { name: 'Withdraw' }).click();
  await expect(page.getByText('You have withdrawn the nomination.')).toBeVisible();
  await page.context().close();
  const rows = civiApi4<any[]>('ElectionNomination.get', { select: ['has_rejected_nomination'], where: [['id', '=', nomination.id]] });
  expect(rows[0].has_rejected_nomination).toBeTruthy();
});
