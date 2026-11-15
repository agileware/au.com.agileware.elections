> **Historical.** Written during version 2.1; may be out of date. Current guidance: [User interface standards](../../../standards/ui-standards.md).

Covers: version 2.1, the screen review against the user interface standards, 2026-11-01 to 2026-11-02. Copied from the project plan with names, paths and account names removed; screenshots under `plan/evidence/` are not kept (image not kept).

# 21 – Screen review against the user interface standards (findings, for the maintainer to check)

Date: 2026-11-01. Standard: [20-ui-standards.md](../../../standards/ui-standards.md) (approved). **Status: findings checked, decisions R1 to R19 all approved by the maintainer on 2026-11-01 ("Approve all"), and the guide amended to match. Nothing in the extension has been changed.** The changes are built on the local `version-2.1` branch when the maintainer says to start.

## 1. How the review was done

Three read-only reviewers each took part of the inventory in section 14 of the guide and checked every screen against sections 2 to 11 and the checklist in section 15. They read the code, templates, managed files, menu, CSS, JavaScript and message templates; nothing was run. The reviewer then checked three of the most serious claims by reading the code itself (the edit form deactivating an election, Declined and Withdrawn being one state, and the "Your vote" block), and all three are true.

**Live check:** the back end screens were looked at in Chrome once the maintainer signed in (section 6, with corrections to this review). The rest of the "Needs live check" items in the detail files (front end as personas, popups, contrast, 44 px targets, 375 / 768 / 1280 px layouts, axe, keyboard order) are still to do.

CiviCRM 6.16.5 SearchKit templates were read for the lists; 6.19 templates were not.

## 2. The detail, per screen

Every finding has an ID, the file and line, the exact current text, the rule in the guide, and the change needed.

| Part | File | Findings |
|---|---|---|
| Back end forms, dialogs, overview, settings, menu titles, error helpers | [21-ui-review/backend-forms.md](21-ui-review/backend-forms.md) | 155 in 15 sections (create/edit election 35, overview 26, add/edit position 18, visibility dialog 12, add candidate 12, edit statement 11, menu titles 11, results page 10 and so on) |
| SearchKit lists | [21-ui-review/searchkit.md](21-ui-review/searchkit.md) | 66 on six lists (Manage Elections 18, Positions running 13, Candidates 12, Positions locked 8, Audit Log 8, Results 7) and 6 cross-cutting |
| Front end and emails | [21-ui-review/frontend-emails.md](21-ui-review/frontend-emails.md) | 126 on eleven screens (ballot 23, election page 21, elections list 15, error pages 11, emails 10 and so on) and a 56 row text scan of the whole extension |

## 3. What the review found, in short

**Back end (all screens)**
- **No breadcrumb anywhere.** `hideNonRequiredItemsOnPage()` switches it off on every Smarty page and no SearchKit page adds one.
- **No Back or Back to Manage Elections on any form.** Add Candidate and Results Page use a "Cancel" submit. The overview has only Back to Manage Elections, and its action buttons are stacked at the bottom in the wrong order with Duplicate among them.
- **Button words.** Save, Create, Edit Position, Add Candidate and Cancel should all be Submit plus the two Back links. The Withdraw Election dialog and the Withdraw row link need to become Deactivate.
- **Labels and words.** Title Case field labels, dash placeholders, three "End Date" fields (the guide said two), "Allow non-logged in access", the dates sentence variants, the audit log "What" column and the cut fingerprint on the overview (the 1 November feedback).
- **Dialogs.** The delete confirmation still says "WARNING: This action cannot be undone." on two dialogs.
- **Guard messages** shown to administrators are not translated, and one can show a column name such as `voting_method`.

**SearchKit lists**
- No heading description, breadcrumb, Back buttons or "Back" wording (the audit and results lists say "Back to the election"), no empty-state sentence ("None found."), no time zone line, no card layout at 375 px.
- The drag handle is titled "Drag to reposition" and cannot be used from the keyboard.
- SearchKit itself supplies: a table with no caption and no `scope` on headings, the count text "N results", icon-only inline edit buttons, `btn btn-primary` toolbar buttons. (Dates already use the CiviCRM format.)

