# How to activate or deactivate an election

A new election is always created **inactive**. An inactive election is never shown to end users, regardless of its Visible Start/End Dates, so it must be activated before people can see it, nominate, accept nominations or vote.

To activate an election, you need to have CiviCRM admin access and follow these steps:

1. Go to **Elections** ([Manage Elections](admin_manage_elections.md)).
2. At least one Position must already exist for the election - see [How to add a position](admin_add_position.md). If no Position has been added yet, the message "At least one Position must be defined before the Election can be active." is shown and the election stays inactive.
3. Find the election in the list. The **Active** column shows **Yes** or **No**.
4. Click **Activate** next to the election and confirm.

To deactivate an election again, for example to make further changes or to temporarily hide it, click **Deactivate** next to an active election and confirm. Deactivating an election immediately hides it from end users, even if it is within its Visible Start/End Dates.

Note that once an election has started (its Nomination Start Date has passed) while it is active, it can no longer be edited or deleted - see [How to edit an election](admin_edit_election.md) and [How to delete an election](admin_delete_election.md).
