# Decisions: voting and elections

What the extension does with candidates, statements, groups, write-ins, referendum questions, locking, results and dates, and why. The index of all decisions is [`README.md`](README.md); decisions about screens and words are in [`interface-and-wording.md`](interface-and-wording.md); decisions about platforms, tests and releases are in [`platform-testing-and-release.md`](platform-testing-and-release.md).

**IDs.** The plan's IDs are kept so the archived plan in [`../history/`](../history/README.md) still matches. Two review rounds each numbered their points `R1`, `R2` and so on. They are prefixed here: `R2.0-n` is the review of 31 October 2026 (version 2.0; the plan also logged these as `D20` to `D29`, which stay the main ID) and `R2.1-n` is the screen review of 1 November 2026 (version 2.1). Small decisions the maintainer made on 2 November are `SD1` to `SD8`, and the STV pull request questions are `S1` to `S6`.

Each entry gives the decision, why, the date, and where it shows. Dates of the 2.0 build before 27 October 2026 are given as "October 2026".

## Candidates, statements and display (version 2.0)

### D1 – Candidate statement reuses the `comments` field and adds a link
- **Decision.** A candidate's statement is the nomination's existing `comments` field, plus an optional `statement_url` (a link to a longer statement or CV).
- **Why.** No new table, and existing statements keep working.
- **Date.** October 2026 (applied on the default).
- **In the code.** `civicrm_election_nomination.statement_url` (upgrade step `20000`); the Edit Statement form and candidate page. The data field `comments` is shown as **Statement**.

### D2 – Statements are plain text
- **Decision.** Plain text with line breaks kept, always escaped. Rich text only if someone asks.
- **Why.** Script injection through names and text was the main finding of the security review; plain text removes the risk.
- **Date.** October 2026.
- **In the code.** Output escaping on every template; see [`../architecture/security.md`](../architecture/security.md).

### D3 – Default for showing statements
- **Decision.** New elections default to **Read statement** (a link that opens the statement); upgraded elections default to **Not shown**. The third choice is **In full**.
- **Why.** An upgraded election must look as it did before.
- **Date.** October 2026.
- **In the code.** `show_statements` (`none`, `link`, `inline`), database default `none` (`20000`), form default `link` in `CRM_Elections_Form_CreateElection`.

### D4 – Hiding photos hides them everywhere
- **Decision.** `show_photos` applies to the ballot, the candidate page, nominations and results.
- **Why.** A setting that hides photos in only some places would surprise organisers.
- **Date.** October 2026.
- **In the code.** `civicrm_election.show_photos`.

### D5 – Candidate name format
- **Decision.** Three formats (display name, First "Nick" Last, Nick Last), a site-wide default in Elections Settings and a per-election override.
- **Why.** Organisations differ on nicknames; formats are built on the server and escaped.
- **Date.** October 2026.
- **In the code.** `civicrm_election.name_format`; `CRM_Elections_Helper_Candidate`; the examples use Robert "Rob" Irwin (`R2.0-7`/`D26`).

### D7 – Admin-managed nominations
- **Decision.** An election can turn online nominating off, so that organisers add the candidates themselves.
- **Why.** Some organisations choose their candidates (follow-up to GitHub issue 28).
- **Date.** 2026-10-27.
- **In the code.** Election setting **Allow people to nominate online** (upgrade `20080`, on for every existing election); **Add Candidate** on the back end.

