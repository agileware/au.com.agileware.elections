import { test, expect, civiAdminUrl, optionalAdminPage } from '../../fixtures/base';
import { civiEval, reseedCouncil } from '../../fixtures/civi';
import { council } from '../../fixtures/personas';

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

test('someone who can only vote is told they cannot nominate', async ({ personaGoto, allowedProblems }) => {
  // A refused page says why and is sent as HTTP 403.
  allowedProblems.push(/403/);
  const page = await personaGoto('V03', 'civicrm/elections/view');
  await expect(page.getByText('You are not able to nominate in this election.')).toBeVisible();
  await expect(page.getByRole('link', { name: 'Nominate', exact: true })).toHaveCount(0);
  const form = await personaGoto('V03', 'civicrm/elections/nominations/create');
  await expect(form.getByText('You are not in a group that can take part in this election.')).toBeVisible();
});

test('the overview shows who is in each role', async ({ browser }) => {
  const admin = await optionalAdminPage(browser);
  if (!admin) {
    return;
  }
  await admin.page.goto(civiAdminUrl('civicrm/elections/overview', { reset: 1, eid: council().election_id }));
  await expect(admin.page.locator('.election-overview-roles')).toContainText('2 people may nominate and second');
  await expect(admin.page.locator('.election-overview-roles')).toContainText('2 people may stand as candidates');
  await admin.close();
});

test('when the organisers choose the candidates there is no nomination form and the page says so', async ({ personaGoto, allowedProblems }) => {
  // Last in this file, as it turns online nominations off for the election; `afterAll` reseeds it.
  civiEval(`civicrm_api3('Election', 'create', ['allow_online_nominations' => 0] + civicrm_api3('Election', 'getsingle', ['id' => ${council().election_id}]));`);
  // A refused page says why and is sent as HTTP 403.
  allowedProblems.push(/403/);
  const page = await personaGoto('V01', 'civicrm/elections/view');
  await expect(page.getByText('chosen by the organisers').first()).toBeVisible();
  await expect(page.getByRole('link', { name: 'Nominate', exact: true })).toHaveCount(0);
  const form = await personaGoto('V01', 'civicrm/elections/nominations/create');
  await expect(form.getByText('there are no online nominations')).toBeVisible();
});
