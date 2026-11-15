# Reviewing screens by hand in a browser

Written from the existing setup, 2026-11-15; not yet followed from scratch.

The automated suites do not replace looking at a screen. Review each screen you change as the people who use it: a voter, a nominee, a candidate, an organiser, and someone who is refused. This page says how to do that on a test site, and what to write down. The checklist for what a screen must be is the user interface standards ([../standards/ui-standards.md](../standards/ui-standards.md)).

## Set up

1. A test site with CiviCRM, the extension deployed and the Beekeepers seed built ([seeding.md](seeding.md)), so there is one election of each kind at a stage that suits looking at it. On WordPress the maintainer's review site is already built this way ([../environments/beekeepers-review-site.md](../environments/beekeepers-review-site.md)).
2. A browser profile kept for testing, with no other accounts in it. DDEV sites use a certificate from a local authority: trust it in the browser once ([../environments/browser-testing-setup.md](../environments/browser-testing-setup.md)).
3. Personal links for the people you will be: `links.php` writes them to a git-ignored file ([seeding.md](seeding.md)). Never paste them into an issue, a pull request or a commit.
4. The mail catcher open in another tab. On DDEV sites Mailpit takes everything the site sends: `http://<site>.ddev.site:8025` (or the secure port, 8026). Nothing is delivered to real people. Clear it before a run so it shows only that run's mail.

## Act as a person

- **Open the personal link in a window where nobody is signed in.** A CMS session wins over a personal link: if the administrator is signed in to the site in that browser, every page opens as the administrator whatever the link says. Sign the administrator out first, or use a separate profile or a private window for each person.
- **Use one window or profile per person** when you want two people at once (a nominator and a nominee, say).
- A personal link opens the election page. For another page, swap `elections/view` in the address: `elections/nominations/create` to nominate, `elections/vote` to vote.
- Act as the person for the whole step, not only the screen: do the thing (nominate, second, accept, withdraw, vote, vote again, read the results), see the message after it, then read the mail it sent.
- Sign in as the organiser (a separate profile) only to administer: create, edit, activate, add candidates. Everything about taking part is the front end.

## Reach each stage

The seeded elections sit at different stages. To see another one, move the election with `move-election.php` ([seeding.md](seeding.md)), for example election 2 to `results`, then run the scheduled jobs so the results are counted:

```bash
ddev exec 'cd /var/www/html/httpdocs && cv api3 Job.execute auth=0'
```

The stages are nominations, advertise (candidates announced and nominees accept), voting and results. Look at each screen at each stage it appears: the election page tells people where the election is and what they can do now, so it changes at every stage. Also look at the refusals: someone outside the election's group, someone else's nomination, a closed stage, a person who has already voted, a bad link.

## What to check on each screen

- **Wording and layout** against the standards: plain words, "people" not "member", one clear next action, messages that say what to do. The message after an action is part of the screen.
- **Phone width.** Set the window to about 375 px wide (the browser's device mode) and check there is no sideways scroll, nothing sticks out of its card, and buttons are comfortably large (36 px high or more). Also 768 px and desktop.
- **Dark mode and contrast.** Turn the browser or operating system to dark and check text, buttons, focus rings and messages stay readable. Try a colour variation of the site's theme: `tests/playwright/fixtures/theme-variants.sh` switches WordPress, Drupal and Joomla default themes between variations (run it inside the container; "default" puts it back).
- **Keyboard.** Do the whole step with Tab, Shift+Tab, Enter and the arrow keys: every button and link can be reached, the focus is always visible, and on the ballot the rank choosers work with the arrow keys and Enter.
- **Screen reader.** Listen to the page with the platform's screen reader (VoiceOver on a Mac): headings in order, form fields with labels, buttons and links with names that make sense out of context, messages announced.
- **The mail.** Read each email in the catcher: who it is from, the subject, the one link in it, and that it works.
- **Each CMS.** The same screen can differ with the theme: look on WordPress, Drupal and Joomla for anything that changes a screen ([cms-and-civicrm-differences.md](cms-and-civicrm-differences.md)).
- **Both CiviCRM versions** when you have both sites.

## What a review records

Write the review where the change is discussed (the pull request, or a file kept outside the repository) and keep it short and specific:

- What you reviewed: the site (CMS and CiviCRM version), the build or commit, the election and stage, the person you acted as.
- For each problem: the screen and step, what you saw, what you expected (cite the standard if one applies), a screenshot, and the width or mode (phone, dark, keyboard).
- What you did **not** look at, so nobody assumes it was covered.
- Every defect found by hand gets an automated test: a persona spec ([personas.md](personas.md)) for the front end, a back end spec or PHPUnit test otherwise.

Screenshots and recordings are for the review. Do not commit ones that show personal links, passwords or real people.
