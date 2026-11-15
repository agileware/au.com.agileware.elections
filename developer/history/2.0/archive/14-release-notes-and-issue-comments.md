> **Historical.** Written during version 2.0; may be out of date. Current guidance: [Releases](../../../releases/README.md).

Covers: version 2.0, the 2.0.0 release notes and issue comment drafts, 2026-10-31 to 2026-11-01. Copied from the project plan with names, paths and account names removed; screenshots under `plan/evidence/` are not kept (image not kept).

# 14 – Release notes 2.0.0 and the GitHub comments (draft, nothing posted)

Not published. `gh` is not installed on this machine and posting to GitHub, tagging and pushing are outward facing, so they wait for an explicit go-ahead. This file is what would be used.

## Release notes (for `CHANGELOG`, the GitHub release and the extension listing)

### Elections 2.0.0
Requires **CiviCRM 6.16 or later**, verified up to 6.19.4. Works on WordPress, Drupal 11 and Joomla; tested on each (WordPress Twenty Twenty-Five, Drupal Olivero, Joomla Cassiopeia).

**For voters and candidates**
- A new election page: a stage stepper, one clear "what you can do now" panel and button, "Your nominations", "Your vote", election cards on the list.
- A redesigned ballot: ranks checked as you choose them, a review step before the vote is saved, works on a phone, dark mode and colour variations follow the theme.
- Candidate statements (with an optional link), photos can be hidden, candidates can be shown by nickname or another name format.
- Elections can be counted **by ranked choice or by most votes** (tick up to as many as there are seats).
- **Write-ins**: where a position allows it, a voter names a person; the person is told, must accept before voting closes, and then appears on the ballot like any other candidate.
- **Referendum questions**: yes or no questions on the same ballot, with a pass threshold and an optional quorum.
- Ties are shown honestly: every tied candidate is listed and none is chosen over another.

**For administrators**
- Manage Elections, Positions, Candidates, Results and Audit log screens built on SearchKit, with drag and drop ordering, duplicate, inline editing and the rules enforced however a change is made.
- The election form has tabs and checks dates as you type. The overview shows the stage, timeline, turnout (counts only), integrity and recent changes.
- Separate groups for **who may nominate** and **who may stand**, on top of who may vote.
- Elections where the organisers choose the candidates can **turn online nominating off**.
- Optional **emails** (editable message templates): nominees are told when they can stand, people written in are invited and reminded.
- Administrators can add candidates and correct statements; the nominee search shows names only.

**Trust and security** (see `plan/12-security-review.md`)
- Every output is escaped (names, statements, links); open redirects and referrer leaks closed.
- One ballot per member and one nominator count per person are enforced by the database; ballots can be added only by the ballot, only while voting is open, and never edited.
- Results are generated once, under a lock, after voting ends, and cannot be written or reset afterwards.
- A hash-chained audit log, ballot and result fingerprints with an integrity check, anonymising that removes the voter, the time and the order.
- Settings that decide who votes and how votes count are locked once people have voted. Contacts in a running election cannot be deleted; merges move their records.

### Upgrading from 1.x
- Install 2.0.0 over the old version and run the database upgrade (Administer, System Settings, Update Check / `cv ext:upgrade-db`). Steps 20000 to 20080 add the new columns and tables, remove duplicate ballot and nominator rows (keeping the oldest), add unique keys and create the audit log.
- Existing elections behave as before: ranked choice, the voting groups for every role, no emails, online nominating on, no write-ins, no questions.
- Results generated before 2.0 show that their integrity cannot be checked (they have no fingerprint); this is expected.
- Behaviour that is now stricter: ballots and results cannot be edited through the API, rules cannot change once people have voted, a contact who took part in a running election cannot be deleted, the results are generated once.
- The upgrade was tested from 1.3.2 to this release on WordPress, Drupal and Joomla with elections in each stage (`tests/upgrade/upgrade-from-v1.sh`).

## Draft comments, one for each issue (to post when the release is tagged)

- **#43 Need for Candidate Statements** – Candidates now have a statement (with an optional link to a longer statement or CV). It is written when they accept, shown on the ballot and nominations as the election chooses (not shown, expandable, or in full), and administrators can read and correct it on the Candidates screen. Released in 2.0.0.
- **#45 Display candidate comments on ballot** – Each election chooses how statements are shown on the ballot: not at all, an expandable "Read statement", or in full. Released in 2.0.0.
- **#44 Don't display photos** – Each election can switch photos off for nominations, ballots, candidate pages and results. Released in 2.0.0.
- **#42 Use of Nick Name** – Each election chooses how candidate names are shown (including nickname formats); the site setting is used when it is not set. Names are escaped, so no markup is needed. Released in 2.0.0.
- **#46 Wording/Grammar?** – The ballot and the other screens were reworded and every string is translatable. Released in 2.0.0.
- **#28 How to "Second" a nomination?** – Documented, and the election page now says what you can do now, including seconding. An election can hide who nominated whom, and an election where the organisers choose the candidates can turn online nominating off. Released in 2.0.0.
- **#3 Accepting nominations** – Split into parts, all in 2.0.0: candidate statements, no photos, administrators add and edit candidates, **plurality (most votes) voting**, and **write-ins** (the person is notified and must accept before voting closes).
- **#4 Nominations process feature request** – There are now separate groups for who may nominate and second, who may stand, and who may vote. Empty means the voting groups, so existing elections are unchanged. Released in 2.0.0.
- **#5 Referenda** – A position can be a yes or no question with renamable wording, a pass threshold (simple majority, two thirds, three quarters or any percentage), an optional quorum and the resolution text. Released in 2.0.0.
- **#27 Adding custom fields to the Nomination entity?** – The need behind this was the candidate statement, which is now a proper field with a link. Released in 2.0.0.
- **#29 Accept/Reject Nomination?** – Documented, and nominees are now emailed (editable message templates) when they have enough nominations to stand and must accept or withdraw. Released in 2.0.0.

Each commit message is short and descriptive; relating commits to issues is by the table in [03-issues-analysis.md](03-issues-analysis.md).