**Front end and emails**
- **No "Back to the election" anywhere.** The candidate page has a primary Back next to a primary Edit statement.
- **Errors reach voters as developer text** ("Member is not allowed to vote in given election."), more than 30 untranslated strings in API and helper code. There is no not-found page and no error page sets a 403 or 404 status.
- Stage and date wording varies in at least nine places ("until", "will close", "has closed"); the front end list and election page show administrator buttons in the CiviCRM style (`button`) that the back end already has.
- The ballot, nominate and accept forms need label, placeholder and capitalisation changes; two primary buttons on one page (candidate page), and a primary button on every nominee card.
- Reduced motion for the celebration is handled correctly, and `election-btn` already has `focus-visible` and a 44 px minimum height.

**Behaviour found while reading (not wording)**
1. **Editing an election deactivates it.** `CreateElection::postProcess()` always sends `is_visible => 0` (`CRM/Elections/Form/CreateElection.php:260`, since the first commit), and the API create replaces it. An active election that has not started becomes inactive after any edit. Elections that have started cannot be edited. The same save also replaces `created_by` with the editor (`api/v3/Election.php:51`).
2. **Declined and Withdrawn are one state in the data.** `Process::withdraw()` always sets `has_rejected_nomination = 1` and `has_accepted_nomination = 0`, for a nominee who declines and for a candidate who pulls out (`CRM/Elections/Helper/Process.php:199-204`). Decision U3 needs the two told apart.

## 4. Decisions needed from the maintainer

All 19 approved as recommended (The maintainer, 2026-11-01), and R20 (added later the same day) approved too. Guide sections amended for R3 (6.2), R4 and R5 (3.7, 3.8, 3.6), R6 (3.8), R7 and R8 and R18 (4, 12), R9 and R10 (3.5), R11 and R12 (6.3), R13 (7.1), R14 (9.8), R15 (5.5), R16 (5.6, 3.8, 9.3, 12) and R19 (8.7). R1, R2, R17 are code changes, R1 timing decided by the maintainer 2026-11-01: all of these changes go into 2.1, no 2.0.1.

