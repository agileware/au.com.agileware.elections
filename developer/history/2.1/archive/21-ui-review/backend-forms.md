> **Historical.** Written during version 2.1; may be out of date. Current guidance: [User interface standards](../../../../standards/ui-standards.md).

Covers: version 2.1, a detail file of the screen review, 2026-11-01 to 2026-11-02. Copied from the project plan with names, paths and account names removed; screenshots under `plan/evidence/` are not kept (image not kept).

# Review of the back end forms, dialogs and pages against the UI standards (plan/20-ui-standards.md)

Scope: Smarty and QuickForm back end screens, the Election Overview page, the Elections Settings page, the menu titles and the shared helpers they call. Read-only review of branch version-2.1 (same code as 2.0.0). Nothing was run; every row is from reading the code. Paths below are relative to `au.com.agileware.elections/` unless stated.

How to read the tables: ID is the finding number; Where is file:line; Found quotes the current text or markup; Rule is the guide section; Change needed says what the screen must do after the change (not code). "Dialog" screens are opened by SearchKit row links with `target crm-popup`; "full page" screens are opened by a plain link.

Shared facts that apply to every screen below (checked once here, not repeated per row unless a screen differs):

- `hideNonRequiredItemsOnPage()` (`elections.php:202-205`) assigns `breadcrumb` FALSE on every full back end page and the overview. No back end page calls `CRM_Utils_System::appendBreadCrumb`. A search of the whole extension finds no breadcrumb code. So no screen has a breadcrumb today (rule 3.1, 3.7).
- No back end Smarty page has a Back or a Back to Manage Elections button, except the overview, which has only Back to Manage Elections.
- No page template contains an `h1`; the heading is the page title set by `CRM_Utils_System::setTitle` or the menu title. Whether the CMS or CiviCRM turns it into an `h1` needs the browser (see Needs live check).
- Smarty back end buttons come from CiviCRM's `CRM/common/formButtons.tpl`, class `crm-button`. `css/electionforms.css:524-575` restyles every `button.crm-button` inside `.crm-election-form-block-container` in the election colour (filled), and only `.crm-button-type-cancel` is made quiet (`css/electionforms.css:595-598`). The overview links use the CiviCRM `button` class on `a`. This is the correct family for rule 9.2 / U9 (`button`), but it means any extra `crm-button` (for example Preview, Fill in the dates) also looks like a primary button.

---

## 1. `civicrm/elections/manage` Manage Elections (page wrapper only)

Files: `ang/afsearchManageElections.aff.html`, `ang/afsearchManageElections.aff.php`, `managed/SavedSearch_ManageElections.mgd.php` (only the toolbar and the row link that belong to dialogs in this review).

Currently conforms: page title "Manage Elections" (`ang/afsearchManageElections.aff.php:6`); the election name links to the Election Overview (`managed/SavedSearch_ManageElections.mgd.php:74`); row actions Edit, Duplicate, Delete are in the 3.8 order and the danger style is on Delete; the table has a description sentence (`:63`).

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| B-MG-1 | `ang/afsearchManageElections.aff.html:11` | `<button class="af-button btn btn-warning" type="reset" crm-icon="fa-undo">Reset</button>` | 4.4, 9.2 | The control that clears the filters is a single link labelled "Clear filters", not a warning-coloured button labelled "Reset". Use a quiet style (not `btn-warning`). |
| B-MG-2 | `managed/SavedSearch_ManageElections.mgd.php:116` | toolbar `'text' => E::ts('Create Election')` | 2.3, 3.6, 3.8 | The toolbar button reads "Add Election" (primary, top right, icon `fa-plus` already right). |
| B-MG-3 | `managed/SavedSearch_ManageElections.mgd.php:102` | row link `'icon' => 'fa-ban', 'text' => E::ts('Withdraw')` shown when `is_locked` | 2.1, U4, 3.8 | The row action is "Deactivate" for every active election (running or not), same icon as the other Deactivate link (`fa-toggle-off`). "Withdraw" is not used for an election. The dialog it opens carries the stronger confirmation (see section 7). |
| B-MG-4 | `ang/afsearchManageElections.aff.html:3-9` | filter labels and help are plain strings: `help_post: 'Yes shows elections that are switched on for voters. No shows elections that are still being set up or have been switched off.'`, `help_post: 'Where the election is now. Not running means it is switched off or its nominations have not started.'`, `<details ... af-title="Filters"`, "Reset" | 2.1, 2.2, 11 | Reword to the Active / Inactive vocabulary ("Yes shows active elections. No shows inactive elections.", and "Not running" becomes the stage word "Inactive" or "Not started"). Whether Afform passes these literal strings through translation is unverified; if it does not, they must be made translatable the way the other aff files do (`{{:: ts('...') }}`). |
| B-MG-5 | `ang/afsearchManageElections.aff.html` (whole file) | no heading, no description or breadcrumb markup in the wrapper; the table description is only in the SearchKit display settings (`managed/SavedSearch_ManageElections.mgd.php:63`) | 3.1, 3.3 | The page needs the breadcrumb root "Manage Elections" shown as the current page. Whether SearchKit shows the table `description` above the table is unverified (live check). |

---

## 2. `civicrm/elections/create` Add Election / Edit Election (full page edit form)

Files: `CRM/Elections/Form/CreateElection.php`, `templates/CRM/Elections/Form/CreateElection.tpl`, `templates/CRM/Elections/Form/ResultsDisplayFields.tpl`, `js/election-form.js`, `CRM/Elections/BAO/Election.php` (date rule messages).

