# Version 2.3: start here

Written 2026-11-21. Version 2.3 is not being built yet: 2.2 comes first ([../2.2/README.md](../2.2/README.md)). This page holds the work the maintainer moved out of 2.2: the Senate count (items 1 and 2) and three small findings (items 3 to 5). When 2.3 starts, bring it up to the layout of the 2.2 page (where things stand, open decisions, traps, start-up checklist).

## The items

| # | Item | State and what it needs |
|---|---|---|
| 1 | **Senate count (STV): decisions S1 to S3, S5 and S6** | Moved from 2.2 on 2026-11-21. Not answered. Read [stv.md](stv.md). The maintainer answers; a comment on the external pull request is then drafted for the maintainer to post. Nothing has been posted. S4 (timing) is answered: 2.3. |
| 2 | **Senate count (STV) built on version 2**, not merged as the external pull request stands | Moved from 2.2 on 2026-11-21. Six blocking problems are listed in [stv.md](stv.md). Needs an upgrade step (the next free number when 2.3 starts: `20140` unless 2.2 uses it), its own attack tests, an independent check against a published count, a large-electorate overflow test, a security re-review, and a contributor credit. A new result or stage rule must be made in every place the stage is worked out and added to `AdminRulesTest::testTheThreeWaysOfWorkingOutTheStageAgree` (item 5). |
| 3 | **Action bar buttons are 38 px high at phone width** | Moved from 2.2 on 2026-11-21. `css/electionforms.css` asks for at least 44 px on a phone or touch screen (`.election-list-actions .btn` in the `max-width: 768px`, `pointer: coarse` rule), but the fixed-height rule for every action bar button (`height: 2.5em !important`) wins, so they are 38 px on all three CMSs. The UI standards say at least 36 px, which they meet; decide whether the 44 px touch size is wanted and, if so, let the touch rule win. Found 2026-11-20, not changed. |
| 4 | **Manage Elections still a little wider than the window on Joomla below about 900 px** | Moved from 2.2 on 2026-11-21. With the table fix the page is at most about 140 px too wide (the Add button stays in view); something else on Manage Elections, probably the filter row, has a minimum width. Not looked into. |
| 5 | **Election stage has three implementations** | Moved from 2.2 on 2026-11-21. Checked 2026-11-17 and confirmed. The stage is worked out in `CRM_Elections_BAO_Election::getStage()` (overview and stepper), `ElectionSpecProvider::renderStage()` (an SQL `CASE` for the Stage column of Manage Elections) and `CRM_Elections_Helper_ElectionStatus::card()` (the cards on the front end list); `ElectionStatus::nextAction()` walks the same ladder a fourth time, and `CRM_Elections_Page_Elections::modifyElectionValues()` works out the card's date flags a second time. They agree today: same order of checks, same boundaries, same clock, same rule for results, and the same words on screen. `AdminRulesTest::testTheThreeWaysOfWorkingOutTheStageAgree` now holds them together in ten states. They differ in three ways that do not show: the keys (`advertise` and `closed` against `candidates`, `nominations_closed` and `voting_closed`), a `deleted` stage only in `getStage()`, and `nextAction()` checking nominations open before candidates advertised (it matters only if the advertise date is before nominations close, which the date rules refuse). Still open: one source for the stage (a refactor that touches the front end election page and needs the three-CMS run). A new stage or rule, for example from the STV work in 2.3, must be made in all of these places and added to the test. |

## Decisions still open

| Decision | State |
|---|---|
| STV questions S1 to S3, S5, S6 | Open, in [stv.md](stv.md) and [../../decisions/voting-and-elections.md](../../decisions/voting-and-elections.md). |
| Approval voting (part of D14) | No request behind it; the recommendation is to drop it, not yet confirmed. |
