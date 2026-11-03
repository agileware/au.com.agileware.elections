# How to reorder positions

The order in which positions are listed for nomination, voting and results can be changed while the election is inactive, or before nominations start.

To reorder positions, you need to have CiviCRM admin access and follow these steps:

1. Go to **Elections** and select the number of positions on the election's row (or **Positions** on its overview).
2. Drag the handle at the start of a row ("Drag to reorder") to where you want the position. The other positions move to make room and the new order is saved as soon as you drop the row. To use the keyboard, use the **Move up** and **Move down** buttons on the row.

Positions can also be renamed, or have their seats, write-ins per seat or description changed, by selecting the value in the list. A position can be edited, **duplicated** (the copy goes to the end of the list) or **deleted** from the buttons on its row. Positions can only be changed before the election starts.

When the election is **locked** (active and its nominations have started) its positions cannot be changed. **Positions** then opens a read-only list that says the positions cannot be changed because the election is locked, with the **Add Position** button disabled (its reason is shown on hover and read out to screen readers), no drag handles and no buttons on the rows. If a change is attempted anyway, for example from a page that was left open while the election started, the message says "This election is active, so its positions cannot be changed. Positions can be changed only before nominations start, or while the election is inactive."

The **Order on the ballot** field on the [Add Position](admin_add_position.md) and [Edit Position](admin_edit_position.md) forms can also be used to set a position's order directly.
