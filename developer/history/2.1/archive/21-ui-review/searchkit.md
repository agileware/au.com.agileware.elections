> **Historical.** Written during version 2.1; may be out of date. Current guidance: [User interface standards](../../../../standards/ui-standards.md).

Covers: version 2.1, a detail file of the screen review, 2026-11-01 to 2026-11-02. Copied from the project plan with names, paths and account names removed; screenshots under `plan/evidence/` are not kept (image not kept).

# Review of the SearchKit and Afform back end lists against the UI standards

Scope: `ang/afsearch*.aff.html` and `.aff.php`, `managed/SavedSearch_*.mgd.php`, `xml/Menu/elections.xml`, `css/`, `js/`, and what supplies the lists (`Civi/Api4/Service/Spec/Provider/ElectionSpecProvider.php`, `CRM/Elections/BAO/ElectionPosition.php`, schema titles).
Extension root: `<workspace>/au.com.agileware.elections` (branch version-2.1). Nothing was changed.
SearchKit behaviour was read from the SearchKit templates installed in the WordPress site (`elections2`, CiviCRM 6.16.5). Newer versions (6.19) were not read, so SearchKit-supplied points are marked as such.

Paths below are relative to the extension root. `html` = `ang/afsearch<Name>.aff.html`, `mgd` = `managed/SavedSearch_<Name>.mgd.php`.

Facts that apply to every screen (verified):
- No `.aff.html` contains a breadcrumb. No hook adds one (`hideNonRequiredItemsOnPage()` in `elections.php:201` only hides it for the Smarty pages).
- No `js/` file and no rule in `css/electionforms.css` is aimed at a SearchKit table or an afform page. The only `@media` rules in the CSS are at lines 784 (stepper) and 1109 (print).
- No display sets `noResultsText`, so every list shows SearchKit's own "None found.".
- Dates are formatted through `CRM_Utils_Date::customFormat()` by SearchKit (`AbstractRunAction.php:1371`), so the CiviCRM date format is used.
- Static text in the `.aff.html` files (filter labels, help, "Filters", "Reset") is translated automatically by Afform's string visitor, and the `{{:: ts() }}` strings are translated by Angular. Display labels in the `.mgd.php` files use `E::ts()`. No translation findings.
- Common (X-) findings are listed at the end and are referred to from each screen.

---

## 1. Manage Elections (`civicrm/elections/manage`)

Files: `ang/afsearchManageElections.aff.html`, `ang/afsearchManageElections.aff.php`, `managed/SavedSearch_ManageElections.mgd.php`.

