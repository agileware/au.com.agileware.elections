# User interface standards

The rules every screen, message and email of the extension follows, on WordPress, Drupal and Joomla. **Must** is required, **should** is expected unless there is a written reason, **may** is optional. Use the checklist in [section 14](#14-checklist-for-reviewing-a-pull-request) when you change or review a screen.

Why each rule exists, and who decided it, is in [`../decisions/interface-and-wording.md`](../decisions/interface-and-wording.md) (decisions `U1` to `U17`, `R2.1-1` to `R2.1-20`). The word rules are in [`wording.md`](wording.md). Where the code differs from a rule, the difference is listed as an **exception** (here and in [section 13](#13-known-exceptions)); the code and the checks win over this page.

## 1. Principles

1. **Say what it is and what happens next.** Every screen has a heading that names it, and every button names what it does.
2. **Plain words, no codes.** Never show a machine name, a database value, an API name or a hash that is cut short. A code that is needed (the audit log fingerprint) is shown in full and is easy to copy.
3. **One way to do each thing.** The same action looks, reads and sits in the same place on every screen, and the same thing has the same name everywhere.
4. **Always a way out.** Every full back end page has a Back route that does not save, and a route to Manage Elections.
5. **Protect the vote.** No screen shows who voted for whom, or a count that identifies a person. Counts only.
6. **For everyone.** WCAG 2.1 AA, keyboard and screen reader first, readable on a phone, using the theme's fonts and colours (including dark mode). It works without JavaScript; JavaScript only improves it.
7. **The same on every CMS.** The same markup on WordPress, Drupal and Joomla, with no CMS-specific hacks (the one recorded exception is the Joomla breadcrumb).
8. **Translatable.** Every word goes through `ts()` (section 11).

## 2. Glossary and action words

Use the word in the **Use** column. Do not use the words in **Avoid** anywhere a person can read them: screens, emails, errors, help text, `docs/`, and tests that look for text. [`wording.md`](wording.md) covers "people, not members" and the test that enforces it.

| Thing | Use | Avoid | Notes |
|---|---|---|---|
| The event | **election** | poll, ballot (for the event) | |
| A role people stand for | **position** | role, office, seat | **Seats** only for the number of winners ("2 seats") |
| A yes or no question | **question** | motion, proposal, resolution | "Referendum question" when explaining; "Resolution text" is a field name |
| A person named for a position who has not accepted | **nominee** | candidate (before acceptance) | |
| The act of naming someone | **nomination**, **nominator** | proposer | |
| Supporting a nomination | **second** (verb), "a second" | endorse, support | "This nomination needs a second" |
| A person standing at the election | **candidate** | contestant, runner | |
| A person a voter names on the ballot | **write-in** | | Always hyphenated |
| What a voter fills in | **ballot** | voting form | |
| What a voter records | **vote** (noun and verb) | submission, response | |
| A person who may vote | **voter**; **people** when describing who is allowed | member, user | **Contact** only for a CiviCRM contact record or permission name |
| Showing the election to the public | **Activate** / **Deactivate** | publish, enable, switch on | |
| The state of being shown | **Active** / **Inactive** | live, published, draft | Shown to administrators only |
| Nominee says yes | **Accept** | agree, confirm | |
| Nominee says no, before accepting | **Decline** (status **Declined**) | reject, refuse | |
| Candidate pulls out after accepting | **Withdraw** (status **Withdrawn**) | resign, retract | "Withdraw" means only this |
| The winners | **result** (one position), **results** (the election) | outcome, tally | **Count** is for numbers such as "5 ballots cast" |
| An election that cannot be edited or deleted | **Locked** | frozen, closed, read-only | Active and nominations have started; lists say "Locked: nominations have started." |
| The record of changes | **audit log** | history, journal | Entries are "audit log entries" |
| The proof the log is intact | **audit log fingerprint** | hash, checksum, digest | |
| The link that identifies a person who is not logged in | **personal link** | checksum link, magic link | "Checksum" never appears on a screen |
| The people allowed | **group** | list, audience | "Voting groups", "Nominating groups", "Candidate groups" |
| The numbered choice on a ranked ballot | **rank** | preference | |
| A candidate's short text | **statement** | bio, manifesto | |

### 2.1 Election stages

These are the only stage names, in this order. The stage badge, the stepper, the timeline and the election list use the same words (`CRM_Elections_BAO_Election::getStage()`, `CRM_Elections_Helper_ElectionStatus`).

| Key | Label | Meaning |
|---|---|---|
| `inactive` | **Inactive** | Not shown to people |
| `upcoming` | **Not started** | Active, nominations not yet open |
| `nominations` | **Nominations open** | |
| `advertise` | **Nominations closed**, then **Candidates advertised** | |
| `voting` | **Voting open** | |
| `closed` | **Voting closed** | |
| `results` | **Results published** | |
| `deleted` | **Deleted** | Administrators only |

The Manage Elections **Stage** filter (`ElectionSpecProvider::getStages()`) lists **Nominations closed** and **Candidates advertised** as two separate values.

Write the event in the present tense in timelines ("Nominations open", "Voting closes") and use "opens on", "closes on", "opened on", "closed on" in sentences. Do not mix them: write "Voting closed on 12 Nov", never "Voting has closed" or "Voting is closed on".

### 2.2 Buttons and links

| Action | Word | Where |
|---|---|---|
| Save a form | **Submit** | Every edit and create form. Never Save, Create or Update |
| Open a form to add | **Add** + thing (**Add Position**, **Add Candidate**, **Add Election**) | List toolbars |
| Open a form to change | **Edit** + thing (**Edit Election**, **Edit Statement**) | Rows, overview |
| Make a copy | **Duplicate** | Never Copy |
| Delete a record | **Delete** | **Remove** only for taking a picture off a form |
| Leave without saving | **Back** (full pages), **Cancel** (dialogs and popups) | Section 3 |
| Go to the list of elections | **Back to Manage Elections** | |
| Go back to the election | **Back to the election** | Front end only |
| Show or hide the election | **Activate**, **Deactivate** | |
| Nominee answers | **Accept**, **Decline**, **Withdraw** | Front end |
| Voter | **Review Your Vote**, **Go Back and Change**, **Submit Vote**, **Change Vote** | Ballot |

A button is a verb phrase with no full stop or ellipsis. Never use **Save**, **Create**, **Update**, **Done**, **OK**, **Return**, **Click here**, or **Close** (except to close a dialog's own window).

### 2.3 Capitalisation and punctuation

- **Title Case**: page titles, menu items, tab names, button and link text that is an action, dialog titles, and screen names (Manage Elections, Election Overview), also inside a sentence.
- **Sentence case**: field labels, column headings, option labels, help text, messages, running text. Nouns from the glossary are lower case in running text ("this election", "the nominations period").
- Help text and messages are full sentences and end with a full stop. Labels, headings, column headings and buttons do not.
- No "..." to cut text short. A value is shown in full or not at all. The only allowed ellipsis is the single character in "Loading…".
- Placeholders say what to do: "Select a position", "Select a person". Not "- Select -".
- Numbers: digits for 10 and over and anywhere beside a label or unit; words for one to nine in running text.

## 3. Page structure

Every full back end page has the same frame, top to bottom:

```
 Manage Elections › <election name> › <page>          breadcrumb, above the heading
 Page heading
 [ Back ] [ Back to Manage Elections ] [ actions ]    action bar (view pages and lists)
 One or two sentences saying what the page is         when it is not obvious
 content
 [ Submit ] [ Back ] [ Back to Manage Elections ]     footer (edit forms only)
```

The shared code is `CRM_Elections_Helper_Frame` (trail, Back address, Submit buttons), `templates/CRM/Elections/Frame/BackButtons.tpl` and `js/election-breadcrumb.js` (the lists). Do not draw a breadcrumb or Back button by hand.

### 3.1 The four kinds of full page

| Kind | Examples | Has |
|---|---|---|
| **List** (SearchKit) | Manage Elections, Positions, Candidates, Results, Audit Log | Breadcrumb, heading, action bar, description, table, empty state |
| **View** | Election Overview | Breadcrumb, heading, action bar, blocks of information, no Submit |
| **Edit form** | Edit Election, Add Position, Add Candidate, Edit Statement, Results Page | Breadcrumb, heading, tabs where long, fields, footer |
| **Dialog** | Duplicate, Delete, Activate, Deactivate | Title, one sentence of what will happen, optional warning, the action and Cancel |

### 3.2 Where the buttons go, and in what order

Order is left to right (right to left languages mirror it).

| Page kind | Where | Buttons, in order | Primary |
|---|---|---|---|
| **View page** (Election Overview) | Top, under the heading, one row that wraps on a phone | **Back**, **Back to Manage Elections**, **Edit Election**, **Positions**, **Candidates**, **Results Page**, **Results** (only when results exist), **Audit Log**, **View as a Voter**, **Activate** or **Deactivate** | **Edit Election** |
| **Top level list** (Manage Elections) | Top, one row, right | **Add Election** | **Add Election** |
| **List below an election** (Positions, Candidates, Results, Audit Log) | Top, one row | **Back**, **Back to Manage Elections** on the left; **Add Position** or **Add Candidate** at the right end. Results and Audit Log have no Add | The Add button |
| **Edit form** | Bottom, after the last field, outside the tabs | **Submit**, **Back**, **Back to Manage Elections** | **Submit** |
| **Dialog** | Bottom | The action (**Delete**, **Duplicate**, **Activate**, **Deactivate**), then **Cancel** | The action; Delete and Deactivate use the danger style |
| **Row in a list** | End of the row | **Edit**, **Duplicate**, **Activate** or **Deactivate**, **Delete** | None |
| **Front end page** | Link top left; the stage panel holds the actions | **Back to the election** (not on the election page itself), then the panel's one primary action, then its secondary actions | The panel's next step |
| **Front end form** | Bottom | The primary action, then the secondary action; **Back to the election** link above the heading | The primary action |

Rules that follow:

- **Back** returns to the page the person came from without saving (the address comes from the request or the referrer, only if it is on this site and is not the page itself), and falls back to Manage Elections or the election overview. **Back to Manage Elections** goes to the list. Both are links styled as quiet buttons.
- Buttons that leave the page without saving are quiet and never primary. There is one primary button in a row and one row of action buttons at a level. Action buttons are not repeated at the top and bottom of a page.
- An action that cannot be used on a view page is shown **disabled with the reason** (hover text and text for screen readers), not hidden, so the row keeps its shape. Edit Election does this once an election is locked. The front end stage panel shows only what can be done now.
- The destructive action uses the danger style. In a list row it is last; in a dialog it is the action followed by Cancel. It is not on view pages: **Duplicate** and **Delete** belong to the Manage Elections list, where the row they act on is visible.
- A dialog has no breadcrumb and no Back; it closes itself.
- **Edit Statement** is one form with two footers: administrators get Submit, Back, Back to Manage Elections and the breadcrumb; a candidate on the front end gets Submit and **Back to the election**.
- **Elections Settings** is CiviCRM's own settings form. It keeps CiviCRM's breadcrumb and gets the standard footer through a form hook (`CRM_Elections_Helper_SettingsFrame`).
- Long forms use tabs (Election: Details, Dates, Participation, Display, Emails, Privacy). The footer is outside the tabs and visible on every tab. Say "Fields marked * are required" once, near the top.

### 3.3 The breadcrumb

The trail is the logical path, not the click history. It always starts at **Manage Elections**, then the election (a link to its Election Overview), then the area, then the page. The last item is the current page, is not a link and carries `aria-current="page"`. The election name is escaped and never cut short.

| Page | Trail |
|---|---|
| Manage Elections | **Manage Elections** (the current page) |
| Add Election | Manage Elections › **Add Election** |
| Edit Election | Manage Elections › *election name* › **Edit Election** |
| Election Overview | Manage Elections › **election name** (the heading is the election name, with "Election Overview" as a small label above it) |
| Positions, Candidates, Results, Results Page, Audit Log | Manage Elections › *election name* › **page** |
| Add or Edit Position | Manage Elections › *election name* › Positions › **Add Position** or **Edit Position** |
| Add Candidate, Edit Statement (back end) | Manage Elections › *election name* › Candidates › **page** |
| Elections Settings | CiviCRM's own trail |
| Dialogs, front end pages | none |

### 3.4 Button and link labels, by screen

A label that is not in this table is added here before it is used. Title Case, verb phrase, no full stop.

| Screen | Labels |
|---|---|
| Every back end page except top level lists | **Back**, **Back to Manage Elections** |
| Manage Elections | **Add Election**; row **Edit**, **Duplicate**, **Activate** or **Deactivate**, **Delete**; the election name links to the overview |
| Election Overview | The row in 3.2; the fingerprint's **Copy** (then **Copied**) |
| Positions | **Add Position**; row **Edit**, **Duplicate**, **Delete**; drag handle "Drag to reorder" with **Move up** and **Move down** |
| Candidates | **Add Candidate**; row **Edit Statement**. There is no row Delete: a nomination is declined or withdrawn, not deleted |
| Results, Audit Log | No action buttons |
| Edit forms | **Submit**, **Back**, **Back to Manage Elections** (**Fill in the dates** on the Dates tab; **Preview** on the Results Page form) |
| Dialogs | **Delete**, **Duplicate**, **Activate**, **Deactivate**, each with **Cancel**; the dialog title carries the noun ("Delete Election") |
| Election list (front end) | One card button, the next step: **Nominate**, **Accept Nomination**, **Vote**, **View Results**, or **View Election** |
| Election page (front end) | The panel's action, **Change Vote**; nominee cards **Second Nomination**, **Nominate** |
| Nominate | **Nominate**; link **Back to the election** |
| Accept or decline | **Accept**, **Decline** (before accepting) or **Withdraw** (after); link **Back to the election** |
| Ballot | **Review Your Vote**, then **Go Back and Change** or **Submit Vote**; **Add Another Write-in**; after voting **Change Vote** (if allowed) |
| Candidate page | **Edit Statement** (the candidate or an administrator); **Back to the election** |
| Results (front end) | Links **View Election Summary** and **Back to the election** |

## 4. Lists (SearchKit)

1. **Heading and description.** One or two sentences say what the table is, who can see it and what a row is. The audit log says that entries cannot be changed or removed.
2. **Columns.** The first column is the name of the thing, as a link (Results starts with **Position**, because a result belongs to a position). Then status, dates (earliest first), counts, actions. Labels are sentence case and name the value ("Voting opens"). No database id, machine name or JSON; a code is replaced by its label (the audit log shows "Election changed", not `election_changed`: `CRM_Elections_Helper_Audit::actionLabel()`).
3. **Row actions** are at the end of the row, an icon with a text label (the label may be visually hidden), in the order Edit, Duplicate, Activate or Deactivate, Delete. Delete is last and in the danger colour.
4. **Filters** sit above the table, labelled, with a help sentence under each, and one **Clear filters** link where there are filters (Manage Elections, Candidates). The Add button is at the top right.
5. **Locked rows.** A SearchKit link cannot be disabled, so when an action is not possible the row shows the reason as text in place of the link ("Locked: nominations have started."). Disabled buttons with a reason are for view pages only.
6. **Ordering.** Where order matters (Positions) the rows have a drag handle with the accessible name "Drag to reorder", a keyboard alternative (**Move up**, **Move down**), and the new order is saved at once with a confirmation announced through an `aria-live` region.
7. **Dates and times** use CiviCRM's format; the time zone is shown once above the table ("Times are shown in <zone>."), not in every cell.
8. **Paging.** 50 rows a page with the count shown.
9. **Empty state.** One sentence that says why the list is empty and what to do next ("There are no positions yet. Select Add Position to add one."). Never an empty table.
10. **Responsive.** At 375 px each row becomes a card with label then value (`data-label` on every cell, set by `ang/electionsLists.js`).
11. **No identifying data.** A list never shows how a person voted.

`ang/electionsLists.js` adds what SearchKit does not supply: a hidden table caption, `scope="col"` on headings, the card labels, the drag handle name and the move buttons. See the SearchKit exceptions in section 13.

## 5. Forms

- **Layout.** One column. Label above the field, help text below it, error below the help. Fields are grouped under short headings in the order a person thinks (what, when, who, how it looks); a start and an end date share a row. The primary button is never disabled while the form is filled in.
- **Labels** name the value in two to four words, in sentence case, with no colon, full stop or asterisk (the form adds the asterisk). A label is **unique on its form** (never five fields called "Start Date") and is the same everywhere the value appears (Voting method, Candidate name format and Statement read the same on the form, the list and the help). A label is not a question. A select with no choice shows "Select a position".
- **Help text** says what the field does and what the choice means, in a full sentence, with an example when a format is expected. A checkbox label states what happens when it is ticked ("Allow people to change their vote"). Exclusive choices are radio buttons or a select; three or fewer options are radio buttons.
- **Group pickers** say what the group is for ("Voting groups"). When CiviCRM hides groups or people from the organiser (no View All Contacts), a notice says why and what to do (`CRM_Elections_Helper_Visibility`).
- **Date labels** name the event: **Visible from**, **Visible until**, **Nominations open**, **Nominations close**, **Candidates advertised from**, **Voting opens**, **Voting closes**, **Results published on**.
- **Errors** name the field and how to fix it ("Voting must close after it opens"), never "Invalid value". Server messages for guards and locks say what is not allowed, why, and what to do instead.
- **After submitting**, a success message in one past-tense sentence naming the thing ("Election saved."); the person lands on the page that makes sense next. A failure keeps the person on the form with their input.
- **Destructive actions** (Delete, Deactivate when running, Withdraw) open a dialog that says what will happen and what cannot be undone, in a sentence ("The election can no longer be seen or used and cannot be restored from the screen."), not "WARNING: This action cannot be undone." Deactivating an election that is running (Withdraw in the code) asks the administrator to type the election's name.

### 5.1 Field labels by form

| Form | Labels |
|---|---|
| Edit Election, Details | **Name**, **Description** |
| Quick set-up | **Election cycle**, **Nominations start on**, **Each phase starts at**, **Each phase ends at**, **Results are published** |
| Dates | **Visible from**, **Visible until**, **Nominations open**, **Nominations close**, **Candidates advertised from**, **Voting opens**, **Voting closes**, **Results published on** |
| Participation | **Voting groups**, **Nominating groups**, **Candidate groups**, **Voting method**, **Required nominations**, **Allow people to nominate online**, **Allow people to change their vote**, **Allow people to take part without logging in** |
| Display | **Show candidate photos**, **Show candidate statements**, **Show who nominated each candidate**, **Candidate name format**, and the results page fields below |
| Emails | **Email people about their nominations**, **Nomination email template** |
| Privacy | **Anonymise votes** |
| Position | **Name**, **Description**, **Position type**, **Seats**, **Order on the ballot**, **Write-ins per seat**; questions: **Wording for Yes**, **Wording for No**, **Wording for Abstain**, **Passes when**, **Pass percentage**, **Quorum (% of voters)**, **Resolution text** |
| Add Candidate | **Position**, **Candidate**, **Mark as accepted**, **Statement** |
| Edit Statement | **Statement**, **Statement link** |
| Nominate | **Position**, **Nominee**, **Reason for the nomination** |
| Decline or Withdraw | **Comments** (optional) |
| Results Page | **Header text**, **Footer text**, **Logo**, **Logo description**, **Remove the logo**, **Show a celebration on the results page**, **Celebration effect**, **Celebration lasts (days)** |
| Elections Settings | **Image source**, **Candidate name format** |
| Ballot | Ranks and ticks are labelled with the candidate's name; the write-in field is **Write someone in** under the position's name |

The labels in the code are the source of truth; they were checked against `CreateElection::buildQuickForm()` when this page was written. The rank selects are labelled **Rank** or **Rank for place N**.

## 6. Front end

- **Election list.** One card per election: name, stage badge, one line on the dates that matter now, and one button for the next step. No administrator tools on any front end page (administrators use the back end).
- **Election page.** Stage stepper, then one "what you can do now" panel with its one primary button, then the person's own nominations and ballot, then positions and candidates. It says who the person is acting as and offers a way out if it is not them.
- **Nominating, accepting, declining.** One question per screen, naming the position and the person. **Accept** and **Decline** before accepting, **Withdraw** after, never both.
- **Candidate page.** Photo (if shown), name in the election's format, position, statement and its link. Nothing about who nominated them unless the election shows it.
- **Results.** Winners and how each was decided; never vote counts or who voted. A tie lists every tied candidate and chooses none. A question shows Passed, Not passed or No quorum.
- **Ballot.** The introduction is built from what the ballot holds. A review step comes before the vote is recorded. After voting, a receipt says the vote was recorded and when. The block "Your vote" may show a voter their own choices, to them alone, never in an email or on a shared page and never when the election anonymises votes. Ranks and ticks keep their input when the page is shown again with an error. **Change Vote** appears only if the election allows it; otherwise a sentence says the vote cannot be changed.
- **Buttons.** One primary button (`election-btn`) for the next step, secondary (`election-btn-quiet`) for alternatives, plain links for navigation.
- **Links** stay in the same window. The exception is a link that leaves the election for another site (a candidate's statement link), which opens in a new window with `rel="noopener noreferrer"` and says so for screen readers.
- **Personal links.** A page reached with a personal link keeps the person on it when they follow card links (the election list carries it).

## 7. Messages, notices and errors

| Kind | Class | Use | Example |
|---|---|---|---|
| Success | `messages success` | Something was saved or done | "Election saved." |
| Information | `messages status` | What to do next, or a state | "Next: add at least one position." |
| Warning | `messages warning` | A choice has a consequence | "Write-ins are allowed but people cannot change their vote." |
| Error | `messages error` | A change was refused or failed | "Positions cannot be changed once people have voted." |

- Messages that belong to the page use `no-popup`. Add `role="status"` (`role="alert"` for errors) where a message appears without a page load.
- Say what happened first, then why, then what to do. Do not blame the person.
- **Access denied** says what the person cannot do and how to get access ("You need to be logged in to see this election. Log in, or use your personal link.") with no code or stack trace. **Not found** says the election could not be found and links to the list, and sends 404 (`throwNotFoundPage()` in `elections.php`).
- Technical detail goes to the CiviCRM log, never the screen. On CiviCRM 6.19 an AJAX message is shown only if it is thrown as `CRM_Elections_Exception`; use it for every refusal that a person should read.

### 7.1 Emails

- Message template titles are `Election - <verb phrase>` with a plain hyphen: "Election - Invite nominee", "Election - Invite written-in person", "Election - Remind written-in person", "Election - Close written-in invitation".
- The subject says what the person is asked to do ("Accept or decline your nomination for President").
- The body says who is writing and for which election, what the person is asked to do and by when (with the time zone), gives one button or link, and says what happens if they do nothing. It never includes a choice a person made on the ballot.
- Emails use the same words as the screens and the same button words. Details are in [`wording.md`](wording.md).

## 8. Content style

1. **Voice.** Plain and direct; second person to voters and candidates ("You have been nominated"), neutral to administrators ("The election is active").
2. **Short.** One idea per sentence, labels of at most four words, help text of at most two sentences.
3. **Tense.** Present for what is true now, past for what happened ("Election saved."), future for dates ("Voting opens on 12 Nov").
4. **No jargon.** Not checksum, API, entity, token or hash. The fingerprint is the only hash a person sees.
5. **No "please"** for normal steps; it is kept for apologies. `WordingTest` fails on it.
6. **Dates and times.** Show them in the format set in CiviCRM (Administer, Localization, Date Formats), never a format chosen by the extension or the CMS, on both back end and front end. In PHP use `CRM_Utils_Date::customFormat()`, in Smarty `crmDate`; date inputs use CiviCRM's date input format. Never hand-format a date. Show the **site time zone** once on the page ("Times are shown in Australia/Sydney.", from `CRM_Elections_Helper_TimeZone`), not in every cell. A date with no time is a whole day. Dates written in documents ("12 Nov 2026") are examples only.
7. **Plurals** go through `ts` with a count ("1 ballot cast", "5 ballots cast"). Never "1 ballots".
8. **The documentation** uses the screen's exact title and button text.

## 9. Visual design and layout

- **Inherit the theme.** Fonts and colours come from the theme and CiviCRM variables with CSS variable fallbacks. No hard-coded colours except status colours that are tested for contrast.
- **Three button levels:** primary (one per screen or card), secondary (back and other actions), danger (delete). Back end Smarty pages use CiviCRM's `button` class, SearchKit and Afform pages use `af-button btn`, the front end uses `election-btn`. A new page uses the family of its place and does not mix them. The back end action bar fixes one height for every button (`.election-action-bar` in `css/electionforms.css`) so a theme's own button sizes do not show.
- **Icons.** Font Awesome through `crm-i`, always with a text label and `aria-hidden="true"` on the icon, the same everywhere: Edit `fa-pencil`, Add `fa-plus`, Duplicate `fa-copy`, Delete `fa-trash`, Back `fa-arrow-left`, View as a Voter `fa-eye`, Positions `fa-list-ol`, Candidates `fa-users`, Results `fa-trophy`, Results Page `fa-image`, Audit Log `fa-shield`, Copy `fa-clipboard`, Activate and Deactivate `fa-toggle-on` and `fa-toggle-off`.
- **Stage badge.** One component, with the stage words (2.1); colour is never the only signal.
- **Tables** are full width with a header row and `scope` on headings.
- **Widths.** Pages work from 375 px to 1280 px with no sideways scroll, and the text column keeps a readable width.
- **Fingerprints and other codes** are shown in a fixed-width font in a box that wraps, in full, with a **Copy** button that says "Copied" and announces it to screen readers (`js/election-copy.js`; it falls back to selecting the text).
- **CSS scope.** Rules for the back end buttons are scoped with `.crm-container .crm-submit-buttons:has(.election-back)`; do not widen them, because that once broke the front end button heights.

## 10. Accessibility (WCAG 2.1 AA)

- One `h1` per page and headings in order. Page content in `main`; the breadcrumb is `nav aria-label="Breadcrumb"`.
- Every control has an accessible name that matches its visible text; icon-only controls have a text alternative; row actions say which row ("Edit Honey Show Stewards 2027").
- Everything works by keyboard in a logical order with a visible focus indicator. A dialog traps focus and returns it when it closes.
- Contrast 4.5:1 for text and 3:1 for controls, in light and dark. Colour is never the only signal.
- Form errors and changing content (copy confirmation, drag result, saved confirmation) are announced with `aria-live` or `role`.
- **Touch targets.** Extension buttons are drawn at least 44 px high on a phone (`min-height: 2.75rem` for `election-btn`; 44 px for list row actions on narrow or touch screens). The layout spec fails any election button under **36 px**, which is the floor the checks enforce because some themes size their own buttons.
- **Dark mode** is not a separate stylesheet: the extension has no `prefers-color-scheme` rules and takes its colours from the theme's variables, so it follows the theme. Check it with the theme variants script (`tests/playwright/fixtures/theme-variants.sh`) and `THEME_VARIANT`.
- **Reduced motion.** The extension's CSS has no animation or transition. The results celebration (`js/election-celebrate.js`) checks `prefers-reduced-motion`, shows a static banner instead, runs for about six seconds, can be skipped, and stops when the tab is hidden.
- Every new or changed screen gets an axe scan and a keyboard check at 375, 768 and 1280 px. The Playwright accessibility and layout specs (`tests/playwright/specs/frontend/`) do this for the front end; for the back end, check by hand.

## 11. Translation and data

- Every string goes through `ts()` (`E::ts()` in PHP, `{ts}` in Smarty, `ts()` in Angular). Never join sentences; variable parts use `%1`, `%2`. Plurals use the count form of `ts`.
- Names, descriptions, statements and links entered by people are escaped on every output and never trusted as markup. CiviCRM's API stores `<` and `>` as `&lt;` and `&gt;` in text fields; decode with `CRM_Elections_Helper_ResultsDisplay::decode()` and escape once on output.
- Strings contain no HTML except where a link is needed, and then the link text is part of the translated sentence.

## 12. Screens

Every back end screen is one of the four kinds in 3.1; every front end screen is a listing, page or form under `civicrm/elections/` (list, view, nominations, vote, candidate, summary). The back end lists are Afform pages with their own routes (`manage`, `position-list`, `position-list-active`, `candidate-list`, `result-list`, `audit-log`, from `ang/*.aff.php`); the rest are menu routes in `xml/Menu/elections.xml`. The routes and the classes behind them are mapped in [`../architecture/code-map.md`](../architecture/code-map.md). A new screen is added to the tables in section 3 before it is built.

## 13. Known exceptions

- **Joomla breadcrumb.** Joomla's own page template draws every step as a link, so on Joomla the current page is a link to `#` with `aria-current="page"` inside it (`CRM_Elections_Helper_Frame::breadcrumbLinks()`). The rule "the current page is not a link" is met on WordPress and Drupal only. Fixing it would mean overriding a core Joomla template. Standalone uses the same form.
- **SearchKit.** These come from SearchKit and are left alone, with the extension adding what it can around them: the count text "N results" (not "Showing 1 to 50 of 132"), the icon-only Submit and Cancel of inline editing (their text is present for screen readers), and toolbar buttons that use `btn btn-primary`. Checked against the 6.16.5 templates; recheck on 6.19.
- **Message templates link.** The administrator-only link "Edit message templates" on the Emails tab opens in a new tab and says so. The rule that links stay in the same window is for the front end.
- **Not built.** The summary "There are N problems to fix" at the top of a form with links to the fields, and a prompt for unsaved changes when leaving a form, are not implemented; QuickForm shows each error beside its field. Add them only if a decision brings them back.
- **Locked reasons.** The only locked reason a list shows is "Locked: nominations have started." (`ElectionSpecProvider::renderLockReason()`); the plan's example "Locked: voting has started" is not built.
- **Elections Settings.** If the form hook fails on a CiviCRM version, `CRM_Elections_Helper_SettingsFrame` logs a warning and CiviCRM's own Save and Cancel stay.

## 14. Checklist for reviewing a pull request

A screen conforms when every box can be ticked. Copy the list into the pull request when a screen changes.

- [ ] The heading names the screen, and a description says what it is (not needed for dialogs and self-evident forms).
- [ ] Breadcrumb as 3.3 on full back end pages; **Back** and **Back to Manage Elections** in the right order and place.
- [ ] Buttons in the place and order of 3.2, labelled as 3.4, one primary, the right class family.
- [ ] Field labels as section 5: sentence case, unique on the form, the same label for the same value everywhere.
- [ ] Every word is in the glossary or is a plain word. No machine names, codes or truncated values. No "member".
- [ ] Capitalisation and punctuation as 2.3; no "...".
- [ ] Dates, times and the time zone as section 8.
- [ ] Empty, success, warning and error states written as section 7; refusals use `CRM_Elections_Exception`.
- [ ] Destructive actions have a confirmation as section 5.
- [ ] Headings, landmarks, names, keyboard, focus, contrast and announcements as section 10; axe and keyboard check at 375, 768 and 1280 px.
- [ ] Nothing shows how someone voted.
- [ ] Strings go through `ts()` with no joined sentences; user text is escaped.
- [ ] Checked on WordPress, Drupal and Joomla, and on CiviCRM 6.16 and 6.19.
- [ ] The documentation page and its screenshot show the same words.
- [ ] A database change has an upgrade step and a test (see [`git-and-pull-requests.md`](git-and-pull-requests.md)).
