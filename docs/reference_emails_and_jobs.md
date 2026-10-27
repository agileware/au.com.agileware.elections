# Emails and scheduled jobs

## Emails

The extension sends a few emails to individual people. Each is built from a CiviCRM message template, so you can change the wording in **Administer > Communications > Message Templates**, under the workflow group **Message Template Workflow for Elections**. Nobody who is marked *Do not email*, has no email address or is on hold is emailed, and an email that cannot be sent never stops the action that caused it.

| Message template | Sent to | When | Controlled by |
|---|---|---|---|
| **Elections - Nominee invited to respond** | A nominee, or a candidate added by an administrator who has not yet accepted | As soon as the nominee has enough nominations to stand, with a link to accept or withdraw | The election's **Email people about their nominations** setting |
| **Elections - Person written in by a voter** | A person a voter has written in | The first time anyone writes them in, with links to accept or decline | Always sent for a write-in: the person cannot respond otherwise |
| **Elections - Reminder to a person written in** | A person written in who has not answered | Once, in the last day before voting closes | Job: Elections Write-in Reminders |
| **Elections - Voting closed before a person written in answered** | A person written in who never answered | Once, after voting closes: the opportunity to accept has passed and they can no longer accept | Job: Elections Write-in Closed Notices |

Links in these emails are personal links when the election allows non-logged in access, otherwise the person logs in first. See [Participating without logging in](setup.md#participating-without-logging-in).

For bulk emails, for example reminding every voter that voting is open, use CiviMail or a scheduled reminder with the Election tokens (`election_name`, `election_position`, `nominator_name`, `nominee_name` for the **Nomination** activity and `election_name` for the **Vote** activity).

## Scheduled jobs

The extension adds three jobs in **Administer > System Settings > Scheduled Jobs**. They are enabled when the extension is installed, and need CiviCRM's cron to be running.

| Job | Runs | What it does |
|---|---|---|
| **Elections Results Job** | Always | Counts the ballots and publishes the results once voting has closed and the Results Start Date has been reached. If this job is not enabled, no results are calculated |
| **Elections Write-in Reminders** | Hourly | Reminds people written in who have not answered, in the day before voting closes |
| **Elections Write-in Closed Notices** | Always | Tells people written in who never answered that voting has closed and they can no longer accept. Only for elections that closed in the last 14 days |
