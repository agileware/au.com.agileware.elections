# Elections Settings

The Elections extension provides a single, site-wide settings page which applies to every election. It is separate from the settings of an individual election (see [How to create a new election](admin_create_election.md)).

To access it, you need CiviCRM admin access and follow these steps:

1. Go to **Administer > System Settings > Elections Settings**, or browse directly to `civicrm/admin/setting/elections`.
2. Set **Image Source** to control where candidate and nominee photos are sourced from:
   - **CMS**: uses the profile photo from the website's CMS user account (falling back to a Gravatar, based on the contact's email address, when no CMS photo is available).
   - **Contact Image**: uses the image on the CiviCRM contact record.
3. Set **Candidate Name Format** to control how candidate names are written on nominations, ballots and results: the contact's display name (for example *Edward Smith*), **First "Nickname" Last** (*Edward "Butch" Smith*) or **Nickname Last** (*Butch Smith*). Contacts without a nickname always show their display name. Each election can override this in its own [display options](admin_create_election.md).
4. Click **Save**.

The selected Image Source is used wherever a candidate or nominee's photo is displayed, including the nominations list, candidate profile pages and election results.
