> **Historical.** Written during version 2.0; may be out of date. Current guidance: [User interface standards](../../../standards/ui-standards.md).

Covers: version 2.0, the front end design plan, 2026-10-27 to 2026-10-31. Copied from the project plan with names, paths and account names removed; screenshots under `plan/evidence/` are not kept (image not kept).

# 13 – Phase 4: front-end design and user experience

Builds on task 09 (the pages already fit and are themed on WordPress Twenty Twenty-Five, Drupal Olivero and Joomla Cassiopeia, with accessible buttons) and on the security work (every output escaped, ballots protected). This phase improves how the election **feels** to the people taking part, without changing who can do what.

## Principles
- Same templates on every CMS, no CMS-specific markup; inherit the theme's fonts and colours; colours through CSS variables with fallbacks; no fixed widths.
- Works without JavaScript; JavaScript only improves it (drag to rank, live checks).
- Keyboard and screen reader first (WCAG 2.1 AA): every new piece is covered by the axe and keyboard checks.
- No vote counts on the results pages (decision D17: results stay as they are, winners and how each was decided).
- Every change keeps the security guarantees: escaped output, one ballot per member, the same server-side checks.
- Each sub-step ships with Playwright checks (layout at three widths, axe, keyboard) and updates the screenshots in `plan/evidence/`.

## Sub-steps, in the order they will be built

### 4.1 Election page: where are we, and what can I do now
- **Stage stepper** across the top: Nominations, Candidates, Voting, Results. The current stage is marked, passed stages are ticked, each shows its date and time in the viewer's time zone. Works as a vertical list on a phone.
- **Next-action panel** for the person looking: one clear sentence and one button, from what they can do right now. Examples: "Nominations are open until Fri 6 Nov, 5:00 pm" with *Submit nomination*; "You have been nominated for President, please accept or withdraw" with *Respond*; "Voting is open" with *Vote now*, or "You voted on Tue 3 Nov. You can change your vote until Fri 6 Nov" with *Change vote*; "Results are published" with *See results*; or why they cannot take part ("Only members can vote in this election").
- Replaces the loose block of buttons and the long help text; the description stays above.

### 4.2 Election list
- Cards instead of accordions: name, a stage badge, the key date ("Voting closes in 3 days"), one button for the stage (*View and nominate*, *Vote*, *Results*).
- Keeps the shortcode options (`action`, `viewpageid`) and the admin view.

### 4.3 The ballot
- **Ranking:** each position is a ranked list. Numbers in dropdowns stay as the accessible and no-JavaScript way; with JavaScript the list can also be dragged (and moved with keys), and the numbers update.
- **Live checks** announced to screen readers: a number used twice, a gap, how many candidates are ranked. Server checks stay the real ones.
- **Review step** before the vote is saved: a plain summary of the choices per position, *Change* and *Confirm my vote*. Nothing is stored until confirmed.
- **Receipt** after saving: what was recorded, the time, and how to change the vote (if allowed). Shown on screen only; nothing about the choices is emailed or logged.
- Works on a phone without dragging.

### 4.4 Nominating, accepting, withdrawing
- **Your nominations** panel on the election page: for the signed-in or linked person, each nomination of them with status chips (Waiting for nominators, Eligible, Accepted, Withdrawn) and the next button (Accept, Withdraw, Edit statement).
- Nomination form: the position and nominee pickers get clearer labels and help, a short summary of the choice before sending, and errors beside the field.
- Accept form: the statement box with a character guide and a preview of how it will look on the ballot.

### 4.5 Candidate page and results
- Candidate page: photo (if shown), positions and status, statement, nominators (if shown), in a tidy card.
- Results: one card per position with the winner badge(s), how it was decided, and the list of the candidates who stood. Print styles for a clean one-page result. (No vote counts, D17.)

### 4.6 Finishing
- Dark and high-contrast support through the CSS variables; re-run the theme variation checks.
- Update the docs and re-record the walkthrough pictures for the user guides.

## What is deliberately left alone
- Who can see which page (D17: no change) and the checks behind each action.
- The back end screens (done in Phase 3).
- HTML emails: the invitation email for write-ins is built with the write-in feature (D8, D11).

## Acceptance
For every sub-step: layout spec and accessibility spec pass at 375, 768 and 1280 px on WordPress, Drupal and Joomla; keyboard checks cover the new controls; the security specs still pass; screenshots before and after are kept; the docs describe the new screens.

## Status (2026-10-27)
- **4.1 Election page: done.** Stage stepper (Nominations, Candidates, Voting, Results; dates in the site time zone; vertical on a phone) and a "what you can do now" panel with one sentence and a button for each situation (nominate, respond to a nomination, vote, change your vote, closed, results, not allowed). Replaces the old help text, date lines and loose buttons. Unit tests cover each message (`ElectionStatusTest`), browser tests cover each stage for each kind of person (`election-page.spec.ts`).
- **4.2 Election list: done.** Cards with the stage badge, one line about the next date and the stage's button.
- **4.3 Ballot: done except dragging.** Ranks are checked as they are chosen (same rank twice, no first choice, how many are ranked), the vote is shown for review in a dialog before it is saved (Go back and change / Confirm my vote), and after voting the election page shows **Your vote** (only to that person, while voting is open). The numbered choice boxes stay as the accessible and no-JavaScript way. Drag to rank (with keys) is not built yet; it is the one remaining item of 4.3.
- **4.4 Nominations: done.** **Your nominations** panel with a status chip for each nomination (waiting for N more nominators, waiting for your answer, accepted, withdrawn) and the buttons that apply (Accept, Withdraw, Edit statement). The nomination and accept forms keep their current layout.
- **4.5 Candidate page and results: partly.** Winner (or Tied) badges on the results cards, print styles that drop the buttons and keep cards together. The candidate page keeps its layout (already fluid from task 09).
- **4.6 Finishing: pending.** Docs for the new screens are updated (`docs/user_vote.md`, `docs/user_accept_nomination.md`); the walkthrough pictures are to be re-recorded in Phase 8; screenshots of the new pages are in `evidence/phase4/`.
