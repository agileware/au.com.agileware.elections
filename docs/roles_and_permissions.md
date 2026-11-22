# Roles and permissions

Each person who works with an election needs a different set of CiviCRM permissions. This page lists what each role needs, how to set it up on WordPress, Drupal and Joomla, and what goes wrong when a permission is missing.

No CiviCRM Membership is needed for any role. What matters is the permissions below and, for voting, being in one of the election's groups.

## The roles

| Role | What they do | CiviCRM permissions they need |
|---|---|---|
| **Election organiser** (for example an Elections Manager) | Adds and runs elections, adds positions and candidates, activates and deactivates elections, reads the audit log | **access CiviCRM**, **administer Elections**, **access AJAX API**, **view all contacts** (or an ACL that lets them view the groups and the people in them) |
| **Candidates manager** (for example a returning officer) | Adds candidates to elections and edits their statements, without setting up, changing, activating or deleting elections. Opens **Election Candidates** in the CiviCRM menu, chooses an election, then uses **Add Candidate** and **Edit Statement** | **access CiviCRM**, **manage Election candidates**, **access AJAX API**, **view all contacts** (or an ACL that lets them view the candidate groups and the people in them) |
| **Organiser who also edits the emails** | The same, plus changes the wording of the election emails | The organiser permissions plus **edit message templates** |
| **Site administrator** | Sets up the site, the Elections Settings page, roles and permissions | **administer CiviCRM** (this is what the *Elections Settings* page needs; the *Elections* menu does not need it) |
| **Person taking part** (voter, nominator, seconder, nominee, candidate) | Nominates, accepts, declines or withdraws, votes, reads the results | **view Elections**, **access AJAX API**, **access uploaded files**, **profile view**, **profile edit**, **access all custom data** |
| **Person using a personal link** | The same as a person taking part, without logging in | None. The election must have *Allow people to take part without logging in* ticked and the link must be valid. See [Participating without logging in](setup.md#participating-without-logging-in) |
| **Results reader** | Reads published results | The same as a person taking part |

A person can hold more than one role, for example an organiser who also votes.

## Why an organiser needs View All Contacts

On the election form, **Voting groups**, **Nominating groups** and **Candidate groups** (on the **Participation** tab) list the CiviCRM groups. CiviCRM only shows a person the groups it lets them see, and it does that through contact permissions. A person with **administer Elections** but without **view all contacts** (or an ACL) therefore sees an empty list, and CiviCRM's own *Manage Groups* page says "None found". Finding people to add as candidates is limited in the same way.

What you will see when it is missing: the election form shows a notice above the group fields ("You cannot see any groups.") and the group fields stay empty. The fix is to give the role **view all contacts** (or an ACL that allows the groups and their contacts to be viewed), then reload the page. The group lists are the same ones a site administrator sees.

## Who can see the results

Results are shown from **Results published on**, once the votes have been counted. From then on, anyone who can open the election can see them. That means people with the **View Elections** permission, and people on a personal link where the election allows personal links. The results are not limited to the people who voted or were eligible to vote.

Until the results are published, organisers can preview them on the election page and in the back end; everyone else sees where the election is up to. After **Visible until** the election and its results are hidden from everyone except organisers.

There is no setting to show the results to the people who took part only. To keep an election's results from people, do not give them the **View Elections** permission or a personal link.

## Setting the permissions

### WordPress

1. In CiviCRM open **Administer > Users and Permissions > WordPress Access Control** (`civicrm/admin/access/wp-permissions`).
2. Find the WordPress role (for example *Elections Manager*) and tick the permissions from the table above. On this page they are written like **CiviCRM: view all contacts** and **CiviCRM: view Elections**.
3. The role also needs to be allowed to open the CiviCRM menu in the WordPress admin, which **access CiviCRM** does.

### Drupal

Open **People > Permissions**, find the permissions under CiviCRM and tick them for the role. The permission names are those in the table.

### Joomla

Open **Users > Access Levels** and **Users > Groups**, then set the CiviCRM permissions for the group (the permission names are those in the table). A group that works in the CiviCRM back end (an organiser or a candidates manager) must also be allowed to log in to the Joomla administrator and to open CiviCRM there. A permission added by an extension, such as **manage Election candidates**, may not be listed on CiviCRM's permissions screen in Joomla; it still works when it is given to the group.

## Checking a role

Log in as someone with only that role and open:

| Page | Needs |
|---|---|
| **Elections** in the CiviCRM menu (Manage Elections), Positions, Candidates, Results, Election overview, Audit Log | access CiviCRM and administer Elections |
| Add or edit an election (the group fields) | the above, plus access AJAX API and view all contacts |
| Add Candidate (finding the person) | the above |
| **Election Candidates** in the CiviCRM menu, an election's Candidates list, Add Candidate, Edit Statement (and nothing else in the back end) | access CiviCRM and manage Election candidates, plus access AJAX API and view all contacts |
| Elections Settings | administer CiviCRM |
| Election list, election page, nominate, accept, decline, withdraw, vote, results | view Elections (or a valid personal link) |

If a page says you do not have permission, or a list is empty, compare the role with this table first.