**Currently conforms:** the page title "Manage Elections" is the page heading; description present (it is a SearchKit info box above the table); the Add button is in the toolbar at top right (`toolbar`, primary style, `fa-plus`); filters are above the table, each labelled, with help sentences ending in a full stop; the election name links to the Election Overview; column order is name, Active, Stage, dates earliest first, Positions count, row actions; sentence case column labels that name the value (Nominations open, Voting opens, Voting closes); no id or machine name column is shown; dates use the CiviCRM format; page size 50; Delete is last and `danger`; Edit, Duplicate, Delete, Positions, Candidates use the icons listed in 9.3; Duplicate and Delete open as popups (dialogs).

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| S-MG-1 | `html:1-13` | No breadcrumb on the page. | 3.1, 3.7 | Show a breadcrumb above the heading with the single item "Manage Elections" as the current page (`nav aria-label="Breadcrumb"`, `aria-current="page"`). |
| S-MG-2 | `mgd:116` | Toolbar button text `E::ts('Create Election')`. | 2.3, 3.6, 3.8, 12 | Change the text to "Add Election". Keep the icon, the primary style and the top right place. |
| S-MG-3 | `mgd:100-107` | Row actions in this order: Activate, Deactivate, Withdraw, Positions, Candidates, Edit, Duplicate, Delete. | 3.6, 3.8, 4.3 | Reorder to Edit, Duplicate, Activate or Deactivate, Delete. |
| S-MG-4 | `mgd:103-104` | Row buttons "Positions" (`civicrm/elections/positions?eid=[id]`) and "Candidates" (`civicrm/elections/candidate-list#/?eid=[id]`). | 3.8 (row: Edit, Duplicate, Activate / Deactivate, Delete), 4.3 | Remove them from the row (the Positions count column already links to Positions, and the election name opens the Election Overview, which has the Candidates button). Decision for the maintainer if they should stay; if they stay, 3.8 must list them. |
| S-MG-5 | `mgd:102` | Row action text `E::ts('Withdraw')`, icon `fa-ban`, shown when `is_locked` is true. | 2.1, 2.3, decision U4 | Replace by a single "Deactivate" for any active election, running or not. The stronger confirmation for a running election belongs in the dialog. |
| S-MG-6 | `mgd:100-101`, `html:10` | Row action styles `'style' => 'success'` (Activate) and `'warning'` (Deactivate); Reset button `btn btn-warning`. | 9.2 (three levels: primary, secondary, danger), 4.3 | Make Activate and Deactivate the normal secondary style. Only Delete is `danger`. Make the Clear filters control quiet (see S-MG-14). |
| S-MG-7 | `mgd:105`, `mgd:107` (conditions `['is_locked','=',FALSE]`) and `mgd:101` | Edit and Delete are hidden when the election is locked; Deactivate is hidden when it is locked. | 3.4, 3.6 (a button not possible now is shown disabled with the reason, position never changes) | Show Edit and Delete in the same place, disabled, with the reason ("Locked: the election is active and nominations have started."). Whether a SearchKit row link can be shown disabled is unverified (the 6.16.5 button template has no disabled state); if it cannot, record it in section 12 or use extension markup. |
| S-MG-8 | `mgd:100-107` | No `title` set on any row link, so the visible text is only "Edit", "Duplicate", "Delete" and so on for every row. | 10 (row actions say which row), 4.3 | Name the row for screen readers, for example "Edit Honey Show Stewards 2027" (use the `[name]` token). The 6.16.5 template puts `link.title` in the `title` attribute only, which is a description and not the accessible name; whether that is enough is unverified, so the markup may need an `aria-label`. |
| S-MG-9 | `mgd:98` | Row action group `'size' => 'btn-xs'`. | 10 (touch targets at least 44 by 44 px on a phone), 9.2 | Use a size that gives 44 px targets at 375 px (at least on a phone). |
| S-MG-10 | `mgd:79-83` | The Active column is `'editable' => TRUE`, so it can be changed inline with SearchKit's own control, with no confirmation, and it duplicates the Activate / Deactivate row action. | 4.5 (a toggle that changes who can see an election asks for confirmation), 1.3 | Make the column read only and leave switching to the Activate / Deactivate dialog, or keep the inline control and ask for confirmation before the change is saved. Show Yes or No. |
| S-MG-11 | `Civi/Api4/Service/Spec/Provider/ElectionSpecProvider.php:37-44` | Stage options: "Not running", "Nominations open", "Candidates announced", "Voting open", "Voting closed", "Results published". Shown in the Stage column (`mgd:84`) and the Stage filter. | 2.2 (Inactive, Not started, Nominations open, Nominations closed / Candidates advertised, Voting open, Voting closed, Results published), 4.2 | Use the 2.2 words. "Not running" covers two states that 2.2 names separately: **Inactive** (not active) and **Not started** (active, nominations not yet open). "Candidates announced" becomes "Candidates advertised". The step "Nominations closed" (closed, candidates not yet advertised) does not exist in `renderStage` (`:97-109`) and needs adding. The Stage filter help in `ElectionSpecProvider.php:61` repeats "Not running". |
| S-MG-12 | `mgd:88`, `html:8` | Column label `E::ts('Results')` and filter label `Results` for `result_date`. | 4.2 (name the value), 5.6 ("Results published on"), 2.2 | Label it "Results published" (column and filter). "Results" is also the name of another screen. |
| S-MG-13 | `html:5`, `html:6` | Filter help: "Yes shows elections that are switched on for voters. No shows elections that are still being set up or have been switched off." and "Not running means it is switched off or its nominations have not started." | 2.1 (avoid switch on, enable; use Active / Inactive), 2.2 | Rewrite in Active and Inactive words, for example "Yes shows active elections. No shows inactive elections." The Stage help follows S-MG-11. |
| S-MG-14 | `html:10` | `<button class="af-button btn btn-warning" type="reset" crm-icon="fa-undo">Reset</button>`. | 4.4 (one "Clear filters" link), 3.8 | Replace by one quiet link or button with the text "Clear filters". |
| S-MG-15 | none (`noResultsText` not set) | Empty table shows SearchKit's "None found." | 4.9 | Set an empty-state sentence with the reason and next step, for example "There are no elections yet. Select Add Election to create one." The same text is shown when filters match nothing, so word it to cover both ("No elections match. Clear the filters or add an election."), or use one sentence. |
| S-MG-16 | `mgd:65` | `'pager' => []` (no `show_count`). | 4.8 (count shown) | Set `show_count` so the count is shown. The wording SearchKit supplies ("5 results") differs from the guide's example "Showing 1 to 50 of 132"; see X-2. |
| S-MG-17 | whole page | No time zone line above the table. | 4.7, 8.7 | Show the time zone once above the table ("Times are shown in <zone>"). Which zone applies (site or election) is not decided in the code read; unverified. |
| S-MG-18 | `mgd:62` | Description: "Use Activate to show an election to voters. An election needs at least one position before it can be active. Once an election is active and nominations have started it is locked: it cannot be edited or deleted, and its positions cannot be changed." | 4.1 | Start with one sentence saying what the table is and what a row is ("Every election that has not been deleted, one row each."), then keep the rule about locking. The "who can see it" part is missing. Low priority. |