Currently conforms: one `Submit` button, default, outside the tab panels at the bottom (`CreateElection.php:223-229`, `.tpl:258-260`); "Edit Election" heading when editing (`:25`); labels already right: Name, Description, Election cycle, Each phase starts at, Each phase ends at, Results are published, Allow people to nominate online, Email people about their nominations, Allow people to change their vote, Show candidate photos, Show who nominated each candidate, Candidate name format; the "You cannot see any groups." notice (`.tpl:120`) is the pattern the guide keeps; the timeline preview has `aria-live="polite"` and date problems have `role="alert"` and `aria-invalid` (`js/election-form.js:55-58`); dates use CiviCRM's datepicker; tabs are a proper tablist with arrow-key handling.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| B-CE-1 | `xml/Menu/elections.xml:13`; no `setTitle` for a new election (`CreateElection.php:24-30`) | New election heading is the menu title "Create Election" | 2.3, 3.7 | Heading "Add Election" when no election id, "Edit Election" with one (already). |
| B-CE-2 | `CreateElection.php:15-31`, `elections.php:202-205` | No breadcrumb (assigned FALSE) | 3.1, 3.7 | Add the trail Manage Elections › Add Election (new) or Manage Elections › election name › Edit Election (edit; election name links to the overview, escaped, never cut short). |
| B-CE-3 | `CreateElection.tpl:258-260` | Footer has only Submit | 3.2, 3.5, 3.6, 3.8 | Footer order: Submit (primary), Back, Back to Manage Elections. Back and Back to Manage Elections are links styled as quiet buttons, not form submits, icons `fa-arrow-left`. Back returns to the page the person came from, otherwise to the page above in the trail (the overview when editing, Manage Elections when adding). |
| B-CE-4 | `CreateElection.tpl:2` | "Use this form to configure the basic information and timing for the various phases of your Election." | 2.4, 3.3 | Lower-case "election" in running text. Add one line at the top: "Fields marked * are required." (3.5). |
| B-CE-5 | `CreateElection.php:149-164`, `:150,154,161` | Five fields labelled "Start Date" (visibility, nomination, advertise candidates, voting, results) and three labelled "End Date" (visibility, nomination, voting). The guide says two End Dates; the code has three. | 5.6, U17 | Labels exactly: "Visible from", "Visible until", "Nominations open", "Nominations close", "Candidates advertised from", "Voting opens", "Voting closes", "Results published on". Each unique on the form. |
| B-CE-6 | `CreateElection.php:146` | quick-start picker label "Nominations open" (would collide with the new label of `nomination_start_date`) | 5.6 | Label "Nominations start on". |
| B-CE-7 | `CreateElection.tpl:33` | "Visible for (weeks after the results)" | 5.6 | "Election stays visible for (weeks after the results)". |
| B-CE-8 | `CreateElection.php:145` | placeholder `'- Choose a cycle -'` | 2.4 | "Select a cycle". |
| B-CE-9 | `CreateElection.tpl:42-45,58-61,72-75,84-87,98-101` and each date row | One description per group ("Set the range of dates when this election can be seen by voters." and four more); the date fields themselves have no help | 5.6 | One sentence of help under each of the eight date fields saying who sees what from that date. The group lines can go or stay as headings only. Group wording: "voters can submit nominations" should be "people can nominate" (voter = person who may vote, 2.1). |
| B-CE-10 | `CreateElection.tpl:22,111`; `CRM/Elections/Helper/TimeZone.php:6-8` | "(times are in %1)" shown twice, and the value is `'GMT' . date('P')`, for example "GMT+11:00" | 8.7 | One line per page: "Times are shown in Australia/Sydney" (the zone name, not an offset), above the dates, not repeated in the quick set-up and the timeline. |
| B-CE-11 | `js/election-form.js:67-69` | `date.toLocaleString([], {dateStyle: 'medium', timeStyle: 'short'})`, and placeholders `'—'` and `' → '` | 8.7 | The timeline preview must show dates in CiviCRM's configured date and time format, not the browser's. Pass CiviCRM's format (or pre-formatted values) to the script. The arrow and dash glyphs should become words ("from … to …"). |
| B-CE-12 | `CRM/Elections/BAO/Election.php:415-425` (shown by `CreateElection.php:236`, `js/election-form.js:42`) | Date messages: "End date must be after start date." (used for three different fields), "Start date must not be before the visibility start date.", "End date must be before visibility end date.", "Start Date must be after nomination end date.", "Start Date must be before visibility end date.", "Start date must be after advertise candidate date.", "Start Date must be after voting end date." | 5.3, 5.6 | Each message names its field with the new label and says how to fix it, for example "Voting closes must be after Voting opens." in the form the guide gives ("Voting must close after it opens"). Same capitalisation in all (sentence case, no "Start Date"). |
| B-CE-13 | `CreateElection.php:237-239` | "Value should be a positive integer." | 5.3 | Name the field: "Required nominations must be a whole number of 1 or more." |
| B-CE-14 | `CreateElection.php:180`; `.tpl:127-130` | label "Number of Required Nominations"; help "Enter the number of nominations required for a person to become eligible candidate in this election." | 2.4, 5.6, 8 | Label "Required nominations". Help is one correct sentence ("The number of nominations a nominee needs to stand as a candidate.") with a full stop. |
| B-CE-15 | `CreateElection.php:182-184` | `addEntityRef('allowed_groups', 'Allowed by Groups', ...)` with placeholder `'- Select Groups -'`; neither goes through `E::ts()` | 11, 2.4, 5.6 | Label "Voting groups", placeholder "Select groups", both wrapped in `E::ts()`. |
| B-CE-16 | `CreateElection.php:201`, `:204` | labels "Nomination Groups", "Candidate Groups"; placeholder `'- Same as voting groups -'` | 5.6, 2.4 | "Nominating groups", "Candidate groups"; placeholder "Same as voting groups". |
| B-CE-17 | `CreateElection.php:172` | "Voting Method" | 2.4, 5.6 | "Voting method". |
| B-CE-18 | `CreateElection.tpl:188` | "Ranked choice: voters number the candidates in order of preference and the count passes votes on until someone has a majority. Most votes win: ... This cannot be changed once people have voted." | 2.1, 8.2 | Four sentences; the guide allows two. Use "rank" (not "preference", "number"), not "count" for the output. For example: "Ranked choice: voters give each candidate a rank. Most votes win: voters tick candidates. It cannot be changed once people have voted." |
| B-CE-19 | `CreateElection.php:168-170,174-176` | "Anonymise Votes" and "Allow people to change their vote" are required `select` fields with `Yes/No` and placeholder `'- Select -'` | 5.2, 2.4, 5.6 | Remove the "- Select -" placeholder. Two exclusive choices are radio buttons (or a checkbox for the "Allow people to change their vote" wording). Label "Anonymise votes". |
| B-CE-20 | `CreateElection.tpl:196,204` | help "Select "Yes" to allow people to change their vote right up until the close of voting in this election." and "Select "Yes" to anonymise votes when results are generated for this election." | 5.2 | Reword to say what the choice means once the control is a checkbox or radio ("People can change their vote until voting closes."); no "Select Yes". |
| B-CE-21 | `CreateElection.php:178`; `.tpl:212` | label "Allow non-logged in access"; help "Allow people to access Election pages and perform actions when not-logged in. Requires the user to have a valid checksum URL." | 2.1, 5.6, 8.4, 2.4 | Label "Allow people to take part without logging in". Help avoids "checksum", "user", "Election pages": "People can take part by using their personal link, without logging in." |
| B-CE-22 | `CreateElection.tpl:154` | "Email a nominee when they have enough nominations to stand, asking them to accept or withdraw." | 2.1, U3 | "accept or decline". |
| B-CE-23 | `CreateElection.php:196` | label "Nomination email" | 5.6 | "Nomination email template". |
| B-CE-24 | `CreateElection.tpl:164` | "A template can use these variables: {$nomineeName}, {$positionName}, ..." | 8.4 | Technical placeholders shown to administrators: confirm with the maintainer whether this is allowed jargon; if not, describe in words or move to the documentation. |
| B-CE-25 | `CreateElection.tpl:163` | "Edit message templates" link with `target="_blank"` and no mention that it opens a new tab | 10 | State "(opens in a new tab)" in the link text. |
| B-CE-26 | `CreateElection.php:218`; `CRM/Elections/BAO/Election.php:43-47` | label "Candidate statements"; options "Not on the ballot", "Expandable "Read statement" on the ballot", "In full on the ballot" | 5.6 | Label "Show candidate statements"; the three choices as in 5.6: "Not shown", "Read statement", "In full". |
| B-CE-27 | `CreateElection.tpl:21,42,58,72,84,99,110`; `ResultsDisplayFields.tpl:3,41` | `h3` group headings with no `h2` above (title is the page heading) | 10 | Headings in order: use `h2` for the group headings. |
| B-CE-28 | `js/election-form.js:106-114`; `CreateElection.tpl:6,19,117,219` | Tab names "Election", "Dates", "Participation", "Display" | 3.5, 5.6 | Tabs: Details, Dates, Participation, Display, Emails, Privacy. The email fields (`send_emails`, nomination template) move to Emails; "Anonymise votes" moves to Privacy; "Election" becomes "Details". The quick set-up stays with Dates. |
| B-CE-29 | `CreateElection.tpl:254` (include `ResultsDisplayFields.tpl`) | The Display tab also holds the results page fields: "Results page" group, "Header text", "Header logo", "Logo description", "Remove the logo", "Footer text", "Display celebration effects on the Election results page", "Celebration effect", "Celebration lasts (days)" | 5.6, 3.8 | 5.6 lists these only for the Results Page form, not for Edit Election. Decision needed: remove them from Edit Election (the Results Page screen already edits them) or add them to the 5.6 table. If kept, labels must read as in B-RD-3 to B-RD-5. |
| B-CE-30 | `CreateElection.tpl:35`; `css/electionforms.css:524-525` | "Fill in the dates" is `<button type="button" class="crm-button">`; styled as a filled primary button | 3.6, 9.2, 3.8 | Style it as a secondary button so Submit is the only primary on the page. "Fill in the dates" is not in the 3.8 table: add it before use. |
| B-CE-31 | `CreateElection.php:275,277` | "Election has been created successfully." / "Election has been updated successfully." | 5.4, 7 | "Election added." / "Election saved." (past tense, names the thing, no "successfully"). |
| B-CE-32 | `CreateElection.php:289-292`, `:283-286` | On an error `setStatus(message, '', 'error')` then `return` | 5.4 | Stays on the form with input kept (good). Message must follow section 7 (what happened, why, what to do); the guard messages it can show are listed in section B-ER. |
| B-CE-33 | `CreateElection.php:260` | `'is_visible' => 0` is sent on every save, new and edit | 7 (a message says what happened), 5.4 | Verified in the code that the form sends it on edit; the effect (an active, not-yet-started election becoming inactive on save) is unverified. If it deactivates, the success message and the page must say so, or the field must be left out of an edit. Needs a live check. |
| B-CE-34 | `CreateElection.php:296`, `finish(...manage...)` | After Submit the person lands on Manage Elections whatever they started from | 5.4 | Unverified whether this is the intended "page they started from". Edit from the overview should return to the overview. |
| B-CE-35 | `CreateElection.php:44-46`, `:30` | A locked election throws `CRM_Extension_Exception($canEdit)` with the strings "Election details cannot be edited once it has been run." / "Election has been deleted and cannot be edited." (not through `ts()`; `CRM/Elections/BAO/Election.php:308,312`) | 7, 11 | Show a written message on a normal error page (what is not allowed, why, what to do: "Deactivate the election to edit it"), with the Back buttons, and pass the text through `ts()`. How CiviCRM renders the exception needs a live check. |

Needs live check (screen 2): that the heading is an `h1` and the tab order and focus are logical; focus moving to an error summary (none exists in the code: errors are per field only, no "There are N problems to fix" summary, see 5.3); contrast of the "passed" timeline text; that "Continue?" in the Fill in the dates confirm (`js/election-form.js:213`) uses Continue/Cancel and not Yes/No; the 375 px layout of the quick set-up fields.

