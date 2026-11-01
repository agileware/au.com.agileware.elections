# Election overview

Select an election's name in [Manage Elections](admin_manage_elections.md) to see it at a glance. The page has a breadcrumb (**Manage Elections** > the election's name), the election's name as the heading with "Election Overview" as a small label above it, and a row of buttons at the top:

- **Back** and **Back to Manage Elections**.
- **Edit Election** - shown disabled, with the reason, once the election is active and its nominations have started. See [How to edit an election](admin_edit_election.md).
- **Positions**, **Candidates**, **Results Page**, **Results** (only once the results are calculated) and **Audit Log**.
- **View as a Voter**.
- **Activate** or **Deactivate**, depending on whether the election is active. See [How to activate or deactivate an election](admin_activate_election.md).

There is no **Duplicate** or **Delete** button here: use the row in [Manage Elections](admin_manage_elections.md).

Below the buttons the page shows:

- **Stage**: Inactive, Not started, Nominations open, Nominations closed, Candidates advertised, Voting open, Voting closed or Results published.
- **Next**: what to do next, in plain words, for example "Add at least one position, then activate the election so people can see it."
- "Times are shown in" followed by the site's time zone. Dates follow the date format set in CiviCRM.
- **Timeline**: every date of the election (**Visible from**, **Nominations open**, **Nominations close**, **Candidates advertised**, **Voting opens**, **Voting closes**, **Results published**, **Visible until**), with the ones that have passed ticked.
- **Numbers**: positions, nominations, nominees with enough nominations, candidates standing (those who accepted) and ballots cast.
- **People written in** (only when someone has been written in - see [Write-ins](admin_write_ins.md)): each person's position and status (Waiting for an answer, Accepted, Declined, Withdrawn, or Did not answer before voting closed). It never shows who wrote someone in. When write-ins are allowed but people cannot change their vote, a warning says so.
- **Who can take part** (only when the election has its own nominating or candidate groups): how many people may vote, may nominate and second, and may stand as candidates.
- **Turnout** (once voting has started): the ballots cast out of the people allowed to vote, and for each position how many candidates are standing and how many ballots had a choice for it. Only counts are shown, never who voted for whom. When the election's votes are anonymised, the number of ballots comes from the Vote activities and the count for each position is not shown ("Not shown because votes are anonymised.").
- **Audit log integrity**: whether the audit log, ballots and results are exactly as they were. See [Security and audit](reference_security_and_audit.md).
- **Audit log**: the **Audit log fingerprint**, shown in full with a **Copy** button (it changes to **Copied** once the fingerprint is on the clipboard), and the most recent entries with **When**, **Who** and **What**, in words such as "Election changed". Note the fingerprint down somewhere outside the website, to be able to show later that nothing was removed from the log.
