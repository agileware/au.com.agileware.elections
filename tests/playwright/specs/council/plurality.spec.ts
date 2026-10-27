import AxeBuilder from '@axe-core/playwright';
import { test, expect } from '../../fixtures/base';
import { advanceCouncil, civiApi4, councilNominationIds, reseedCouncil } from '../../fixtures/civi';
import { council } from '../../fixtures/personas';
import { personaUrl } from '../../fixtures/personas';

/**
 * The same council, counted by most votes: voters tick candidates instead of ranking them. A ballot
 * cannot tick more than the seats, the review shows the ticked names, and a tie across the last seat is
 * shown as a tie with every tied candidate listed.
 */
test.describe.configure({ mode: 'serial', retries: 0 });

let ids: Record<string, number> = {};

test.beforeAll(() => {
  reseedCouncil();
  const data = council();
  advanceCouncil('fill-nominations');
  advanceCouncil('to-advertise');
  advanceCouncil('fill-responses');
  civiApi4('Election.update', { where: [['id', '=', data.election_id]], values: { voting_method: 'plurality' } });
  advanceCouncil('to-voting');
  ids = councilNominationIds(data.election_id);
});

test.afterAll(() => {
  reseedCouncil();
});

/** Tick candidates on the ballot as a persona and confirm the review. */
async function tickAndVote(page: import('@playwright/test').Page, persona: string, choices: string[]) {
  await page.goto(personaUrl(persona, 'civicrm/elections/vote'));
  await page.waitForLoadState('networkidle');
  for (const key of choices) {
    await page.locator(`input[type="checkbox"][name="candidate_${ids[key]}"]`).check();
  }
  await page.getByRole('button', { name: 'Vote', exact: true }).click();
  const review = page.getByRole('dialog', { name: 'Check your vote' });
  await expect(review).toBeVisible();
  await review.getByRole('button', { name: 'Confirm my vote' }).click();
  await expect(page.getByText(/You have successfully voted/)).toBeVisible();
}

test('the ballot has tick boxes and says how many can be chosen', async ({ personaGoto }) => {
  const page = await personaGoto('V01', 'civicrm/elections/vote');
  await expect(page.getByText('Tick the candidates you want to vote for.')).toBeVisible();
  await expect(page.locator('input[type="checkbox"][name^="candidate_"]').first()).toBeVisible();
  await expect(page.getByText('Tick one candidate.').first()).toBeVisible();
  await expect(page.getByText(/Please number the candidates/)).toHaveCount(0);
  const result = await new AxeBuilder({ page }).include('.crm-container').withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze();
  expect(result.violations.filter((v) => ['serious', 'critical'].includes(v.impact || ''))).toEqual([]);
});

test('ticking more candidates than there are seats is stopped', async ({ personaGoto }) => {
  const page = await personaGoto('V01', 'civicrm/elections/vote');
  await page.locator(`input[type="checkbox"][name="candidate_${ids['President|N01']}"]`).check();
  await page.locator(`input[type="checkbox"][name="candidate_${ids['President|N02']}"]`).check();
  await expect(page.getByText('You can choose no more than 1.')).toBeVisible();
  await page.getByRole('button', { name: 'Vote', exact: true }).click();
  await expect(page.getByRole('dialog', { name: 'Check your vote' })).toHaveCount(0);
  await expect(page.getByText(/You have successfully voted/)).toHaveCount(0);
});

test('voters tick their choices and see them on the election page', async ({ persona }) => {
  const page = await persona('V01');
  await tickAndVote(page, 'V01', ['President|N01', 'Treasurer|N04', 'Secretary|N03']);
  await page.goto(personaUrl('V01', 'civicrm/elections/view'));
  const own = page.locator('.election-your-vote');
  await expect(own).toContainText('N01'.slice(1));
  await expect(own.locator('ul li').first()).toBeVisible();
  await page.context().close();

  const two = await persona('V02');
  await tickAndVote(two, 'V02', ['President|N01', 'Treasurer|N05']);
  await two.context().close();
});

test('results count the ticks: a winner, and a tie across the only seat', async ({ personaGoto }) => {
  advanceCouncil('to-results');
  advanceCouncil('generate');
  const positions = civiApi4<Array<{ name: string; result_status: string }>>('ElectionPosition.get', {
    select: ['name', 'result_status'],
    where: [['election_id', '=', council().election_id]],
    limit: 0,
  });
  const status = Object.fromEntries(positions.map((p) => [p.name, p.result_status]));
  expect(status['President']).toBe('mostvotes');
  expect(status['Treasurer']).toBe('tie');

  const page = await personaGoto('V03', 'civicrm/elections/view');
  const treasurer = page.locator('.election-result-position', { hasText: 'Treasurer' });
  await expect(treasurer).toContainText('tie');
  // Treasurer, and the three council roles nobody voted in (their two candidates are level on no votes), list
  // both candidates as tied; President and the uncontested Secretary have a winner.
  await expect(page.locator('.election-winner-badge-tie')).toHaveCount(8);
  await expect(page.locator('.election-winner-badge:not(.election-winner-badge-tie)')).toHaveCount(2);
});