---

## 3. `civicrm/elections/overview` Election Overview (view page)

Files: `CRM/Elections/Page/ElectionOverview.php`, `templates/CRM/Elections/Page/ElectionOverview.tpl`, `CRM/Elections/BAO/Election.php` (next step and stage text), `CRM/Elections/Helper/Integrity.php`.

Currently conforms: the stage badge shows the stage word as text, not by colour only (`ElectionOverview.tpl:6`), and the stage words are the 2.2 words (`CRM/Elections/BAO/Election.php:58-78`); timeline and audit dates use `crmDate` (`.tpl:20,97`); the Next message uses `messages status no-popup`; counts only, never who voted for whom, and write-ins never show who wrote them in; recent audit entries are election changes, not individual ballots; icons are right for Positions `fa-list-ol`, Candidates `fa-users`, Results `fa-trophy`, Audit `fa-shield`, Edit `fa-pencil`, View `fa-eye`, Back `fa-arrow-left`, all `aria-hidden` with text; both back links exist as links.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| B-OV-1 | `ElectionOverview.php:26` | heading `E::ts('Election: %1', ...)` | 3, 3.7, 3.3 | Decision needed: the guide names the screen "Election Overview" and the last breadcrumb item is the election name. Proposed heading "Election Overview" with the election name as the first visible line below it (or "Election Overview: name"). |
| B-OV-2 | `ElectionOverview.php:12,16`, `elections.php:202-205` | No breadcrumb | 3.1, 3.7 | Breadcrumb "Manage Elections › election name" (name is the current page, not a link). |
| B-OV-3 | `ElectionOverview.tpl:2-4` | Only "Back to Manage Elections" (inside a `p` above the stage); no "Back" | 3.2, 3.4, 3.6 | The top row, under the heading, one row: Back, Back to Manage Elections (both quiet), then the actions. "Back" returns to the page the person came from, otherwise Manage Elections. |
| B-OV-4 | `ElectionOverview.tpl:105-114` | Action buttons at the bottom of the page in `<p class="election-overview-actions">`, in this order: Positions, Candidates, Audit log, Results (only when results exist), Results page, Edit settings (only when editable), View as a voter, Duplicate | 3.4, 3.6, 3.8 | Move to the top row right after the two back buttons, in this order: Edit Election, Positions, Candidates, Results Page, Results (only when results exist), Audit Log, View as a Voter. One row only, nothing at the bottom, not repeated. |
| B-OV-5 | `ElectionOverview.tpl:105-114` | Labels "Audit log", "Results page", "Edit settings", "View as a voter" | 3.8, 2.4 | "Audit Log", "Results Page", "Edit Election", "View as a Voter" (Title Case). |
| B-OV-6 | `ElectionOverview.tpl:113` | "Duplicate" button with `crm-popup` | 3.4 | Remove. Duplicate and Delete belong to the Manage Elections list only. |
| B-OV-7 | `ElectionOverview.tpl:111` | Edit button is only output when `$canEdit` | 3.4 | When the election cannot be edited, show "Edit Election" disabled with the reason on hover and for screen readers (the reason is what `canEdit()` returns, which today is an untranslated string: see B-CE-35). |
| B-OV-8 | `ElectionOverview.tpl:106-113` | All action links are the same `button` style | 3.6 | "Edit Election" is the one primary. Others secondary. The two back buttons quiet. |
| B-OV-9 | `ElectionOverview.tpl:110` | Results Page icon `fa-image` | 9.3 | 9.3 gives no icon for Results Page. Decision: add `fa-image` to the guide or choose one. |
| B-OV-10 | `ElectionOverview.php:31,38` | Timeline labels "Visible to people" and "Hidden from people" | 2.1, 2.2, 5.6, U5 | "Visible from" and "Visible until" (the 5.6 date labels) so the overview, the form and the list agree. Confirm that the timeline wording should follow the form labels. |
| B-OV-11 | `ElectionOverview.tpl:20,17` and page | No time zone line, though many dates are shown | 8.7 | One line above the timeline: "Times are shown in <zone name>". |
| B-OV-12 | `ElectionOverview.tpl:15,27,39,44,55,65,88` | Group headings are `h3`; no `h2` | 10 | Use `h2` (order). |
| B-OV-13 | `ElectionOverview.tpl:28-34` | `<table class="election-counts">` rows have `th` without `scope`, no caption; the headings "Eligible candidates", "Candidates standing" | 9.6, 10, 2.1 | `scope="row"` on the row headings; the "Numbers" heading names the table. "Eligible candidates" counts people who have enough nominations, not yet accepted; the glossary calls them nominees until acceptance: confirm and relabel ("Nominees with enough nominations"). |
| B-OV-14 | `ElectionOverview.tpl:38-40` | The write-in warning is `messages status no-popup` | 7 | It is a warning (the guide uses exactly this sentence as the warning example): `messages warning`. |
| B-OV-15 | `ElectionOverview.php:73` | Status texts "Accepted: on the ballot", "Did not answer before voting closed (told)", "Waiting for an answer (reminded)" | 2.4, 8 | No colon or bracketed asides in a status cell. For example "Accepted", "Did not answer before voting closed" and a separate column or short phrase for whether they were reminded or told. Confirm wording with the maintainer. |
| B-OV-16 | `ElectionOverview.tpl:57` | `{ts 1=$eligibleVoters}%1 people may vote{/ts}` | 8.8 | Use the plural form ("1 person may vote", "5 people may vote"). |
| B-OV-17 | `ElectionOverview.tpl:58`; `ElectionOverview.php:51` | `{$role.label|escape}: {$role.count|escape}` built from two pieces, labels "May nominate and second", "May stand as candidates" | 11, 8.8 | One translated sentence with variables ("%1 people may nominate and second"), plural-aware. |
| B-OV-18 | `ElectionOverview.tpl:67` | `{ts}%1 ballots cast{/ts}{if ...}, {ts}out of %1 people allowed to vote (%2%){/ts}{/if}` | 11, 8.8 | One sentence per case with plural forms ("1 ballot cast, out of 40 people allowed to vote (3%)"). Not joined with a comma in the template. |
| B-OV-19 | `ElectionOverview.tpl:78` | "Not shown: votes are anonymised" | 2.4 | Remove the colon: "Not shown because votes are anonymised." |
| B-OV-20 | `ElectionOverview.tpl:88` | Heading "Integrity" | 2.1, 8.4 | Name it for what it is: "Audit log integrity" or "Audit log". Decision for the maintainer. |
| B-OV-21 | `ElectionOverview.tpl:97` and `CRM/Elections/Helper/Audit.php:112` | The recent audit "What" column prints `{$entry.action|escape}`, the code (`election_changed`, `position_created`, `results_generated`, `votes_anonymised` ...) | 1.2, 4.2 | Show a label ("Election changed", "Position added", "Results generated"). |
| B-OV-22 | `ElectionOverview.tpl:94,46,71` | Table headings "When", "Who", "What" without `scope`, and the audit table has no caption | 9.6, 10 | `scope="col"`; a caption or the heading above names the table. |
| B-OV-23 | `ElectionOverview.tpl:102` | `{ts 1=$auditHead|truncate:16:''}Audit log fingerprint: %1... Note it down ...{/ts}` | 1.2, 2.4, 9.9, U11 | Show the whole fingerprint (64 characters), not cut short and no "...", in a fixed-width box that wraps, with a "Copy" button (`fa-clipboard`) next to it that changes to "Copied" for a few seconds and announces it with `aria-live`. The explanatory sentence is separate from the code. |
| B-OV-24 | `ElectionOverview.php:189-200` (`CRM/Elections/BAO/Election.php:177-205`) | Next-step texts: "Nominees are accepting or withdrawing.", "Voting has closed. ...", "Activate the election when it is ready for people to see." | 2.1, 2.2, U3 | "accepting or declining"; "Voting closed." wording per 2.2. The "Activate" instruction has no button on this page (3.6 gives the overview no Activate or Deactivate): decision needed on how an administrator activates from here (link in the Next message, or an action). |
| B-OV-26 | `ElectionOverview.tpl:7` | The description is shown without class and sits above the Next message | 3 | Order as in the frame: breadcrumb, heading, action bar, then the description. |
| B-OV-27 | `ElectionOverview.tpl:3`, `:106-113` | Icons use `role="img" aria-hidden="true"` together | 10 | Use `aria-hidden="true"` only (an element hidden from assistive technology should not carry a role). Minor. |

Needs live check (screen 3): contrast of `.election-timeline-passed` (`css/electionforms.css:494-497` uses `opacity: 0.6`); the badge colours (`css/electionforms.css:453-470` hard-coded); 375 px table behaviour (9.6 asks for cards); the action row wrapping.

