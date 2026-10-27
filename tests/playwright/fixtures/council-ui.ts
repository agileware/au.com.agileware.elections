import { expect, type Page } from '@playwright/test';
import { personaUrl, council } from './personas';

/**
 * The steps of an election as a person takes them on the website. Each takes the persona's
 * own page, which was opened with their personal link.
 */

/** Nominate (or second) someone through the nomination form. */
export async function nominate(page: Page, persona: string, position: string, nominee: string, nomineeContactId: number, reason: string, options: { search?: string } = {}) {
  await page.goto(personaUrl(persona, 'civicrm/elections/nominations/create'));
  await page.waitForLoadState('networkidle');
  await page.locator('#s2id_position').click();
  await page.locator('.select2-result-label', { hasText: new RegExp(`^${position}$`) }).click();
  if (options.search) {
    // Use the nominee search as a person would: this is the path that needs the personal link's access.
    await page.locator('#s2id_contact').click();
    await page.locator('.select2-input:visible').fill(options.search);
    // Search results show the sort name, for example "01, Nominee".
    await page.locator('.select2-result-label', { hasText: `${nominee.slice(1)}, Nominee` }).first().click();
  }
  else {
    await page.evaluate((id) => { (document.querySelector('#contact') as HTMLInputElement).value = String(id); }, nomineeContactId);
  }
  await page.getByLabel('Why do you want to nominate this person for this position?').fill(reason);
  await page.getByRole('button', { name: 'Nominate' }).click();
  await expect(page.getByText('Nomination submitted.')).toBeVisible();
}

/** Accept a nomination through the Accept Nomination form. */
export async function accept(page: Page, persona: string, nominationId: number, statement: string, link: string) {
  await page.goto(personaUrl(persona, 'civicrm/elections/nominations/accept', { enid: nominationId }));
  await page.getByLabel('Candidate statement').fill(statement);
  await page.getByLabel('Link to a longer statement or CV').fill(link);
  await page.getByRole('button', { name: 'Accept' }).click();
  await expect(page.getByText('Nomination accepted.')).toBeVisible();
}

/** Withdraw a nomination through the Withdraw Nomination form. */
export async function withdraw(page: Page, persona: string, nominationId: number, reason: string) {
  await page.goto(personaUrl(persona, 'civicrm/elections/nominations/reject', { enid: nominationId }));
  await page.getByLabel('Comments').fill(reason);
  await page.getByRole('button', { name: 'Withdraw' }).click();
  await expect(page.getByText('You have withdrawn the nomination.')).toBeVisible();
}

/**
 * Vote through the ballot. `ranks` maps a nomination id to the rank given (1 is the first choice).
 * The ranking boxes are set directly: they are the same fields the ranking dropdowns fill in.
 */
export async function vote(page: Page, persona: string, ranks: Record<number, number>) {
  await page.goto(personaUrl(persona, 'civicrm/elections/vote'));
  await page.waitForLoadState('networkidle');
  await page.evaluate((r) => {
    for (const [nominationId, rank] of Object.entries(r)) {
      const field = document.querySelector(`input[name="candidate_${nominationId}"]`) as HTMLInputElement | null;
      if (!field) {
        throw new Error(`No ranking box for nomination ${nominationId}`);
      }
      field.value = String(rank);
    }
  }, ranks);
  await page.getByRole('button', { name: 'Vote', exact: true }).click();
  // The ballot shows the choices for review first; the vote is saved when it is confirmed.
  const review = page.getByRole('dialog', { name: 'Check your vote' });
  if (await review.waitFor({ state: 'visible', timeout: 3000 }).then(() => true, () => false)) {
    await review.getByRole('button', { name: 'Confirm my vote' }).click();
  }
  await expect(page.getByText(/You have successfully voted/)).toBeVisible();
}

/** Ballot of a persona as nomination id -> rank, from the plan the fixture wrote. */
export function ballotFor(persona: string, nominationIds: Record<string, number>): Record<number, number> {
  const ranks: Record<number, number> = {};
  for (const [position, candidates] of Object.entries(council().ballots[persona] ?? {})) {
    for (const [candidate, rank] of Object.entries(candidates)) {
      ranks[nominationIds[`${position}|${candidate}`]] = rank;
    }
  }
  return ranks;
}
