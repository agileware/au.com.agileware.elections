# Senate count (STV)

Planning page for version 2.2, items 1 and 2 of [the roadmap](README.md). It describes an external pull request that adds the Senate count (single transferable vote, STV) as a way to count a position with several seats, the problems that stop it being merged into version 2 as it stands, the risks, the options, and six questions that need an answer from the maintainer. It was written from reading the pull request and the version 2 code on 2026-11-01 and re-checked against the code on 2026-11-15. Nothing in the pull request was run, and nothing has been posted to it.

## What the pull request proposes

The pull request is [linuxaustralia/au.com.agileware.elections#1](https://github.com/linuxaustralia/au.com.agileware.elections/pull/1), "Add Senate count (STV) as a per-position counting option". Its author is a contributor on a fork. On 2026-11-15 it was open with no comments or reviews, 17 files changed (692 lines added, 8 removed), made against the fork's `master`, which holds the 1.x code.

It:

- adds a `counting_method` column to `civicrm_election_position` (`irv` by default, or `senate`), a "Counting method" drop-down on the position form, a "Counting" column in the positions table, and two new result statuses (`senate`, and `senate_lot` when a tie was settled by a random draw);
- adds `CRM_Elections_Helper_SenateCount` (335 lines), the Australian Senate count under section 273 of the Commonwealth Electoral Act 1918: the Droop quota (`floor(ballots / (seats + 1)) + 1`), weighted inclusive Gregory surplus transfers, exclusion of one candidate at a time with transfers in order of transfer value, truncation of the votes credited by each transfer, ties broken by the last count where the tied candidates differed, and then a random draw; fractions are kept exact as numerator and denominator pairs;
- makes `Results.php` count a position marked `senate` with the new engine (a position with at least as many seats as candidates keeps the old path);
- moves vote anonymising out of the position loop so later positions can still be counted;
- adds about 250 lines of tests (six hand-counted scenarios for the engine, three result tests) and documentation.

## Why it is worth having

- The method is the right one for several seats. In version 2 the ranked choice count for a multi-seat position stops once the top candidate has a majority of first preferences and fills the other seats in first preference order (`assignRanksIfClearMajority` in `CRM/Elections/Helper/Results.php`). That is not proportional. Not verified: a worked example that shows the difference.
- The engine is self-contained pure PHP that does not touch the database, so it can be tested on its own.
- It uses the same ballot as ranked choice (one row per preference), so the ballot form, secrecy, anonymising and the one ballot per person rule need no change.
- The anonymising problem it fixes (a 1.x bug) is already fixed in version 2: `generateResult` counts every position first and then calls `removeVotes` once, inside the lock and the transaction.

## Problems to solve for version 2

The pull request cannot be merged as it is.

1. **Upgrade step numbering.** It adds `upgrade_10400` with `'add' => '6.16'`. A site already on 2.x never runs a step in the 1.x range, so it would have no `counting_method` column. The step must be in the 2.x sequence (the next free number is `20140`) with the right `add` version.
2. **The random draw breaks a version 2 rule.** For ties version 2 never chooses a winner: every tied candidate is listed and marked tied. The pull request calls `random_int` when the results are made, so the answer cannot be reproduced or audited, and the results are generated once and fingerprinted. Question S1.
3. **Ballots are read differently from ranked choice.** `normaliseBallot` stops at the first gap in a voter's numbers. Version 2's `compactRanks` closes gaps, so a ranking that includes someone who is not a candidate (a person written in who did not accept, or who withdrew) carries on to the next choice. With STV such a ballot would lose its later preferences, so the same ballot would count differently under the two methods. The engine should use `compactRanks`.
4. **The per-position setting does not fit what version 2 changed.**
   - The positions list is a SearchKit display, so the edit to the old positions table template has nothing to change; the "Counting" column must be built in SearchKit.
   - The position form now has `position_type` (referendum questions), seats and write-ins per seat. A referendum question must ignore the counting method.
   - Positions cannot be changed while an election is active (from the start of nominations; `CRM_Elections_BAO_ElectionPosition::assertCanChange`), and changes to election and position settings go in the audit log. `counting_method` must be covered by both. (`AUDITED_ELECTION_FIELDS` in `CRM/Elections/Helper/Guard.php` lists the election fields; the position fields are compared in `CRM_Elections_BAO_ElectionPosition::normalise`, so a new position field needs adding there too.)
   - Duplicating an election or a position must copy it.
   - The election already has `voting_method` (`irv` or `plurality`) with the ballot rules in `CRM_Elections_VotingMethod`. A second setting on the position must agree with it, for example by being hidden when the election is "Most votes win".
5. **Results display.** The new statuses need handling wherever version 2 shows results: the results page, the summary, the results display options, exports, the integrity fingerprint, and the PHPUnit and Playwright result tests.
6. **Wording.** New text must say "people", not "members", and pass `WordingTest`.

## Risks and gaps

- **Overflow in the exact fractions.** Numerators and denominators multiply on every surplus transfer and are kept as PHP integers, which silently become floats above about 9.2e18. A large electorate with several chained surpluses could reach that, and integer division on a float would then fail or give a wrong count. Fix with scaled integer arithmetic or `bcmath` or `gmp`, and add a stress test (thousands of ballots, many seats). Not verified: nobody has run it.
- **No count sheet.** The engine records each count's tallies for tie breaking but does not return them. STV results are questioned far more than ranked choice results, so the results page and the audit log should keep the quota, each round's totals, who was elected or excluded when, and each transfer value, and the integrity fingerprint should cover it.
- **What counts as a formal ballot.** Any ballot with a valid first preference is counted and later numbers are optional. The Senate requires a minimum number of preferences. The rule for associations must be chosen, shown on the ballot instructions and written in the docs. Question S3.
- **Truncation.** Each transfer is truncated to whole votes, as in the Senate, so a few fractions of votes drop out. It is correct for the Senate method but must be stated in the docs.
- **Independent check.** The tests were hand-counted by the author, so they share the author's reading of the rules. Check the engine against a published count (the Australian Electoral Commission publishes distributions of preferences for Senate counts) or a separate implementation before relying on it.
- **Untested areas.** Gaps and withdrawn candidates, write-ins, a reported or drawn tie, the upgrade, the position guard, a full persona election with an STV position, and a large electorate.

## Options

| Option | What it means | Cost |
|---|---|---|
| **A. Build STV in 2.2 on version 2** | Port the engine: a step from `20140`, the guard and audit log, `compactRanks`, a count sheet, a SearchKit column, docs, PHPUnit and Playwright tests on 6.16 and 6.19, an independent check and a security re-review. Keep the author's commits (cherry-pick with their name) and give a contributor credit. | A full item of work and a new round of testing on three CMSs. |
| **B. Keep STV for a later version** | Release 2.2 without it. | None now; the question stays open. |
| **C. Drop approval voting** | Treat only STV: approval voting has no pull request or request behind it. Can be combined with A or B. | None. |

The earlier recommendation (made before 2.0.0 was released) was to keep STV for later and drop approval voting. The maintainer has since placed STV in 2.2, so option A with C is the working assumption; question S4 confirms it.

## Questions for the maintainer

| # | Question | Options | Recommendation |
|---|---|---|---|
| **S1** | How are ties handled in an STV count? | (a) Report the tie, list the tied candidates and let the organisation decide. (b) A documented draw recorded in the audit log, started by the organiser with a button, not automatic. (c) The pull request's automatic random draw. | (a), because it matches the rest of version 2. |
| **S2** | Where does the setting live? | (a) Per position, `irv` or `stv`, for ranked elections only (a Chair by ranked choice and a committee of five by STV in one election). (b) Per election, `stv` as a third voting method beside ranked choice and most votes win. | (a) if mixing in one election is wanted; otherwise (b) is simpler. |
| **S3** | What is a formal ballot? | Optional preferential (any valid first preference counts), or a minimum number of preferences. | Optional preferential. |
| **S4** | Timing | STV in 2.2 (option A) or later (option B). | A, now that 2.2 is planned around it. |
| **S5** | Speaking to the fork | Whether the maintainer comments on the pull request, or contacts its author or the fork's organisation about retargeting it at version 2. A draft can be prepared once S1 to S4 are answered. | Comment after S1 to S4, thanking the author and saying how the contribution will be used. |
| **S6** | Running the engine | Whether to run the pull request's tests and the stress checks (overflow, ties, gaps), in an empty directory outside the repository because it is third-party code. | Yes, before relying on the engine. |

Record the answers in [../../decisions/voting-and-elections.md](../../decisions/voting-and-elections.md) with their date. For how counting works today, see [../../architecture/voting-methods.md](../../architecture/voting-methods.md).