**Needs live check:** exactly one `h1` and that it is the afform title; whether CiviCRM adds its own default breadcrumb to afform pages (S-MG-1); the inline edit of Active in 6.19; keyboard order of the toolbar, filters, table and row actions; focus visible; axe at 375, 768 and 1280 px; 375 px layout (X-1); the Add Election button is 44 px high at 375 px.

Also applies: X-1, X-2, X-3, X-4.

---

## 2. Positions, running (`civicrm/elections/position-list`)

Files: `ang/afsearchElectionPositions.aff.html`, `.aff.php`, `managed/SavedSearch_ElectionPositions.mgd.php` (display `ElectionPositions_Table_1`). Reached from the redirect route `civicrm/elections/positions` (`CRM/Elections/Page/ElectionPositions.php:27`).

**Currently conforms:** heading and description present; Add Position is primary with `fa-plus`; the drag handle exists (`'draggable' => 'sortorder'`) and rows are sorted by running order (`sortorder` ASC); Duplicate and Delete use the right icons, Delete is last and `danger`; Duplicate and Delete open as popups; no id or machine name column; "Seats" and "Write-ins per seat" labels match 5.6; sentence case labels; plural of the count text is by SearchKit; Positions and Seats columns show no identifying vote data.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| S-PO-1 | `html:1-7` | No breadcrumb. | 3.1, 3.7 | Show Manage Elections › election name (link to the Election Overview) › Positions, last item current. |
| S-PO-2 | `html:3-4` | Action bar is `<p>` with "Back to Manage Elections" then "Add Position" side by side at the left. No "Back" button. | 3.2, 3.6, 3.8, U7 | Row order: Back, Back to Manage Elections at the left; Add Position at the right end of the same row. "Back" returns to the page the person came from, falling back to the Election Overview. |
| S-PO-3 | `ang/afsearchElectionPositions.aff.php:6` | Page title `E::ts('Election Positions')`. | 3.7, 3.8, 3.3 (screen name "Positions"), 2.4 | Title the page "Positions" so the heading, the breadcrumb and the Overview button read the same. |
| S-PO-4 | `mgd:57-62` | Row actions are Duplicate and Delete only; no Edit. | 3.6, 3.8 (Positions row: Edit, Duplicate, Delete), 4.3 | Add "Edit" first (icon `fa-pencil`, to `civicrm/elections/positions/create?eid=[election_id]&epid=[id]`, which exists). Inline editing of name, seats, write-ins and description can stay. |
| S-PO-5 | `mgd:57-62` | No `title` on the row links. | 10, 4.3 | Name the row for screen readers ("Edit Beekeeper President", "Delete Beekeeper President"). Same caveat as S-MG-8. |
| S-PO-6 | `mgd:56` | `'size' => 'btn-xs'`. | 10 (44 px targets) | Use a size with 44 px targets on a phone. |
| S-PO-7 | SearchKit `crmSearchDisplayTableBody.html:3` (6.16.5) | The handle is `<span class="crm-draggable" title="Drag to reposition">` with an icon that is `aria-hidden`; it is not focusable and there is no move up or move down control. The header cell holds an icon titled "Drag columns to reposition" and a screen reader text "Bulk row actions". | 4.6, 10, 9.3 | The accessible name must be "Drag to reorder" and a keyboard alternative (Move up, Move down for the row, with the position name) must exist. This cannot be set in the display settings, so it needs extension markup or JavaScript on this page. The new order must be announced (`aria-live`) after it is saved. Header cell name should say "Reorder", not "Bulk row actions". Record in section 12 if left to SearchKit. |
| S-PO-8 | `mgd:51`, `mgd:92` | Column label `E::ts('Type')`. | 5.6 (the label is "Position type"), 4.2, 5.6 line 312 | Label the column "Position type". |
| S-PO-9 | `CRM/Elections/BAO/ElectionPosition.php:20-21` | The values shown in that column are "Election position" and "Referendum question". | 5.6 (Position or Question), 2.1 | Show "Position" and "Question". |
| S-PO-10 | `mgd:46-47` | `'limit' => 100`, `'pager' => FALSE`. | 4.8 (50 rows a page, count shown) | Set page size 50 with the pager and the count. A drag across pages needs a decision (positions are normally few; if drag order is needed on a longer list, keep one page and show the count). |
| S-PO-11 | none | No `noResultsText`: shows "None found.". | 4.9 | "There are no positions yet. Select Add Position to add one." |
| S-PO-12 | `mgd:43` and `html` locked variant | Description: "... Positions can only be changed before the election starts." The Manage Elections description says "Once an election is active and nominations have started it is locked" and the locked page says "cannot be changed while the election is active." | 8.6, 2.2 | Use one wording of the lock rule on all three screens. |
| S-PO-13 | `xml/Menu/elections.xml:23-27` | Route `civicrm/elections/positions` (redirect only) has title "Election Positions". | 3.7, 2.4 | Change to "Positions" so the title is right if it is ever shown. Low priority. |

