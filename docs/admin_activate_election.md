# How to activate or deactivate an election

A new election is always added **inactive**. An inactive election is never shown to end users, regardless of its **Visible from** and **Visible until** dates, so it must be activated before people can see it, nominate, accept nominations or vote.

To activate an election, you need to have CiviCRM admin access and follow these steps:

1. Go to **Elections** ([Manage Elections](admin_manage_elections.md)).
2. At least one position must already exist for the election - see [How to add a position](admin_add_position.md). If no position has been added yet, the message "Add at least one position before activating the election." is shown and the election stays inactive.
3. Find the election in the list. The **Active** column shows **Yes** or **No**, and the **Stage** column shows **Inactive** for an election that is not active.
4. Select **Activate** on the election's row, or **Activate** on its [overview](admin_election_overview.md). A dialog titled **Activate Election** says that the election will be visible to people from its **Visible from** date until its **Visible until** date. Select **Activate** to confirm, or **Cancel**. The message "Election activated." is shown.

To deactivate an election again before it starts, for example to make further changes or to temporarily hide it, select **Deactivate** on the election's row (or on its overview) and confirm in the **Deactivate Election** dialog. Deactivating an election immediately hides it from end users, even if it is within its **Visible from** and **Visible until** dates. The message "Election deactivated." is shown.

Once an election has started while it is active it is locked, and **Deactivate** is the way to pull it from view. The dialog says that the election is active and its nominations have started, and asks you to type the election's name in **Type the name of the election to confirm**. The election is then hidden from everyone except administrators and can be edited again, and the change is written to the [audit log](reference_security_and_audit.md).

Note that once an election has started (its **Nominations open** date has passed) while it is active, it can no longer be edited or deleted - see [How to edit an election](admin_edit_election.md) and [How to delete an election](admin_delete_election.md). Editing an election does not change whether it is active.
