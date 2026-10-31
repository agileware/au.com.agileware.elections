# Manage Elections

**Elections** in the CiviCRM menu opens the **Manage Elections** list. Staff need the CiviCRM permissions **access CiviCRM** and **administer Elections**.

The list shows every election that has not been deleted: its name, whether it is **Active**, its **Stage**, the dates nominations open, voting opens and closes and results are published, and how many positions it has.

An election is **locked** once it is active and its nominations have started. A locked election cannot be edited or deleted and its positions cannot be changed, so those buttons are not shown on its row.

For each election you can:

- **Select the election name** to open its [overview](admin_election_overview.md).
- **Activate** it, or **Deactivate** it while it has not started (a confirmation pops up) - see [How to activate or deactivate an election](admin_activate_election.md).
- Open its **Positions** - see [How to add a position](admin_add_position.md) and [How to reorder positions](admin_reorder_positions.md).
- Open its **Candidates** - see [Candidates](admin_candidates.md).
- **Withdraw** a locked election if it must be pulled from view. You type the election's name to confirm, the election is hidden from everyone except administrators, and the withdrawal is written to the audit log.
- **Edit** its settings (only before it starts) - see [How to edit an election](admin_edit_election.md).
- **Duplicate** it - see [How to duplicate an election](admin_duplicate_election.md).
- **Delete** it - see [How to delete an election](admin_delete_election.md).

**Create Election** starts a new election - see [How to create a new election](admin_create_election.md).

Use the filters above the list to find an election. Each filter has a short explanation under it:

- **Election**: part of the election's name.
- **Active**: elections switched on for voters, or still being set up or switched off.
- **Stage**: Not running (switched off, or nominations have not started), Nominations open, Candidates announced, Voting open, Voting closed or Results published.
- **Voting closes**: elections whose voting closes in a period. Pick a ready-made period such as This month or Next 7 days, or enter your own dates.
- **Results**: elections whose results are published in a period.

The list can be sorted by selecting a column heading.
