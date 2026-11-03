# Elections Settings

The Elections extension provides a single, site-wide settings page which applies to every election (the page says "These settings apply to every election."). It is separate from the settings of an individual election (see [How to add a new election](admin_create_election.md)).

To access it, you need CiviCRM admin access and follow these steps:

1. Go to **Administer > System Settings > Elections Settings**, or browse directly to `civicrm/admin/setting/elections`.
2. Set **Image source** to control where candidate and nominee photos are sourced from:
   - **CMS**: uses the profile photo from the website's CMS user account (falling back to a Gravatar, based on the contact's email address, when no CMS photo is available).
   - **Contact image**: uses the image on the CiviCRM contact record.
3. Set **Candidate name format** to control how candidate names are written on nominations, ballots and results: the contact's display name (for example *Robert Irwin*), **First "Nickname" Last** (*Robert "Rob" Irwin*) or **Nickname Last** (*Rob Irwin*). People without a nickname always show their display name. Each election can override this in its own [display options](admin_create_election.md).
4. Select **Submit**. **Back** and **Back to Manage Elections** next to it leave the page without saving.

The selected image source is used wherever a candidate or nominee's photo is displayed, including the nominations list, candidate profile pages and election results.
