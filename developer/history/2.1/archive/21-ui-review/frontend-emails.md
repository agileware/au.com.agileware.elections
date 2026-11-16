> **Historical.** Written during version 2.1; may be out of date. Current guidance: [User interface standards](../../../../standards/ui-standards.md).

Covers: version 2.1, a detail file of the screen review, 2026-11-01 to 2026-11-02. Copied from the project plan with names, paths and account names removed; screenshots under `plan/evidence/` are not kept (image not kept).

# Review: front end, error pages, emails and whole-extension text scan

Read-only review of `au.com.agileware.elections` (branch version-2.1, same code as 2.0.0) against `plan/20-ui-standards.md`. Paths are relative to the extension root. Everything below was read in the source; nothing was run. Items that need a browser are under "Needs live check" for each screen.

Rule numbers are sections of `20-ui-standards.md`. "3.8" is the label table, "5.6" the field labels, "6.x" front end, "7" messages, "7.1" emails, "10" accessibility, "11" translation.

Three things that affect many rows:

1. **Declined and Withdrawn cannot be told apart in the data.** `Process::withdraw()` (CRM/Elections/Helper/Process.php:199-204) always writes `has_rejected_nomination = 1` and `has_accepted_nomination = 0`, whether the person had accepted or not. U3 needs "Declined" (never accepted) and "Withdrawn" (had accepted). Nothing stored says which it was. A change is needed (a stored state, with an upgrade script) before the status words can be right. Rows that depend on this say "(needs U3 data)".
2. **No "Back to the election" link exists on any front end screen** (searched: the only match in the front end is the button "Back" on the candidate page). 3.6 and 3.8 require it on Nominate, Accept, Decline/Withdraw, Candidate, Edit Statement, Ballot and Results.
3. **Plan 13 and plan 20 disagree on the vote receipt.** Plan 13 (line 28) wants a receipt of "what was recorded". Plan 20, 6.2 says never what was chosen on a shared or printed page. The extension currently shows the choices (F-VW-8). Plan 20 is later and approved, so it is treated as the rule here.

---

## 1. Elections list (`civicrm/elections`)

Files: CRM/Elections/Page/Elections.php, templates/CRM/Elections/Page/Elections.tpl, CRM/Elections/Helper/ElectionStatus.php (`card()`).

**Currently conforms:** one card per election (`article`, `h2` name); stage badge shows a word, not only a colour; one date line and at most one `election-btn` per card; dates go through `CRM_Utils_Date::customFormat` and `crmDate`; names and descriptions escaped; empty state exists; the admin status uses the words Active and Inactive; list sorts running elections first.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| F-EL-1 | ElectionStatus.php:280 | button `'View Election results'` | 3.8 | `View Results` |
| F-EL-2 | ElectionStatus.php:283, 293, 300 | buttons `'View the Candidates'` (closed, advertise) and `'View the Nominations'` (nominations closed) | 3.8 | `View Election` (nothing to do). The card has no way to know if this person can nominate, accept or vote, so it cannot give the 3.8 next step without that data (see F-EL-4) |
| F-EL-3 | ElectionStatus.php:286-289 | button `'Vote in the Election'`; set to `''` when the person cannot vote | 3.8 | `Vote` when the person can vote; `View Election` when they cannot (no empty button) |
| F-EL-4 | ElectionStatus.php:297 | buttons `'View and submit Nominations'` / `'View the Nominations'` | 3.8 | `Nominate` for a person who can nominate; `Accept Nomination` for a person with a nomination waiting for an answer; otherwise `View Election`. `card()` needs the person's `canNominate` and pending nominations passed in (the election page already has them) |
| F-EL-5 | ElectionStatus.php:302 | `'button' => ''` for Not started (no button) | 3.8, 3.6 | `View Election` ("when there is nothing to do") |
| F-EL-6 | ElectionStatus.php:293 | stage badge `'Candidates announced'` | 2.2 | `Candidates advertised` (the BAO already uses this word, CRM/Elections/BAO/Election.php:73; build the badge from `getStage()` so the words come from one place) |
| F-EL-7 | ElectionStatus.php:275-303 (no branch) | Badge shows the date-based stage for an inactive election (for example "Voting open"), and a separate sentence says `Election is Inactive.` (Elections.tpl:37) | 2.2 (Inactive is a stage), U5 | When the election is inactive the badge says `Inactive`. Remove the separate sentence, or turn it into the same badge word |
| F-EL-8 | ElectionStatus.php:280-302 | lines `'Results published on %1'`, `'Election results will be available on %1'`, `'Voting will close on %1'`, `'Voting will open on %1'`, `'Nominations will close on %1'`, `'Nominations will open on %1'`, `'Candidates will be advertised from %1'`, `'The organisers are choosing the candidates'` | 2.2, 8.3 | Present tense, "opens on / closes on", sentence ends with a full stop: `Nominations open on %1.`, `Nominations close on %1.`, `Candidates advertised from %1.`, `Voting opens on %1.`, `Voting closes on %1.`, `Results will be published on %1.` (closed stage), `Results published on %1.` (results), `The organisers are choosing the candidates.` |
| F-EL-9 | Elections.tpl:17 | time zone link on every card: `<a class="election-card-timezone" target="_blank" ...>({$siteTimeZone|escape})</a>`; value is `'GMT' . date('P')` (CRM/Elections/Helper/TimeZone.php:5), for example `(GMT+11:00)` | 8.7, 6.3 | Show the time zone once above the cards: `Times are shown in %1.` with the zone name (for example Australia/Sydney). No per-card link, no new window |
| F-EL-10 | Elections.tpl:2 | admin button `Create Election`, class `button`, icon `fa-plus-circle` | 2.3, 3.6, 9.2, 9.3 | If kept on the front end: `Add Election`, icon `fa-plus`, class `election-btn`. Better (decision): remove admin controls from the front end list, since Manage Elections already has them (see F-EL-11) |
| F-EL-11 | Elections.tpl:36-54 | admin links in each card: `Deactivate`/`Activate`, `Positions`, `Edit`, `Delete`, class `action-item crm-hover-button`, order Activate, Positions, Edit, Delete; no Duplicate | 3.6, 4.3, 9.2 | Decision. Recommend removing. If kept: order Edit, Duplicate, Activate or Deactivate, Delete (Delete last, danger style), `election-btn election-btn-quiet`, and a visually hidden election name in each link text ("Edit %1") per 10 |
| F-EL-12 | Elections.tpl:37, 39 | `Election is Inactive.` / `Election is Active.` with `class="error"` / `class="success"` | 2.2, 7 | Not an error. See F-EL-7; if a sentence is kept: `This election is inactive.` / `This election is active.` in a `messages status` block |
| F-EL-13 | Elections.tpl:59 | `At least one Position must be defined before the Election can be active.` | 2.4, 7 | `This election needs at least one position before it can be activated. Add a position first.` (lower case nouns; says the next step) |
| F-EL-14 | Elections.tpl:7 | empty state `<h2 class="error-heading">{ts}No elections found.{/ts}</h2>` | 4.9 (applied to lists), 7 | A `messages status` sentence with the next step: `There are no elections to show yet.` plus, for administrators, `Add an election to get started.` Do not use a heading styled as an error |
| F-EL-15 | Elections.tpl:20-21 | `You voted in this election on %1.` then `You can vote again if you wish to change your vote.` | 2.1 (vote, "change your vote"), 6.2 | `You voted on %1. You can change your vote until %2.` (one sentence pair with the closing date) |

**Needs live check:** whether the card list has an `h1` when the shortcode blanks the title (`CRM_Utils_System::setTitle('')`, Elections.php:31) and the theme supplies none (heading order, 10); badge contrast in light and dark; card layout at 375 px; the 44 px target of the card button (CSS sets `min-height: 2.75rem` for `.election-btn`, css/electionforms.css:531).

---

## 2. Election page (`civicrm/elections/view`)