**Needs live check:** the drop confirmation ("short confirmation" after a drag; SearchKit uses `crmStatus` on save, wording not verified); that the new order is saved at once on 6.16 and 6.19; keyboard use of the handle; the inline edit Save and Cancel controls (SearchKit supplies icon buttons whose hidden text is "Save" and "Cancel"; see X-2); 375 px layout; 44 px targets.

Also applies: X-1, X-2, X-4.

---

## 3. Positions, locked (`civicrm/elections/position-list-active`)

Files: `ang/afsearchElectionPositionsLocked.aff.html`, `.aff.php`, display `ElectionPositions_Locked` in `managed/SavedSearch_ElectionPositions.mgd.php`.

**Currently conforms:** the page says why nothing can be edited; no row actions or drag handle are offered; Position, Seats and Write-ins per seat labels match 5.6; no id or machine name column; the redirect picks this page when the election is locked.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| S-PL-1 | `html:1-7` | No breadcrumb. | 3.1, 3.7 | Same trail as Positions (S-PO-1). |
| S-PL-2 | `html:3` | Only "Back to Manage Elections". No "Back". No Add Position. | 3.2, 3.4, 3.6, U7 | Show Back, Back to Manage Elections and, at the right end, Add Position **disabled with the reason** ("Positions cannot be changed while the election is locked."), so the row keeps its shape. |
| S-PL-3 | `html:5` and `mgd:85` | Two messages for the same fact: `<p class="election-locked-note" role="status">The positions of this election are listed below. They cannot be changed while the election is active.</p>` and the display description "The positions of an active election. They cannot be changed, so there is nothing to drag, edit or delete." | 4.1, 7, 8.2 | Keep one sentence. A static note on page load does not need `role="status"`. The class `election-locked-note` has no CSS rule (`css/electionforms.css`), so it is an unstyled paragraph; use the info message style of section 7 or drop the class. |
| S-PL-4 | `ang/afsearchElectionPositionsLocked.aff.php:6` | Title `E::ts('Election Positions')`, the same as the editable page. | 3.7, 3.8 | Title "Positions" (see S-PO-3). |
| S-PL-5 | `mgd:92` | Column label `E::ts('Type')`; values as in S-PO-9. | 5.6 | Label "Position type"; values "Position" / "Question". |
| S-PL-6 | `mgd:88-89` | `'limit' => 100`, `'pager' => FALSE`. | 4.8 | 50 per page with count. |
| S-PL-7 | none | "None found." | 4.9 | "This election has no positions." |
| S-PL-8 | whole page | The set of row actions is hidden, not disabled (no Edit, Duplicate, Delete columns). | 3.4, 3.6 | Show Edit, Duplicate and Delete in the same place as on the running page, disabled with the reason, or record this as a deliberate exception in 3.6. Decision for the maintainer. |