---

## 4. `civicrm/elections/positions/create` Add Position / Edit Position (full page edit form)

Files: `CRM/Elections/Form/CreateElectionPosition.php`, `templates/CRM/Elections/Form/CreateElectionPosition.tpl`, `api/v3/ElectionPosition.php`.

Currently conforms: labels Name, Seats, Write-ins per seat, Passes when, Description; the question-only rows hide without JavaScript failing (all shown); "Enter a percentage above 0 and up to 100." and the quorum error read as fixes; footer is a single default button.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| B-CP-1 | `CreateElectionPosition.php:41`, `:79`; `xml/Menu/elections.xml:31` | headings "Add Election Position for %1", "Edit Election Position for %1"; menu "Create Election Position" | 2.3, 3.7, 3.8 | "Add Position" and "Edit Position" (election name in the breadcrumb, not the heading). |
| B-CP-2 | `CreateElectionPosition.php:19`, `elections.php:202-205` | No breadcrumb | 3.1, 3.7 | Manage Elections › election name › Positions › Add Position or Edit Position (Positions links to the list). |
| B-CP-3 | `CreateElectionPosition.php:124-130` | Button `E::ts(($this->epId) ? 'Edit Position' : 'Create')` | 2.3, 3.8, 11 | "Submit" for both. The `E::ts()` call on a variable string is not translatable. |
| B-CP-4 | `CreateElectionPosition.tpl:65-71` | Footer has the one button only | 3.2, 3.6, 3.8 | Submit, Back, Back to Manage Elections as links styled as quiet buttons. Back goes to the list the person came from (Positions). |
| B-CP-5 | `CreateElectionPosition.tpl:2-3` | "Use this form to add a position to be nominated for and voted on in your election." / "Positions describe an elected role to which one more persons may be elected." (also shown on Edit) | 2.1, 8, 2.4 | One accurate description for both modes. "persons" becomes "people"; "one more" is a typo for "one or more"; "elected role" fine. For example "A position is a role people stand for. One or more people can be elected to it." |
| B-CP-6 | `CreateElectionPosition.php:109-110` | labels "Order" and "Type" | 5.6 | "Order on the ballot" and "Position type". |
| B-CP-7 | `CRM/Elections/BAO/ElectionPosition.php:20-21` | Type options "Election position", "Referendum question" | 5.6, 2.1 | "Position" and "Question". |
| B-CP-8 | `CreateElectionPosition.php:111-113` | "Wording of Yes", "Wording of No", "Wording of Abstain" | 5.6 | "Wording for Yes", "Wording for No", "Wording for Abstain". |
| B-CP-9 | `CreateElectionPosition.php:115-117` | "Percentage", "Quorum", "Resolution" | 5.6 | "Pass percentage", "Quorum (% of voters)", "Resolution text". |
| B-CP-10 | `CreateElectionPosition.tpl:11,62` | "Name of the position, to be displayed on the nomination and voting forms." / "Additional information about the position to be shown on the nomination and voting forms." | 2.1, 2.4 | The voter's form is the "ballot". "The name of the position, shown on the nomination form and the ballot." |
| B-CP-11 | `CreateElectionPosition.tpl:21`, `:26` | "Number of persons who are to be elected to the position" (no full stop); "Order that the position is shown in on the ballot." | 2.1, 2.4, 5.6 | "The number of people to be elected to this position." (5.6 says the help states winners = seats in a sentence). "persons" is banned. |
| B-CP-12 | `CreateElectionPosition.tpl:16` | "An election position is one that people stand for. A referendum question is a yes or no question that voters answer." | 2.1 | "A position is something people stand for. A question is a yes or no question voters answer." (matches the new option labels). |
| B-CP-13 | `CreateElectionPosition.tpl:57` | Write-ins help has five sentences (how many; 0 means off; told straight away and must accept; works best with the setting on) | 8.2 | Two sentences maximum; move the rest to the documentation. |
| B-CP-14 | `CreateElectionPosition.tpl:42,47` | Percent sign typed after the input (`... %`) with no unit in the label | 5.2 | Unit named in the label ("Pass percentage", "Quorum (% of voters)") and the visible % kept. |
| B-CP-15 | `CreateElectionPosition.php:137,154` | "Seats must be a valid positive integer." / "Order must be a valid positive integer." | 5.3 | Name the field as labelled and say how: "Seats must be a whole number of 1 or more." / "Order on the ballot must be a whole number of 1 or more." |
| B-CP-16 | `CreateElectionPosition.php:180,191` | "Election position has been created successfully." / "Election position has been edited successfully." | 5.4 | "Position added." / "Position saved." |
| B-CP-17 | `CreateElectionPosition.php:194` | `civicrm_api3('ElectionPosition', 'create', ...)` has no try/catch; the API refuses with "This election is active, so its positions cannot be changed. Positions can be changed only before nominations start, or while the election is switched off." and `'Election position cannot be added/modified for deleted election.'` (`api/v3/ElectionPosition.php:25,29`; the second is not through `ts()`) | 5.3, 5.4, 7, 11 | Catch the refusal, keep the person on the form with input kept, show the sentence as an error message (`messages error`). Use the glossary ("position", "inactive", not "switched off") and `ts()` on the second string. |
| B-CP-18 | `CreateElectionPosition.php:28-31` | A locked election throws `CRM_Extension_Exception($canEdit)` ("Election details cannot be edited once it has been run." which also describes positions wrongly) | 7, 11 | Written error page with Back buttons; text should say positions cannot be changed because the election is active and running. See B-CE-35. |

Needs live check (screen 4): that Add Position opens as a full page from the list (the aff link has no popup target, `ang/afsearchElectionPositions.aff.html:4`) so the breadcrumb and Back buttons apply; row Edit for positions (its link is not in `managed/SavedSearch_ElectionPositions.mgd.php` lines 58-59, so it is probably inline editing: unverified).

---

## 5. `civicrm/elections/positions/duplicate` Duplicate Position (dialog)

Files: `CRM/Elections/Form/DuplicateElectionPosition.php`, `templates/CRM/Elections/Form/DuplicateElectionPosition.tpl`.

Currently conforms: title "Duplicate Position"; button pair "Duplicate" (default) and "Cancel"; one sentence of what will happen with the position named; the Name field label "Name of the copy"; no breadcrumb (dialog).

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| B-DP-1 | `DuplicateElectionPosition.tpl:2` | "Copy the position "%1" to the end of the list." | 2.3 | "Duplicate the position "%1". The copy is added to the end of the list." (never "Copy" for the action). |
| B-DP-2 | `DuplicateElectionPosition.php:39` | `'"%1" has been added.'` | 5.4 | "Position duplicated." |
| B-DP-3 | `DuplicateElectionPosition.php:42` | error title "Position not copied" | 2.3 | "Position not duplicated". |

---

## 6. `civicrm/elections/positions/delete` Delete Position (dialog)

Files: `CRM/Elections/Form/DeleteElectionPosition.php`, `templates/CRM/Elections/Form/DeleteElectionPosition.tpl`, `templates/CRM/Elections/Form/DeleteConfirmMessage.tpl` (shared with Delete Election).

Currently conforms: button pair "Delete" (default) and "Cancel" text; uses the `messages status no-popup` block.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| B-XP-1 | `xml/Menu/elections.xml:69` | menu title "Delete Election Position" | 2.1, 3.8 | "Delete Position". |
| B-XP-2 | `DeleteConfirmMessage.tpl:7-8` | `<span class="font-red bold">WARNING: This action cannot be undone.</span>` then "Click 'Delete' if you want to continue." | 5.5 | Replace with a sentence that says what is lost: the position's name and what is removed with it, for example "This deletes the position "name" and any nominations for it. This cannot be recovered." Whether nominations are removed with the position is unverified (no delete rule is visible in `schema/` or the API); confirm before wording. Do not say "Click 'Delete'". |
| B-XP-3 | `DeleteElectionPosition.php:22-52`, `DeleteElectionPosition.tpl` | The dialog never names the position (the position array is loaded but not assigned to the template) | 3.3, 5.5 | Name the position in the one sentence. |
| B-XP-4 | `DeleteConfirmMessage.tpl:2-4,10-12` | The button row is output twice, at the top and the bottom (`location="top"` and `location="bottom"`) | 3.6 | Buttons only at the bottom of the dialog (not repeated). |
| B-XP-5 | `DeleteConfirmMessage.tpl:6-7` | `<div class="icon inform-icon"></div>` (empty element) and `font-red bold` classes | 9.1, 5.5 | Use the standard warning message class (`messages warning`) without a decorative empty element or colour class. |
| B-XP-6 | `DeleteElectionPosition.php:36-46` | Delete is the default submit with no danger style | 3.6, 9.2 | Delete uses the danger style; Cancel quiet. |
| B-XP-7 | `DeleteElectionPosition.php:59` | "Election position has been deleted successfully." | 5.4 | "Position deleted." |
| B-XP-8 | `DeleteElectionPosition.php:29-31`, `:55-57` | A locked election throws `CRM_Extension_Exception` (raw, untranslated text "Election details cannot be edited once it has been run."); a refusal from the API in `postProcess` is not caught | 5.3, 7, 11 | Show a written error inside the dialog, for example "Positions cannot be deleted while the election is active and running. Deactivate it first." (see B-CE-35, B-CP-17). |
| B-XP-9 | `DeleteElectionPosition.php:72-85` | `throwAccessDeniedPage` for a bad id: "You are not authorized to access this page." | 7 | "Not found" message (see B-ER-3). |

