import { test, expect } from '@playwright/test';
import { advanceCouncil, councilNominationIds, moveToStage, reseedCouncil } from '../../fixtures/civi';
import { council } from '../../fixtures/personas';
import { personaUrl } from '../../fixtures/personas';
import { AUTH_DIR } from '../../fixtures/global-setup';
import { frontUrl } from '../../fixtures/cms';
import fs from 'node:fs';
import path from 'node:path';

/**
 * The election page tells each person where the election is (a stage stepper) and what they can do now
 * (one sentence and a button), at every stage, for the people who matter at that stage.
 */
test.describe.configure({ mode: 'serial', retries: 0 });

async function view(browser: import('@playwright/test').Browser, persona: string) {
  const context = await browser.newContext();
  const page = await context.newPage();
  await page.goto(personaUrl(persona, 'civicrm/elections/view'));
  await page.waitForLoadState('networkidle');
  return { page, close: () => context.close() };
}

async function stepper(page: import('@playwright/test').Page) {
  const items = page.locator('.election-stepper .election-step');
  await expect(items).toHaveCount(4);
  const labels = await items.locator('.election-step-label').evaluateAll((els) => els.map((e) => (e.firstChild?.textContent || '').trim()));
  expect(labels).toEqual(['Nominations', 'Candidates', 'Voting', 'Results']);
  return {
    states: await items.evaluateAll((els) => els.map((e) => e.className.replace(/.*election-step-(passed|current|upcoming).*/, '$1'))),
    current: await page.locator('.election-stepper [aria-current="step"] .election-step-label').evaluateAll((els) => els.map((e) => (e.firstChild?.textContent || '').trim())),
  };
}

test('nominations: a voter is invited to nominate, an outsider is told they cannot', async ({ browser }) => {
  reseedCouncil();
  const voter = await view(browser, 'V01');
  expect((await stepper(voter.page)).current).toEqual(['Nominations']);
  expect((await stepper(voter.page)).states).toEqual(['current', 'upcoming', 'upcoming', 'upcoming']);
  const panel = voter.page.locator('.election-next-action');
  await expect(panel).toContainText('Nominations are open until');
  await expect(panel.getByRole('link', { name: 'Submit Nomination' })).toBeVisible();
  await voter.close();

  const outsider = await view(browser, 'OUT');
  const note = outsider.page.locator('.election-next-action');
  await expect(note).toContainText('not able to nominate');
  await expect(note.getByRole('link')).toHaveCount(0);
  await outsider.close();
});

test('the list of elections shows a card with the stage and the button for it', async ({ browser }) => {
  // The list is for logged-in members (the saved member session), so it is skipped where there is none.
  const state = path.join(AUTH_DIR, 'member.json');
  test.skip(!fs.existsSync(state), 'No saved member session on this CMS');
  const context = await browser.newContext({ storageState: state });
  const list = await context.newPage();
  await list.goto(frontUrl('civicrm/elections'));
  await list.waitForLoadState('networkidle');
  const card = list.locator('.election-card', { hasText: 'Playwright Council Election' });
  await expect(card.locator('.election-badge')).toHaveText('Nominations open');
  await expect(card.getByRole('link', { name: 'View and submit Nominations' })).toBeVisible();
  await context.close();
});

test('candidates: a nominee is asked to respond, everyone else sees the candidates', async ({ browser }) => {
  advanceCouncil('fill-nominations');
  moveToStage('advertise');
  const nominee = await view(browser, 'N01');
  const states = (await stepper(nominee.page)).states;
  expect(states).toEqual(['passed', 'current', 'upcoming', 'upcoming']);
  const panel = nominee.page.locator('.election-next-action');
  await expect(panel).toContainText('You have been nominated for');
  await expect(panel).toContainText('accept or withdraw');
  const respond = panel.getByRole('link', { name: 'Respond to Nomination' });
  await expect(respond).toHaveAttribute('href', /nominations\/accept/);
  // Their own nominations, with where each stands and what they can do.
  const mine = nominee.page.locator('.election-my-nominations');
  await expect(mine.locator('.election-my-nomination').first()).toContainText('Waiting for your answer');
  await expect(mine.getByRole('link', { name: 'Accept Nomination' }).first()).toBeVisible();
  await expect(mine.getByRole('link', { name: 'Withdraw Nomination' }).first()).toBeVisible();
  await nominee.close();

  // Someone who still needs another nominator is told so, and cannot accept yet.
  const unseconded = await view(browser, 'N08');
  await expect(unseconded.page.locator('.election-my-nominations')).toContainText('Waiting for 1 more nominator');
  await expect(unseconded.page.locator('.election-my-nominations').getByRole('link', { name: 'Accept Nomination' })).toHaveCount(0);
  await unseconded.close();

  const voter = await view(browser, 'V05');
  await expect(voter.page.locator('.election-next-action')).toContainText('Nominations are closed');
  await expect(voter.page.locator('.election-next-action').getByRole('link')).toHaveCount(0);
  await voter.close();
});

