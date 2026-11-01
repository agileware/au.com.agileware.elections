# Manage Elections

**Elections** in the CiviCRM menu opens the **Manage Elections** list. Staff need the CiviCRM permissions **access CiviCRM** and **administer Elections**.

The list shows every election that has not been deleted: its name, whether it is **Active**, its **Stage**, the dates **Nominations open**, **Voting opens**, **Voting closes** and **Results published**, and how many positions it has. The page says "Times are shown in" followed by the site's time zone, and dates follow the date format set in CiviCRM. The list shows 50 elections a page.

An election is **locked** once it is active and its nominations have started. A locked election cannot be edited or deleted and its positions cannot be changed, so **Edit** and **Delete** are not shown on its row. The **Edit and delete** column says "Locked: nominations have started." instead.

For each election you can:

- **Select the election name** to open its [overview](admin_election_overview.md).
- **Select the number of positions** to open its **Positions** - see [How to add a position](admin_add_position.md) and [How to reorder positions](admin_reorder_positions.md). Its **Candidates** are opened from the overview - see [Candidates](admin_candidates.md).
- **Edit** its settings (only before it starts) - see [How to edit an election](admin_edit_election.md).
- **Duplicate** it - see [How to duplicate an election](admin_duplicate_election.md).
- **Activate** it, or **Deactivate** it (a confirmation pops up) - see [How to activate or deactivate an election](admin_activate_election.md). Only one of the two is shown, depending on whether the election is active. Deactivating an election that has started asks you to type the election's name to confirm; the election is hidden from everyone except administrators, and the change is written to the audit log.
- **Delete** it - see [How to delete an election](admin_delete_election.md).

**Add Election** starts a new election - see [How to add a new election](admin_create_election.md).

Use the filters above the list to find an election. Each filter has a short explanation under it, and **Clear filters** resets them:

- **Election**: part of the election's name.
- **Active**: Yes shows active elections. No shows inactive elections.
- **Stage**: where the election is now: Inactive, Not started, Nominations open, Nominations closed, Candidates advertised, Voting open, Voting closed or Results published.
- **Voting closes**: elections whose voting closes in a period. Pick a ready-made period such as This month or Next 7 days, or enter your own dates.
- **Results published**: elections whose results are published in a period.

The list can be sorted by selecting a column heading. When no election matches, the list says "No elections found. Clear the filters, or select Add Election to create one."