---

## 7. `civicrm/elections/duplicate` Duplicate Election (dialog)

Files: `CRM/Elections/Form/DuplicateElection.php`, `templates/CRM/Elections/Form/DuplicateElection.tpl`.

Currently conforms: title "Duplicate Election"; buttons "Duplicate" (default) and "Cancel"; description names the election and what is not copied; no breadcrumb.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| B-DE-1 | `DuplicateElection.tpl:2` | `Copy "%1" with its positions as a new inactive election. ... All dates move by the same amount, so the stages keep their spacing.` | 2.3, 8.2 | "Duplicate" not "Copy". Three sentences is above the two-sentence guideline for help but this is dialog body text; keep it to the one sentence of what will happen plus one on the dates. |
| B-DE-2 | `DuplicateElection.php:23` | label "Start date of the copy" (field `visibility_start_date`) | 5.6 | A date label names the event: "Visible from". The help says the other dates move by the same amount. |
| B-DE-3 | `DuplicateElection.php:44` | `"%1" has been created as a copy. It is inactive until you activate it.` | 5.4, 2.3 | "Election duplicated. It is inactive until you activate it." (past tense, names the thing, "activate" is the glossary word). |
| B-DE-4 | `DuplicateElection.php:36` | default date `date('Y-m-d H:i:s', strtotime('+1 day'))` | 8.7 | Machine value for the picker, not displayed text. Unverified that PHP's timezone equals CiviCRM's; check live. |
| B-DE-5 | `DuplicateElection.php:43` | `CRM_Elections_BAO_Election::duplicate()` is not wrapped in try/catch; no error message path | 5.3, 5.4 | A failure keeps the dialog open with a written error. |

---

## 8. `civicrm/elections/delete` Delete Election (dialog)

Files: `CRM/Elections/Form/DeleteElection.php`, `templates/CRM/Elections/Form/DeleteElection.tpl`, `templates/CRM/Elections/Form/DeleteConfirmMessage.tpl`.

Currently conforms: buttons "Delete" (default) and "Cancel" as text.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| B-XE-1 | `DeleteConfirmMessage.tpl:7-8` | "WARNING: This action cannot be undone." and "Click 'Delete' if you want to continue." | 5.5 | The sentence that says what is lost: "This deletes the election and its positions. Nominations and votes cannot be recovered." (the wording the guide gives). Note the code only sets `is_deleted = 1` (`DeleteElection.php:54`): the data is hidden, not removed. Confirm with the maintainer which sentence is true. |
| B-XE-2 | `DeleteElection.php:13-36`, `DeleteElection.tpl` | The election is never loaded or named in the dialog | 3.3, 5.5 | Name the election in the sentence. |
| B-XE-3 | `DeleteConfirmMessage.tpl:2-4,10-12` | Buttons shown at top and bottom | 3.6 | Once, at the bottom. |
| B-XE-4 | `DeleteConfirmMessage.tpl:6-7` | empty `div.icon.inform-icon`, `font-red bold` | 9.1, 5.5 | Standard `messages warning`. |
| B-XE-5 | `DeleteElection.php:18-28` | Delete not in the danger style | 3.6, 9.2 | Danger style; Cancel quiet. |
| B-XE-6 | `DeleteElection.php:56`, `:59` | "Election has been deleted successfully." / "Error occurred while deleting an election." with `setStatus(..., 'danger')` | 5.4, 7 | "Election deleted." and a written error with the valid CiviCRM status type `error` (`danger` is not a CiviCRM type: verified in the code, behaviour unverified). |
| B-XE-7 | `DeleteElection.php:45-48` and `CRM/Elections/BAO/Election.php:325` | `throw new CRM_Extension_Exception($canDelete)` with "Election cannot be deleted once it has been run." (not through `ts()`) | 5.3, 7, 11 | A written message in the dialog that says what to do instead ("Deactivate the election first.") and goes through `ts()`. |

---

## 9. `civicrm/elections/visibility` Activate Election / Deactivate Election (dialog)

Files: `CRM/Elections/Form/ElectionVisibility.php`, `templates/CRM/Elections/Form/ElectionVisibility.tpl`, `CRM/Elections/Helper/Guard.php`, `CRM/Elections/BAO/Election.php` (`assertCanActivate`).

Currently conforms: the title switches between "Activate Election" and "Deactivate Election"; typing the election's name before the running election is switched off (the stronger confirmation the guide asks for); the audit log is kept; the name check ignores case and spaces.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| B-VI-1 | `xml/Menu/elections.xml:75` | menu title "ElectionVisibility" | 1.2 | A readable title, "Activate Election". The page sets the real title in code. |
| B-VI-2 | `ElectionVisibility.php:27-47` | Third state "Withdraw Election" (title and button) for an active running election | 2.1, U3, U4 | Remove. Title "Deactivate Election", button "Deactivate", with the stronger confirmation (typing the name) kept when the election is running. |
| B-VI-3 | `ElectionVisibility.php:28-34` | The only button is `Activate Election`, `Deactivate Election` or `Withdraw Election`; no Cancel | 3.6, 3.8 | Action "Activate" or "Deactivate" (3.8), then "Cancel". "Deactivate" uses the danger style. 5.5 says the action is named "Delete Election"; 3.8 gives "Delete / Cancel": the guide disagrees with itself. This review follows 3.8: Decision for the maintainer. |
| B-VI-4 | `ElectionVisibility.tpl:16-18` | "This will restrict all pages for this election to administrators." / "This will remove the administrator restriction on all pages for this election." | 2.1, 5.5, 8.1 | One sentence that says the result in glossary words: "This hides the election from everyone except administrators." / "This makes the election visible to people during its visible dates." Name the election in the sentence. |
| B-VI-5 | `ElectionVisibility.tpl:4-5` | "This election is active and its nominations have started." / "Withdrawing it hides every page of the election from everyone except administrators, and it can then be edited again. The withdrawal is written to the audit log." | 2.1, U4, 5.5 | "Deactivating it hides ... It can then be edited again. The change is recorded in the audit log." Use `messages warning`. |
| B-VI-6 | `ElectionVisibility.php:38`; `.tpl:10` | label "Type the name of the election to confirm"; help "The name is: %1" | 2.4, 5.2 | Label fine; the help is a full sentence with a full stop and no colon: "The name is %1." |
| B-VI-7 | `ElectionVisibility.php:74` | "The name does not match the name of this election." | 5.3 | Say how to fix it: "Type the election's name exactly as shown." |
| B-VI-8 | `ElectionVisibility.php:100` | "Election has been withdrawn." / "Election has been deactivated successfully." / "Election has been activated successfully." | 5.4 | "Election deactivated." / "Election activated." |
| B-VI-9 | `ElectionVisibility.php:103` | error title "Election not changed" with the exception text | 5.3 | Title fine; body per B-VI-10 and B-VI-11. |
| B-VI-10 | `CRM/Elections/BAO/Election.php:220` | "At least one Position must be defined before the Election can be active." | 2.4 | Lower case "position", "election" and say what to do: "Add at least one position before activating the election." |
| B-VI-11 | `CRM/Elections/Helper/Guard.php:284` | "... so it cannot be switched off here. Use Withdraw on the Manage Elections page." | 2.1, U4 | Refers to the removed Withdraw; "Use Deactivate on Manage Elections." and "switched off" becomes "deactivated". |
| B-VI-12 | `ElectionVisibility.tpl:2,14` | `messages warning` / `messages status` blocks have no role | 7 | `no-popup` is present; add `role="status"` where shown without a page load (dialogs). Minor. |

---

## 10. `civicrm/elections/candidates/add` Add Candidate (full page edit form)

Files: `CRM/Elections/Form/AddCandidate.php`, `templates/CRM/Elections/Form/AddCandidate.tpl`.