Files: CRM/Elections/Page/ViewElection.php, templates/CRM/Elections/Page/ViewElection.tpl, ElectionStatus.tpl, ViewElectionBlocks/*.tpl, WelcomeMessage.tpl, CRM/Elections/Helper/ElectionStatus.php.

**Currently conforms:** stepper is a `nav` with an `aria-label`, `aria-current="step"` on the current step and visually hidden state text; the "what you can do now" panel is a labelled `section` with a single action in every case; the person's own nominations list uses `election-btn` and `election-btn-quiet`; the page says who the person is acting as and offers another login (WelcomeMessage.tpl); positions, candidates and names are escaped; dates use `crmDate` and `customFormat`; no vote counts anywhere; photos have `alt=""` with the name beside them.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| F-VW-1 | ViewElection.tpl:2-6 | `Edit Election` and `Delete Election` buttons (class `button`, icons `fa-edit`, `fa-trash`) at the top of the front end page, shown only when the election is not running | 3.4, 3.6, 9.2, 9.3 | Remove `Delete Election` (Delete is not on a view page). Decision for `Edit Election`: remove (administrators have the Election Overview), or keep as one quiet `election-btn election-btn-quiet`, icon `fa-pencil`, shown disabled with the reason when the election is running instead of hidden |
| F-VW-2 | ElectionStatus.php:83, 107 | panel button `'Respond to Nomination'` | 3.8 | `Accept Nomination` (same words as the card) |
| F-VW-3 | ElectionStatus.php:117 | panel button `'Submit Nomination'` | 3.8, 2.3 (Submit is for edit forms) | `Nominate` |
| F-VW-4 | ElectionStatus.php:96 | panel button `'Vote Now'` | 3.8 | `Vote` |
| F-VW-5 | ElectionStatus.php:106 | `You have been nominated for %1. Please accept or withdraw before voting opens on %2.` | 2.1, U3, 8.5 | `You have been nominated for %1. Accept or decline before voting opens on %2.` |
| F-VW-6 | ElectionStatus.php:73 | `Voting has closed. The results will be published on %1.` | 2.2 | `Voting closed on %1. The results will be published on %2.` (pass the closing date) |
| F-VW-7 | ElectionStatus.php:87, 92, 96 | `Voting is open until %1. ...` (lines 87 and both variants at 96), `You can change your vote until %1.` (92). Line 82 already says "closes on" | 2.2, 8.3 | Use the "closes on" wording everywhere: `Voting closes on %1.` Keep "until" only if the guide is changed; as written, 2.2 lists the sentence variants and "until" is not one of them |
| F-VW-8 | ElectionStatus.php:117, 119 | `Nominations are open until %1. ...` | 2.2, 8.3 | `Nominations close on %1.` |
| F-VW-9 | ElectionStatus.php:122, 125 | `Nominations are closed. ...` (no date) | 2.2 | `Nominations closed on %1.` (pass the closing date) |
| F-VW-10 | ElectionStatus.php:90-94 | `$message . ' ' . E::ts('You can change your vote until %1.')` and `$message . ' ' . E::ts('Thank you for voting.')`, joined with a space | 11, 6.2 | One translated string per case. When the vote cannot be changed say so (6.2): `You voted on %1. Your vote cannot be changed.` Replace `Thank you for voting.` |
| F-VW-11 | ElectionStatus.tpl:49-64 and ElectionStatus.php:141-173 | section `Your vote` lists each position and the candidates the person chose in rank order, with `This is what was recorded. Only you can see it here.` Shown on the election page, which can be opened with a personal link (cid and cs in the address) | 1 (principle 5), 6.2, 7.1 | Remove the choices. Show only `Your vote was recorded on %1.` (the panel already says when). Decision needed because plan 13 asked for the receipt. If kept, it must not appear on a page opened through a personal link |
| F-VW-12 | ElectionStatus.php:208, 215, 224, 232 | own nominations: status `'Withdrawn'`; buttons `'Withdraw Nomination'` (shown while waiting for nominators, while waiting for an answer, and after accepting), `'Accept Nomination'`, `'Edit statement'` | 2.3, U3, 3.8 | Waiting and waiting-for-answer states: button `Decline`. Accepted state: `Withdraw`. `Edit Statement`. Status `Declined` or `Withdrawn` (needs U3 data) |
| F-VW-13 | ElectionStatus.php:34-37, 41 | stepper labels `'Nominations'`, `'Candidates'`, `'Voting'`, `'Results'`; hidden state words `current stage`, `finished`, `not started` | 2.2 ("the same words appear on the stage badge, the stepper") | Labels `Nominations open`, `Candidates advertised`, `Voting open`, `Results published`. Use `not yet started` for the hidden state word, so it is not confused with the stage called Not started. There is also no Inactive or Voting closed step; decide whether the stepper needs them |
| F-VW-14 | ElectionStatus.tpl:12 | `{ts}Times are shown in{/ts} <a target="_blank" ...>{$siteTimeZone|escape}</a>`; value `GMT+11:00` | 8.7, 11, 6.3 | One string: `Times are shown in %1.` with the zone name and no link to another site |
| F-VW-15 | ViewElectionBlocks/nominations.tpl:41-54 | `{ts}Candidate Status:{/ts}` then `{ts}Accepted{/ts}` / `{ts}Withdrawn{/ts}` / `{ts}Pending{/ts}` | 5.6 (no colon), 11, U3, 2.4 | Show the status as one badge word: `Accepted`, `Declined`, `Withdrawn`, `Waiting for an answer` (matches the person's own list). No colon label, no joined fragments |
| F-VW-16 | ViewElectionBlocks/nominations.tpl:48-51 | the decline comments (`rejection_comments`) of a nominee are printed under their name for every visitor | 1 (principle 5 spirit), 7 | Decision. The decline form never says who will see the comments (F-DC-3). Either do not show them, or tell the person on the Decline screen who sees them |
| F-VW-17 | ViewElectionBlocks/nominations.tpl:67 | button `{ts}Need Second{/ts}`, class `election-btn` on each nominee card | 2.1, 3.8, 6.3 | Not in 3.8 and not a verb phrase. Decision: add `Second Nomination` to 3.8. Make these `election-btn election-btn-quiet`, since a page has one primary |
| F-VW-18 | ViewElectionBlocks/nominations.tpl:5, 9, 19, 82, 84; positions.tpl:3 | `Existing Nominations`, `Positions are not added yet to be nominated.`, `There are no existing nominations` (no full stop), `There are no eligible candidates.`, `There are no eligible nominations.`, `Positions are not added yet.` | 7, 2.4 | Full stops, say why and the next step: `There are no positions yet.`, `There are no nominations yet.`, `There are no candidates yet.` Heading `Nominations` (drop "Existing") |
| F-VW-19 | ViewElectionBlocks/questions.tpl:9 | `{ts}You can choose:{/ts} ` followed by option labels joined with `, ` in the template | 11, 5.6 (no colon) | `The choices are %1.` with the list passed as one variable |
| F-VW-20 | WelcomeMessage.tpl:3-4 | `Welcome, %1.` in an `<h2>`; `Not %1, or want to do this for a different person? %2`; link `Login here.` | 3.8 (never "Click here"; a link says what it does), 7 (Log in), 10 (heading order) | Paragraph, not `h2`: `You are taking part as %1. Not you? %2` with link text `Log in`. (This partial is included by every front end form and page, so the fix applies to all of them) |
| F-VW-21 | 3.8 vs the page | 3.8 lists secondary buttons `View Candidates`, `View Nominations` on the election page; none exist (candidates and nominations are blocks further down the same page) | 3.8 | Decision: add in-page links, or remove them from 3.8 |

**Needs live check:** one `h1` on the page and the order h1, h2, h3 when the shortcode blanks the title (ViewElection.php:66); the panel text contrast and the `election-chip` states; stepper vertical layout at 375 px; whether the welcome `h2` is announced before the page heading; the personal-link address (checksum in the URL) being visible beside "Your vote".

---

## 3. Nominate (`civicrm/elections/nominations/create`)

Files: CRM/Elections/Form/CreateElectionNomination.php, templates/CRM/Elections/Form/CreateElectionNomination.tpl.

**Currently conforms:** the one button is `Nominate` (3.8) and is the primary; position is a select, nominee a search; no breadcrumb; success message is one past-tense sentence (`Nomination submitted.`); form checks eligibility and shows an error page for people who may not nominate.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| F-NM-1 | whole screen | no "Back to the election" link; the form has no way out except the browser | 3.6, 6.1, 3.8 | Add the quiet link `Back to the election` above the heading (link keeps `eid` and the personal-link query) |
| F-NM-2 | CreateElectionNomination.php:61; xml/Menu/elections.xml:45 | heading `Submit Nomination for %1`; menu title `Create Election Nomination` | 2.3 (Submit belongs to edit forms), 3.8 | `Nominate in %1`; menu title `Nominate` |
| F-NM-3 | CreateElectionNomination.php:134 | label `Nominated Position` | 5.6 | `Position` |
| F-NM-4 | CreateElectionNomination.php:135 | `$this->addEntityRef('contact', 'Nominee', ...)` label passed as a plain string, not through `E::ts` | 11, 5.6 | `E::ts('Nominee')` |
| F-NM-5 | CreateElectionNomination.php:147 | label `Why do you want to nominate this person for this position?` | 5.6 (Nominate: Reason for the nomination), 5.6 (label is not a question) | `Reason for the nomination` |
| F-NM-6 | CreateElectionNomination.php:134, 137 | placeholders `- Select Position -`, `- Select Nominee -` | 2.4, 5.6 | `Select a position`, `Select a person` |
| F-NM-7 | CreateElectionNomination.tpl:50 | `Start typing a name or email...` | 2.4, U11 | `Type a name or email` (no dots) |
| F-NM-8 | CreateElectionNomination.tpl:6 | `Use this form to nominate someone to a given position in this election by selecting the relevant position and then searching for your nominee by name or email. Only persons that can vote in this election may be nominated.` | 2.1 (persons), 8.2 | `Choose a position, then search for the person you want to nominate by name or email. Only people who can vote in this election can be nominated.` |
| F-NM-9 | CreateElectionNomination.php:186-191 | on a refused nomination the error message is set and the person is redirected to the election page | 5.4 (failure keeps the person on the form with input kept) | Stay on the form with the input kept; show the message there |

**Needs live check:** nested label (tpl:29 wraps `{$form.reason.label}` in a `<label>`; CiviCRM's `.label` normally already is a `<label>`, so check the markup for two nested labels); select2 and entity-ref keyboard use and accessible names; the required marker; error summary and focus on a validation error (5.3).

---

## 4. Accept Nomination (`civicrm/elections/nominations/accept`)

Files: CRM/Elections/Form/AcceptNomination.php, templates/CRM/Elections/Form/AcceptNomination.tpl.

**Currently conforms:** `Accept` is the primary button (3.8); the screen names the position and the election; statement and link fields with server-side link check; success sentence `Nomination accepted.`; works for personal links.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| F-AC-1 | AcceptNomination.php:106-112 | only one button, `Accept`. Decline is a different page, reached from "Withdraw Nomination" on the election page or `declineUrl` in the write-in emails | 3.8, 6.1 | Add a quiet `Decline` button (link to the Decline screen, or a decline section on this form; decision). Order: Accept, then Decline |
| F-AC-2 | whole screen | no "Back to the election" link | 3.6, 3.8 | Add the quiet link above the heading |
| F-AC-3 | AcceptNomination.tpl:9 | `You're eligible as a candidate for %1` (before accepting) | 2.1 (candidate only after acceptance), 8.4 | `You have been nominated for %1.` |
| F-AC-4 | AcceptNomination.tpl:3 | `To accept your candidacy, write a short statement for voters and press the "Accept" button. You can change your statement until voting starts.` | 6.1, 8.2 | `Write a short statement for voters, then select Accept. Select Decline if you do not want to stand. You can change your statement until voting starts.` |
| F-AC-5 | AcceptNomination.php:84, 85 | labels `Candidate statement`, `Link to a longer statement or CV` | 5.6 | `Statement`; `Statement link` with help `A link to a longer statement or CV.` |
| F-AC-6 | AcceptNomination.php:63; Process.php:152 | `You cannot accept a rejected nomination.` | 2.1, U3 | `You cannot accept a nomination that you declined or withdrew.` (and a next step) |
| F-AC-7 | AcceptNomination.php:58; Process.php:149 | `You already have accepted this nomination.` | 7 | `You have already accepted this nomination.` |
| F-AC-8 | AcceptNomination.php:132; also Process.php:155 | `Enter a web address starting with http:// or https://` (no full stop, field not named) | 5.3, 2.4 | `The statement link must start with http:// or https://.` |
| F-AC-9 | AcceptNomination.tpl:13, 23 | `<label>{ts}Election{/ts}</label>`, `<label>{ts}Position{/ts}</label>` with no field | 10 | Show as plain text or a description list, not labels without a control |

**Needs live check:** heading level and text of the page title (menu title `Accept Nomination` is used; no `setTitle`); focus after an error; button order and size on a phone.

---

## 5. Decline or Withdraw Nomination (`civicrm/elections/nominations/reject`)

Files: CRM/Elections/Form/RejectNomination.php, templates/CRM/Elections/Form/RejectNomination.tpl, xml/Menu/elections.xml:143.

**Currently conforms:** one question per screen with position and election named; `Comments` (optional) is the label 5.6 gives; the comment is optional; success sentence in the past tense.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| F-DC-1 | RejectNomination.php:103; xml:143; tpl:3 | button `Withdraw`, menu title `Withdraw Nomination`, help `To withdraw your candidacy, add any comments and press the "Withdraw" button.` for every case | 2.1, 3.8, U3 | The words follow the state. Before accepting: title `Decline Nomination`, button `Decline`, help `Add a comment if you like, then select Decline.` After accepting: title `Withdraw Nomination`, button `Withdraw`, help `Add a comment if you like, then select Withdraw.` |
| F-DC-2 | RejectNomination.php:128, 58, 73; Process.php:187, 190, 194 | `You have withdrawn the nomination.`, `You have withdrawn this nomination.`, `You cannot withdraw the nomination once voting is started.` | U3, 5.4 | `Nomination declined.` / `Nomination withdrawn.` by state; `You have already declined this nomination.`; `You cannot withdraw your nomination once voting has started.` |
| F-DC-3 | whole screen | the screen does not say it cannot be undone (Process.php:78: the person can never be nominated again for the position) or who sees the comments (see F-VW-16) | 5.5, 7 | One sentence under the heading: `You cannot be nominated for this position again.` and `Your comment is shown on the candidate page.` (if it still is) |
| F-DC-4 | RejectNomination.tpl:10 | `You're eligible as a candidate for %1` | 2.1 | `You have been nominated for %1.` (before accepting) or `You are a candidate for %1.` (after) |
| F-DC-5 | whole screen | no "Back to the election" link | 3.6, 3.8 | Add the quiet link above the heading |
| F-DC-6 | RejectNomination.tpl:15, 25 | `<label>Election</label>`, `<label>Position</label>` with no field | 10 | As F-AC-9 |

**Needs live check:** destructive-action wording seen next to the button; keyboard order.

---

## 6. Candidate page (`civicrm/elections/candidate`)

Files: CRM/Elections/Page/ElectionCandidate.php, templates/CRM/Elections/Page/ElectionCandidate.tpl, CandidateStatement.tpl.

**Currently conforms:** photo only when the election shows photos (`alt=""`); name in the election's name format; statement shown inline with a link that is only made for http or https; "Was nominated by" only when the election shows nominators; one `h2` for the name and `h3` per position.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| F-CP-1 | ElectionCandidate.tpl:70-72 | two `election-btn` (primary) buttons in a row: `Edit statement` and `Back` | 3.6 (one primary; leave-without-saving is quiet; front end uses "Back to the election" at top left) | Only `Edit Statement` is a button. `Back to the election` becomes a quiet link above the heading |
| F-CP-2 | ElectionCandidate.tpl:70 | `Edit statement` | 3.8, 2.4 | `Edit Statement` |
| F-CP-3 | ElectionCandidate.tpl:30, 32 | `Candidate Status:` / `Nomination Status:` then `Accepted`, `Withdrawn`, `Not Accepted`, `Pending` | 5.6, 11, 2.4, U3 | One status badge word: `Accepted`, `Declined`, `Withdrawn`, `Waiting for an answer`. No colon, no joined fragments. (`Not Accepted` after voting starts is shown only for accepted people by `removeNonRequiredPositions`, so the string can go) |
| F-CP-4 | ElectionCandidate.tpl:39 | decline comments printed under the status | see F-VW-16 | same decision |
| F-CP-5 | ElectionCandidate.php:48; xml:81 | page title is built as `$name . ' - ' . $election->name`; menu title is `ElectionCandidate` and is what the person sees when the page refuses (setTitle is reached only on success, :14-31) | 11, 1.2 | Title through one string: `%1 - %2` or `Candidate: %1`. Menu title `Candidate` |
| F-CP-6 | CandidateStatement.tpl:9, 15 | `<a ... target="_blank" rel="noopener noreferrer">{ts}Read the full statement{/ts}</a>` | 6.3 (links stay in the same window) | Remove `target="_blank"` |

**Needs live check:** `h1` is the page title, then `h2` name (heading order); photo layout at 375 px; the `Edit Statement` button alone at the end.

---

## 7. Edit Statement (`civicrm/elections/candidate/statement`)

Files: CRM/Elections/Form/EditStatement.php, templates/CRM/Elections/Form/EditStatement.tpl.

**Currently conforms:** one form for candidate and administrator, with the permission check; statement and link validation; success `Statement saved.` and return to the candidate page.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| F-ES-1 | EditStatement.php:69 | button `Save` | 2.3, U6 | `Submit` |
| F-ES-2 | whole screen | no way out (Back to the election for the candidate; Back and Back to Manage Elections for an administrator) | 3.5, 3.6, 3.7 | Candidate: quiet `Back to the election` above the heading. Administrator: breadcrumb `Manage Elections › election › Candidates › Edit Statement` and footer Submit, Back, Back to Manage Elections. Decision: same route serves both, so the page must choose the frame by who is using it |
| F-ES-3 | EditStatement.php:52; xml/Menu/elections.xml:89 | `Candidate Statement for %1`; menu title `Candidate Statement` | 3.8 | `Edit Statement for %1`; menu title `Edit Statement` |
| F-ES-4 | EditStatement.php:63, 64 | labels `Candidate statement`, `Link to a longer statement or CV` | 5.6 | `Statement`; `Statement link` with help `A link to a longer statement or CV.` |
| F-ES-5 | EditStatement.php:122 | `Enter a web address starting with http:// or https://` | 5.3, 2.4 | `The statement link must start with http:// or https://.` |
| F-ES-6 | EditStatement.php:43 | `throwAccessDeniedException($this, $e->getMessage())` shows the API's own message | 7 (technical detail goes to the log) | `This statement could not be found.` with a link back |
| F-ES-7 | EditStatement.tpl:9 | `<label>{ts}Position{/ts}</label>` with no field | 10 | plain text |

**Needs live check:** focus and error summary after a bad link.

---

## 8. Ballot (`civicrm/elections/vote`)

Files: CRM/Elections/Form/VoteCandidates.php, templates/CRM/Elections/Form/VoteCandidates.tpl, js/election-ballot.js, CRM/Elections/VotingMethod.php, api/v3/ElectionVote.php, CRM/Elections/Helper/WriteIn.php, Referendum.php.

**Currently conforms:** a review step exists (native `dialog`, labelled by its heading, focus moves to the title and returns to the button on close); ranks and ticks are checked as they are chosen with an `aria-live="polite"` status; questions are in `fieldset` and `legend`; every control is labelled with the candidate's name; candidate order is shuffled; write-in text uses "write-in" with a hyphen; the ballot works without JavaScript (the server checks again); after voting the person lands on the election page; Change Vote only when the election allows it; the election page and not the ballot shows what was recorded.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| F-BL-1 | VoteCandidates.php:82 | submit button `Vote` | 3.8, 6.2 | `Review Your Vote`. Decision for people without JavaScript: the form posts straight away, so the label would not be true. Either add a server-side review step, or have the button read `Submit Vote` when JavaScript is not running |
| F-BL-2 | VoteCandidates.php:100, 101, 105, 106 | review dialog texts `Check your vote`, `This is how your vote will be recorded. Nothing is saved until you confirm.`, `Go back and change`, `Confirm my vote` | 3.8, 2.3, 6.2 | Title `Review Your Vote`; `Nothing is saved until you select Submit Vote.`; buttons `Go Back and Change` and `Submit Vote` |
| F-BL-3 | VoteCandidates.php:104; election-ballot.js:139 | `No preference given for: %1.` ; review line built as `row.name + ' – ' + text.rank.replace(...)` | 2.1 (rank, not preference), 11 | `You have not ranked anyone for %1.` (plurality: `You have not chosen anyone for %1.`); build the row text from one translated string such as `%1, rank %2` |
| F-BL-4 | VoteCandidates.php:87; xml/Menu/elections.xml:61 | heading `You are Voting for %1`; menu title `Vote in an Election` | 2.4, 1 (heading names the screen) | `Ballot for %1`; menu title `Ballot` |
| F-BL-5 | whole screen | no "Back to the election" link | 3.6, 3.8 | Add the quiet link above the heading |
| F-BL-6 | VotingMethod.php:90 | `Votes are numbered from 1 onwards, where 1 is your highest preference, followed by 2 and so on.` | 2.1 (rank) | `Give each candidate you want a rank. Rank 1 is your first choice.` |
| F-BL-7 | VotingMethod.php:91 | `For positions where multiple seats are available, you only need to vote once, with your order of preferences being considered across all seats.` | 2.1, 8.2 | `For positions with more than one seat, you vote once. Your ranks count across all the seats.` |
| F-BL-8 | VotingMethod.php:137 | `Vote for %1. Please number the candidates from 1 to %2 in the order of your preferences.` | 8.5, 2.1 | `Rank the candidates for %1, from 1 to %2.` |
| F-BL-9 | VoteCandidates.php:410 | `For each position, you may or may not assign a number to candidates. But if you are assigning numbers for a position, you must assign number 1 to at least one candidate.` | 5.3 | `For %1, give your first choice the rank 1.` (the browser check already says "Give your first choice the rank 1.") |
| F-BL-10 | VoteCandidates.php:350, 381 | `Rank must be a valid positive integer.` | 5.3 | `Choose a rank from the list.` |
| F-BL-11 | VoteCandidates.php:390, 400 | `Rank must be different for each candidate.` | 5.3 | `Each candidate needs a different rank.` |
| F-BL-12 | VoteCandidates.tpl:45; VoteCandidates.php:248, 258 | write-in legend `Write in a candidate`; labels `Write-in %1`, `Rank of write-in %1` | 5.6 (write-in field is "Write someone in", the position's name is the group heading) | Group heading is the position name; field label `Write someone in`; rank label `Rank` (hidden position/number text for screen readers) |
| F-BL-13 | VoteCandidates.php:234, 250, 258 | placeholders `- Rank -`, `- Type at least 3 letters of a name -` | 2.4 | `Select a rank`, `Type at least 3 letters of a name` |
| F-BL-14 | VoteCandidates.tpl:55 | button `Add another write-in` | 3.8 (label must be in the table first), 2.3 | `Add Another Write-in`, and add it to 3.8 |
| F-BL-15 | VoteCandidates.tpl:46 | four-sentence help beginning `Know someone who is not listed?` | 8.2 (two sentences) | Two sentences, for example `Type a name to write someone in. They are told straight away and your vote for them counts only if they accept before voting closes.` |
| F-BL-16 | VoteCandidates.tpl:67 | `<em>{ts}If you have already voted, your existing votes will be replaced.{/ts}</em>` shown to every voter, including first-time voters and when the vote cannot be changed | 6.2 | Show only to a voter who has already voted and may change it: `Your new vote replaces your earlier vote.` Not italic |
| F-BL-17 | VoteCandidates.tpl:21 | each position's prompt is `<p><strong>`, candidate rows are not grouped | 10 (headings, groups) | Group each position in a `fieldset` with a `legend` (as the questions already do) or give it a heading |
| F-BL-18 | VoteCandidates.php:488, 490 | `You have successfully voted as %1 in the election.` / `You have successfully voted in the election.` | 5.4, 6.2 | `Vote recorded.` (the election page panel then says when) |
| F-BL-19 | VoteCandidates.php:467-472 | on an API error the message is shown and the person is redirected to a fresh ballot (`Civi::url(...)` redirect) | 6.2 (input kept when shown again with an error), 5.4 | Keep the person on the form with the choices they made |
| F-BL-20 | api/v3/ElectionVote.php:129, 133, 137, 145, 152, 158, 165, 261, 267 (and the other messages in `_civicrm_api3_election_vote_validate_ballot`); CRM/Elections/Helper/WriteIn.php:54-91; Referendum.php:32-42 | messages reach the screen through `$e->getMessage()` (VoteCandidates.php:467) and are plain strings, not through `ts()`, and written for developers: `Votes cannot be added for deleted election.`, `Votes cannot be added before voting period is started.`, `Votes cannot be added after voting period is ended.`, `Member is not allowed to vote in given election.`, `Member has already voted in given election.`, `Your ballot is already being saved. Please wait a moment and try again.`, `A ballot must rank at least one candidate.`, `Each vote must be for a candidate.`, `A candidate can only be ranked once.` | 11, 5.3, 2.1 (member), 7 | Put each through `E::ts` and rewrite for the voter, for example: `This election is not open for voting.`, `Voting has not opened yet.` (`Voting opens on %1.`), `Voting closed on %1.`, `You cannot vote in this election.`, `You have already voted and cannot change your vote.`, `Your vote is being saved. Wait a moment, then check the election page.`, `Rank at least one candidate, or answer a question.`, `Each rank must be for a candidate on the ballot.`, `Each candidate can be ranked once.`. WriteIn and Referendum strings listed in section 15 |
| F-BL-21 | VoteCandidates.php:49 | `You have already voted in this election.` set without `return`, so the ballot form is still built | 7 | (behaviour, not wording) Check that the refused ballot is not shown with fields |
| F-BL-22 | VoteCandidates.php:59 | `Voting is closed on %1` (no full stop, variant) | 2.2, 7 | `Voting closed on %1.` plus the link `Back to the election` |
| F-BL-23 | VoteCandidates.php:124, 295, 313 | `Candidates are not available in election for voting.`, `Positions are not added in election for voting.`, `Nominations are not added in election for voting.` | 7 | `There are no candidates to vote for yet.` and `There are no positions to vote on yet.` with `Back to the election` |

**Needs live check:** review dialog focus trap and Escape; dialog button style against 9.2 (it uses `.election-review-confirm` and `.election-review-back` rather than `election-btn` and `election-btn-quiet`, css/electionforms.css:915-950); select2 rank controls by keyboard; announcement of the rank status; error summary at the top (5.3); contrast of the review dialog in dark mode; 375 px layout of the candidate rows with photos.

---

## 9. Election summary and results (`civicrm/elections/summary`, and the results mode of `civicrm/elections/view`)

Files: templates/CRM/Elections/Page/ElectionSummary.tpl, ElectionResults.tpl, PositionResultTypeMessage.tpl, ResultsHeader.tpl, ResultsFooter.tpl, CRM/Elections/Page/ElectionSummary.php, js/election-celebrate.js.

**Currently conforms:** no vote counts and no information about who voted on either page (read in full: only winners, tied candidates, candidates and nominations); a tie lists every tied candidate with a `Tied` badge and says none is chosen over another; questions show `Passed`, `Not passed`, `No quorum`; the logo has an `alt` from the settings; the celebration is not shown when the device asks for reduced motion (a status banner is shown instead), ends by itself after about 6.5 seconds, stops on Escape or the `Skip the celebration` button, pauses when the tab is hidden, never blocks clicks (`pointer-events:none`, `aria-hidden` canvas).

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| F-RS-1 | ElectionSummary.tpl, ElectionResults.tpl | no "Back to the election" link on the summary page | 3.8 (Results front end: link Back to the election) | Add the quiet link above the heading on the summary page (the results mode of the election page is the election page itself) |
| F-RS-2 | ElectionResults.tpl:17 | `<center><a ...>{ts}View the Election summary{/ts}</a></center>` | 2.4 (mixed capitals), 3.8, 2.3 | `View Election Summary` as a link button, not in a `<center>` element. Decision: "summary" is not in 2.1; either add it or use `View Results` |
| F-RS-3 | ElectionSummary.php:31 | `Election summary is not available yet.` shown on the access-denied page | 7 | `The results are not published yet. They will be published on %1.` with `Back to the election` |
| F-RS-4 | ElectionSummary.tpl:10-11; ElectionResults (via ElectionStatus.tpl for the stepper) | `Results published on %1` followed by a link `(GMT+11:00)` that opens another site in a new window | 8.7, 6.3 | One line, time zone named once on the page |
| F-RS-5 | ViewElection.php:47 | page title `%1 - Result` | 2.1 (results) | `Results for %1` |
| F-RS-6 | ElectionSummary.tpl:31 | `{$candidate.$memberdisplayname|escape} {ts}(tied){/ts}` | 11 | `%1 (tied)` as one string |
| F-RS-7 | ElectionResults.tpl:34 | two strings `Winners for %1` / `Winner for %1` chosen with an `if` | 11 (plural form of `ts`) | one string with the plural form |
| F-RS-8 | PositionResultTypeMessage.tpl:22 | `The number of candidates remaining equals the number of position holders. These candidates are the winners.` | 2.1 (seats) | `The number of candidates equals the number of seats, so all of them are elected.` |
| F-RS-9 | ResultsDisplayFields.tpl:48 (help); js/election-celebrate.js (no stored "already shown") | help says the celebration is shown `each time it is opened for a few days`; nothing remembers that it ran | 9.8 ("runs once") | Decision: either record that this browser has seen it and show once, or change 9.8. The help text and 9.8 must agree |

**Needs live check:** the celebration banner and the Skip button reach by keyboard, and the Skip button's contrast in dark mode (`Canvas`/`CanvasText` colours); `h1` then `h2 Election Results`; winner photos at 375 px; logo `alt` when the field is empty.

---

## 10. Error pages (access denied, not found, closed)

Files: templates/CRM/Elections/Helper/Error.tpl, elections.php (`throwAccessDeniedException`, `throwAccessDeniedPage`, `throwNonMemberAccessDenied`, `retrieveElectionIdFromUrl`, `retrieveContactChecksumFromUrl`, `findElectionById`), CRM/Elections/Page/Base.php, Form/Base.php.

**Currently conforms:** no stack, code or machine name appears in the handled cases; the message is escaped; a button back to the list is offered by default; handled errors use `ts`.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| F-ER-1 | Error.tpl:1 | `<div class="messages status no-popup election-visibility-message">` | 7 (Error: `messages error`) | `messages error no-popup` |
| F-ER-2 | (searched CRM/, elections.php, templates/) | no HTTP status is set for refused pages (no 403, no 404) | 7 | Send 403 for access denied and 404 for not found on every CMS. Whether CiviCRM's own permission denial already does so for the menu-level check (`CRM_Elections_Permission::check`) is not verified; the extension's own pages need it |
| F-ER-3 | Error.tpl:5 | `<a href=... class="election-btn">{ts}{$return_button_text}{/ts}</a>` | 11 (a variable inside `ts` is not translatable), 6.3, 3.6 | Pass the already translated text and print it; make it a quiet link, not the primary button |
| F-ER-4 | elections.php:336; AcceptNomination.php:71; RejectNomination.php:66 | link texts `Return to Elections`, `View the election` | 3.8 (never Return), 3.8 | `Back to the election` when the election is known; `View Elections` when it is not |
| F-ER-5 | elections.php:363 (and used at AcceptNomination.php:41, RejectNomination.php:41, ElectionCandidate.php:28, Process.php:33, 243, 251, 264) | `You are not authorized to access this page.` (and `authorised` at elections.php:439, 446; `You are not authorized to perform this action.` at :319) | 7 (what they cannot do and how to get access), 8.4 | Logged-out visitor: `You need to be logged in to see this election. Log in, or use your personal link.` with a `Log in` link. Logged-in person without access: `You do not have access to this page. Ask the organisers if you think you should.` One spelling (the site otherwise uses "organisers") |
| F-ER-6 | elections.php:405, 413 | `Unauthorised. Invalid contact credentials.` / `Unauthorised. Missing valid contact credentials.`, passed with options `['']` so no return link is shown | 7, 2.1, 8.4 | `This personal link does not work. Log in, or ask the organisers for a new link.` with a next-step link |
| F-ER-7 | elections.php:377 | `Unable to view this Election, Election ID parameter missing.` | 2.4, 8.4 | `This page needs an election. Go to the list of elections and choose one.` with `View Elections` |
| F-ER-8 | elections.php:437-447 | `findElectionById` throws `CRM_Extension_Exception` with `You are not authorised to access this page.` when the election does not exist; there is no not-found page anywhere (searched for "not found" and "could not be found") | 7 (Not found says the election could not be found and links to the list) | Add a not-found page: `This election could not be found.` with `View Elections`. What a visitor sees today for an uncaught exception is not verified (needs live check) |
| F-ER-9 | elections.php:353 | `You may only see this page if you can take part in this election.` | 7 | `You are not in a group that can take part in this election. Ask the organisers if you think you should be.` |
| F-ER-10 | xml/Menu/elections.xml:37, 45, 61, 81, 135 | page title shown on an error page where the title is set only on success: `View Election`, `Create Election Nomination`, `Vote in an Election`, `ElectionCandidate`, `ElectionSummary` | 1.2, 3.8 | Titles that name the screen (`Election`, `Nominate`, `Ballot`, `Candidate`, `Election Summary`) |
| F-ER-11 | VoteCandidates.php:59 | closed ballot page `Voting is closed on %1` | 2.2, 7 | `Voting closed on %1.` with `Back to the election`; when results are out also `View Results` |

**Needs live check:** the HTTP status of each refused page on WordPress, Drupal and Joomla; what a visitor sees when `findElectionById` throws; whether the page has a heading and landmark (`main`); focus on arrival; the look of `messages error`.

---

## 11. Emails (message templates)

Files: managed/MessageTemplates.mgd.php, msg_tpl/*, CRM/Elections/Helper/Mailer.php, docs/reference_emails_and_jobs.md.

**Currently conforms:** titles follow `Election - <verb phrase>` with a plain hyphen, in the order nomination, write-in (`Election - Invite nominee`, `Invite written-in person`, `Remind written-in person`, `Close written-in invitation`); each email says which election and position; each says what happens if nothing is done; no email shows any choice made on a ballot, and none says which voter wrote a person in; dates go through `CRM_Utils_Date::customFormat` (Mailer.php:246-247); text and HTML versions match; names escaped; subjects escaped with `htmlall`.

| ID | Where | Found | Rule | Change needed |
|---|---|---|---|---|
| F-EM-1 | msg_tpl/elections_nominee_invited_subject.tpl | `You have been nominated for {$positionName} in {$electionName}` | 7.1 (subject says what the person is asked to do) | `Accept or decline your nomination for {$positionName} in {$electionName}` (7.1's own example starts with "Please", 8.5 says no "please" for normal steps; the two conflict, see Cross-cutting) |
| F-EM-2 | elections_nominee_invited_html.tpl:3; elections_nominee_invited_text.tpl:5 | `Please accept or withdraw the nomination before voting opens on {$votingOpens}.` | 2.1, U3, 8.5 | `Accept or decline your nomination before voting opens on {$votingOpens}.` |
| F-EM-3 | elections_nominee_invited_html.tpl:4; _text.tpl:7 | link text `Respond to the nomination`; text `Respond here: {$acceptUrl}` | 3.8, 7.1 (one button or link, same button words) | One link, text `Accept Nomination` (the page offers Accept and Decline, F-AC-1); text version `Accept or decline your nomination: {$acceptUrl}` |
| F-EM-4 | elections_write_in_invited_html.tpl:4; _text.tpl:7, 9; elections_write_in_reminder_html.tpl:4; _text.tpl:7, 9 | two links in each: `Accept the nomination` and `decline it`; text: `Accept or decline here: {$acceptUrl}` then `To decline: {$declineUrl}` | 7.1 (one button or link) | One link `Accept Nomination` to the Accept screen, which will have Decline on it. Drop `declineUrl` from the template variables (Mailer.php:109) once F-AC-1 is done |
| F-EM-5 | elections_write_in_reminder_subject.tpl | `Reminder: please answer by {$votingCloses} for {$positionName} in {$electionName}` | 8.5, 7.1 | `Reminder: accept or decline your nomination for {$positionName} by {$votingCloses}` |
| F-EM-6 | elections_write_in_closed_subject.tpl | `Voting has closed: the chance to accept for {$positionName} in {$electionName} has passed` | 2.2 | `Voting closed on {$votingCloses}: you can no longer accept your nomination for {$positionName}` |
| F-EM-7 | all four `_html.tpl` and `_text.tpl` | dates `{$votingOpens}`, `{$votingCloses}` carry no time zone (Mailer.php:246-247 uses `customFormat` only) | 7.1 (by when, with time zone), 8.7 | Add a variable for the zone name and print `Times are shown in %zone.` once per email |
| F-EM-8 | all four bodies | the body never says who is writing (organisation or site) | 7.1 | One line naming the organisation, for example with CiviCRM's `{domain.name}` token (check the token is available in the workflow context) |
| F-EM-9 | elections_write_in_closed_html.tpl:4; _text.tpl:7 | `You can still see the election at <a>{$electionName}</a>.` link text is the election name; text `You can still see the election here:` | 3.8 (never "here"), 7.1 | Link text `View Election` |
| F-EM-10 | docs/reference_emails_and_jobs.md:9, 18, 20, 30 | `with a link to accept or withdraw`; `Nomination email` setting; `non-logged in access`; `Results Start Date` | 7.1 (emails match screens), 5.6 | `accept or decline`; `Nomination email template`; `taking part without logging in`; `Results published on` |

**Needs live check:** rendered subject length and wrapping in a mail client; the HTML version in a client that blocks styles; that `{domain.name}` resolves in these templates; the time zone variable on sites where the election time zone differs from the site's.

---

## 12. Text scan tables (whole extension)

Scope: every `.php`, `.tpl`, `.html`, `.js`, `.xml` and `msg_tpl` file outside `tests/`, `docs/` images, `mixin/` and generated DAO/civix files. The rows below are user-visible matches. Rows outside the front end and emails are included because the scan covers the whole extension; other reviewers may list them under their screens too. Items that the guide explicitly allows are listed in 12.9.

### 12.1 withdraw, reject, decline, refuse

| ID | file:line | Current text | Rule | New text |
|---|---|---|---|---|
| T-1 | CRM/Elections/Form/ElectionVisibility.php:31, 37 | `Withdraw Election` (button, title) | U4, 2.1 | `Deactivate Election` (stronger confirmation when running; audit log entry kept) |
| T-2 | CRM/Elections/Form/ElectionVisibility.php:100; templates/CRM/Elections/Form/ElectionVisibility.tpl:5 | `Election has been withdrawn.`; `Withdrawing it hides every page of the election from everyone except administrators, and it can then be edited again. The withdrawal is written to the audit log.` | U4 | `Election deactivated.`; `Deactivating hides the election from everyone except administrators. You can then edit it again. The change is recorded in the audit log.` |
| T-3 | managed/SavedSearch_ManageElections.mgd.php:102 | row action `Withdraw` (icon `fa-ban`) | U4 | `Deactivate` (icon `fa-toggle-off` as for the normal case, line 101) |
| T-4 | CRM/Elections/Helper/Guard.php:284 | `This election is active and its nominations have started, so it cannot be switched off here. Use Withdraw on the Manage Elections page.` | U4, 5.3 | `This election is active and its nominations have started. To switch it off, use Deactivate on the Manage Elections page.` (and "switch off" per 12.5) |
| T-5 | CRM/Elections/BAO/Election.php:194 | `Nominees are accepting or withdrawing. Check the candidates before voting opens.` | U3 | `Nominees are accepting or declining. Check the candidates before voting opens.` |
| T-6 | templates/CRM/Elections/Form/CreateElection.tpl:154 | `asking them to accept or withdraw` | U3 | `asking them to accept or decline` |
| T-7 | CRM/Elections/Helper/Process.php:78 | `This person has withdrawn their nomination, so they cannot be nominated again for this position.` | U3 | `This person declined or withdrew their nomination, so they cannot be nominated for this position again.` (needs U3 data to name which) |
| T-8 | CRM/Elections/Helper/WriteIn.php:77 | `That person has declined or withdrawn for this position and cannot be written in.` (plain string, not `ts`) | 11 | through `E::ts`, text kept |
| T-9 | CRM/Elections/Form/AcceptNomination.php:63; Process.php:152 | `You cannot accept a rejected nomination.` | 2.1 | `You cannot accept a nomination that you declined or withdrew.` |
| T-10 | templates/CRM/Elections/Page/ElectionHelpText.tpl:9 | `... have the option of withdrawing them. Nominations that require one or persons to second them will also be marked as such – press the "Need second" button to add your nominations` | 2.1, 8.5 | file to be removed or rewritten, see T-56 |
| T-11 | xml/Menu/elections.xml:143 | menu title `Withdraw Nomination` | U3 | `Decline Nomination` (the button and title change with the state, F-DC-1) |
| T-12 | CRM/Elections/Helper/ElectionStatus.php:208; ViewElectionBlocks/nominations.tpl:48; ElectionCandidate.tpl:38 | status `Withdrawn` (also for a person who never accepted) | U3 | `Declined` or `Withdrawn` (needs U3 data) |

### 12.2 member, user, persons, contacts, role

| ID | file:line | Current text | Rule | New text |
|---|---|---|---|---|
| T-13 | api/v3/ElectionVote.php:97, 141 | `Multiple members are not yet supported.` (plain string) | 2.1 | `Only one voter can be given at a time.` through `E::ts` |
| T-14 | api/v3/ElectionVote.php:145, 158 | `Member is not allowed to vote in given election.`; `Member has already voted in given election.` | 2.1, 11 | `You cannot vote in this election.`; `You have already voted in this election.` through `E::ts` |
| T-15 | api/v3/ElectionNomination.php:31 | `Member is already nominated for the selected position.` | 2.1, 11 | `This person is already nominated for this position.` |
| T-16 | Civi/Elections/ActionProvider/IsUserAllowedToVoteAction.php:52; VoteAction.php:68 | `User has already voted.` | 2.1 | `This person has already voted.` |
| T-17 | Civi/Elections/CompilerPass.php:35 | `Check whether the user may vote` | 2.1 | `Check whether a person may vote` |
| T-18 | templates/CRM/Elections/Form/CreateElection.tpl:212 | `... when not-logged in. Requires the user to have a valid checksum URL.` | 2.1, 8.4 | `Lets people take part with their personal link, without logging in. Each person needs their own link.` |
| T-19 | templates/CRM/Elections/Form/CreateElectionNomination.tpl:6 | `Only persons that can vote in this election may be nominated.` | 2.1 | `Only people who can vote in this election can be nominated.` |
| T-20 | templates/CRM/Elections/Form/CreateElectionPosition.tpl:3, 21 | `Positions describe an elected role to which one more persons may be elected.`; `Number of persons who are to be elected to the position` | 2.1 (position, persons, seats) | `A position is what people stand for, for example President. Each position has one or more seats.`; `The number of winners for this position.` |
| T-21 | settings/elections.setting.php:35; templates/CRM/Elections/Form/CreateElection.tpl:251 | `Nicknames are only shown for contacts that have one.`; `... only used for contacts that have one.` | 2.1 | `... for people who have one.` |
| T-22 | templates/CRM/Elections/Form/AddCandidate.tpl:6; CreateElection.tpl:121 | `... if your role has the permission View All Contacts ... add it to your role` | 2.1 (role) | Allowed here: it means the CMS role and names the CiviCRM permission. Keep, but write `your user role` if "role" could be read as a position (decision, low) |

### 12.3 Visible to, Hidden, publish, enable, switched on or off

| ID | file:line | Current text | Rule | New text |
|---|---|---|---|---|
| T-23 | CRM/Elections/Page/ElectionOverview.php:31, 38; CRM/Elections/Form/CreateElection.php:124 | `Visible to people`, `Hidden from people`, `Visible to voters` | 2.1, 5.6, U5 | `Visible from`, `Visible until` (5.6 date labels) |
| T-24 | Civi/Api4/Service/Spec/Provider/ElectionSpecProvider.php:50, 61 | `Switched on and nominations have started, ...`; `Not running means switched off or nominations have not started.` | 2.1 | `Active and nominations have started, ...`; `Not running means inactive, or nominations have not started.` |
| T-25 | CRM/Elections/BAO/ElectionPosition.php:103; api/v3/ElectionPosition.php:30, 52 | `... or while the election is switched off.` | 2.1 | `... or while the election is inactive.` |

### 12.4 Save, Create, Update, Return, Click here, Login

| ID | file:line | Current text | Rule | New text |
|---|---|---|---|---|
| T-26 | CRM/Elections/Form/EditStatement.php:69; Form/ResultsDisplay.php:30 | button `Save` | 2.3, U6 | `Submit` |
| T-27 | CRM/Elections/Form/CreateElectionPosition.php:127; Form/AddCandidate.php:42 | buttons `Edit Position` / `Create`; `Add Candidate` | 2.3, U6 | `Submit` |
| T-28 | templates/CRM/Elections/Page/Elections.tpl:2; managed/SavedSearch_ManageElections.mgd.php:116; xml/Menu/elections.xml:13 | `Create Election` | 2.3 | `Add Election` (menu title `Add Election`) |
| T-29 | xml/Menu/elections.xml:31, 45 | `Create Election Position`, `Create Election Nomination` | 2.3 | `Add Position`, `Nominate` |
| T-30 | templates/CRM/Elections/Page/ElectionOverview.tpl:111 | `Edit settings` | 2.3, 3.8 | `Edit Election` |
| T-31 | elections.php:336 | `Return to Elections` | 3.8 | see F-ER-4 |
| T-32 | templates/CRM/Elections/Page/WelcomeMessage.tpl:4 | `Login here.` | 3.8, 7 | `Log in` |

### 12.5 please, WARNING, ellipsis, placeholders, "non-logged"

| ID | file:line | Current text | Rule | New text |
|---|---|---|---|---|
| T-33 | CRM/Elections/VotingMethod.php:137 | `Please number the candidates ...` | 8.5 | see F-BL-8 |
| T-34 | CRM/Elections/Helper/ElectionStatus.php:106; msg_tpl/elections_nominee_invited_html.tpl:3, _text.tpl:5; msg_tpl/elections_write_in_reminder_subject.tpl | `Please accept or withdraw ...`; `Reminder: please answer by ...` | 8.5 | see F-VW-5, F-EM-2, F-EM-5 |
| T-35 | CRM/Elections/Helper/WriteIn.php:167; Helper/Process.php:59; api/v3/ElectionVote.php:152; api/v3/Election.php:37 | `... Please wait a moment and try again.`; `Please correct the form errors.` | 8.5 | `Wait a moment and try again.`; `Fix the problems below.` (keep "please" only for an apology) |
| T-36 | templates/CRM/Elections/Form/DeleteConfirmMessage.tpl:7-8 | `WARNING: This action cannot be undone.` and `Click 'Delete' if you want to continue.` | 5.5, 2.4, 3.8 | a sentence saying what is lost, for example `This deletes the election and its positions. Nominations and votes cannot be recovered.`; button text carries the action (`Delete Election`) |
| T-37 | templates/CRM/Elections/Page/ElectionOverview.tpl:102 | `Audit log fingerprint: %1...` (value cut to 16 characters with `truncate:16:''` and a literal `...`) | 2.4, 9.9, U11 | show the full fingerprint in a fixed-width wrapping box with a `Copy` button |
| T-38 | templates/CRM/Elections/Form/CreateElectionNomination.tpl:50 | `Start typing a name or email...` | 2.4 | `Type a name or email` |
| T-39 | CRM/Elections/Form/CreateElectionNomination.php:134, 137; Form/AddCandidate.php:35; Form/VoteCandidates.php:234, 250, 258; Form/CreateElection.php:169, 175, 184, 204 | `- Select Position -`, `- Select Nominee -`, `- Select person -`, `- Rank -`, `- Type at least 3 letters of a name -`, `- Select -`, `- Select -`, `- Select Groups -`, `- Same as voting groups -` (CreateElection.php:169, 175, 184 are plain strings, not through `ts`) | 2.4, 5.6, 11 | `Select a position`, `Select a person`, `Select a person`, `Select a rank`, `Type at least 3 letters of a name`, `Select a value`..., `Select groups`, `Same as voting groups`, all through `E::ts` (CreateElection placeholders for the owner of that screen to word) |
| T-40 | CRM/Elections/Form/CreateElection.php:178; templates/CRM/Elections/Form/CreateElection.tpl:212 | `Allow non-logged in access` | 5.6, 2.1 | `Allow people to take part without logging in` |
| T-41 | managed/SavedSearch_ElectionAudit.mgd.php:43 | `Each carries a hash of the entry before it, which is checked on the election overview.` | 2.1 (hash) | `Each entry carries a fingerprint of the entry before it. The Election Overview checks them.` |

### 12.6 Hand-formatted dates, time zone label, joined or untranslated text

| ID | file:line | Current | Rule | New |
|---|---|---|---|---|
| T-42 | CRM/Elections/Helper/TimeZone.php:5 | `return 'GMT' . date('P');` (shown as `(GMT+11:00)` on the list, stepper, results and summary) | 8.7 | name of the site or election time zone (for example Australia/Sydney), shown once per page |
| T-43 | js/election-form.js:68, 76-77 | `date.toLocaleString([], {dateStyle: 'medium', timeStyle: 'short'})` and `' → '` between two dates (timeline preview on the election form) | 8.7 (never hand-format a date in JavaScript) | use the CiviCRM date and time format passed to the page from PHP (`CRM_Utils_Date::customFormat`); this is a back end screen, listed because the scan covers all JavaScript |
| T-44 | elections.php:172, 176, 191-192 | shortcode builder labels `Elections`, `Election Info`, `Display visible elections`, `Display non visible elections` are plain strings | 11, 2.1 (visible), 2.4 | `E::ts('Elections')`, `E::ts('Election Info')`, `Show active elections`, `Show inactive elections` |
| T-45 | CRM/Elections/Page/ElectionCandidate.php:48 | `$this->electionNomination['member_nominee.display_name'] . ' - ' . $this->election->name` | 11 | one `E::ts('%1 - %2', ...)` |
| T-46 | CRM/Elections/Tokens.php:19 | `$label . ' :: ' . E::ts('Elections')` | 11 | one string `%1 :: Elections` through `ts` |
| T-47 | CRM/Elections/Tokens.php:31-34 | token labels `Election Name`, `Election Position`, `Nominator Name`, `Nominee Name` | 2.4 (labels sentence case) | `Election name`, `Position`, `Nominator name`, `Nominee name` ("Election position" is also the old word for position) |
| T-48 | templates/CRM/Elections/Helper/Error.tpl:5 | `{ts}{$return_button_text}{/ts}` | 11 | see F-ER-3 |
| T-49 | CRM/Elections/Helper/WriteIn.php:54-91; CRM/Elections/Helper/Referendum.php:32-42; api/v3/ElectionVote.php:97-165, 261-290 (searched with a pattern for string literals passed to `Exception(`, `civicrm_api3_create_error(` and `'error' =>`); api/v3/ElectionNomination.php:31-48; api/v3/ElectionPosition.php:33; api/v3/ElectionNominationSeconder.php:31-50; api/v3/Election.php:37, 47 | More than 30 messages written as plain strings, not through `ts()`, and written for developers (for example `Election cannot be deleted once it has been started`, `Nomination cannot be added after nomination period is ended.`, `Write-ins and choices must be lists.`, `That option does not belong to the question.`) | 11, 7 | All through `E::ts`, written for the person. Those that can reach a voter or nominee (WriteIn, Referendum, ElectionVote, ElectionNomination) first; the rest are administrator or API messages. Full list by grep in the file column |
| T-50 | CRM/Elections/Helper/ElectionStatus.php:92, 94 | message built as `$message . ' ' . E::ts(...)` | 11 | see F-VW-10 |
| T-51 | templates/CRM/Elections/Page/ElectionStatus.tpl:12; ViewElectionBlocks/nominations.tpl:41-54; ViewElectionBlocks/questions.tpl:9 | label fragment `ts` + variable or second `ts` | 11 | see F-VW-14, F-VW-15, F-VW-19 |

### 12.7 Capitalisation and other word rules found by the scan

| ID | file:line | Current text | Rule | New text |
|---|---|---|---|---|
| T-52 | templates/CRM/Elections/Page/ElectionHelpText.tpl:8, 12, 15; ElectionResults.tpl:17 | `This Election is currently in the Nominations phase.`, `The Nominations period for this Election is now closed.`, `View the Election summary` | 2.4 | lower case nouns in running text; see F-RS-2 |
| T-53 | CRM/Elections/Page/ElectionOverview.php:73 | `Declined` (write-in status) | 2.2 | allowed word; shown here to confirm U3 vocabulary is already used for write-ins |
| T-54 | CRM/Elections/Form/CreateElection.php:25; xml/Menu/elections.xml:75, 81, 135 | `Edit Election` (title), `ElectionVisibility`, `ElectionCandidate`, `ElectionSummary` | 1.2 | code names as titles; see F-ER-10; `ElectionVisibility` → `Activate Election` / `Deactivate Election` set per state |

### 12.8 Dead or only partly reachable templates (listed so they are not fixed twice)

| ID | file | Finding | Change needed |
|---|---|---|---|
| T-55 | templates/CRM/Elections/Page/ElectionNotificationMessages.tpl | not included by any PHP or template (searched `include file=` and `getTemplateFileName`); contains `Nominations will open on %1`, `Voting will close on %1`, the time zone link in every line, `Voting closed.`, etc. | Delete the file |
| T-56 | templates/CRM/Elections/Page/ElectionHelpText.tpl | included only by ElectionResults.tpl, which is shown only after results are generated, so only the last two blocks (`Voting has closed for this election. The candidates are shown below.` and `The votes have been counted for this election. The winners are shown below.`) can show | Keep only those two lines; delete the rest (they hold the banned words in T-10 and T-52) |

### 12.9 Matches the guide allows (not findings)

- `CreateElectionPosition.tpl:34` `Shown instead of No, for example Against or Reject.` is the exact example in 5.2.
- `Elections Settings`, option `Contact Image` (settings/elections.setting.php:23): refers to a CiviCRM contact record, allowed by 2.1.
- `Declined`, `Withdrawn` words in the code comments and DAO/field names (`has_rejected_nomination`) are internal names, not shown.
- Date handling in PHP that is not shown to a person: `Dates.php:111`, `DuplicateElection.php:36`, `Mailer.php:122-178`, `Elections.php:40`, `Process.php:286` (database values and logic).
- No "Click here", "Update", "Done" or "OK" buttons were found; no `strtotime` or `date()` is used to display a date to a person in PHP or templates.

---

## 13. Cross-cutting

1. **"Back to the election" is missing everywhere** on the front end (F-NM-1, F-AC-2, F-DC-5, F-CP-1, F-ES-2, F-BL-5, F-RS-1). Build it once as a shared partial (quiet `election-btn`, keeps `eid` and the personal-link query) and place it above the heading on every sub page.
2. **The Accept and Decline screens need to become a pair.** F-AC-1, F-DC-1, F-EM-3, F-EM-4 and F-VW-12 all depend on one decision: how Decline is reached from the Accept screen. The four emails and the election page then link to one screen.
3. **U3 needs data.** Declined and Withdrawn are one state in the database (see the top). A stored state (or an event in the audit log that can be read back) plus an upgrade script is needed before T-12, F-VW-12, F-VW-15 and F-CP-3 can be exact.
4. **Time zone and dates.** One helper that returns the zone name and one "Times are shown in %1." line per page replaces the per-card and per-row zone links (F-EL-9, F-VW-14, F-RS-4, F-EM-7, T-42). Date sentences follow 2.2 everywhere: "opens on / closes on / closed on". The words "until", "will close", "has closed" appear in at least nine places (F-EL-8, F-VW-6 to F-VW-9, F-EM-6).
5. **External links open in a new window** in four places (tz links F-EL-9, F-VW-14, F-RS-4; statement link F-CP-6); 6.3 says links stay in the same window. Statement links to another site are the case worth confirming as a decision.
6. **Error text reaches voters unchanged.** The ballot shows `$e->getMessage()` from API code that is not translated and uses developer words (F-BL-20, T-49). Same for nominate (CreateElectionNomination.php:187) and accept, decline and statement pages. One rule: any message that can reach a person is written through `E::ts` in plain words; technical detail goes to the log (7).
7. **Static `<label>` with no control** appears on Accept, Decline and Edit Statement (F-AC-9, F-DC-6, F-ES-7). Use text, not labels, for read-only values (10).
8. **Primary button count.** Candidate page has two (F-CP-1), and every nominee card on the election page has a primary `Need Second` or `Nominate` button (F-VW-17); 6.3 allows one primary per page or card. Make the repeated ones quiet.
9. **The guide conflicts with itself in three places** that the review had to choose between: 7.1 example subject begins "Please accept or decline ..." while 8.5 says no "please" for normal steps; plan 13 receipt vs 6.2 (top of this file); 9.8 "runs once" vs the celebration help text that says "each time it is opened" (F-RS-9). The choices made above are marked as decisions; each needs a one-line ruling.
10. **Admin tools on front end pages** (Edit Election, Delete Election, Create Election, Activate, Positions, Edit, Delete in the list cards; F-EL-10, F-EL-11, F-VW-1) use the CiviCRM button family on a front end page, which 9.2 and U9 forbid, and duplicate what the back end has. Recommended decision: remove them from the front end.
11. **Reduced motion is handled correctly** for the celebration (checked in js/election-celebrate.js); CSS has `focus-visible` and 44 px minimum height for `election-btn`. No finding.

---

## Count of findings per screen

| Screen | Findings |
|---|---|
| Elections list | 15 (F-EL-1 to F-EL-15) |
| Election page | 21 (F-VW-1 to F-VW-21) |
| Nominate | 9 (F-NM-1 to F-NM-9) |
| Accept Nomination | 9 (F-AC-1 to F-AC-9) |
| Decline or Withdraw Nomination | 6 (F-DC-1 to F-DC-6) |
| Candidate page | 6 (F-CP-1 to F-CP-6) |
| Edit Statement | 7 (F-ES-1 to F-ES-7) |
| Ballot | 23 (F-BL-1 to F-BL-23) |
| Election summary and results | 9 (F-RS-1 to F-RS-9) |
| Error pages | 11 (F-ER-1 to F-ER-11) |
| Emails | 10 (F-EM-1 to F-EM-10) |
| Text scan (whole extension) | 56 rows (T-1 to T-56; many repeat a screen finding) |
