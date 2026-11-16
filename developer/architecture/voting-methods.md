# Voting methods and counting

How ballots are cast, stored, counted and anonymised, and where to add another voting method. Written from the code at release 2.1.0: `CRM/Elections/VotingMethod.php`, `CRM/Elections/Helper/Results.php`, `Referendum.php`, `WriteIn.php`, `api/v3/ElectionVote.php`.

## Methods

The method is a setting of the election (`civicrm_election.voting_method`), fixed once any ballot exists. An empty or unknown stored value means ranked choice.

| Method | Key | Voter | Stored |
|---|---|---|---|
| Ranked choice (instant runoff) | `irv` | Gives each candidate they want a different rank, 1 is the first choice. Candidates left out are not ranked | One `civicrm_election_vote` row for each ranked candidate, with the rank |
| Most votes win | `plurality` | Ticks up to as many candidates as there are seats | One row for each tick, always rank 1 |
| Yes or no question | (a position with `position_type = referendum`) | Chooses one answer or leaves the question blank | One `civicrm_election_option_vote` row |

Both candidate methods store a ballot the same way, so the one ballot per person rule, anonymising, fingerprints and the review step do not depend on the method. One election can mix positions and questions on one ballot.

## What a ballot must satisfy

`ElectionVote.addvotes` validates before it changes anything, so a refused ballot never costs a person their earlier ballot:

- the election is active, voting has started and not ended, the person is in the voting groups, and a second ballot is only accepted when `allow_revote` is on (the new ballot then replaces the old one in one transaction);
- every ranked candidate is on the ballot of this election (eligible, accepted, not declined or withdrawn);
- ranks are whole numbers from 1 up to the number of candidates for the position; at least one rank is 1; for ranked choice no two candidates share a rank; gaps are allowed and are closed when counting;
- plurality: every rank is 1 and no more ticks than seats (`VotingMethod::validatePosition()`);
- referendum choices name a question of this election, a choice of that question, once each (`Referendum::resolve()`);
- write-ins respect the number of places and the candidate groups (`WriteIn::resolve()`).

## Counting ranked choice

`Results::generateResultsForNominations()`, for the candidates of one position:

1. Take the ballot rows for the candidates (people who accepted). Write-ins who did not accept are not candidates, so their rows are not counted.
2. **Normalise with `compactRanks()`.** Each voter's remaining choices are renumbered 1, 2, 3 in the order they gave them. If a first choice was not a candidate, the second choice becomes the first.
3. If seats are at least the number of candidates, everyone is ranked by first-choice votes and the status is `equalseats` or `moreseats`.
4. Otherwise, if the leading candidate has more than half of the ballots that rank a remaining candidate, the status is `majority` and ranks follow first-choice votes.
5. If two candidates remain for one seat without a majority, both get rank 1 and the status is `tie`.
6. Otherwise eliminate the candidate with the fewest first-choice votes (one candidate each round, the first in the working list when several share the fewest), move those ballots to their next remaining choice, and repeat from step 3.

A position with more than one seat is counted in the same way: the voter votes once and the ranks count across all seats. This is **not** a single transferable vote count (no quota, no surplus transfers). The open work to add one is in [../roadmap/2.2/stv.md](../roadmap/2.2/stv.md).

## Counting most votes win

`CRM_Elections_VotingMethod::countPlurality()`: sort by number of ticks; candidates with the same number share a rank; status `equalseats` / `moreseats` when seats cover the candidates, `tie` when the candidates tied on votes run across the last seat, otherwise `mostvotes`.

## Seats, ranks and ties

Results store only ranks (`civicrm_election_result`) and a `result_status` for each position. At display time `BAO/ElectionResult::getRanksForPosition()` fills the seats from rank 1 down: a rank that fits in the open seats wins; a rank that runs across the last seat is a tie and every candidate in it is marked `is_tied`. A tie is never settled by choosing one candidate: the extension reports the tie and leaves it unresolved.