Currently conforms: heading names the screen; fields Position and Candidate use the 5.6 labels; the "You cannot see the people in this election's groups." notice uses `messages warning no-popup` with `role="alert"` and says how to get access; the Candidate field is limited to the candidate groups.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| B-AC-1 | `AddCandidate.php:21` | heading "Add Candidate to %1" | 3.7 | "Add Candidate" (election name is in the breadcrumb). Decision: keep the election name as a sub-line if wanted. |
| B-AC-2 | `AddCandidate.php:16-19` (no `hideNonRequiredItemsOnPage`) | Page does not set or hide the breadcrumb; whatever CiviCRM draws shows | 3.1, 3.7 | Manage Elections › election name › Candidates › Add Candidate. How it looks today needs a live check. |
| B-AC-3 | `AddCandidate.php:42-43`, `.tpl:15-17` | Buttons "Add Candidate" (submit) and "Cancel" (a form submit) | 2.3, 3.2, 3.6, 3.8 | "Submit", then "Back" and "Back to Manage Elections" as links styled as quiet buttons. |
| B-AC-4 | `AddCandidate.tpl:2` | "Use this when nominations are made outside the website. The person is added as an eligible candidate; tick "has accepted" if they have already agreed to stand, so they appear on the ballot. They must belong to one of the election's groups." | 2.1, 5.2, 8.2 | Refers to a checkbox called "has accepted" that is labelled "The candidate has accepted"; use the final label (B-AC-5). "eligible candidate" for someone who has not accepted should be "nominee". Three sentences: shorten to two and use the 3.5 required line. |
| B-AC-5 | `AddCandidate.php:38` | label "The candidate has accepted" | 5.6, 5.2 | "Mark as accepted", with help that says what ticking does ("The candidate is on the ballot straight away. Leave it unticked to ask them to accept.") |
| B-AC-6 | `AddCandidate.php:39` | label "Candidate statement" | 5.6 | "Statement". |
| B-AC-7 | `AddCandidate.php:32` | placeholder via the first option `'- select -'` | 2.4 | "Select a position". |
| B-AC-8 | `AddCandidate.php:35` | placeholder `'- Select person -'` | 2.4 | "Select a person". |
| B-AC-9 | `AddCandidate.php:23-29` | If the election has no positions the select has only "- select -" and nothing explains why | 7, 4.9 | An empty-state sentence: "There are no positions to add a candidate to. Add a position first." with a way to do it. |
| B-AC-10 | `AddCandidate.php:58,61` | "The candidate has been added." / title "Candidate not added" | 5.4 | "Candidate added." (title is fine). |
| B-AC-11 | `AddCandidate.php:63` | After success or failure `finish()` redirects to the candidate list | 5.4 | On failure stay on the form with the input kept. |
| B-AC-12 | `AddCandidate.tpl:10-13` | Layout `form-layout` table; required marks and the line "Fields marked * are required." absent | 3.5 | Add the line. |

---

## 11. `civicrm/elections/candidate/statement` Edit Statement (back end use by administrators)

Files: `CRM/Elections/Form/EditStatement.php`, `templates/CRM/Elections/Form/EditStatement.tpl`. The same form is used by candidates on the front end; rows marked (admin) apply when `isElectionAdmin()`; the others apply to both.

Currently conforms: success "Statement saved."; heading names the statement and the person; the link field is checked and the error is shown next to the field; the page escapes the position name.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| B-ES-1 | `EditStatement.php:52`; `xml/Menu/elections.xml:89` | heading "Candidate Statement for %1" (menu: "Candidate Statement") | 2.3, 3.7 | "Edit Statement". The candidate's name can be a sub-line. |
| B-ES-2 | `EditStatement.php:28`, `elections.php:202-205` (admin) | No breadcrumb | 3.1, 3.7 | Manage Elections › election name › Candidates › Edit Statement for an administrator. |
| B-ES-3 | `EditStatement.php:66-72` | Button "Save", only one | 2.3, 3.8 | "Submit". Administrator footer: Submit, Back, Back to Manage Elections (3.8). The candidate's own use: front end rule (the "Back to the election" link) is for the front end reviewer. |
| B-ES-4 | `EditStatement.php:63` | label "Candidate statement" | 5.6 | "Statement". |
| B-ES-5 | `EditStatement.php:64` | label "Link to a longer statement or CV" | 5.6 | Label "Statement link", help "A link to a longer statement or CV." |
| B-ES-6 | `EditStatement.tpl:3` | "Write a short statement for voters: your experience, what you want to achieve, and optionally a link to a longer statement or CV." (shown to administrators too) | 8.1 | The wording speaks to the candidate ("your"). For an administrator editing someone else's statement use neutral wording ("A short statement for voters: the person's experience and what they want to achieve."). |
| B-ES-7 | `EditStatement.tpl:9` | `<label>{ts}Position{/ts}</label>` with no `for` and no control | 10 | A label with nothing to label: show it as plain text (or a read-only definition), not a `label`. |
| B-ES-8 | `EditStatement.tpl:7-39` | Rows are `div`s with `crm-election-form-col-left/right`, unlike every other form (`form-layout` table); the footer button sits in `col-right` | 5.1, 3.6 | Same layout as the other forms: label above the field, help below, footer after the last field. |
| B-ES-9 | `EditStatement.php:122` and `CRM/Elections/Helper/Guard.php:110` | "Enter a web address starting with http:// or https://" | 7, 2.4 | Full sentence with a full stop. |
| B-ES-10 | `EditStatement.php:138-146` | After saving, redirect to the front end candidate page (`civicrm/elections/candidate`), also for an administrator | 5.4 | Unverified whether an administrator started from the Candidates list; the person should land on the page they started from. Live check. |
| B-ES-11 | `EditStatement.php:32,36-45,48-50` | Bad id, not found or not allowed: `throwAccessDeniedPage` ("You are not authorized to access this page.") or the raw exception message | 7 | See B-ER-1 to B-ER-3. |

---

## 12. `civicrm/elections/results-display` Results Page (full page edit form)

Files: `CRM/Elections/Form/ResultsDisplay.php`, `templates/CRM/Elections/Form/ResultsDisplay.tpl`, `templates/CRM/Elections/Form/ResultsDisplayFields.tpl`, `CRM/Elections/Helper/ResultsDisplay.php`.

Currently conforms: the description explains that these settings can change at any time; labels Header text, Footer text, Logo description, Remove the logo, Celebration effect, Celebration lasts (days) are the 5.6 labels; help states limits ("Up to 200 characters."); the logo alt text is validated and explained; the preview button has an icon with text and `aria-hidden`; on success the person goes to the overview.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| B-RD-1 | `ResultsDisplay.php:25`; `xml/Menu/elections.xml:115` | heading "Results page for %1" | 2.4, 3.7 | "Results Page" (Title Case; the election name is in the breadcrumb). |
| B-RD-2 | `ResultsDisplay.php:16`, `elections.php:202-205` | No breadcrumb | 3.1, 3.7 | Manage Elections › election name › Results Page. |
| B-RD-3 | `ResultsDisplay.php:30-31` | Buttons "Save" and "Cancel" (a form submit) | 2.3, 3.2, 3.8 | "Submit", "Back", "Back to Manage Elections" (links, quiet). Back goes to the overview. |
| B-RD-4 | `CRM/Elections/Helper/ResultsDisplay.php:72` | label "Header logo" | 5.6 | "Logo". |
| B-RD-5 | `CRM/Elections/Helper/ResultsDisplay.php:75` | label "Display celebration effects on the Election results page" | 5.6, 2.4 | "Show a celebration on the results page". |
| B-RD-6 | `ResultsDisplayFields.tpl:3-4` | `h3` "Results page" and description "A logo, a header and a footer for the page that shows the results." repeated inside a screen whose heading is already Results Page | 3, 10 | Do not repeat the screen name as a heading. When used alone, no group heading is needed. When used inside Edit Election, see B-CE-29. Headings are `h3` with no `h2` (10). |
| B-RD-7 | `ResultsDisplayFields.tpl:7-38` | Field order: Header text, Logo, Logo description, Footer text | 5.6 | 5.6 lists Header text, Footer text, Logo, Logo description, Remove the logo. Confirm the intended order. |
| B-RD-8 | `ResultsDisplayFields.tpl:55`; `css/electionforms.css:524-525` | "Preview" is a `button.crm-button` styled as a filled primary button | 3.6, 9.2 | Secondary style so Submit is the only primary. "Preview" is not in 3.8: add it before use. |
| B-RD-10 | `ResultsDisplay.php:53,56` | "The results page has been saved." / title "Results page not saved" | 5.4 | "Results page saved." |
| B-RD-11 | `ResultsDisplay.php:59` | After a failure the page still redirects to the overview | 5.4 | On failure stay on the form with the input kept. |

---

## 13. `civicrm/admin/setting/elections` Elections Settings (CiviCRM's generic settings form)

Files: `settings/elections.setting.php`, `xml/Menu/elections.xml:149-153`, `elections.php:95-101`.

