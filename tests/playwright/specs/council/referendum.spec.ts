import AxeBuilder from '@axe-core/playwright';
import { test, expect, civiAdminUrl, optionalAdminPage } from '../../fixtures/base';
import { advanceCouncil, civiApi4, civiEval, reseedCouncil } from '../../fixtures/civi';
import { council, personaUrl } from '../../fixtures/personas';

/**
 * A yes or no question on the same ballot as the council positions: one choice for each voter, counted by the
 * pass threshold, shown as passed or not passed with no counts.
 */
test.describe.configure({ mode: 'serial', retries: 0 });

const QUESTION = 'Adopt the new constitution?';

test.beforeAll(() => {
  reseedCouncil();
  const data = council();
  advanceCouncil('fill-nominations');
  advanceCouncil('to-advertise');
  advanceCouncil('fill-responses');
  // Added the way an administrator does, before the election is shown to people.
  civiEval(`
    CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET is_visible = 0 WHERE id = ${data.election_id}');
    $id = (int) civicrm_api3('ElectionPosition', 'create', ['election_id' => ${data.election_id}, 'name' => '${QUESTION}', 'quantity' => 1, 'sortorder' => 20, 'position_type' => 'referendum', 'pass_threshold' => 50, 'quorum_percent' => 5, 'resolution_text' => 'That the constitution be replaced.'])['id'];
    CRM_Elections_BAO_ElectionPosition::saveOptions($id, ['yes' => 'For', 'no' => 'Against', 'abstain' => 'Abstain']);
    CRM_Core_DAO::executeQuery('UPDATE civicrm_election SET is_visible = 1 WHERE id = ${data.election_id}');
  `);
  advanceCouncil('to-voting');
});

test.afterAll(() => {
  reseedCouncil();
});

async function answer(page: import('@playwright/test').Page, persona: string, choice: string | null) {
  await page.goto(personaUrl(persona, 'civicrm/elections/vote'));
  await page.waitForLoadState('networkidle');
  if (choice) {
    await page.getByRole('radio', { name: choice }).check();
  }
  // A ballot needs something on it: the uncontested Secretary has one candidate.
  await page.locator('input[name^="candidate_"]').first().evaluate((el) => { (el as HTMLInputElement).value = '1'; });
  await page.getByRole('button', { name: 'Vote', exact: true }).click();
  const review = page.getByRole('dialog', { name: 'Check your vote' });
  await expect(review).toBeVisible();
  if (choice) {
    await expect(review).toContainText(choice);
  }
  await review.getByRole('button', { name: 'Confirm my vote' }).click();
  await expect(page.getByText(/You have successfully voted/)).toBeVisible();
}

test('the question is on the ballot with its wording and no rank boxes', async ({ personaGoto }) => {
  const page = await personaGoto('V01', 'civicrm/elections/vote');
  const question = page.locator('.election-question-block');
  await expect(question).toHaveCount(1);
  await expect(question).toContainText(QUESTION);
  await expect(question).toContainText('That the constitution be replaced.');
  await expect(question.getByRole('radio')).toHaveCount(3);
  await expect(question.getByRole('radio', { name: 'For' })).toBeVisible();
  await expect(question.getByRole('radio', { name: 'Against' })).toBeVisible();
  await expect(question.getByRole('radio', { name: 'Abstain' })).toBeVisible();
  await expect(question.locator('input[name^="candidate_"]')).toHaveCount(0);
  const result = await new AxeBuilder({ page }).include('.crm-container').withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze();
  expect(result.violations.filter((v) => ['serious', 'critical'].includes(v.impact || ''))).toEqual([]);
});

test('voters answer, see their answer, and can change it', async ({ persona }) => {
  for (const [who, choice] of [['V01', 'For'], ['V02', 'For'], ['V03', 'Against'], ['V04', 'Abstain']] as const) {
    const page = await persona(who);
    await answer(page, who, choice);
    await page.goto(personaUrl(who, 'civicrm/elections/view'));
    await expect(page.locator('.election-your-vote')).toContainText(choice);
    await page.context().close();
  }
  // V03 changes their mind.
  const page = await persona('V03');
  await answer(page, 'V03', 'For');
  await page.context().close();
  const rows = civiApi4<any[]>('ElectionOptionVote.get', { select: ['id'], limit: 0 });
  expect(rows).toHaveLength(4);
});

test('the overview counts the question as a ballot choice without saying how anyone answered', async ({ browser }) => {
  const admin = await optionalAdminPage(browser);
  if (!admin) {
    return;
  }
  await admin.page.goto(civiAdminUrl('civicrm/elections/overview', { reset: 1, eid: council().election_id }));
  const row = admin.page.locator('.election-turnout-table tbody tr', { hasText: QUESTION });
  await expect(row).toContainText('Question');
  await expect(row).toContainText('4');
  await expect(admin.page.locator('.election-turnout-table')).not.toContainText('Against');
  await admin.close();
});

test('the result says passed, with no counts', async ({ personaGoto }) => {
  advanceCouncil('to-results');
  advanceCouncil('generate');
  const [question] = civiApi4<any[]>('ElectionPosition.get', { select: ['result_status'], where: [['name', '=', QUESTION], ['election_id', '=', council().election_id]] });
  expect(question.result_status).toBe('passed');
  const page = await personaGoto('V05', 'civicrm/elections/view');
  const block = page.locator('.election-result-question');
  await expect(block).toContainText(QUESTION);
  await expect(block).toContainText('Passed');
  await expect(block).toContainText('That the constitution be replaced.');
  await expect(block).not.toContainText(/\d+\s*(votes|%)/);
});
