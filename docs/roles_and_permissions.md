# Roles and permissions

Each person who works with an election needs a different set of CiviCRM permissions. This page lists what each role needs, how to set it up on WordPress, Drupal and Joomla, and what goes wrong when a permission is missing.

No CiviCRM Membership is needed for any role. What matters is the permissions below and, for voting, being in one of the election's groups.

## The roles

| Role | What they do | CiviCRM permissions they need |
|---|---|---|
| **Election organiser** (for example an Elections Manager) | Creates and runs elections, adds positions and candidates, activates elections, reads the audit log | **access CiviCRM**, **administer Elections**, **access AJAX API**, **view all contacts** (or an ACL that lets them view the groups and the people in them) |
| **Organiser who also edits the emails** | The same, plus changes the wording of the election emails | The organiser permissions plus **edit message templates** |
| **Site administrator** | Sets up the site, the Elections Settings page, roles and permissions | **administer CiviCRM** (this is what the *Elections Settings* page needs; the *Elections* menu does not need it) |
| **Person taking part** (voter, nominator, seconder, nominee, candidate) | Nominates, accepts or withdraws, votes, reads the results | **view Elections**, **access AJAX API**, **access uploaded files**, **profile view**, **profile edit**, **access all custom data** |
| **Person using a personal link** | The same as a person taking part, without logging in | None. The election must have *Allow non-logged in access* ticked and the link must be valid. See [Participating without logging in](setup.md#participating-without-logging-in) |
| **Results reader** | Reads published results | The same as a person taking part |

A person can hold more than one role, for example an organiser who also votes.

## Why an organiser needs View All Contacts

On the election form, **Allowed by Groups**, **Nomination Groups** and **Candidate Groups** list the CiviCRM groups. CiviCRM only shows a person the groups it lets them see, and it does that through contact permissions. A person with **administer Elections** but without **view all contacts** (or an ACL) therefore sees an empty list, and CiviCRM's own *Manage Groups* page says "None found". Finding people to add as candidates is limited in the same way.

What you will see when it is missing: the election form shows a notice above the group fields ("You cannot see any groups...") and the group fields stay empty. The fix is to give the role **view all contacts** (or an ACL that allows the groups and their contacts to be viewed), then reload the page. The group lists are the same ones a site administrator sees.

## Setting the permissions

### WordPress

1. In CiviCRM open **Administer > Users and Permissions > WordPress Access Control** (`civicrm/admin/access/wp-permissions`).
2. Find the WordPress role (for example *Elections Manager*) and tick the permissions from the table above. On this page they are written like **CiviCRM: view all contacts** and **CiviCRM: view Elections**.
3. The role also needs to be allowed to open the CiviCRM menu in the WordPress admin, which **access CiviCRM** does.

### Drupal

Open **People > Permissions**, find the permissions under CiviCRM and tick them for the role. The permission names are those in the table.

### Joomla

Open **Users > Access Levels** and **Users > Groups**, then set the CiviCRM permissions for the group (the permission names are those in the table).

## Checking a role

Log in as someone with only that role and open:

| Page | Needs |
|---|---|
| **Elections** in the CiviCRM menu (Manage Elections), Positions, Candidates, Election overview, Audit log | access CiviCRM and administer Elections |
| Create or edit an election (the group fields) | the above, plus access AJAX API and view all contacts |
| Add a candidate (finding the person) | the above |
| Elections Settings | administer CiviCRM |
| Election list, election page, nominate, accept, withdraw, vote, results | view Elections (or a valid personal link) |

If a page says you do not have permission, or a list is empty, compare the role with this table first.