**Needs live check:** that the locked page is chosen only when the election is locked; the status role announcement; 375 px layout.

Also applies: X-1, X-2, X-4.

---

## 4. Candidates (`civicrm/elections/candidate-list`)

Files: `ang/afsearchElectionCandidates.aff.html`, `.aff.php`, `managed/SavedSearch_ElectionCandidates.mgd.php`.

**Currently conforms:** heading and description present; Add Candidate is primary; filters are above the table and labelled; the candidate name links to the candidate page; Nominators is a count and not a list of people; no id, machine name or vote data (counts and statements only); sentence case labels; the Statement label matches 5.6; the default sort is by position running order then name.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| S-CA-1 | `html:1-16` | No breadcrumb. | 3.1, 3.7 | Show Manage Elections › election name › Candidates. |
| S-CA-2 | `html:3-4` | Action bar has "Back to Manage Elections" and "Add Candidate" side by side at the left; no "Back". | 3.2, 3.6, U7 | Back, Back to Manage Elections at the left; Add Candidate at the right end of the row. |
| S-CA-3 | `html:4` | Add button icon `crm-icon="fa-user-plus"`. | 9.3 (Add is `fa-plus`) | Use `fa-plus`. |
| S-CA-4 | `ang/afsearchElectionCandidates.aff.php:6` | Title `E::ts('Election Candidates')`. | 3.7, 3.8, 3.3 | Title "Candidates". |
| S-CA-5 | `mgd:83` and the columns `mgd:67-81` | No row actions at all. | 3.6, 3.8 (Candidates row: Edit Statement, Delete), 4.3 | Add "Edit Statement" (`civicrm/elections/candidate/statement?eid=[election_position_id.election_id]&enid=[id]`, route exists) and "Delete". There is **no route or form for deleting a nomination** in `xml/Menu/elections.xml`, so Delete is new work or a decision to change 3.8. Inline editing of the Statement and Statement link can stay. |
| S-CA-6 | `mgd:67-81` | Column order: Position, Candidate, Nominators, Eligible, Accepted, Withdrawn, Statement, Statement link. | 4.2 (first column is the name of the thing, then status, dates, counts, actions) | Candidate first, then the status columns (Eligible, Accepted, and the declined / withdrawn state), then Position, then Nominators, then the statement columns, then actions. Keep the position then name sort so rows stay grouped. |
| S-CA-7 | `mgd:79` | Column `has_rejected_nomination` labelled `E::ts('Withdrawn')`, editable. `Process::withdraw()` (`CRM/Elections/Helper/Process.php:199-203`) sets `has_rejected_nomination = 1` and `has_accepted_nomination = 0` for **both** a decline before accepting and a withdrawal after accepting. | 2.1, decision U3 (Declined before accepting, Withdrawn after) | The column cannot tell the two apart as built. A nominee who declined shows "Withdrawn: Yes". Either show one status column worded neutrally and remove "Withdrawn", or record which of the two it was (a data change, which needs an upgrade script) and label the values Declined and Withdrawn. Decision for the maintainer. |
| S-CA-8 | `mgd:68-75` (label "Candidate"), `mgd:57-58` (display), `html:8` | Column and filter "Candidate" list everyone nominated, including nominees who have not accepted. | 2.1 (nominee before acceptance, candidate after) | Label the column and filter by what is shown. Decision for the maintainer: "Nominee" for the name column (with the Accepted column saying who is standing), or keep "Candidate" and change 2.1. |
| S-CA-9 | `html:13` | `<button class="af-button btn btn-warning" type="reset" crm-icon="fa-undo">Reset</button>`. | 4.4, 9.2 | Replace by one quiet "Clear filters". |
| S-CA-10 | `mgd:63-64` | `'limit' => 100`, `'pager' => []` (no count). | 4.8 | Page size 50, show the count. |
| S-CA-11 | none | "None found." | 4.9 | "There are no candidates yet. Add a candidate, or open nominations so people can nominate." (the guide's own sentence). The same list is shown when filters match nothing; cover both. |
| S-CA-12 | `mgd:61` | Description: "Select a statement or link to edit it. Who is standing can be changed until voting starts; statements can be corrected until voting ends." | 4.1 | Add one sentence saying what the table is and what a row is ("Everyone nominated in this election, one row each."). Low priority. |

**Needs live check:** filter fields on Eligible and Accepted render as a Yes / No control with a label; the inline edit Save and Cancel (X-2); 375 px layout; the "Filters" summary is keyboard reachable.

Also applies: X-1, X-2, X-4.

---

## 5. Results (`civicrm/elections/result-list`)

Files: `ang/afsearchElectionResults.aff.html`, `.aff.php`, `managed/SavedSearch_ElectionResults.mgd.php`.

**Currently conforms:** heading and description present; no action buttons other than the back buttons (as 3.8 says); the table shows rank and candidate only and never how anyone voted (4.12, 1.5); the candidate name links to the candidate page; no id or machine name; sentence case labels; "Rank" matches 2.1.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| S-RE-1 | `html:1-6` | No breadcrumb. | 3.1, 3.7 | Show Manage Elections › election name › Results. |
| S-RE-2 | `html:3` | Single button `{{:: ts('Back to the election') }}` linking to the Election Overview. | 3.2, 3.8 ("Back to the election" is front end only; back end uses Back and Back to Manage Elections), 12 | Replace with two buttons, in this order: "Back" and "Back to Manage Elections". |
| S-RE-3 | `ang/afsearchElectionResults.aff.php:6` | Title `E::ts('Election Results')`. | 3.7, 3.8 | Title "Results". |
| S-RE-4 | `mgd:57-66` | First column is Position, then Rank, then Candidate. | 4.2 (first column is the name of the thing) | Decision for the maintainer: a row here is one ranked candidate, so Candidate would come first with Position second; or state that the position is the row group. Keep the position then rank sort. |
| S-RE-5 | `mgd:54-55` | `'limit' => 100`, `'pager' => []` (no count). | 4.8 | Page size 50 with count. |
| S-RE-6 | none | "None found." | 4.9 | "There are no results yet. Results appear after voting closes and the results are generated." |
| S-RE-7 | `mgd:51` | Description: "The ranking for each position after the votes were counted. The number of seats for a position says how many of the top ranks win." Seats are not shown in the table. | 4.1, 6.1 | Show the seat count next to the position, or word the description so a reader does not have to look elsewhere. Low priority; the description also does not say who can see the list. |

**Needs live check:** ties display (two rows with the same rank); 375 px layout; Back target.

Also applies: X-1, X-2, X-4.

---

## 6. Audit Log (`civicrm/elections/audit-log`)

Files: `ang/afsearchElectionAudit.aff.html`, `.aff.php`, `managed/SavedSearch_ElectionAudit.mgd.php`.

**Currently conforms:** heading present; the description says that entries cannot be changed or removed (4.1); newest first (`['id','DESC']`); page size 50 with count (`'limit' => 50`, `show_count`); no actions (as 3.8); "Who" shows "System" when there is no contact; no vote data (the log never records ballots, and the description says so); striped table; dates use the CiviCRM format.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| S-AU-1 | `html:1-6` | No breadcrumb. | 3.1, 3.7 | Show Manage Elections › election name › Audit Log. |
| S-AU-2 | `html:3` | Single button `{{:: ts('Back to the election') }}`. | 3.2, 3.8, 12 | Replace with "Back" then "Back to Manage Elections". |
| S-AU-3 | `ang/afsearchElectionAudit.aff.php:6` | Title `E::ts('Election Audit Log')`. | 3.7, 3.8, 2.1 | Title "Audit Log". |
| S-AU-4 | `mgd:51`; values written by `CRM/Elections/Helper/Guard.php:414`, `Results.php:365`, `Guard.php:159`, `Integrity.php:79`, `WriteIn.php:133` | "What" column is the raw `action` string: `election_changed`, `position_created`, `nomination_deleted`, `results_generated`, `votes_anonymised`, `vote_removed`, `write_ins_summary` and so on. | 4.2 (a code is replaced by its label), 1.2 | Show a label for each code, in sentence case ("Election changed", "Results generated", "Votes anonymised", "Vote removed", "Write-ins summarised"). The `ElectionAudit` field has no option list (`schema/ElectionAudit.entityType.php`, `action` is `varchar(64)`), so the labels need to be supplied by a pseudoconstant or a mapping; the stored value must not change because it is part of the hash chain. |
| S-AU-5 | `mgd:52` | "Details" column (`dataType` Text) shows the stored `details`, which `schema/ElectionAudit.entityType.php` describes as "JSON describing the change". | 4.2 (no JSON value), 1.2 | Show the details as readable text (field changed from X to Y, number of rows), not JSON. Still no vote data. |
| S-AU-6 | `mgd:43` | Description: "Entries cannot be changed or removed. Each carries a hash of the entry before it, which is checked on the election overview." | 2.1 (avoid hash; say fingerprint), 8.4 | Reword, for example "Entries cannot be changed or removed. Each one carries a fingerprint of the entry before it, which is checked on the Election Overview." |
| S-AU-7 | `mgd:49` | "When" column is a Timestamp with no time zone shown anywhere on the page. | 4.7, 8.7 | Show the time zone once above the table. Which zone the stored value is in (the code stores PHP `date()`, `CRM/Elections/Helper/Audit.php:38`) must be stated; unverified whether it is site time. |
| S-AU-8 | none | "None found." | 4.9 | "There are no audit log entries for this election yet." |

**Needs live check:** count text at the foot of the table; 375 px layout; whether a long `details` value wraps.

Also applies: X-1, X-2, X-4.

---

## Cross-cutting

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| S-X-1 | `css/electionforms.css` (no table rules; `@media` only at 784 and 1109), the SearchKit table template (`<table class="table table-striped">` directly, no wrapper) | There is no card layout for list rows at 375 px, no `table-responsive` wrapper and no extension CSS for any of the six tables. | 4.11, 9.6, 9.7 | Add CSS so each row becomes a card (label then value, row actions at the end) at 375 px on all six tables, with no horizontal scroll. Cannot be checked without a browser which layout is shown today. |
| S-X-2 | SearchKit templates 6.16.5 (`crmSearchDisplayTable.html`, `...Body.html`, `crmSearchDisplayEditable.html`, `toolbar.html`, `Pager.html`, `ResultCount.html`) | Supplied by SearchKit and not changeable in display settings: the table has no `<caption>` and its `<th>` have no `scope`; count text "N results" (not "Showing 1 to 50 of 132"); inline edit buttons are icons with hidden text "Save" and "Cancel"; toolbar buttons use `btn btn-primary`, not `af-button btn`; row link text has no row name. | 10, 4.8, 4.10, 9.2, 9.6 | Section 12 of the guide should record each as a SearchKit-supplied exception (4.10 already allows this for inline edit). For the caption and `scope`, add them with extension markup or JavaScript, or record as an exception. The 6.19 templates were not read; unverified. |
| S-X-3 | all six screens | No page says which time zone the times use. | 4.7, 8.7 | One line above each table that has dates (Manage Elections, Audit Log). Decide the source (site time zone or election time zone). |
| S-X-4 | `html` files (each repeats its own button markup with fixed targets) | The back buttons, the add button and the filters are written separately in six files with fixed URLs (`civicrm/elections/manage`, the Overview). The page cannot return to the page the person came from. | 3.2, U7, 3.6, 1.3 | One shared definition of the breadcrumb and the action bar (Back, Back to Manage Elections, Add) used by all six, with Back going to the previous page and falling back to the Election Overview. |
| S-X-5 | the five `.aff.php` titles | "Manage Elections", "Election Positions" (twice), "Election Candidates", "Election Results", "Election Audit Log". The Overview buttons say "Positions", "Candidates", "Results", "Audit Log". | 3.7, 3.8, 8.9 | Titles are the short names (covered by S-PO-3, S-PL-4, S-CA-4, S-RE-3, S-AU-3; listed here to make the choice once). |
| S-X-6 | outside this scope, seen while reading | `templates/CRM/Elections/Page/ElectionOverview.tpl:92-102` shows an "Audit log" mini table with the raw action (`{$entry.action|escape}`) and the fingerprint cut with "%1..."; `CRM/Elections/Form/ElectionVisibility.php:35,40,48` still says "Withdraw Election"; `CRM/Elections/Helper/ElectionStatus.php:293` still says "Candidates announced". | 4.2, 9.9, 2.4, 2.1, U4, 2.2 | For the reviewers of the Overview, the dialogs and the front end. The overview audit table is in a Smarty template, not a managed file, so it is not in this review. |

Counts for the summary:
- Manage Elections: 18 (S-MG-1 to S-MG-18)
- Positions, running: 13 (S-PO-1 to S-PO-13)
- Positions, locked: 8 (S-PL-1 to S-PL-8)
- Candidates: 12 (S-CA-1 to S-CA-12)
- Results: 7 (S-RE-1 to S-RE-7)
- Audit Log: 8 (S-AU-1 to S-AU-8)
- Cross-cutting: 6 (S-X-1 to S-X-6)
