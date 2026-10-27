import { test, expect, civiAdminUrl, expectNoPhpOutput } from '../../fixtures/base';
import { civiApi4, civiEval } from '../../fixtures/civi';
import { addNomination, addPosition, createElection, createMembers, day, nm, nominationFlags, RUN, nomineeNames, removeRunData, removeRunMembers, startElection } from '../../fixtures/data';

test.afterAll(() => {
  removeRunData();
  removeRunMembers();
});

const listUrl = (electionId: number) => civiAdminUrl('civicrm/elections/candidate-list', { reset: 1 }) + `#/?eid=${electionId}`;

function failure(fn: () => void): string {
  try {
    fn();
  }
  catch (e: any) {
    return String(e.stderr || e.message);
  }
  return '';
}

test.describe('Election candidates (SearchKit list)', () => {
  test('lists the nominations of an election with their status', async ({ adminPage }) => {
    const { groupId, ids } = createMembers(['Ada', 'Bo']);
    const election = createElection(nm('Cand list'), { groupId });
    const chair = addPosition(election, 'Chair', 1);
    addNomination(chair, ids.Ada, { accepted: true, statement: 'Vote Ada' });
    addNomination(chair, ids.Bo, { accepted: false });
    await adminPage.goto(listUrl(election));
    const rows = adminPage.locator('crm-search-display-table tbody tr');
    await expect(rows).toHaveCount(2);
    await expect(rows.nth(0)).toContainText('Chair');
    await expect(rows.nth(0)).toContainText('Ada');
    await expect(rows.nth(0)).toContainText('Vote Ada');
    await expect(rows.nth(1)).toContainText('Bo');
    await expectNoPhpOutput(adminPage);
  });

  test('a statement can be edited in place', async ({ adminPage }) => {
    const { groupId, ids } = createMembers(['Cy']);
    const election = createElection(nm('Cand edit'), { groupId });
    const nomination = addNomination(addPosition(election, 'Chair', 1), ids.Cy, { accepted: true, statement: 'Old words' });
    await adminPage.goto(listUrl(election));
    const row = adminPage.locator('crm-search-display-table tbody tr').first();
    await row.locator('[data-field-name="comments"] .crm-editable-enabled').click();
    await row.locator('input[type="text"]').fill('New words from the administrator');
    await row.getByRole('button', { name: 'Save' }).click();
    await expect.poll(() => nominationFlags(nomination).comments).toBe('New words from the administrator');
  });

  test('an administrator can add a candidate directly', async ({ adminPage }) => {
    const { groupId, ids } = createMembers(['Di']);
    const election = createElection(nm('Cand add'), { groupId });
    addPosition(election, 'Chair', 1);
    await adminPage.goto(listUrl(election));
    await adminPage.getByRole('link', { name: 'Add Candidate' }).click();
    await adminPage.getByLabel('Position').selectOption({ label: 'Chair' });
    await adminPage.locator('#s2id_contact, .crm-form-entityref').first().click();
    await adminPage.locator('.select2-input:visible').fill('Di');
    await adminPage.locator('.select2-result-label', { hasText: 'Di' }).first().click();
    await adminPage.getByLabel('Candidate statement').fill('Added by the board');
    await adminPage.getByRole('button', { name: 'Add Candidate' }).click();
    await expect(adminPage.locator('crm-search-display-table tbody tr', { hasText: 'Di' })).toBeVisible();
    expect(nomineeNames(election).some((n) => n.includes('Di'))).toBe(true);
    const [nomination] = civiApi4<Array<{ id: number }>>('ElectionNomination.get', { select: ['id'], where: [['member_nominee', '=', ids.Di]] });
    expect(nominationFlags(nomination.id)).toMatchObject({ is_eligible_candidate: true, has_accepted_nomination: true, comments: 'Added by the board' });
  });

  test('someone outside the election groups cannot be added', async () => {
    const { groupId } = createMembers(['Ed']);
    const election = createElection(nm('Cand outsider'), { groupId });
    const positionId = addPosition(election, 'Chair', 1);
    const [outsider] = civiApi4<Array<{ id: number }>>('Contact.create', { values: { contact_type: 'Individual', first_name: 'Fay', last_name: `Member ${RUN}` } });
    // The same method the Add Candidate form calls.
    expect(failure(() => civiEval(`CRM_Elections_BAO_ElectionNomination::addCandidate(${positionId}, ${outsider.id});`))).toContain('Only people who can take part');
    expect(nomineeNames(election)).toEqual([]);
  });

  test('who is standing is frozen once voting starts, statements can still be corrected', async () => {
    const { groupId, ids } = createMembers(['Gus']);
    const election = createElection(nm('Cand frozen'), { groupId });
    const nomination = addNomination(addPosition(election, 'Chair', 1), ids.Gus, { accepted: true });
    startElection(election);
    civiApi4('Election.update', { values: { advertise_candidates_date: day(-3), voting_start_date: day(-1), voting_end_date: day(5), result_date: day(6), nomination_end_date: day(-4) }, where: [['id', '=', election]] });

    expect(failure(() => civiApi4('ElectionNomination.update', { values: { has_accepted_nomination: false }, where: [['id', '=', nomination]] }))).toContain('Who is standing cannot be changed once voting has started');
    expect(nominationFlags(nomination).has_accepted_nomination).toBe(true);
    expect(failure(() => civiApi4('ElectionNomination.update', { values: { comments: 'Typo fixed' }, where: [['id', '=', nomination]] }))).toBe('');
    expect(nominationFlags(nomination).comments).toBe('Typo fixed');
  });
});
