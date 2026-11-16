# Decisions: interface and wording

Why the screens look and read the way they do. The current rules themselves are in [`../standards/ui-standards.md`](../standards/ui-standards.md) and [`../standards/wording.md`](../standards/wording.md); this page keeps the decisions behind them. The index of all decisions is [`README.md`](README.md).

**IDs.** The plan's IDs are kept. `U1` to `U17` are the interface standard decisions (1 November 2026). Two review rounds each numbered their points `R1`, `R2` and so on; they are prefixed here: `R2.0-n` is the review of 31 October 2026 (version 2.0; also logged as `D20` to `D29`, which stay the main ID) and `R2.1-n` is the screen review of 1 November 2026 (version 2.1). `F1` to `F7` are the choices made while building from the maintainer's feedback of 1 November, and `SD1` to `SD6` the small decisions of 2 November. The remaining `R2.1-n` and `SD` decisions about behaviour are in [`voting-and-elections.md`](voting-and-elections.md).

Each entry gives the decision, why, the date and where it shows.

## Words

### D6 – A wording pass over all interface text
- **Decision.** Every user-visible string was reviewed and wrapped in `ts()`.
- **Why.** Translatable and consistent text throughout.
- **Date.** October 2026 (2.0 build). **In the code.** Every string; the glossary in the standard.

### D23 (R2.0-4) and U2 – "People", not "members"
- **Decision.** Everyone who takes part is "people" (voters, nominees, candidates, organisers). "Member" and "membership" appear in nothing a person reads, except proper names and the sentence "No CiviCRM Membership is needed". Database columns and API parameters (`member_id`, `member_nominee`) keep their names. "Contact" only for CiviCRM records and permissions. The test role "Elections Member" became "Elections Participant".
- **Why.** Not everyone who votes is a member, and the word suggests a CiviCRM Membership is needed. Renaming columns would break existing sites for no benefit.
- **Date.** 2026-10-31 (D23), 2026-11-01 (U2). **In the code.** `CRM_Elections_WordingTest`; about 40 strings and the docs; see [`../standards/wording.md`](../standards/wording.md).

### D26 (R2.0-7) – Example name
- **Decision.** Examples use Robert Irwin, with the invented nickname Rob: Display name (Robert Irwin), First "Nickname" Last (Robert "Rob" Irwin), Nickname Last (Rob Irwin).
- **Why.** A real nickname is not needed to show a format.
- **Date.** 2026-10-31. **In the code.** `CRM_Elections_Helper_Candidate`, the docs and unit tests.

### U1 – Capitalisation
- **Decision.** Title Case for page titles, menu items, tabs and action buttons; sentence case for labels, column headings, help and messages.
- **Why.** Matches CiviCRM's own screens and what was mostly used.
- **Date.** 2026-11-01. **In the code.** Section 2.3 of the standard; labels in `CRM_Elections_Form_*`.

### U3 – Decline and Withdraw have one meaning each
- **Decision.** A nominee refusing uses **Accept** or **Decline** (status Declined). **Withdraw** is only for a candidate who had accepted (status Withdrawn). The menu item "Withdraw Nomination" became "Decline Nomination", and the button changes with the state.
- **Why.** Each word gets one meaning. The code and messages said "rejected" while the button said "Withdraw".
- **Date.** 2026-11-01. **In the code.** `civicrm/elections/nominations/reject` (the route name is kept); `RejectNomination.tpl`; the stored difference is `R2.1-2`. The test fails on "rejected nomination".

### U4 – Withdraw Election becomes Deactivate
- **Decision.** The administrator's "Withdraw Election" is **Deactivate**, with a stronger confirmation when the election is running (the election's name is typed) and the audit entry kept.
- **Why.** So "withdraw" means only a candidate pulling out. The result is the same.
- **Date.** 2026-11-01. **In the code.** `CRM_Elections_Form_ElectionVisibility`.

### U5 – Active and Inactive
- **Decision.** Administrators see **Active** and **Inactive** (not Visible or Hidden). "Visible to people" is used only in the Dates tab labels.
- **Date.** 2026-11-01. **In the code.** The Active column; the stage badge.

### U11 – No "..." in text
- **Decision.** No ellipsis to cut text short; placeholders say what to do ("Select a person").
- **Why.** The maintainer asked for it on the fingerprint: a value is shown in full or not at all.
- **Date.** 2026-11-01. **In the code.** `CRM_Elections_Form_AddCandidate` and the other forms.

### SD1 – "Locked" is in the glossary
- **Decision.** **Locked** names an election that is active and has started, or has ballots or results. Lists say "Locked: nominations have started."
- **Date.** 2026-11-02. **In the code.** `ElectionSpecProvider::renderLockReason()`.

### SD3 and SD4 – Two wordings left as they are
- **Decision.** The label "CMS" on Elections Settings stays; the list of template variables stays in the nomination email field's help.
- **Date.** 2026-11-02. **In the code.** `settings/`; `CreateElection.tpl`.