Currently conforms: the page title and navigation item "Elections Settings" match; CiviCRM's own trail is kept (3.7); the name format options are translated.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| B-ST-1 | `settings/elections.setting.php:12` | label "Image Source" | 2.4, 5.6 | "Image source". |
| B-ST-2 | `settings/elections.setting.php:15` | "Select the source of the image to use for the candidate." | 5.2, 8 | A sentence that says what the choice means: "Where candidate photos come from." plus what each option does. |
| B-ST-3 | `settings/elections.setting.php:22-23` | options "CMS", "Contact Image" | 2.4, 8.4 | "Contact image" in sentence case. "CMS" is jargon: a plain label (for example "The website user's picture") needs the maintainer's confirmation. |
| B-ST-4 | `settings/elections.setting.php:32` | label "Candidate Name Format" | 2.4, 5.6 | "Candidate name format". |
| B-ST-5 | `settings/elections.setting.php:35` | "... Nicknames are only shown for contacts that have one." | 2.1 | "for people who have one". |
| B-ST-6 | CiviCRM core form `CRM_Admin_Form_Generic` (page callback `xml/Menu/elections.xml:150`) | The form footer is CiviCRM's own (Save, Cancel); there are no Back and Back to Manage Elections | 3.7, 3.8, 3.6 | 3.8 lists "Submit, Back, Back to Manage Elections" for Elections Settings. That needs a form hook to rename the button and add the two links: confirm it is wanted. What CiviCRM shows is unverified (live check). |
| B-ST-7 | settings page | No description of the page | 3 | A one-sentence description at the top (what the settings are for). |

---

## 14. Menu titles (`xml/Menu/elections.xml`)

Titles come from the route table; several routes set a different title in code. Routes outside the back end listed too because the guide asks for every route.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| B-MN-1 | `xml/Menu/elections.xml:13` | `civicrm/elections/create`: "Create Election" | 2.3, 3.8 | "Add Election". |
| B-MN-2 | `:25` | `civicrm/elections/positions`: "Election Positions" | 3.7 | "Positions". |
| B-MN-3 | `:31` | `civicrm/elections/positions/create`: "Create Election Position" | 2.3 | "Add Position". |
| B-MN-4 | `:45` | `civicrm/elections/nominations/create`: "Create Election Nomination" (front end route) | 2.3 | "Nominate". |
| B-MN-5 | `:61` | `civicrm/elections/vote`: "Vote in an Election" (front end) | 2.4 | Confirm: "Vote" or "Ballot". |
| B-MN-6 | `:69` | `civicrm/elections/positions/delete`: "Delete Election Position" | 2.3 | "Delete Position". |
| B-MN-7 | `:75` | `civicrm/elections/visibility`: "ElectionVisibility" | 1.2 | "Activate Election". |
| B-MN-8 | `:81` | `civicrm/elections/candidate`: "ElectionCandidate" (front end) | 1.2 | "Candidate". |
| B-MN-9 | `:89` | `civicrm/elections/candidate/statement`: "Candidate Statement" | 3.8 | "Edit Statement". |
| B-MN-10 | `:135` | `civicrm/elections/summary`: "ElectionSummary" (front end) | 1.2 | "Results" (or "Election Results"): front end reviewer to settle. |
| B-MN-11 | `:143` | `civicrm/elections/nominations/reject`: "Withdraw Nomination" (front end) | 2.1, U3 | "Decline Nomination". |

Titles already right: "Elections", "Delete Election", "View Election", "Accept Nomination", "Duplicate Election", "Duplicate Position", "Add Candidate", "Results Page", "Results Logo", "Election Overview", "Elections Settings".

---

## 15. Shared: error, access denied and not found handling (helpers and `Error.tpl`)

Files: `elections.php:317-380,437-447`, `templates/CRM/Elections/Helper/Error.tpl`, `CRM/Elections/Form/Base.php`, `CRM/Elections/Page/Base.php`. These produce the screen shown whenever a back end form refuses.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| B-ER-1 | `elections.php:319`, `:363` | "You are not authorized to perform this action." / "You are not authorized to access this page." (US spelling; elsewhere "authorised", "Unauthorised") | 7 | Say what the person cannot do and how to get access ("You need the Administer Elections permission to use this page. Ask a site administrator."). One spelling across the extension. |
| B-ER-2 | `elections.php:336` | return button text "Return to Elections", target the front end list (`Civi::url('current://civicrm/elections')`) | 3.8 | On back end pages "Back to Manage Elections" and Manage Elections as the target. ("Return" is on the never-used list.) |
| B-ER-3 | `elections.php:377`, `:439`, `:446` | "Unable to view this Election, Election ID parameter missing." / "You are not authorised to access this page." for an election that does not exist | 7, 2.4 | Not found says "The election could not be found." and links to the list. "Election ID parameter" is jargon. A missing election is not an access problem. |
| B-ER-4 | `templates/CRM/Elections/Helper/Error.tpl:1` | `<div class="messages status no-popup ...">` and no `role` | 7, 10 | `messages error` with `role="alert"`. |
| B-ER-5 | `templates/CRM/Elections/Helper/Error.tpl:5` | `<a class="election-btn">{ts}{$return_button_text}{/ts}</a>` | 9.2, 11 | Back end pages use the CiviCRM `button` class, not `election-btn`; `ts()` on a variable is not translatable (the text is already translated where it is set). |
| B-ER-6 | `elections.php:377,216,224` | `throwAccessDeniedException($form, ..., [''])` passes an array with one empty element, so no return button is assigned | 3.2 | Always give the error page its Back button(s). |
| B-ER-7 | `CRM/Elections/BAO/Election.php:308,312,325`; `api/v3/ElectionPosition.php:29` | Guard strings not through `ts()`: "Election details cannot be edited once it has been run.", "Election has been deleted and cannot be edited.", "Election cannot be deleted once it has been run.", "Election position cannot be added/modified for deleted election." | 11 | Wrap in `E::ts()`. They are shown to people (see B-CE-35, B-CP-18, B-XP-8, B-XE-7). |
| B-ER-8 | `CRM/Elections/Helper/Guard.php:306` | `E::ts('%1 cannot be changed once people have voted or the results are generated.', [1 => $field])` where `$field` is a column name | 1.2, 5.3 | The message can read, for example, "voting_method cannot be changed ...": replace the machine name with the field's label. |

---

## Cross-cutting

1. **No breadcrumb anywhere.** `hideNonRequiredItemsOnPage()` switches the breadcrumb off on every back end full page, and no page adds one. Needs one shared helper that builds the 3.7 trail from the election and the page name.
2. **No Back or Back to Manage Elections on any form.** All forms either have one button, or a "Cancel" submit (`AddCandidate`, `ResultsDisplay`). Edit forms need the 3.8 footer: Submit, Back, Back to Manage Elections, with the two links quiet. A shared template include would apply it to Edit Election, Position, Add Candidate, Edit Statement, Results Page and the Settings page.
3. **Button labels.** "Save", "Add Candidate", "Create", "Edit Position", "Cancel" (as a way out of a full page) all become "Submit" (edit forms) or the two Back links. The labels in dialogs follow 3.8: "Activate"/"Deactivate", with "Cancel".
4. **Dialog structure.** Delete, Duplicate and Activate/Deactivate need: title, one sentence naming the thing and what will happen, the action (Delete and Deactivate in the danger style), Cancel, buttons not repeated at the top. The delete confirmation text is shared (`DeleteConfirmMessage.tpl`) and the same "WARNING: This action cannot be undone." appears on two dialogs.
5. **Success and error messages.** Every message says "has been ... successfully". Standard: "<Thing> <past tense>." Failure keeps the person on the form with input kept (Add Candidate and Results Page redirect on failure; Create Position does not catch API refusals).
6. **Guard strings** shown through `CRM_Extension_Exception` and not through `ts()`: see B-ER-7.
7. **Labels in Title Case** on forms and settings: "Anonymise Votes", "Voting Method", "Number of Required Nominations", "Nomination Groups", "Candidate Groups", "Image Source", "Candidate Name Format", "Contact Image".
8. **Placeholders with dashes:** "- Choose a cycle -", "- Select -", "- Select Groups -", "- Same as voting groups -", "- select -", "- Select person -".
9. **Heading order:** group headings are `h3` on the Edit Election form, the Results Page fields and the overview, with no `h2`.
10. **Date labels, time zone and JavaScript dates** (B-CE-5, B-CE-10, B-CE-11, B-OV-11).
11. **Plurals and joined sentences** on the overview (B-OV-16 to B-OV-18).
12. **Help text over two sentences** on Edit Election (voting method, nominations online, nomination email, "Allow non-logged in access") and Position (write-ins).
13. **Scripts and styles for extra `crm-button`s** (Fill in the dates, Preview) are styled as primary by `css/electionforms.css:524-525`.
14. Seen outside this scope, for the other reviewers: `templates/CRM/Elections/Page/Elections.tpl:2` ("Create Election" button, `button` class, shown on the front end list), lines 42-53 of the same file (row links "Deactivate", "Activate", "Edit Election", "Delete Election" for administrators); `templates/CRM/Elections/Page/ViewElection.tpl:3-4` ("Edit Election" and "Delete Election" buttons on the front end page, `button delete`; the guide says Delete belongs on Manage Elections); `ang/afsearchElectionAudit.aff.html:3` and `ang/afsearchElectionResults.aff.html:3` ("Back to the election" on back end list pages: 3.8 says "Back" and "Back to Manage Elections"); `ang/afsearchElectionCandidates.aff.html:4` button icon `fa-user-plus` (9.3 says Add is `fa-plus`).

