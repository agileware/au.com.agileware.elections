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
  test('has Election, Dates, Participation and Display tabs', async ({ adminPage }) => {
    await adminPage.goto(civiAdminUrl('civicrm/elections/create', { reset: 1 }));
    const tabs = adminPage.getByRole('tab');
    await expect(tabs).toHaveText(['Election', 'Dates', 'Participation', 'Display']);
    await expect(adminPage.locator('#name')).toBeVisible();
    await expect(adminPage.getByLabel('Anonymise Votes')).toBeHidden();

    await adminPage.getByRole('tab', { name: 'Participation' }).click();
    await expect(adminPage.getByLabel('Anonymise Votes')).toBeVisible();
    await expect(adminPage.locator('#name')).toBeHidden();

    // Keyboard: arrow keys move between tabs.
    await adminPage.getByRole('tab', { name: 'Participation' }).focus();
    await adminPage.keyboard.press('ArrowRight');
    await expect(adminPage.getByRole('tab', { name: 'Display' })).toHaveAttribute('aria-selected', 'true');
    await expectNoPhpOutput(adminPage);
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
    await expect(adminPage.locator('.election-date-problem').first()).toHaveText('End date must be after start date.');
    await expect(adminPage.locator('.election-timeline-problem')).toHaveCount(1);
    await expect(adminPage.getByRole('tab', { name: 'Dates' })).toHaveClass(/has-problem/);

    // Voting that ends after the election stops being visible.
    await setDate(adminPage, 'nomination_end_date', '2031-01-12 17:00:00');
    await setDate(adminPage, 'voting_end_date', '2031-04-30 17:00:00');
    // The voting end date is past the end of visibility, and now the results date comes before it.
    await expect(adminPage.locator('.election-date-problem')).toHaveText(['End date must be before visibility end date.', 'Start Date must be after voting end date.']);

    await setDate(adminPage, 'voting_end_date', '2031-01-27 17:00:00');
    await expect(adminPage.locator('.election-date-problem')).toHaveCount(0);
  });

  test('saving with a problem opens the tab that has it and keeps the form', async ({ adminPage }) => {
    await adminPage.goto(civiAdminUrl('civicrm/elections/create', { reset: 1 }));
    // Empty form: the first required field is on the Election tab.
    await adminPage.getByRole('tab', { name: 'Display' }).click();
    await adminPage.getByRole('button', { name: 'Submit' }).click();
    await expect(adminPage.getByRole('tab', { name: 'Election' })).toHaveAttribute('aria-selected', 'true');
    await expect(adminPage.locator('#name')).toBeVisible();
  });
  test('the nomination email is chosen from the message templates, with a link for people who may edit them', async ({ adminPage, managerPage }) => {
    await adminPage.goto(civiAdminUrl('civicrm/elections/create', { reset: 1 }));
    await adminPage.getByRole('tab', { name: 'Participation' }).click();
    const choice = adminPage.getByLabel('Nomination email');
    await expect(choice).toBeVisible();
    await expect(choice.locator('option:checked')).toHaveText('Election - Invite nominee');
    await expect(adminPage.getByRole('link', { name: 'Edit message templates' })).toBeVisible();
    await expect(adminPage.getByText('{$nomineeName}', { exact: false })).toBeVisible();

    // Only when emails are on.
    await adminPage.getByLabel('Email people about their nominations').uncheck();
    await expect(choice).toBeHidden();
    await adminPage.getByLabel('Email people about their nominations').check();
    await expect(choice).toBeVisible();

    // An organiser who cannot edit message templates still chooses one, without the link.
    await managerPage.goto(civiAdminUrl('civicrm/elections/create', { reset: 1 }));
    await managerPage.getByRole('tab', { name: 'Participation' }).click();
    await expect(managerPage.getByLabel('Nomination email')).toBeVisible();
    await expect(managerPage.getByRole('link', { name: 'Edit message templates' })).toHaveCount(0);
  });
});
