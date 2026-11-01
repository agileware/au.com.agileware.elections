import { test, expect, civiAdminUrl, expectNoPhpOutput } from '../../fixtures/base';
import type { Page } from '@playwright/test';

/**
 * The election create/edit form: sections in tabs, date order checked as the dates change,
 * a timeline preview, and problems shown on the right tab after saving.
 */
async function setDate(page: Page, name: string, value: string) {
  await page.evaluate(([n, v]) => {
    // @ts-ignore CRM is CiviCRM's global
    CRM.$(`input[name="${n}"]`).val(v).trigger('change');
  }, [name, value]);
}

async function fillValidDates(page: Page) {
  const dates: Record<string, string> = {
    visibility_start_date: '2031-01-01 09:00:00',
    visibility_end_date: '2031-03-31 17:00:00',
    nomination_start_date: '2031-01-05 09:00:00',
    nomination_end_date: '2031-01-12 17:00:00',
    advertise_candidates_date: '2031-01-15 09:00:00',
    voting_start_date: '2031-01-20 09:00:00',
    voting_end_date: '2031-01-27 17:00:00',
    result_date: '2031-02-01 09:00:00',
  };
  for (const [name, value] of Object.entries(dates)) {
    await setDate(page, name, value);
  }
}

test.describe('election form', () => {
  test('has Details, Dates, Participation, Display, Emails and Privacy tabs', async ({ adminPage }) => {
    await adminPage.goto(civiAdminUrl('civicrm/elections/create', { reset: 1 }));
    const tabs = adminPage.getByRole('tab');
    await expect(tabs).toHaveText(['Details', 'Dates', 'Participation', 'Display', 'Emails', 'Privacy']);
    await expect(adminPage.locator('#name')).toBeVisible();
    await expect(adminPage.getByLabel('Anonymise votes')).toBeHidden();

    await adminPage.getByRole('tab', { name: 'Privacy' }).click();
    await expect(adminPage.getByLabel('Anonymise votes')).toBeVisible();
    await expect(adminPage.locator('#name')).toBeHidden();

    // Keyboard: arrow keys move between tabs.
    await adminPage.getByRole('tab', { name: 'Participation' }).focus();
    await adminPage.keyboard.press('ArrowRight');
    await expect(adminPage.getByRole('tab', { name: 'Display' })).toHaveAttribute('aria-selected', 'true');
    await expectNoPhpOutput(adminPage);
  });

  test('every date has its own name, its own help and the time zone is shown once', async ({ adminPage }) => {
    await adminPage.goto(civiAdminUrl('civicrm/elections/create', { reset: 1 }));
    await expect(adminPage.getByText('Fields marked * are required.')).toBeVisible();
    await adminPage.getByRole('tab', { name: 'Dates' }).click();
    const labels = ['Visible from', 'Visible until', 'Nominations open', 'Nominations close', 'Candidates advertised from', 'Voting opens', 'Voting closes', 'Results published on'];
    for (const label of labels) {
      await expect(adminPage.locator('label').filter({ hasText: new RegExp(`^${label}\\s*\\*?$`) }), label).toHaveCount(1);
    }
    await expect(adminPage.locator('label', { hasText: /^(Start|End) Date/ })).toHaveCount(0);
    // One line, with the name of the zone, above the dates.
    await expect(adminPage.getByText(/^Times are shown in /)).toHaveCount(1);
    // One sentence of help under each date.
    await expect(adminPage.locator('.election-form-panel#election-panel-dates tr[class*="crm-create-election-form-block-"] .description')).toHaveCount(8);
    // Group headings come after the page heading, in order.
    await expect(adminPage.locator('#election-panel-dates h3')).toHaveCount(0);
    await expect(adminPage.locator('#election-panel-dates h2.election-form-group-heading').first()).toBeVisible();
    // The quick set-up button is not a second primary button.
    await expect(adminPage.locator('#election-quick-fill')).not.toHaveClass(/crm-button/);
  });

  test('the labels, placeholders and choices follow the standard', async ({ adminPage }) => {
    await adminPage.goto(civiAdminUrl('civicrm/elections/create', { reset: 1 }));
    await adminPage.getByRole('tab', { name: 'Participation' }).click();
    for (const label of ['Voting groups', 'Nominating groups', 'Candidate groups', 'Voting method', 'Required nominations', 'Allow people to nominate online', 'Allow people to change their vote', 'Allow people to take part without logging in']) {
      await expect(adminPage.getByLabel(label), label).toBeVisible();
    }
    await expect(adminPage.locator('#s2id_allowed_groups')).toContainText('Select groups');
    await expect(adminPage.locator('#s2id_nomination_groups')).toContainText('Same as voting groups');
    await expect(adminPage.locator('body')).not.toContainText('- Select');
    await adminPage.getByRole('tab', { name: 'Dates' }).click();
    await expect(adminPage.locator('#quick_cycle option').first()).toHaveText('Select a cycle');
    await adminPage.getByRole('tab', { name: 'Display' }).click();
    await expect(adminPage.getByLabel('Show candidate statements').locator('option')).toHaveText(['Not shown', 'Read statement', 'In full']);
    await expect(adminPage.getByLabel('Show a celebration on the results page')).toBeVisible();
    await expect(adminPage.getByLabel('Logo', { exact: true })).toBeVisible();
    await adminPage.getByRole('tab', { name: 'Emails' }).click();
    await expect(adminPage.getByLabel('Email people about their nominations')).toBeVisible();
  });

  test('saving with a required nominations that is not a whole number names the field', async ({ adminPage }) => {
    await adminPage.goto(civiAdminUrl('civicrm/elections/create', { reset: 1 }));
    await adminPage.locator('#name').fill('Playwright labels check');
    await adminPage.getByRole('tab', { name: 'Participation' }).click();
    await adminPage.getByLabel('Required nominations').fill('abc');
    await adminPage.getByRole('button', { name: 'Submit' }).click();
    await expect(adminPage.getByText('Required nominations must be a whole number of 1 or more.').first()).toBeVisible();
    // Back on the same form with what was typed.
    await expect(adminPage.locator('#name')).toHaveValue('Playwright labels check');
  });

  test('shows a date problem straight away and a timeline of the stages', async ({ adminPage }) => {
    await adminPage.goto(civiAdminUrl('civicrm/elections/create', { reset: 1 }));
    await adminPage.getByRole('tab', { name: 'Dates' }).click();
    await fillValidDates(adminPage);
    const timeline = adminPage.locator('.election-timeline-preview li');
    await expect(timeline).toHaveCount(5);
    await expect(adminPage.locator('.election-date-problem')).toHaveCount(0);
    await expect(adminPage.locator('.election-timeline-problem')).toHaveCount(0);

    // Nominations end before they start.
    await setDate(adminPage, 'nomination_end_date', '2031-01-02 09:00:00');
    await expect(adminPage.locator('.election-date-problem').first()).toHaveText('Nominations close must be after Nominations open.');
    await expect(adminPage.locator('.election-timeline-problem')).toHaveCount(1);
    await expect(adminPage.getByRole('tab', { name: 'Dates' })).toHaveClass(/has-problem/);

    // Voting that ends after the election stops being visible.
    await setDate(adminPage, 'nomination_end_date', '2031-01-12 17:00:00');
    await setDate(adminPage, 'voting_end_date', '2031-04-30 17:00:00');
    // The voting end date is past the end of visibility, and now the results date comes before it.
    await expect(adminPage.locator('.election-date-problem')).toHaveText(['Voting closes must not be after Visible until.', 'Results published on must be after Voting closes.']);

    await setDate(adminPage, 'voting_end_date', '2031-01-27 17:00:00');
    await expect(adminPage.locator('.election-date-problem')).toHaveCount(0);
  });

  test('saving with a problem opens the tab that has it and keeps the form', async ({ adminPage }) => {
    await adminPage.goto(civiAdminUrl('civicrm/elections/create', { reset: 1 }));
    // Empty form: the first required field is on the Details tab.
    await adminPage.getByRole('tab', { name: 'Display' }).click();
    await adminPage.getByRole('button', { name: 'Submit' }).click();
    await expect(adminPage.getByRole('tab', { name: 'Details' })).toHaveAttribute('aria-selected', 'true');
    await expect(adminPage.locator('#name')).toBeVisible();
  });
  test('the nomination email is chosen from the message templates, with a link for people who may edit them', async ({ adminPage, managerPage }) => {
    await adminPage.goto(civiAdminUrl('civicrm/elections/create', { reset: 1 }));
    await adminPage.getByRole('tab', { name: 'Emails' }).click();
    const choice = adminPage.getByLabel('Nomination email template');
    await expect(choice).toBeVisible();
    await expect(choice.locator('option:checked')).toHaveText('Election - Invite nominee');
    await expect(adminPage.getByRole('link', { name: 'Edit message templates (opens in a new tab)' })).toBeVisible();
    await expect(adminPage.getByText('{$nomineeName}', { exact: false })).toBeVisible();

    // Only when emails are on.
    await adminPage.getByLabel('Email people about their nominations').uncheck();
    await expect(choice).toBeHidden();
    await adminPage.getByLabel('Email people about their nominations').check();
    await expect(choice).toBeVisible();

    // An organiser who cannot edit message templates still chooses one, without the link.
    await managerPage.goto(civiAdminUrl('civicrm/elections/create', { reset: 1 }));
    await managerPage.getByRole('tab', { name: 'Emails' }).click();
    await expect(managerPage.getByLabel('Nomination email template')).toBeVisible();
    await expect(managerPage.getByRole('link', { name: 'Edit message templates' })).toHaveCount(0);
  });
  test('the quick set-up fills in valid dates for each cycle', async ({ adminPage }) => {
    await adminPage.goto(civiAdminUrl('civicrm/elections/create', { reset: 1 }));
    await adminPage.getByRole('tab', { name: 'Dates' }).click();
    const value = (name: string) => adminPage.locator(`input[name="${name}"]`).inputValue();

    // Nothing chosen yet.
    await adminPage.locator('#election-quick-fill').click();
    await expect(adminPage.locator('.election-quick-status')).toContainText('Choose an election cycle');

    await adminPage.locator('#quick_cycle').selectOption('2-3');
    await adminPage.evaluate(() => {
      // @ts-ignore CRM is CiviCRM's global
      CRM.$('input[name="quick_start"]').val('2031-03-03 09:00:00').trigger('change');
    });
    await adminPage.locator('#election-quick-fill').click();
    await expect(adminPage.locator('.election-quick-status')).toContainText('The dates are filled in');
    expect(await value('visibility_start_date')).toBe('2031-03-03 09:00:00');
    expect(await value('nomination_start_date')).toBe('2031-03-03 09:00:00');
    expect(await value('nomination_end_date')).toBe('2031-03-16 17:00:00');
    expect(await value('advertise_candidates_date')).toBe('2031-03-16 17:05:00');
    expect(await value('voting_start_date')).toBe('2031-03-17 09:00:00');
    expect(await value('voting_end_date')).toBe('2031-04-06 17:00:00');
    expect(await value('result_date')).toBe('2031-04-06 17:05:00');
    expect(await value('visibility_end_date')).toBe('2031-06-29 17:05:00');
    // The same rules the form applies show no problem.
    await expect(adminPage.locator('.election-date-problem')).toHaveCount(0);

    // A second fill asks first, and keeping the dates keeps them.
    await adminPage.locator('#quick_cycle').selectOption('1-1');
    await adminPage.locator('#election-quick-fill').click();
    const dialog = adminPage.locator('.ui-dialog', { hasText: 'This replaces the dates entered so far' });
    await expect(dialog).toBeVisible();
    await dialog.getByRole('button', { name: 'Cancel' }).click();
    expect(await value('voting_end_date')).toBe('2031-04-06 17:00:00');
    await adminPage.locator('#election-quick-fill').click();
    await adminPage.locator('.ui-dialog', { hasText: 'This replaces the dates entered so far' }).getByRole('button', { name: 'Continue' }).click();
    await expect.poll(() => value('voting_end_date')).toBe('2031-03-16 17:00:00');
  });

  test('the times the phases start and end are used, and bad times are explained', async ({ adminPage, allowedProblems }) => {
    // A refused request is answered with HTTP 500 and the reason.
    allowedProblems.push(/suggestDates|500/);
    await adminPage.goto(civiAdminUrl('civicrm/elections/create', { reset: 1 }));
    await adminPage.getByRole('tab', { name: 'Dates' }).click();
    await adminPage.locator('#quick_cycle').selectOption('1-1');
    await adminPage.evaluate(() => {
      // @ts-ignore CRM is CiviCRM's global
      CRM.$('input[name="quick_start"]').val('2031-03-03 10:30:00').trigger('change');
    });
    await adminPage.locator('#quick_start_time').fill('08:15');
    await adminPage.locator('#quick_end_time').fill('08:15');
    await adminPage.locator('#election-quick-fill').click();
    await expect(adminPage.locator('.election-quick-status')).toContainText('The end time must be later than the start time');
    await adminPage.locator('#quick_end_time').fill('20:45');
    await adminPage.locator('#quick_results').selectOption('morning');
    await adminPage.locator('#election-quick-fill').click();
    await expect.poll(() => adminPage.locator('input[name="voting_start_date"]').inputValue()).toBe('2031-03-10 08:15:00');
    expect(await adminPage.locator('input[name="nomination_end_date"]').inputValue()).toBe('2031-03-09 20:45:00');
    expect(await adminPage.locator('input[name="result_date"]').inputValue()).toBe('2031-03-17 08:15:00');
  });
});