## Strings that violate the glossary (all distinct, seen in this scope)

| String | Where | Glossary rule | Use instead |
|---|---|---|---|
| "Withdraw Election", "Election has been withdrawn.", "Withdrawing it hides ...", "The withdrawal is written to the audit log." | `ElectionVisibility.php:31,37,100`; `.tpl:5` | 2.1, U4 | Deactivate Election, "Election deactivated." |
| "Withdraw" (row link for a locked election) | `managed/SavedSearch_ManageElections.mgd.php:102` | 2.1, U4 | Deactivate |
| "Use Withdraw on the Manage Elections page." | `Guard.php:284` | 2.1, U4 | Use Deactivate |
| "Nominees are accepting or withdrawing." | `BAO/Election.php:194` | 2.1, U3 | accepting or declining |
| "asking them to accept or withdraw" | `CreateElection.tpl:154` | 2.1, U3 | accept or decline |
| "Withdraw Nomination" (menu) | `elections.xml:143` | 2.1, U3 | Decline Nomination |
| "Number of persons who are to be elected to the position", "one more persons may be elected" | `CreateElectionPosition.tpl:21,3` | 2.1 persons | people |
| "Allowed by Groups" | `CreateElection.php:182` | 2.1, 5.6 | Voting groups |
| "Nomination Groups", "Candidate Groups" | `CreateElection.php:201` | 5.6 | Nominating groups, Candidate groups |
| "Visible to people", "Hidden from people", "Visible to voters" | `ElectionOverview.php:31,38`; `CreateElection.php:124` | 2.2, U5, 5.6 | Visible from, Visible until |
| "Allow non-logged in access", "valid checksum URL", "user" | `CreateElection.php:178`; `.tpl:212` | 2.1 (checksum, user) | personal link, people |
| "nomination and voting forms" | `CreateElectionPosition.tpl:11,62` | 2.1 (voting form) | the ballot |
| "in order of preference", "voters number the candidates", "the count passes votes on" | `CreateElection.tpl:188` | 2.1 (preference, count) | rank |
| "Start Date", "End Date", "Start date of the copy", "advertise candidate date", "visibility start date", "visibility end date" | `CreateElection.php:149-164`; `BAO/Election.php:415-425`; `DuplicateElection.php:23` | 5.6 | the event labels |
| "Election position", "Referendum question" as option labels | `BAO/ElectionPosition.php:20-21` | 5.6 | Position, Question |
| "eligible candidate" for someone who has not accepted | `AddCandidate.tpl:2`; `CreateElection.tpl:130`; `ElectionOverview.tpl:31` | 2.1 (nominee before acceptance) | nominee |
| "Copy "%1" ...", "Copy the position ...", "has been created as a copy", "Position not copied" | `DuplicateElection.tpl:2`; `DuplicateElectionPosition.tpl:2`; `DuplicateElection.php:44`; `DuplicateElectionPosition.php:42` | 2.3 | Duplicate |
| "Create Election", "Create Election Position", "Create Election Nomination", "Create" | `elections.xml:13,31,45`; `CreateElectionPosition.php:127`; `SavedSearch_ManageElections.mgd.php:116` | 2.3 | Add Election, Add Position, Nominate |
| "Edit settings", "Save", "Add Candidate" (as a button), "Edit Position" (as a button), "Return to Elections" | `ElectionOverview.tpl:111`; `EditStatement.php:69`, `ResultsDisplay.php:30`; `AddCandidate.php:42`; `CreateElectionPosition.php:127`; `elections.php:336` | 2.3, 3.8 | Edit Election; Submit; Back to Manage Elections |
| "Candidate statement", "Candidate Statement for %1", "Link to a longer statement or CV" | `AddCandidate.php:39`; `EditStatement.php:52,63,64` | 2.1, 5.6 | Statement, Edit Statement, Statement link |
| "Election has been created successfully." and the other "has been ... successfully" messages | `CreateElection.php:275,277`; `CreateElectionPosition.php:180,191`; `DeleteElection.php:56`; `DeleteElectionPosition.php:59`; `ElectionVisibility.php:100`; `AddCandidate.php:58`; `ResultsDisplay.php:53` | 5.4, 7 | "<Thing> <past tense>." |
| "WARNING: This action cannot be undone." and "Click 'Delete' if you want to continue." | `DeleteConfirmMessage.tpl:7-8` | 5.5 | the sentence that says what is lost |
| "this Election", "Election pages", "Position must be defined ... Election can be active", "Unable to view this Election" | `CreateElection.tpl:2,212`; `BAO/Election.php:220`; `elections.php:377` | 2.4 | lower case |
| "Number of Required Nominations", "Anonymise Votes", "Voting Method", "Image Source", "Candidate Name Format", "Contact Image" | `CreateElection.php:180,168,172`; `settings/elections.setting.php:12,32,23` | 2.4, 5.6 | sentence case |
| "- Choose a cycle -", "- Select -", "- Select Groups -", "- Same as voting groups -", "- select -", "- Select person -" | `CreateElection.php:145,169,175,184,204`; `AddCandidate.php:32,35` | 2.4 | "Select a ..." |
| "Audit log" (button), "Results page" (button), "View as a voter" (button) | `ElectionOverview.tpl:108,110,112` | 3.8 | Audit Log, Results Page, View as a Voter |
| "Display celebration effects on the Election results page", "Header logo" | `Helper/ResultsDisplay.php:75,72` | 5.6 | Show a celebration on the results page, Logo |
| "Reset" (filters) | `afsearchManageElections.aff.html:11` | 4.4 | Clear filters |
| "ElectionVisibility", "ElectionCandidate", "ElectionSummary" | `elections.xml:75,81,135` | 1.2 | readable titles |
| "Fingerprint ... %1..." (truncated) | `ElectionOverview.tpl:102` | 1.2, 2.4, 9.9 | full fingerprint with Copy |
| "The name is: %1" | `ElectionVisibility.tpl:10` | 5.2 | "The name is %1." |

## Points where the guide itself needs a decision

1. 5.5 says a destructive dialog's button is named "Delete Election"; 3.8 says "Delete / Cancel". Findings above follow 3.8.
2. 3.8 has no entry for "Fill in the dates", "Preview" (Results Page) or the row-less buttons in the quick set-up; add them or remove the buttons.
3. 9.3 has no icon for Results Page (the code uses `fa-image`).
4. The overview heading and breadcrumb (B-OV-1): "Election Overview" heading vs the election name.
5. 3.6 gives the overview no Activate or Deactivate action; the Next message tells the administrator to activate (B-OV-24).
6. 5.6 lists no results page fields for Edit Election although the form has them (B-CE-29).
7. 12 says the election form has two End Date fields; the code has three (B-CE-5).
8. Edit Statement serves two audiences (administrator and candidate); the back end footer (Submit, Back, Back to Manage Elections) and the front end rule (Back to the election) need one rule for a shared form.
9. Elections Settings footer: 3.8 asks for Submit, Back, Back to Manage Elections on CiviCRM's generic settings form; that needs a form hook (B-ST-6).

## Needs live check (cannot be settled from code)

- Whether the page title is rendered as the `h1` on WordPress, Drupal and Joomla, and heading order after the change.
- How CiviCRM renders `CRM_Extension_Exception` thrown from forms (B-CE-35, B-CP-18, B-XP-8, B-XE-7).
- What the "Cancel" submit does on the full pages today (AddCandidate, ResultsDisplay): destination unverified.
- Whether the breadcrumb really disappears on each page (assigned FALSE) and what shows on Add Candidate, Duplicate and Delete when opened outside a popup.
- Whether an edited, active, not-yet-started election becomes inactive after Submit (B-CE-33).
- Contrast of the passed timeline items (opacity 0.6), the stage badge colours and the date problem text; 44 px touch targets; 375, 768 and 1280 px layouts; axe and keyboard checks.
- Whether Afform translates the literal strings in `afsearchManageElections.aff.html` (B-MG-4), and how SearchKit shows the table description.
- Whether `crm-popup` dialogs close and refresh the list correctly after each action.