| `result_status` | Meaning |
|---|---|
| `majority` | Ranked choice, clear majority reached |
| `mostvotes` | Most votes win, seats decided |
| `equalseats`, `moreseats` | As many or fewer candidates than seats |
| `tie` | Tied across the last seat |
| `nonominations` | No candidate stood |
| `passed`, `failed`, `noquorum` | Referendum outcomes |

The constants are `CRM_Elections_BAO_Election::$RESULTS_*`; the wording is in `templates/CRM/Elections/Page/PositionResultTypeMessage.tpl`.

## Write-ins

A position allows write-ins when `write_ins_per_seat` is 1 to 10 (places = per seat times seats). During voting a voter names a person in the candidate groups. The person becomes a nomination with `is_write_in = 1`, eligible but not accepted, and is invited by email. Votes for them are ordinary ballot rows; they count only if the person accepts before voting closes, and then the person is a candidate for everyone who votes afterwards. Declining, or no answer, drops the votes and `compactRanks()` moves each ballot to the voter's next choice. This is the only way anyone can join the ballot once voting has started. Nothing that records a write-in shows who wrote them in (see [security.md](security.md)).

## Referendum questions

`CRM_Elections_Helper_Referendum::decide()`:

- Choices are Yes, No and an optional Abstain, with the election's own wording (`civicrm_election_option`).
- The share is Yes out of Yes plus No (Abstain is left out).
- A threshold of 50 means **more than half**, so exactly half fails; any other threshold means **at least** that share (a share kept to two decimals such as 66.67 is allowed for rounding).
- No Yes or No answers at all fails.
- An optional quorum is the percentage of people allowed to vote (counted from the voting groups when results are generated) who took part, Abstain included. Missing the quorum gives `noquorum`.

## Anonymised ballots

When `anonymize_votes` is on (the default), `Results::removeVotes()` runs after every position is counted, in the same transaction:

- the voter is removed from every ballot row (`member_id` becomes NULL);
- the time of every row is set to the time voting closed;
- rows are written back in a random order (a voter's rows stay together).

Counting needs the voter ids, so anonymising always comes last. The ballot fingerprint is made from `candidate:rank` lines only, so it still verifies after anonymising. The `Vote` activities (one for each person, never what they chose) remain and drive turnout.

## Results are generated once

`Results::generateResult()` refuses unless voting has ended, takes a lock, deletes any partial rows, counts, sets `has_results_generated`, writes the fingerprints to the audit log (`Integrity::recordGeneration()`), then anonymises. After that the ballots, results and the voting dates are locked, and the overview compares the stored fingerprints with the data each time it is shown. See [security.md](security.md).

## Adding a voting method

1. Add a key and label to `VotingMethod::getOptions()`, with instructions (`instructions()`) and the position prompt (`positionPrompt()`); put per-position ballot rules in `validatePosition()`.
2. Count it in `Results::generateResultsForPosition()` (the branch on `VotingMethod::forElection()`), writing ranks and a `result_status` through `saveResultAndAssignRanks()`. Reuse the existing statuses and the `$RESULTS_*` constants where they fit; add a message for a new status in `PositionResultTypeMessage.tpl`.
3. Keep the stored ballot shape (one row for each choice with a rank) unless there is a strong reason: the guards, fingerprints, anonymising and turnout all read `civicrm_election_vote`. A new table must be added to every one of them (see how `civicrm_election_option_vote` was added: `Guard`, `Integrity`, `Results::removeVotes()`, `Election::getCounts()`, `Eligibility::hasVoted()`, contact merge and delete).
4. Update the ballot form (`Form/VoteCandidates.php`, `templates/CRM/Elections/Form/VoteCandidates.tpl`, `js/election-ballot.js`) and the results templates.
5. Lock it: it is already in `Guard::LOCKED_ONCE_VOTED` through `voting_method`. A new method needs no schema change, so no upgrade step, unless it needs a new column.
6. Tests: `VotingMethodTest`, a case in `SecurityTest` for anything that touches ballot rows, and a Playwright spec on the model of `tests/playwright/specs/council/plurality.spec.ts`.