test('voting: vote now, change your vote, or not allowed to vote', async ({ browser }) => {
  advanceCouncil('fill-responses');
  moveToStage('voting');
  advanceCouncil('fill-ballots');
  const newVoter = await view(browser, 'V29');
  expect((await stepper(newVoter.page)).states).toEqual(['passed', 'passed', 'current', 'upcoming']);
  await expect(newVoter.page.locator('.election-next-action')).toContainText('Voting is open until');
  await expect(newVoter.page.locator('.election-next-action').getByRole('link', { name: 'Vote Now' })).toBeVisible();
  await newVoter.close();

  const voted = await view(browser, 'V01');
  const panel = voted.page.locator('.election-next-action');
  await expect(panel).toContainText('You voted on');
  await expect(panel.getByRole('link', { name: 'Change Vote' })).toHaveAttribute('href', /elections\/vote/);
  // The person's own ballot is shown back to them: each position with their choices in order.
  const own = voted.page.locator('.election-your-vote');
  await expect(own).toContainText('President');
  await expect(own.locator('ol').first().locator('li').first()).toContainText('Nominee');
  await voted.close();

  const outsider = await view(browser, 'OUT');
  await expect(outsider.page.locator('.election-next-action')).toContainText('Only members can vote');
  await expect(outsider.page.locator('.election-next-action').getByRole('link')).toHaveCount(0);
  await outsider.close();
});

test('ballot: ranks are checked as they are chosen and the vote is reviewed before it is saved', async ({ browser }) => {
  const data = council();
  const ids = councilNominationIds(data.election_id);
  const first = ids['President|N01'];
  const second = ids['President|N02'];
  const { page, close } = await view(browser, 'V29');
  await page.goto(personaUrl('V29', 'civicrm/elections/vote'));
  await page.waitForLoadState('networkidle');
  const setRank = (nomination: number, rank: string) => page.evaluate(([n, r]) => {
    // @ts-ignore CRM is CiviCRM's global
    CRM.$(`input[name="candidate_${n}"]`).val(r).trigger('change');
  }, [nomination, rank] as [number, string]);

  // The same rank twice is flagged straight away.
  await setRank(first, '1');
  await setRank(second, '1');
  await expect(page.locator('.election-rank-problem').first()).toContainText('more than one candidate');
  // A vote with no first choice is flagged too.
  await setRank(second, '');
  await setRank(first, '2');
  await expect(page.locator('.election-rank-problem').first()).toContainText('rank 1');
  await setRank(first, '1');
  await setRank(second, '2');
  await expect(page.locator('.election-rank-problem')).toHaveCount(0);

  // The vote is shown for review before anything is saved.
  await page.getByRole('button', { name: 'Vote', exact: true }).click();
  const dialog = page.getByRole('dialog', { name: 'Check your vote' });
  await expect(dialog).toBeVisible();
  await expect(dialog).toContainText('President');
  await expect(dialog.locator('li').first()).toContainText('rank 1');
  await dialog.getByRole('button', { name: 'Go back and change' }).click();
  await expect(dialog).toBeHidden();

  // Confirming saves it, and the election page shows what was recorded.
  await page.getByRole('button', { name: 'Vote', exact: true }).click();
  await page.getByRole('dialog', { name: 'Check your vote' }).getByRole('button', { name: 'Confirm my vote' }).click();
  await expect(page.getByText(/You have successfully voted/)).toBeVisible();
  await expect(page.locator('.election-your-vote')).toContainText('President');
  await close();
});

test('closed: voting has closed and says when the results come', async ({ browser }) => {
  moveToStage('results');
  // Results are generated by the scheduled job once the results date is reached: before that, the page says so.
  const voter = await view(browser, 'V05');
  const text = await voter.page.locator('.crm-container').innerText();
  expect(text).toMatch(/Voting has closed|The votes have been counted/);
  await voter.close();
  reseedCouncil();
});