### SD5 – Labels added to the label table
- **Decision.** "Second Nomination", "Add Another Write-in" and "View Election Summary" are added to the label table. The unbuilt View Candidates and View Nominations buttons are dropped. The election card already says "View Results"; "View Election Summary" is the link on the results page that opens the summary, so it keeps its name.
- **Date.** 2026-11-02. **In the code.** Section 3.4 of the standard.

### SD2 – Delete Election dialog sentence
- **Decision.** The Delete Election dialog says the election can no longer be seen or used and cannot be restored from the screen.
- **Why.** Replaces "WARNING: This action cannot be undone." with what is lost.
- **Date.** 2026-11-02. **In the code.** `DeleteConfirmMessage.tpl`.

## Structure, buttons and labels

### U6 – One word on every edit form: Submit
- **Decision.** **Submit** on every edit form. Confirmation dialogs keep the verb of what they do (Delete, Duplicate, Activate, Deactivate). SearchKit's own inline Save is left as it is.
- **Why.** One word for one action; "Save", "Create" and "Add Candidate" were all in use.
- **Date.** 2026-11-01. **In the code.** `CRM_Elections_Helper_Frame::addSubmitButtons()`. (`F4` is the same decision recorded while building.)

### U7 – Back goes where the person came from
- **Decision.** **Back** returns to the page the person came from, falling back to the page above in the breadcrumb. The address is read when the page first opens, kept only if it is on this site and is not the page itself, and carried by a form that is shown again.
- **Date.** 2026-11-01. **In the code.** `CRM_Elections_Helper_Frame::backUrl()`; `FrameTest`. (`F1` is the same.)

### U8 – Breadcrumb on every full back end page
- **Decision.** `Manage Elections › election name › page` on every full back end page; none on dialogs or the front end. The election name links to the overview; the current page is not a link.
- **Date.** 2026-11-01. **In the code.** `CRM_Elections_Helper_Frame::trail()`, `js/election-breadcrumb.js`. (`F3` is the same.) The Joomla exception is `SD6`.

### U9 – Three button families
- **Decision.** CiviCRM `button` on back end Smarty pages, `af-button btn` on SearchKit and Afform pages, `election-btn` on the front end only.
- **Date.** 2026-11-01. **In the code.** `css/electionforms.css`.

### U10 – Dates use CiviCRM's format
- **Decision.** Dates and times always use the format set in CiviCRM (back end and front end), through `CRM_Utils_Date::customFormat()` and `crmDate`, never a format chosen by the extension or the CMS. The time zone is shown once per page. (The proposal was a fixed `12 Nov 2026, 5:00 pm`; the maintainer changed it.)
- **Why.** The front end pages are CiviCRM pages, and CiviCRM's own front end pages do the same.
- **Date.** 2026-11-01. **In the code.** `CRM_Elections_Helper_TimeZone::getTimeZoneLine()`; see section 8 of the standard.

### U12, U13 – Empty states and icons
- **Decision.** Every list has an empty state with a reason and the next step (U12). The icon list is fixed, one icon per action (U13).
- **Date.** 2026-11-01. **In the code.** `noResultsText` in `managed/SavedSearch_*.mgd.php`; icons in section 9 of the standard.

### U15, U16, U17 – Button table, breadcrumb table, field labels
- **Decision.** Button placement and order exactly as the table in section 3.2 and the labels as 3.4 (U15); the breadcrumb for each page as the table in 3.3, with Elections Settings keeping CiviCRM's own (U16); field labels as section 5, in particular the date labels instead of "Start Date", and "Allow people to take part without logging in" (U17).
- **Why.** Five fields were all labelled "Start Date" and told apart only by the heading above them.
- **Date.** 2026-11-01. **In the code.** `CRM_Elections_Form_CreateElection::buildQuickForm()`, the templates under `templates/CRM/Elections/`.

### F2 – Which screens get the standard frame
- **Decision.** Full-page edit forms and view pages (Edit and Add Election, Add and Edit Position, Add Candidate, the candidate statement, the Results page form, the overview and the Positions, Candidates, Results and Audit log pages) get the breadcrumb and Back buttons. Dialogs that open in a popup keep their own buttons because a popup closes itself.
- **Date.** 2026-11-01. **In the code.** `CRM_Elections_Helper_Frame::isPopup()`.

### F5 – Edit Election is disabled, not hidden, once an election has run
- **Decision.** The button stays in first place and is shown disabled with the reason, so the row keeps its shape and the reason is visible.
- **Date.** 2026-11-01. **In the code.** `ElectionOverview.tpl` (`election-action-disabled`).

### F6 and F7 – Fingerprint and audit log wording
- **Decision.** The fingerprint is shown in full (64 characters) above the audit log table in a fixed-width font that wraps, with a **Copy** button that says "Copied" and works without the clipboard permission (it falls back to selecting the text). A fixed list gives each audit action a label (Election changed, Votes anonymised and so on); an action not in the list has its name turned into words, so a code is never shown.
- **Date.** 2026-11-01. **In the code.** `js/election-copy.js`; `CRM_Elections_Helper_Audit::actionLabel()`; `FrameTest`.