| # | Decision | Recommendation |
|---|---|---|
| R1 | **Edit Election deactivates an active election** (finding above). Fix it? | **Yes**: an edit keeps the election's current Active/Inactive state and does not change who created it. Release timing (The maintainer, 2026-11-01): goes into 2.1 with everything else, no 2.0.1. |
| R2 | **Declined vs Withdrawn** (U3) needs a stored difference: add a column recording that a candidate withdrew after accepting, with an upgrade script. Existing rejected rows cannot be told apart reliably (no record of what was accepted first), so they would show as **Declined**. | **Yes**, add the column and the upgrade step; show existing rows as Declined. |
| R3 | **The "Your vote" block on the election page** lists each position's chosen candidates to the signed-in voter or a person using their personal link. Guide 6.2 says never show what was chosen on a shared page. | **Keep the block** (a voter can check what was recorded; only they see it) and amend 6.2 to say: the receipt may show the person their own choices to them alone, never to anyone else, and never when the election anonymises votes. |
| R4 | **The overview heading.** Today "Election Overview"; breadcrumb ends with the election name. | The heading is the **election name**, with "Election Overview" as a small label above it; the breadcrumb's last item is the election name. |
| R5 | **Activate / Deactivate on the overview** (3.6 gives it none, but the Next message tells the administrator to activate). | Add it to the overview action row, after View as a Voter, shown as **Activate** or **Deactivate** with the stage badge's state. |
| R6 | **Candidates list.** No route deletes a nomination, so 3.8's row **Delete** cannot exist; the first column should be the name (it is Position); the "Candidate" column also lists nominees; Withdrawn cannot be told from Declined until R2. | Remove Delete from the Candidates row actions in 3.8; first column Name, then Position; the status column says Nominee, Candidate, Declined or Withdrawn. |
| R7 | **Results list** also starts with Position (4.2 wants the name). | Keep Position first here: a result belongs to a position. Record this as the exception in 4.2. |
| R8 | **Locked actions on lists.** 3.4 wants a disabled action with its reason, but a SearchKit link cannot be disabled. | Show the reason in the row (a "Locked: voting has started" text) and do not show the link; keep disabled buttons for the overview only. |
| R9 | **Elections Settings** is CiviCRM's generic settings form. Adding Submit, Back, Back to Manage Elections needs a form hook. | Add the hook; the page keeps CiviCRM's own breadcrumb as 3.7 says. If it proves fragile on 6.19, leave CiviCRM's own Save and record it as an exception. |
| R10 | **Edit Statement** is used by candidates (front end) and administrators (back end). | Administrator: Submit, Back, Back to Manage Elections plus the breadcrumb. Candidate: Submit and **Back to the election**. One form, two footers chosen by who is using it. |
| R11 | **Administrator buttons on front end pages** (Create Election, Activate, Edit, Delete on the list cards; Edit Election and Delete Election on the election page). They use the CiviCRM `button` class, which U9 forbids on the front end, and repeat the back end. | **Remove them** from the front end; administrators use the back end. |
| R12 | **Links in a new window.** 6.3 says links stay in the same window. Four places open a new one (three time zone links and a candidate's statement link to another site). | Replace the time zone links with one line "Times are shown in %1." Keep the statement link in a new window with `rel="noopener noreferrer"`, because it leaves the election; amend 6.3 to allow this for links to other sites. |
| R13 | **The guide against itself:** 7.1's example subject says "Please accept…" and 8.5 says no "please". | 8.5 wins; the example becomes "Accept or decline your nomination for President". |
| R14 | **Celebration:** 9.8 says it runs once; its help text says it shows each time the page is opened. | Keep the behaviour (shows each time the results page is opened, with a visible stop control); change 9.8 to say that. |
| R15 | **Dialog button names:** 5.5 says "Delete Election", 3.8 says "Delete" / "Cancel". | Title of the dialog "Delete Election"; the button just **Delete** (3.8). Same for the others. |
| R16 | **Guide gaps** found: no 3.8 entry for **Fill in the dates** and **Preview** (Results Page), no icon for Results Page (code uses `fa-image`), the guide says two "End Date" fields (there are three), the Edit Election form includes the results page fields that 5.6 does not list. | Add the labels and the icon (`fa-image`) and the three End Dates; add the results page fields to 5.6 under Display. |
| R17 | **Error pages.** There is no not-found page and no error page sends 403 or 404. More than 30 plain developer messages reach voters. | Build a not-found page; send 403 and 404; every message a person can see goes through `ts()` in plain words, with detail to the CiviCRM log. Part of the 2.1 work. |
| R18 | **SearchKit limits** (table caption and `scope`, "N results", icon-only inline edit buttons, toolbar `btn btn-primary`, 375 px cards). | Add the caption, `scope` and a card layout with extension CSS and a little JavaScript; record "N results", the inline edit icons and the toolbar classes in guide section 12 as SearchKit exceptions. Check 6.19 when built. |
| R19 | **Time zone line:** site time zone or the election's? | The **site time zone** (what dates are stored and displayed in today), shown once as "Times are shown in %1." |
| R20 | **Election list refuses people on a personal link** (cause and evidence in section 8): the route lacks the access callback the other public routes have; the documentation says it works. | **Yes** (approved by the maintainer, 2026-11-01): add the callback; carry the personal link on the card links if it is not already; add a Playwright test for a personal link on the list. |

## 5. Proposed order of work for the changes

If the decisions above are agreed, the changes are built on the local `version-2.1` branch in this order, each with tests, docs updated and screenshots re-recorded at the end:
1. **R1 fix** (and the `created_by` fix), with a test. Separate commit on `version-2.1`; released with 2.1 (decided 2026-11-01).
2. **Shared frame:** one breadcrumb helper, one action bar (Back, Back to Manage Elections, then the actions), one edit form footer, one "Back to the election" partial. Everything else builds on this.
3. **Election overview** (the 1 November feedback: O1 to O6, plus R4 and R5), then the edit forms and dialogs.
4. **Terminology and labels** across back end, SearchKit and front end (the glossary, Submit, Decline/Withdraw wording, Deactivate, the date sentences, capitalisation, placeholders, plurals), with a test that fails on the banned words.
5. **R2** data change and upgrade step, then the Declined and Withdrawn wording.
6. **Front end:** error pages (R17), election page and list, ballot, emails.
7. **SearchKit lists** (R6 to R8, R18), then the live accessibility checks at 375, 768 and 1280 px on WordPress, Drupal and Joomla.

## 6. Live check of the back end (Chrome, signed in as the site administrator account, `election-test`, CiviCRM 6.19.4, 2026-11-01)

Looked at, read-only (nothing was submitted or changed): Election Overview (elections 2 and 5), Manage Elections, Candidates, Audit Log, Positions (active election), Edit Election (elections 1 and 5) and the Results page form. **Not yet looked at:** the front end as personas (the signed-in WordPress admin session overrides personal links, so the front end check has to be done after signing out of the front end host), 375 / 768 / 1280 px layouts, dark mode, axe, keyboard order, the Duplicate and Delete popups, and Drupal and Joomla.

**Confirmed as the review said**
- The overview has no breadcrumb, no Back, "Back to Manage Elections" only; the action buttons are at the bottom, stacked and staggered; Duplicate is among them; the Edit button is "Edit settings".
- The audit table's What column shows `nomination_changed`; the text below it reads "Audit log fingerprint: 1bfd7a2a7baf22f7..." (cut with "...").
- Edit Election has one footer button, Submit, and no Back or Back to Manage Elections. Five "Start Date" and three "End Date" labels, plus Title Case labels ("Number of Required Nominations", "Voting Method", "Anonymise Votes") and "Allow non-logged in access".
- The overview heading reads "Election: <name>"; the timeline says "Visible to people" and "Hidden from people".
- No page has an `h1`: the page title is an `h2` (WordPress back end; heading order to be fixed in the standard frame).
- The error page for an election that cannot be edited ends with a "Return home" button (not Back or Back to Manage Elections).

**Corrections to the review**
- **SearchKit lists do show a breadcrumb**, but it is CiviCRM's own menu trail ("CiviCRM » Elections » Manage Elections", "… » Election Candidates"). It never includes the election name, so it is not the 3.7 trail; the review said "no breadcrumb" for the lists, which is wrong. The Smarty pages (overview, forms) show none, as the review said. The standard frame must replace CiviCRM's trail on these pages.
- **The audit list does have a description** ("Entries cannot be changed or removed. Each carries a hash of the entry before it, which is checked on the election overview."), so S-AU's missing-description finding is wrong. It uses the word "hash" (the glossary says fingerprint), and the Back button reads "Back to the election". The audit and candidate lists load their rows after the page appears, so an empty table in a screenshot is not a finding.
- Positions (active election) also has a description and an info box ("They cannot be changed, so there is nothing to drag, edit or delete."); only the Back button and the breadcrumb are off.

**New**
- **An inactive election that has already run opens Edit Election with an error banner ("Error: Election details cannot be edited once it has been run.") above a form that still has a Submit button** (election 5). An active one that has run shows a separate error page (election 1). The two cases behave differently and the first lets a person fill in a form that cannot be saved. The standard (3.4, 3.5) wants a disabled action with the reason; for the form itself, the fix is to show the reason and no form.
- The Results page form is complete and readable but its button is "Save"; that matches the review.

## 7. Live check of the front end, part 1 (as the signed-in administrator, 2026-11-01)

The persona check (nominee, nominator, voter, candidate through personal links) cannot be done while the site administrator account is signed in to the same host: the WordPress session wins over a personal link. Waiting for the maintainer to sign out of `election-test`. Looked at as the administrator, read-only: the election list (`civicrm/elections`) and the election page for election 2.

**Confirmed**
- The election list shows an administrator-only **Create Election** button in the CiviCRM `button` style on a front end page (R11).
- Card buttons read "Vote in the Election", "View the Candidates", "View and submit Nominations", "View Election results", not the 3.8 words (Vote, View Candidates, Nominate or Accept Nomination, View Results).
- A stage badge reads "Candidates announced"; the guide's stage word is "Candidates advertised" (2.2).
- The time zone appears as a link "(GMT+11:00)" on every card that opens in a new window (R12); on the election page it is one line "Times are shown in GMT+11:00" with the link. The guide asks for the zone name, not an offset (R19: the site time zone, for example "Times are shown in Australia/Sydney").
- Dates use CiviCRM's format ("October 6th, 2026 9:00 AM"). Good: no change.
- The election list and election page each have one `h1` (the title). Good: no change.
- The election page has no "Back to the election" or Back link (none is needed on the election page itself); the stage stepper and "Voting is open until …" panel read well.

## 8. Live check of the front end, part 2 (as the voter Eleanor Whitlock on her personal link, 2026-11-01)

The maintainer signed out; Eleanor (contact 123) is a voter who has already voted in election 2. Looked at, read-only: election page (election 2, voting), ballot (election 2), nominate (election 1), election page for results (election 8), ballot for a closed election (8), an unknown election (999), and the election list at a narrow window.

**Confirmed**
- **Ballot:** the submit button is "Vote" (the guide wants Review Your Vote, then Submit Vote); no back or "Back to the election" link; heading "You are Voting for …" (capital V); no placeholders to check on a plurality ballot.
- **Nominate:** the heading is "Submit Nomination for …"; the position select shows "- Select Position -"; labels "Nominated Position" and "Nominee" are each present three times in the markup (the select and its search widget repeat them); help says "Only persons that can vote…" (glossary: people); the long label "Why do you want to nominate this person for this position?" is a question (5.6 wants "Reason for the nomination"); button "Nominate" is right; no back link.
- **Results page:** heading "<election name> – Result"; the link "View the Election summary" (capitalisation, 2.4); no back link.
- **"Your vote"** shows Eleanor her recorded choices under each position, with the note "This is what was recorded. Only you can see it here." (R3: kept; the build checks it is hidden for anonymised votes).
- **Error pages:** an unknown election (999) shows "You are not authorised to access this page." under the heading "View Election", not a not-found page; a closed ballot shows **both** a red "Error: You are not authorised to access this page." and a green "Voting is closed on October 17th, 2026 5:00 PM." with a "Return to Elections" button (contradictory messages; "Return to Elections" and "Voting is closed on" are old wordings). Both end in "Return home" or "Return to Elections", not Back to the election. (R17)
- Good as it is: the welcome strip with "Not Eleanor Whitlock … Login here.", the stage stepper, the time zone line, one `h1` per page, and no horizontal scroll at the narrowest window the browser allows (500 px; true 375 px not testable in this window).

**Needs follow-up**
- **The election list on a personal link: cause found (2026-11-01).** `civicrm/elections` is the only public route that lacks `<access_callback>CRM_Elections_Permission::check</access_callback>` in `xml/Menu/elections.xml`. The other eight public routes (view, nominate, accept, reject, vote, candidate, statement, summary, results logo) have it, and that callback is what lets a valid personal link through. So a person with only a link is refused at the list with "You do not have permission to access this page." (reproduced twice, as Eleanor, on election-test). `docs/roles_and_permissions.md` line 52 says the list works with "view Elections (or a valid personal link)", so this is a defect against the documentation, not a design choice. **Decision R20 (new):** add the callback to the list route in 2.1 and, when the person arrived on a personal link, carry `cid` and `cs` on the card links so the next page does not refuse them (check this when building: not verified whether the card links keep the link). **Approved by the maintainer, 2026-11-01.**
- **Not looked at:** the Accept and Decline screens (need a nominee with a pending nomination), the candidate page, the ballot's review window and write-in field, the summary page, emails as received, dark mode, axe, keyboard order, Drupal and Joomla.

## 9. Build status (2026-11-01, local `version-2.1`, nothing pushed)

Done and committed: R1 and R20 fixes, the page frame (breadcrumb on every back end page, Back and Back to Manage Elections, Submit on every edit form), the overview (O1 to O6), the dialogs, and the wording, label, list, front end, error page and email changes of the four passes (back end forms, SearchKit lists, front end pages and errors, front end forms and emails). PHPUnit: 319 tests pass. Upgrade step 20120 rewrites the email wording on installed sites (the reserved copy always; the editable copy only if the site never changed it), with a test.

Open: R2 (Declined versus Withdrawn: a stored difference, an upgrade step, and the status words in the overview write-in table, the election page, the candidate page and the Candidates list), the full Playwright run (being brought to green after the wording changes), the docs and screenshots, and the live check on election-test and on Drupal and Joomla.

Notes for later: the STV work in [18](../../../roadmap/2.2/stv.md) planned its upgrade steps from 20120; 2.1 now uses 20120 (emails) and will use the next numbers for R2, so STV starts after them. Decisions the passes could not make are listed in the final report of this build and need a ruling: the template variable list on the nomination email field (B-CE-24), the sentence on the Delete Election dialog (B-XE-1), the label "CMS" on the Elections Settings page (B-ST-3), "View Election Summary" versus "View Results", whether to build View Candidates and View Nominations buttons, and "Add Another Write-in" for the ballot (not yet in guide 3.8).

## 10. Build result (2026-11-01, local `version-2.1`, 12 commits, nothing pushed)

**Built:** everything in sections 3 and 4 (R1 to R20), including R2 (new column `has_withdrawn_nomination`, upgrade step 20130; nominations refused before the upgrade show as Declined) and the email wording upgrade (step 20120, with a test). User documentation updated to the new screens.

**Tests:** PHPUnit 321 tests pass. Browser suite: WordPress 115 passed, 2 flaky (passed on retry), 1 skipped by design; Drupal 11: 117 passed, 1 skipped; Joomla 5: 110 passed, 8 skipped (six by design, one needs a member session, one is the screenshots spec). The 1.3.2 upgrade test passes. The build found and fixed real defects: seats and required nominations accepted 0, a PHP warning on the election list for an election without a description, a 500 on every back end page on Joomla (the breadcrumb format) and a deprecation banner on Drupal.

**Still to do**
- Look at the new build in Chrome on election-test (not deployed there: election-test still runs the 2.0.0 release for the maintainer's testing). Deploying 2.1 there upgrades its database (steps 20120 and 20130); the snapshot `before-2.0.0-release` can restore it.
- Re-record the documentation pictures. Out of date: `images/admin_election/01-04.gif`, `admin_edit/01-03.gif`, `admin_add/01-04.gif`, `admin_delete/01-03.gif`, `user_nominate/01-04.gif`, `user_vote/01-04.gif`, `user_view_results/01-03.gif`, `setup/election-listing-page.png`, `setup/election-page.png`.
- Keep Chromium's system libraries across DDEV stop and start (they are lost each time and `npx playwright install --with-deps chromium` is needed): todo item 4.
- The GitHub workflows still list the deleted `version-2` branch (`branches: [master, version-2]`); change it when 2.1 is pushed.

**Decisions (The maintainer, 2026-11-02)**
1. "Locked" stays and is in the glossary (guide 2.1).
2. The Delete Election dialog says the election can no longer be seen or used and cannot be restored from the screen. Done.
3. The label "CMS" on Elections Settings stays.
4. The template variables stay in the nomination email field help.
5. "Second Nomination", "Add Another Write-in" and "View Election Summary" are in guide 3.8; the unbuilt View Candidates and View Nominations buttons are dropped from the guide. The election card button already reads "View Results"; "View Election Summary" is the link on the results page that opens the summary, so it keeps its name.
6. Joomla's breadcrumb (current page is a link) is a recorded exception in the guide.
7. Rechecked and agreed (below): an inactive election with ballots keeps its rules locked; nothing to change.
8. STV upgrade steps start at 20140.

**Decision 7 rechecked.** An inactive election that has ballots opens its edit form without any banner (the banner seen on 2.0.0 came from the old code). The rules that decide who votes and how votes count (voting groups, nominating and candidate groups, anonymise votes, allow a changed vote, required nominations, voting method, voting dates, results) stay locked once ballots exist, whether the election is active or not: the server refuses the save with a message naming the setting (tested: `SecurityTest::testAnInactiveElectionThatHasBallotsKeepsItsRulesLockedAndNothingElse`). The name, description, display options and the nomination and visibility dates can still be corrected. My earlier recommendation (refuse editing altogether) is not needed: the finer rule already protects the vote. The maintainer agreed on 2026-11-02: keep it as it is.

## Progress
See [08-progress.md](08-progress.md).