### D17 – Who can see published results
- **Decision.** Keep the current behaviour: anyone logged in with `view Elections`, or anyone with a valid personal link when the election allows access without logging in, with no group check. Results pages show winners and how each was decided, never vote counts, and ties are shown as ties. No new setting.
- **Why.** Confirmed by the maintainer; changing it would break existing sites.
- **Date.** 2026-10-27; asked again and reconfirmed by the maintainer on 2026-11-15 (option "keep as is", with the rule stated in the user guide: [Who can see the results](../../docs/roles_and_permissions.md#who-can-see-the-results)). Open alternatives, if an organisation asks: a per-election setting (everyone who can open the election, only people eligible to take part, organisers only; needs a column and an upgrade step), or results that outlast the election's visibility window.
- **In the code.** `CRM_Elections_Permission::check()`; `CRM_Elections_Page_ElectionSummary`; results page rules in [`../standards/ui-standards.md`](../standards/ui-standards.md#6-front-end).

## Groups, write-ins, questions (version 2.0)

### D8 – Write-ins
- **Decision.** A voter picks a person from a group; there is no free text. The person written in is notified immediately (once) and must **accept before voting closes** for the votes for them to count. Once they accept they are a candidate like any other and appear on the ballot for later voters. Votes for a person who has not accepted at the close are dropped and the ballot moves to the voter's next choice. There is no review stage and no cut-off for new write-ins.
- **Why.** Free text cannot be notified or checked; accepting first keeps the ballot honest while keeping results on the results date.
- **Date.** 2026-10-27.
- **Rules applied on the default.**
  - Who can be written in: people in the election's voting groups, or the candidate group when there is one. Someone who is already a candidate for the position, or withdrew from it, cannot be written in for it; someone who declined a write-in cannot be written in again.
  - **Write-ins per seat** is a per-position number from 0 to 10; 0 (off) for every existing position. A position with S seats shows up to S times W places; the ballot starts with one and **Add Another Write-in** adds more. The limit is enforced on the server, in the form, the API and inline edits.
  - One record for each person and position: the first write-in creates a pending record and sends the invitation; later write-ins add votes and send nothing. Accepting creates a normal nomination marked as a write-in and moves the votes onto it. Accepting skips nominating and seconding by design and is recorded in the audit log.
  - Voters are told that a write-in counts only if the person accepts before voting closes.
  - The overview warns when write-ins are on and **Allow people to change their vote** is off, because people who voted earlier cannot see a new candidate.
  - The person search shows names only, after a few typed letters, with a rate limit.
  - One invitation email and one reminder before voting closes.
- **In the code.** `civicrm_election_position.write_ins_per_seat`, `is_write_in` and the `write_in_*` dates on nominations (`20060`, `20090`); `CRM_Elections_Helper_WriteIn`; the four write-in and nominee email templates.

### D9 – Separate groups for nominating and standing
- **Decision.** Two optional groups beside the voting groups: `nomination_groups` (who may nominate and second) and `candidate_groups` (who may be nominated or written in). Empty means the voting groups.
- **Why.** Existing elections must behave as before, and organisations often let more people nominate than vote.
- **Date.** 2026-10-27.
- **Rules applied.** Someone who can nominate but not vote sees the election and nomination screens and is refused the ballot, and the other way round. A seconder must be in the nominating group; a nominee must be in the candidate group when they accept; the write-in picker offers only the candidate group. Group membership is checked at the moment of each action, smart groups included; votes already cast stay whatever happens to the voter's groups later. Nominating yourself is allowed.
- **In the code.** `civicrm_election.nomination_groups`, `candidate_groups` (upgrade `20040`); `CRM_Elections_Helper_Eligibility`; the overview's "Who can take part".

### D10 – Referendum questions
- **Decision.** Options are stored in a new `election_option` table, never as nominations, so a question can never be treated as a candidate.
- **Why.** Keeps questions out of the candidate rules.
- **Date.** 2026-10-27.
- **Rules applied.**
  - Pass threshold per question: simple majority (more than half; a tie fails), two thirds, three quarters, or any percentage ("at least that share", allowing for the two-decimal rounding of 66.67).
  - Percentage basis: Yes divided by Yes plus No. Abstain is an explicit option; it counts towards quorum, not towards the percentage.
  - Quorum is optional (off by default): a minimum share of the people allowed to vote who took part, counted from the voting groups when results are generated. Not met gives "No quorum".
  - The wording of the options can be renamed (For and Against, Approve and Reject) and resolution text added. One election can hold both positions and questions.
  - Results show **Passed**, **Not passed** or **No quorum** and the resolution text, never counts (D17). One ballot per person, counted once, anonymised and fingerprinted like the rest; the question and its options freeze once ballots exist.
- **In the code.** `ElectionOption` and `ElectionOptionVote` tables (`20070`); `position_type`, `pass_threshold`, `quorum_percent`, `resolution_text` on positions; `CRM_Elections_Helper_Referendum`; see [`../architecture/voting-methods.md`](../architecture/voting-methods.md).

### D11 – Emails about nominations
- **Decision.** Editable message templates, and a per-election setting **Email people about their nominations** (on for new elections, off for every existing one). The first email goes to a nominee who has enough nominations to stand, with an accept or decline link. Emails honour Do not email, no address and on hold, and an email never blocks the action. Voters-opening and reminder mailings are left to CiviMail.
- **Why.** A stretch goal that proved needed by write-ins (D8).
- **Date.** Started 2026-10-27.
- **In the code.** `civicrm_election.send_emails` (`20050`); `CRM_Elections_Helper_Mailer`; `managed/MessageTemplates.mgd.php`; [`../standards/wording.md`](../standards/wording.md#writing-emails).

## Locking, dates and the results page (version 2.0, review of 31 October)

### D21 (R2.0-2) – No changing buttons on an active election
- **Decision.** An election is **Locked** when it is active and its nominations have started. A locked election shows no Edit, Delete or Deactivate on the Manage Elections row. An administrator who must hide a running election uses **Deactivate** with a confirmation that asks them to type the election's name (called Withdraw in the code), which is audited. The server refuses to switch off a locked election any other way, so the Active column cannot be used to get round it. "Active" stays the name of the switch; "Locked" names the state.
- **Why.** Deactivating a running election would unlock it for editing, a back door around the lock.
- **Date.** 2026-10-31.
- **In the code.** `is_locked` and `stage` computed fields (`Civi/Api4/Service/Spec/Provider/ElectionSpecProvider.php`); `CRM_Elections_Helper_Guard::$withdrawing`; `CRM_Elections_Form_ElectionVisibility`.

### D22 (R2.0-3) – Read-only positions page for an active election
- **Decision.** A separate read-only positions page, chosen by the `civicrm/elections/positions` route, with a clear message on every refused write. The message is the same for drag, inline edit, Duplicate and Delete.
- **Why.** SearchKit settings are static, so one page cannot turn drag and edit off by election state. **Root cause of the generic error:** CiviCRM 6.19 hides an exception's message from AJAX callers unless the exception says it is safe to show; every refusal now throws `CRM_Elections_Exception`.
- **Date.** 2026-10-31.
- **In the code.** `civicrm/elections/position-list-active` (`ang/afsearchElectionPositionsLocked.aff.php`); `CRM_Elections_Exception`.

### D24 (R2.0-5) – Nomination email template chosen per election
- **Decision.** A per-election drop-down of the site's message templates (default "Election - Invite nominee"), with a link to Message Templates for people who may edit them. Every template the extension ships is titled "Election - " plus a verb phrase. The write-in emails stay on fixed templates.
- **Why.** Organisations want their own wording without code; an empty choice keeps the default so existing sites do not change.
- **Date.** 2026-10-31.
- **In the code.** `civicrm_election.nomination_message_template_id` (upgrade `20100`, foreign key with `ON DELETE SET NULL`); the retitling in the same step.

### D25 (R2.0-6) – Date calculator
- **Decision.** The Dates tab has a **Quick set-up**. The rules, in one server-side function: visible from equals nominations open; nominations close at the end time on the last day of the period; candidates advertised 5 minutes after nominations close; voting opens at the start time on the following day; voting closes at the end time on the last day of its period; results 5 minutes after voting closes (or the next morning); visible until 12 weeks after the results; a calculated start that is not after the previous end moves forward in 5-minute steps; four cycles (1 and 1, 2 and 2, 2 and 3, 2 and 4 weeks). The rule "nominations start after the visibility start" became "not before".
- **Why.** One source of truth, tested for daylight saving changes, instead of a second copy in JavaScript.
- **Date.** 2026-10-31.
- **In the code.** `CRM_Elections_Helper_Dates::schedule()`; API action `Election.suggestDates` (`Civi/Api4/Action/Election/SuggestDates.php`); `DateRulesTest`, `ScheduleTest`.

### D27 (R2.0-8) – Celebration effects
- **Decision.** Five effects plus "Surprise me", default off. The celebration runs on every visit to the results page for N days after the results are published (default 5, 1 to 30). No "once per browser" rule. It respects reduced motion, can be skipped, and stops after about six seconds.
- **Why.** The decision is made on the server from the results date, so nothing is stored in the browser.
- **Date.** 2026-10-31.
- **In the code.** `celebration_enabled`, `celebration_effect`, `celebration_days` (`20110`); `js/election-celebrate.js`.

### D28 (R2.0-9) – Results page logo, header and footer text
- **Decision.** Per-election logo, header text and footer text, plain text only, editable through a **Results Page** form from the overview at any time (a small widening of the plan, which said until the results are published; every change is audited). The same header and footer on the summary page. The logo is served by a public route only once results are published.
- **Why.** A typo after publication is worth fixing; SVG can carry script, so only PNG, JPEG, GIF and WebP up to 2 MB are accepted.
- **Date.** 2026-10-31.
- **In the code.** `results_header_text`, `results_footer_text`, `results_logo_file_id`, `results_logo_alt` (`20110`); `civicrm/elections/results-display` and `results-logo`; `CRM_Elections_Helper_ResultsDisplay` (`decode()`).

### D29 (R2.0-10) – Permissions for organisers
- **Decision.** Document the permissions of every role (`docs/roles_and_permissions.md`) and show a notice on the form when groups or people are hidden. No extension API that lists group titles around CiviCRM's rules. The test site's organiser role includes `view all contacts`.
- **Why.** Which groups a person sees follows CiviCRM's contact permissions or ACLs. An API that ignored them would show organisers groups that CiviCRM hides from them.
- **Date.** 2026-10-31.
- **In the code.** `CRM_Elections_Helper_Visibility`; test roles `elections_manager` and `elections_limited` in `tests/playwright/fixtures/setup-environment.sh`.

## Behaviour found during the 2.1 screen review (November 2026)

### R2.1-1 – Editing an election keeps its Active state
- **Decision.** Saving the edit form no longer deactivates an active election that has not started, and no longer replaces who created it.
- **Why.** `CreateElection::postProcess()` always sent `is_visible => 0` since the first commit. The fix goes into 2.1 with everything else; there is no 2.0.1.
- **Date.** 2026-11-01. **In the code.** `CRM_Elections_Form_CreateElection::valuesForNewElection()` (only a new election is given `is_visible` and `created_by`); `AdminRulesTest::testEditingAnActiveElectionKeepsItActiveAndKeepsItsCreator`.

### R2.1-2 – Declined and Withdrawn are stored apart
- **Decision.** A new column records that a candidate withdrew after accepting. Existing refused nominations show as **Declined**, because the data never said which they were.
- **Why.** The words need one meaning each (`U3`).
- **Date.** 2026-11-01. **In the code.** `civicrm_election_nomination.has_withdrawn_nomination` (upgrade `20130`); `CRM_Elections_Helper_Process::withdraw()`.

### R2.1-3 – The "Your vote" block stays
- **Decision.** The election page may show a voter their own recorded choices, to them alone, never to anyone else, never in an email and never when the election anonymises votes.
- **Why.** A voter can check what was recorded.
- **Date.** 2026-11-01. **In the code.** The election page block; the build checks it is hidden for anonymised votes. See [`../standards/ui-standards.md`](../standards/ui-standards.md#6-front-end).

### R2.1-20 – The election list works on a personal link
- **Decision.** Add the access callback to the `civicrm/elections` route and carry the personal link on the card links.
- **Why.** It was the only public route without `CRM_Elections_Permission::check`, so a person with only a link was refused; the documentation already said it worked.
- **Date.** 2026-11-01. **In the code.** `xml/Menu/elections.xml`; `personalLinkQuery` in the election list template.

### SD7 – A started rule stays locked even if the election is inactive
- **Decision.** The rules that decide who votes and how votes count (groups, anonymise votes, allow a changed vote, required nominations, voting method, voting dates, results) stay locked once ballots exist, whether or not the election is active. The name, description, display options and the nomination and visibility dates can still be corrected.
- **Why.** The finer rule already protects the vote, so refusing all editing is not needed.
- **Date.** 2026-11-02. **In the code.** `CRM_Elections_Helper_Guard`; `SecurityTest::testAnInactiveElectionThatHasBallotsKeepsItsRulesLockedAndNothingElse`.

## Open decisions

### D13 – A granular permission
- **Question.** Add a permission between `administer Elections` and `view Elections` (for example `manage Election candidates`, so a returning officer can add candidates and fix statements without creating, activating or deleting elections)?
- **Status.** Deferred to version 2.2 on 2026-11-03. Default: build it only if an organisation needs it. If built: the permission, the Candidates routes split from the other admin routes, audit entries, tests on three CMSs, and `docs/roles_and_permissions.md`.

### D14 – More voting methods
- **Question.** Add counting methods beyond ranked choice and most votes (approval, Senate count for several seats)?
- **Status.** Open. 2.0 shipped ranked choice and most votes. Recommended: keep STV for 2.2 and drop approval voting, which has no request behind it (the second part is unconfirmed). See [`../roadmap/2.2/stv.md`](../roadmap/2.2/stv.md).

### S1 to S6 – The Senate count (STV) pull request
A pull request on the Linux Australia fork (against the 1.x code) adds a Senate count, STV, as a per-position option. A review found it cannot be merged as it is: it uses an upgrade step number from the 1.x series, picks winners of ties by a random draw (version 2 never picks a winner of a tie), stops reading a voter's numbers at the first gap (version 2 closes gaps), does not fit the version 2 position and election settings, and shows no count sheet. The review was done by reading the code; none of its code has been run.

| ID | Question | Recommendation | Status |
|---|---|---|---|
| S1 | Ties in an STV count | Report the tie and let the organisation decide (matches the rest of version 2); alternatives are a documented draw run by the organiser with a button, or the pull request's automatic draw | Open |
| S2 | Where the setting lives | Per position (`irv` or `stv`, ranked elections only), if mixing in one election is wanted; or per election as a third voting method | Open |
| S3 | What is a formal ballot | Optional preferential: any valid first preference counts | Open |
| S4 | Timing | Build in 2.2, after the points above are decided | Open |
| S5 | Speaking to the fork | Whether to comment on the pull request or contact the author about retargeting it. Nothing has been posted | Open |
| S6 | Running the engine | Run its tests and stress checks (overflow, ties, gaps) in an empty directory outside the project, since it is third-party code | Open |

Related decision `SD8` (2026-11-02): the STV upgrade steps start at `20140`. If the code is adopted, keep the author's commits and give a contributor credit.
