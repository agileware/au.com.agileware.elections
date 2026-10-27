import { test, expect, civiAdminUrl, expectNoPhpOutput } from '../../fixtures/base';
import { advanceCouncil, civiApi4, councilNominationIds, moveToStage, reseedCouncil } from '../../fixtures/civi';
import { accept, ballotFor, nominate, vote, withdraw } from '../../fixtures/council-ui';
import { council, POSITIONS, voters, nominees } from '../../fixtures/personas';

/**
 * A complete mock council election, start to finish, with every person acting as themselves on
 * the website front end (personal links) and the administrator on the back end.
 *
 *   Roles:   President, Secretary, Treasurer, Council Member 1, Council Member 2, Council Member 3
 *   People:  30 voters (V01..V30), 10 nominees (N01..N10, six of whom become candidates), an outsider
 *   Ballots: 38 members vote (V29 and V30 do not), some partial, one changes their vote
 *
 * The stages are moved by changing the election's dates, as the passing of time would.
 */
test.describe.configure({ mode: 'serial', retries: 0 });

let nominationIds: Record<string, number> = {};

// A nominee's name as it may appear: the dev site may show nicknames, CI does not.
const NAME: Record<string, RegExp> = Object.fromEntries(nominees.map((n) => [n, new RegExp(`Nominee (["\u201c].*?["\u201d] )?${n.slice(1)}`)]));

test.beforeAll(() => {
  reseedCouncil();
});

test.afterAll(() => {
  // Leave the site as the other specs expect it: the election at the nominations stage.
  reseedCouncil();
});

test('the election has been set up with six roles and is open for nominations', async ({ adminPage }) => {
  expect(Object.keys(council().positions)).toEqual(POSITIONS);
  expect(voters).toHaveLength(30);

  if (!adminPage) {
    return; // back end checks need an administrator session (WordPress only)
  }
  await adminPage.goto(civiAdminUrl('civicrm/elections/manage', { reset: 1 }));
  const row = adminPage.locator('tr', { hasText: 'Playwright Council Election' });
  await expect(row).toBeVisible();
  await expect(row.getByText('Yes', { exact: true })).toBeVisible();
  await expect(row.getByRole('link', { name: '6', exact: true })).toBeVisible();

  await adminPage.goto(civiAdminUrl('civicrm/elections/overview', { reset: 1, eid: council().election_id }));
  await expect(adminPage.locator('.election-stage')).toHaveText('Nominations open');
  await expect(adminPage.locator('.election-count-positions')).toHaveText('6');
  await expectNoPhpOutput(adminPage);
});

test('nominators nominate and second, and the outsider cannot take part', async ({ persona, personaGoto }) => {
  test.setTimeout(10 * 60 * 1000);
  const data = council();

  let first = true;
  for (const [position, nominee, nominators] of data.plan) {
    for (const nominator of nominators) {
      const page = await persona(nominator);
      await nominate(page, nominator, position, nominee, data.links[nominee].cid, `${nominator} nominates ${nominee} for ${position}`, first ? { search: 'Nominee' } : {});
      first = false;
      await page.context().close();
    }
  }

  // Who is eligible follows from how many people nominated them.
  const rows = civiApi4<Array<{ is_eligible_candidate: boolean; 'election_position_id.name': string; 'member_nominee.last_name': string }>>('ElectionNomination.get', {
    select: ['is_eligible_candidate', 'election_position_id.name', 'member_nominee.last_name'],
    where: [['election_position_id.election_id', '=', data.election_id]],
    limit: 0,
  });
  expect(rows).toHaveLength(data.plan.length);
  for (const [position, nominee, nominators] of data.plan) {
    const row = rows.find((r) => r['election_position_id.name'] === position && `N${r['member_nominee.last_name']}` === nominee)!;
    expect(row.is_eligible_candidate, `${nominee} for ${position}`).toBe(nominators.length >= 2);
  }

  // The outsider has a valid link but is not in the election's group.
  const outsider = await personaGoto('OUT', 'civicrm/elections/nominations/create');
  await expect(outsider.getByText('You may only see this page if you are a member.')).toBeVisible();
});

test('nominees accept, withdraw or stay silent, and six people end up standing', async ({ persona, personaGoto }) => {
  test.setTimeout(10 * 60 * 1000);
  const data = council();
  moveToStage('advertise');
  nominationIds = councilNominationIds(data.election_id);

  for (const [position, nominee, , outcome] of data.plan) {
    const id = nominationIds[`${position}|${nominee}`];
    const page = await persona(nominee);
    if (outcome === 'accept' || outcome === 'accept_then_withdraw') {
      await accept(page, nominee, id, `Statement of ${nominee} for ${position}`, `https://example.org/${nominee}`);
    }
    if (outcome === 'withdraw' || outcome === 'accept_then_withdraw') {
      await withdraw(page, nominee, id, 'Changed my mind');
    }
    await page.context().close();
  }

  // A nominee who was not seconded enough has nothing to accept.
  const unseconded = await personaGoto('N08', 'civicrm/elections/view');
  await expect(unseconded.getByRole('link', { name: 'Accept Nomination' })).toHaveCount(0);

  // Who is standing, role by role.
  const standing = civiApi4<Array<{ 'election_position_id.name': string; 'member_nominee.last_name': string }>>('ElectionNomination.get', {
    select: ['election_position_id.name', 'member_nominee.last_name'],
    where: [['election_position_id.election_id', '=', data.election_id], ['is_eligible_candidate', '=', true], ['has_accepted_nomination', '=', true]],
    limit: 0,
  });
  const byRole: Record<string, string[]> = {};
  for (const r of standing) {
    (byRole[r['election_position_id.name']] ??= []).push(`N${r['member_nominee.last_name']}`);
  }
  for (const position of POSITIONS) {
    expect((byRole[position] ?? []).sort(), position).toEqual([...data.candidates[position]].sort());
  }
  expect(new Set(Object.values(byRole).flat()).size).toBe(6);
});

