# Security and audit

The extension is built so that the result of an election can be trusted: nobody can change the outcome, see how someone voted, vote when they should not, or vote twice, and a change made behind the extension's back can be found.

## What the extension guarantees

- **One ballot per member.** Enforced by the database as well as the code. A double click or two requests at once leave one ballot.
- **Ballots go through one door.** Ballot rows can only be written by the voting process, while voting is open, after the checks on who may vote and who may be voted for. They cannot be edited, and cannot be added or removed through the API after voting closes.
- **Who is standing is fixed once voting starts.** Settings, positions and candidates are frozen, apart from write-ins, which have their own protected process. Settings such as who may vote, anonymising and changing votes are locked once the first ballot exists.
- **Results are written once.** They are generated after voting has ended, in one step, and cannot be edited or regenerated.
- **Ballot secrecy.** Anonymised votes lose the voter, the time and the order of the rows, so they cannot be matched to the Vote activity that records who voted. A write-in leaves nothing that shows who wrote the person in. Counts only are shown on the overview, never who voted for whom.
- **Contacts in a running election cannot be deleted**, and merging contacts moves their election records to the contact that is kept.
- **Output is escaped** on every page, and web addresses in statements and photos must be http(s).

## The audit log

Every important change is written to a log in which each entry carries the hash of the one before it, so an entry edited or removed in the middle is found. It records changes to election settings, positions, nominations, results generation, anonymising, and ballots removed by someone other than the voter. It never records how anyone voted.

Open it from the election overview (**Audit log**), or at `civicrm/elections/audit-log`. It is read-only.

## The integrity check

When the results are generated, a fingerprint of the ballots and of the results is written to the audit log. The election overview checks them and shows **Integrity**: *ok*, *changed* (something was altered after the results), or *cannot be checked* for results generated before this feature.

When you publish the results, note the **audit log fingerprint** shown on the overview somewhere outside the website. It proves later that nothing was cut off the end of the log.

## What the extension cannot prevent

- Someone who can write to the database can change rows. The extension makes that detectable (fingerprints, audit chain). Keep database access to few people and keep backups.
- A personal link is a key: whoever has it can act as that person until it expires. Keep the checksum timeout short, and send links only to the person.
- The Vote activity records that a person voted and when, which is how turnout is shown.
- The server clock and time zone decide which stage the election is in.

The full review is kept with the project plans (security review, findings 1 to 20).
