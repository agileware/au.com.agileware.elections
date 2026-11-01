import { test, expect, civiAdminUrl, optionalAdminPage } from '../../fixtures/base';
import { civiEval, civiApi4, reseedCouncil } from '../../fixtures/civi';
import { nominate } from '../../fixtures/council-ui';
import { council, personaUrl } from '../../fixtures/personas';

/**
 * Separate groups for nominating and for standing. Everyone in the council group can still vote; only
 * the nomination group can nominate and second, and the people the election lets stand are limited to
 * the candidate group. An election with no groups of its own behaves as it always did.
 */
test.describe.configure({ mode: 'serial', retries: 0 });

test.beforeAll(() => {
  reseedCouncil();
  const data = council();
  const cid = (p: string) => data.links[p].cid;
  // V01 and V02 may nominate; only N01 and N02 may stand.
  civiEval(`
    $n = civicrm_api3('Group', 'create', ['title' => 'PW Nominators ' . uniqid(), 'name' => 'pw_nominators_' . uniqid()])['id'];
    $c = civicrm_api3('Group', 'create', ['title' => 'PW Candidates ' . uniqid(), 'name' => 'pw_candidates_' . uniqid()])['id'];
    foreach ([${cid('V01')}, ${cid('V02')}] as $id) { civicrm_api3('GroupContact', 'create', ['group_id' => $n, 'contact_id' => $id]); }
    foreach ([${cid('N01')}, ${cid('N02')}] as $id) { civicrm_api3('GroupContact', 'create', ['group_id' => $c, 'contact_id' => $id]); }
    civicrm_api3('Election', 'create', ['nomination_groups' => (string) $n, 'candidate_groups' => (string) $c] + civicrm_api3('Election', 'getsingle', ['id' => ${data.election_id}]));
  `);
});

test.afterAll(() => {
  reseedCouncil();
  civiEval(`foreach (civicrm_api3('Group', 'get', ['name' => ['LIKE' => 'pw_%'], 'options' => ['limit' => 0]])['values'] as $g) { civicrm_api3('Group', 'delete', ['id' => $g['id']]); }`);
});

test('someone who can only vote is told they cannot nominate', async ({ personaGoto }) => {
  const page = await personaGoto('V03', 'civicrm/elections/view');
  await expect(page.getByText('You are not able to nominate in this election.')).toBeVisible();
  await expect(page.getByRole('link', { name: 'Submit Nomination' })).toHaveCount(0);
  const form = await personaGoto('V03', 'civicrm/elections/nominations/create');
  await expect(form.getByText('You may only see this page if you can take part in this election.')).toBeVisible();
});

test('a member of the nomination group nominates a person who may stand', async ({ persona }) => {
  const data = council();
  const page = await persona('V01');
  await page.goto(personaUrl('V01', 'civicrm/elections/view'));
  await expect(page.getByRole('link', { name: 'Submit Nomination' })).toBeVisible();
  await nominate(page, 'V01', 'President', 'N01', data.links.N01.cid, 'N01 may stand');
  await page.context().close();
  const rows = civiApi4<any[]>('ElectionNomination.get', { select: ['id'], where: [['election_position_id.election_id', '=', data.election_id]], limit: 0 });
  expect(rows).toHaveLength(1);
});

test('a person outside the candidate group cannot be nominated, and nothing is stored', async ({ persona }) => {
  const data = council();
  const page = await persona('V02');
  await page.goto(personaUrl('V02', 'civicrm/elections/nominations/create'));
  await page.waitForLoadState('networkidle');
  await page.locator('#s2id_position').click();
  await page.locator('.select2-result-label', { hasText: /^President$/ }).click();
  await page.evaluate((id) => { (document.querySelector('#contact') as HTMLInputElement).value = String(id); }, data.links.N05.cid);
  await page.getByLabel('Why do you want to nominate this person for this position?').fill('N05 is outside the candidate group');
  await page.getByRole('button', { name: 'Nominate' }).click();
  await expect(page.getByText('Nomination submitted.')).toHaveCount(0);
  await page.context().close();
  const rows = civiApi4<any[]>('ElectionNomination.get', { select: ['id'], where: [['member_nominee', '=', data.links.N05.cid]], limit: 0 });
  expect(rows).toHaveLength(0);
});

test('the overview shows who is in each role', async ({ browser }) => {
  const admin = await optionalAdminPage(browser);
  if (!admin) {
    return;
  }
  await admin.page.goto(civiAdminUrl('civicrm/elections/overview', { reset: 1, eid: council().election_id }));
  await expect(admin.page.locator('.election-overview-roles')).toContainText('May nominate and second: 2');
  await expect(admin.page.locator('.election-overview-roles')).toContainText('May stand as candidates: 2');
  await admin.close();
});