test('thirty voters and the nominees vote, one changes their vote, the outsider cannot', async ({ persona, personaGoto }) => {
  test.setTimeout(15 * 60 * 1000);
  const data = council();
  moveToStage('voting');

  const voting = Object.keys(data.ballots);
  expect(voting.filter((p) => p.startsWith('V'))).toHaveLength(28);
  // Members vote four at a time, each in their own browser.
  for (let i = 0; i < voting.length; i += 4) {
    await Promise.all(voting.slice(i, i + 4).map(async (name) => {
      const page = await persona(name);
      await vote(page, name, ballotFor(name, nominationIds));
      await page.context().close(); // keeps the number of open browsers down
    }));
  }

  const ballots = () => civiApi4<Array<{ member_id: number }>>('ElectionVote.get', {
    select: ['member_id'],
    where: [['election_nomination_id.election_position_id.election_id', '=', data.election_id]],
    limit: 0,
  });
  expect(new Set(ballots().map((b) => b.member_id)).size).toBe(voting.length);

  // V01 changes their vote, then votes as before: the new ballot replaces the old one each time.
  const v01 = await persona('V01');
  const president = (name: string) => nominationIds[`President|${name}`];
  await vote(v01, 'V01', { [president('N02')]: 1 });
  expect(ballots().filter((b) => b.member_id === data.links.V01.cid)).toHaveLength(1);
  await vote(v01, 'V01', ballotFor('V01', nominationIds));
  expect(ballots().filter((b) => b.member_id === data.links.V01.cid).length).toBe(Object.keys(ballotFor('V01', nominationIds)).length);

  // Nominees who did not stand are not on the ballot, and the outsider cannot vote.
  const ballot = await personaGoto('V29', 'civicrm/elections/vote');
  await expect(ballot.locator(`input[name="candidate_${nominationIds['Treasurer|N07']}"]`)).toHaveCount(0);
  await expect(ballot.locator(`input[name="candidate_${nominationIds['Secretary|N03']}"]`)).toHaveCount(1);
  const outsider = await personaGoto('OUT', 'civicrm/elections/vote');
  await expect(outsider.getByText('You may only see this page if you are a member.')).toBeVisible();
});

test('results follow the ballots: clear winners, a tie and an uncontested role', async ({ personaGoto, adminPage }) => {
  test.setTimeout(5 * 60 * 1000);
  const data = council();
  moveToStage('results');
  advanceCouncil('generate');

  // Work out the expected result from the ballots, independently of the extension.
  const firstChoices = (position: string) => {
    const counts: Record<string, number> = Object.fromEntries(data.candidates[position].map((c) => [c, 0]));
    for (const ballot of Object.values(data.ballots)) {
      for (const [candidate, rank] of Object.entries(ballot[position] ?? {})) {
        if (rank === 1) counts[candidate]++;
      }
    }
    return counts;
  };

  const page = await personaGoto('V05', 'civicrm/elections/view');
  await expect(page.getByText('The votes have been counted for this election.')).toBeVisible();
  const text = await page.locator('main').innerText();

  for (const position of POSITIONS) {
    const counts = firstChoices(position);
    const ranked = Object.entries(counts).sort((a, b) => b[1] - a[1]);
    const section = text.slice(text.search(new RegExp(`Winners? for ${position}\\b`)));
    const block = section.split(/Winners? for /)[1] ?? section;
    if (data.candidates[position].length === 1) {
      expect(block, position).toMatch(NAME[ranked[0][0]]);
    }
    else if (ranked[0][1] === ranked[1][1]) {
      expect(block, `${position} is a tie`).toContain('no clear majority');
    }
    else {
      expect(block, position).toContain('Winner by clear majority');
      expect(block, position).toMatch(NAME[ranked[0][0]]);
    }
  }
  await expectNoPhpOutput(page);

  // The summary lists everyone who stood and everyone nominated.
  const summary = await personaGoto('V05', 'civicrm/elections/summary');
  await expect(summary.getByRole('heading', { name: 'President' })).toBeVisible();

  // The administrator sees the turnout on the back end.
  if (!adminPage) {
    return;
  }
  await adminPage.goto(civiAdminUrl('civicrm/elections/overview', { reset: 1, eid: data.election_id }));
  await expect(adminPage.locator('.election-stage')).toHaveText('Results published');
  await expect(adminPage.locator('.election-count-ballots')).toHaveText(String(Object.keys(data.ballots).length));
  await expect(adminPage.locator('.election-count-candidates')).toHaveText('11');
});
