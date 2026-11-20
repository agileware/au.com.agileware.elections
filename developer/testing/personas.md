# Personas

This reference page describes the people the tests act as, and why the front end is tested as people and not as an administrator. The data behind it is built by `tests/phpunit/CRM/Elections/Test/CouncilFixture.php` and `tests/playwright/fixtures/seed-council.php` ([seeding.md](seeding.md)).

## Why people, through personal links

An election is mostly used by people who are not administrators: voters, nominators, nominees, candidates. Looking at the screens as an administrator hides the bugs they meet: the wrong permission on a page, a nominee search a personal-link user cannot reach, a refusal message, a button that depends on whose nomination it is. Browser tests found each of these early. So:

- **The election process is tested on the front end, as people.** The back end is used only to administer elections, and is tested separately.
- **A person is a personal link** (`cid` plus a checksum `cs`) on an election that allows people to take part without logging in. No password is involved, and each persona gets its own browser context. See [seeding.md](seeding.md).
- **A CMS session wins over a personal link.** Someone logged in as an administrator in the same browser acts as the administrator whatever link they open, so the administrator must be signed out when you look at the front end as a voter ([manual-browser-testing.md](manual-browser-testing.md)).
- **Every defect found by hand gets a persona test.**

Joomla note: the persona specs run in full on Joomla because they use links, not logins. Since 2.2 the back end specs that need a manager, limited or member session run there too ([playwright.md](playwright.md)).

## The model

| Persona | Count | Who they are |
|---|---|---|
| `V01` to `V30` | 30 | Voters. Contacts named "Voter 01" to "Voter 30", in the election's group. They nominate and second. |
| `N01` to `N10` | 10 | Nominees. Contacts named "Nominee 01" to "Nominee 10", also in the group (so they vote). `N01` has the nickname Butch and `N03` has Mo, to test name display. |
| Candidates | 6 | The nominees who accepted at least one position: `N01` to `N06`. |
| `OUT` | 1 | The outsider ("Outsider Person"): not in the election's group, so refused at every step. |

That is 41 personas. Everyone except `OUT` is in the election's group, so 40 people can vote, and 38 do (`V29` and `V30` never vote).

## The council plan

The election has six single-seat positions: President, Secretary, Treasurer, Council Member 1, Council Member 2 and Council Member 3. Two nominators (the election requires two) nominate and second each nominee. What each nominee then does:

| Position | Nominee | Nominators | What the nominee does |
|---|---|---|---|
| President | N01 | V01, V02 | accepts |
| President | N02 | V03, V04 | accepts |
| President | N03 | V05, V06 | withdraws |
| President | N08 | V01 | nothing: only one nominator, never eligible |
| Secretary | N03 | V07, V08 | accepts (the only candidate: uncontested) |
| Secretary | N04 | V09, V10 | ignores the invitation: not a candidate |
| Secretary | N09 | V02, V03 | ignores the invitation |
| Treasurer | N04 | V11, V12 | accepts |
| Treasurer | N05 | V13, V14 | accepts |
| Treasurer | N07 | V15, V16 | withdraws |
| Council Member 1 | N06 | V17, V18 | accepts |
| Council Member 1 | N01 | V19, V20 | accepts |
| Council Member 2 | N02 | V21, V22 | accepts |
| Council Member 2 | N06 | V23, V24 | accepts |
| Council Member 3 | N03 | V25, V26 | accepts |
| Council Member 3 | N05 | V27, V28 | accepts |
| Council Member 3 | N10 | V29, V30 | accepts, then withdraws |

Candidates standing at voting: President N01 and N02; Secretary N03; Treasurer N04 and N05; Council Member 1 N06 and N01; Council Member 2 N02 and N06; Council Member 3 N03 and N05.

## The ballots

The 38 ballots are worked out from a fixed pattern (people are numbered 1 to 40, voters then nominees). The pattern gives clear winners in most positions, a deliberate **tie for Treasurer** (19 first choices each), an **uncontested Secretary**, **abstentions** (every fifth person leaves the Secretary blank) and **partial ballots** (every seventh person ranks only a first choice). The council spec checks the results against ballots worked out independently of the extension's counting, and one persona changes their vote.

## What the specs do with them

- `specs/council/council-election.spec.ts` runs the whole election: the administrator checks the set-up, every nominator nominates and seconds through the form (the first through the real nominee search), nominees accept or withdraw, 38 people vote, the outsider is refused, results are checked, and the administrator sees the turnout. The other `specs/council/` specs repeat the council with another rule (most votes, groups, write-ins, referendum, organiser-chosen candidates, the results page).
- `specs/frontend/` uses personas at each stage, for the people who matter at that stage (the election page tells each person where the election is and what they can do now).
- `CouncilPersonaTest` in PHPUnit runs all 41 at every stage without a browser.

The Beekeepers seed is a second set of people (30 Beekeepers) for reviewing by hand, not used by the suites ([seeding.md](seeding.md)).
