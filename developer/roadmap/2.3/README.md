# Version 2.3: start here

Written 2026-11-21. Version 2.3 is not being built yet: 2.2 comes first ([../2.2/README.md](../2.2/README.md)). This page holds the work the maintainer moved out of 2.2. When 2.3 starts, bring it up to the layout of the 2.2 page (where things stand, open decisions, traps, start-up checklist).

## The items

| # | Item | State and what it needs |
|---|---|---|
| 1 | **Senate count (STV): decisions S1 to S3, S5 and S6** | Moved from 2.2 on 2026-11-21. Not answered. Read [stv.md](stv.md). The maintainer answers; a comment on the external pull request is then drafted for the maintainer to post. Nothing has been posted. S4 (timing) is answered: 2.3. |
| 2 | **Senate count (STV) built on version 2**, not merged as the external pull request stands | Moved from 2.2 on 2026-11-21. Six blocking problems are listed in [stv.md](stv.md). Needs an upgrade step (the next free number when 2.3 starts: `20140` unless 2.2 uses it), its own attack tests, an independent check against a published count, a large-electorate overflow test, a security re-review, and a contributor credit. A new result or stage rule must be made in every place the stage is worked out and added to `AdminRulesTest::testTheThreeWaysOfWorkingOutTheStageAgree` (see the 2.2 roadmap, "Election stage has three implementations"). |

## Decisions still open

| Decision | State |
|---|---|
| STV questions S1 to S3, S5, S6 | Open, in [stv.md](stv.md) and [../../decisions/voting-and-elections.md](../../decisions/voting-and-elections.md). |
| Approval voting (part of D14) | No request behind it; the recommendation is to drop it, not yet confirmed. |