### D20 (R2.0-1) – Manage Elections filters
- **Decision.** Help text under every filter; date range filters for **Voting closes** and **Results published** only (none for the other dates); a **Stage** filter.
- **Why.** CiviCRM's date ranges give relative periods with no code; the table already holds the dates.
- **Date.** 2026-10-31. **In the code.** `ang/afsearchManageElections.aff.html`; the computed `stage` field.

## Decisions from the screen review (2.1)

| ID | Decision | Why | In the code |
|---|---|---|---|
| R2.1-4 | The overview heading is the election name, with "Election Overview" as a small label above it; the breadcrumb's last item is the election name | The page is about one election | `ElectionOverview.php` (`setTitle`) |
| R2.1-5 | **Activate** or **Deactivate** is in the overview action row after View as a Voter | The "Next" message tells the administrator to activate | `ElectionOverview.tpl` |
| R2.1-6 | No Delete in the Candidates row actions; first column is the name, then position; the status column says Nominee, Candidate, Declined or Withdrawn | No route deletes a nomination | `managed/SavedSearch_ElectionCandidates.mgd.php` (`nomination_status:label`) |
| R2.1-7 | Results list keeps **Position** as its first column | A result belongs to a position (the recorded exception to "name first") | `SavedSearch_ElectionResults.mgd.php` |
| R2.1-8 | When an action is not possible a list row shows the reason as text and no link; disabled buttons with a reason are for the overview only | A SearchKit link cannot be disabled | `ElectionSpecProvider::renderLockReason()` |
| R2.1-9 | **Elections Settings** gets Submit, Back and Back to Manage Elections through a form hook and keeps CiviCRM's own trail; if the hook fails the page keeps CiviCRM's Save | The page is CiviCRM's generic settings form | `CRM_Elections_Helper_SettingsFrame` |
| R2.1-10 | **Edit Statement** is one form with two footers chosen by who uses it: Submit, Back, Back to Manage Elections for administrators; Submit and **Back to the election** for a candidate | One form, two audiences | `CRM_Elections_Form_EditStatement` |
| R2.1-11 | Administrator buttons (Create Election, Activate, Edit, Delete) are removed from the front end | They used the CiviCRM `button` class that U9 forbids there and repeated the back end | `Elections.tpl` (no administrator tools) |
| R2.1-12 | The time zone links that opened a new window become one line "Times are shown in %1."; a candidate's statement link keeps opening a new window with `rel="noopener noreferrer"` | The link leaves the election | `CandidateStatement.tpl`; `CRM_Elections_Helper_TimeZone` |
| R2.1-13 | No "please": the example subject is "Accept or decline your nomination for President" | The standard's rule 8.5 wins | `WordingTest` |
| R2.1-14 | The celebration shows each time the results page is opened, with a visible stop control | Matches the help text and the server-side window (`D27`) | `js/election-celebrate.js` |
| R2.1-15 | The dialog title carries the noun ("Delete Election"); the button is only the verb (**Delete**) | One word per action | The `Delete*` and `ElectionVisibility` forms |
| R2.1-16 | Labels added to the standard: **Fill in the dates**, **Preview**, the Results Page icon `fa-image`, three End Date fields, the results page fields under Display | Gaps found in the review | Section 3.4, 5 and 9 of the standard |
| R2.1-17 | A not-found page; 403 and 404 sent; every message a person can see goes through `ts()` in plain words, with detail in the CiviCRM log | More than 30 developer messages reached voters | `throwNotFoundPage()` in `elections.php`; `ErrorPagesTest` |
| R2.1-18 | SearchKit limits get a table caption, `scope` and a card layout at 375 px through extension CSS and a little JavaScript; "N results", the icon-only inline edit buttons and the toolbar `btn btn-primary` are recorded exceptions | SearchKit supplies them | `ang/electionsLists.js` |
| R2.1-19 | The time zone line shows the site time zone, once: "Times are shown in %1." | It is what dates are stored and shown in | `CRM_Elections_Helper_TimeZone::getTimeZoneLine()` |

The review was done by reading the code; the decisions were approved on 2026-11-01. R2.1-1, R2.1-2, R2.1-3 and R2.1-20 are in [`voting-and-elections.md`](voting-and-elections.md).

## Joomla breadcrumb exception

### SD6 – The current page is a link on Joomla
- **Decision.** Joomla's page template draws every breadcrumb step as a link, so on Joomla the current page is a link to `#` with `aria-current="page"` inside it. The rule "the current page is not a link" is met on WordPress and Drupal only.
- **Why.** Fixing it would mean overriding a core Joomla template.
- **Date.** 2026-11-02. **In the code.** `CRM_Elections_Helper_Frame::breadcrumbLinks()`.

## Open decisions

### U14 – Publishing the standards with the extension
- **Question.** Does the standard also go into `docs/` (published with the extension and listed in `mkdocs.yml`), linked from `CONTRIBUTING.md`, with its checklist as a pull request template?
- **Status.** Open (2.2). The maintainer decided on 2026-11-01 that the guide stays out of `docs/` for now. It now lives in [`../standards/ui-standards.md`](../standards/ui-standards.md) in the repository's developer guide, which is left out of releases. `CONTRIBUTING.md` and `.github` are also left out of the released package; `docs/` is not. There is no pull request template yet.
