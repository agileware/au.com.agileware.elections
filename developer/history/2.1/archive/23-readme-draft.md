> **Historical.** Written during version 2.1; may be out of date. Current guidance: [Version 2.1 summary](../../../history/2.1/README.md).

Covers: version 2.1, the draft of the new README opening, 2026-11-03. Copied from the project plan with names, paths and account names removed; screenshots under `plan/evidence/` are not kept (image not kept).

# 23 – Draft: new opening of the extension README (for the maintainer's review)

**Status: approved by the maintainer and committed to the extension on 2026-11-03 (`docs/README.md`, pushed to pull request #49).** It replaces everything in `docs/README.md` before the "Prerequisites" heading (the old introduction, the two lists of capabilities, the paragraph about activating an election, and the results picture). Everything from "Prerequisites" down stays as it is. Once approved it is committed on `version-2.1` (pull request #49).

Notes for the review are at the end. The draft text starts below the line.

---

**Elections** (au.com.agileware.elections) turns your CiviCRM into a place where your organisation can hold real elections: nominate people, put them on a ballot, let your members vote, and publish the results, with no spreadsheets, no separate voting service and no export of your member list to a third party. Your people are already in CiviCRM, so the election already knows who they are, which groups they belong to, and how to reach them.

It runs on **WordPress, Drupal and Joomla**, works on a phone, and is built to be used by everyone: keyboard and screen reader friendly, with light and dark appearance.

This page is a tour of what is possible. The **[documentation](https://github.com/agileware/au.com.agileware.elections/blob/master/docs/index.md)** is the full guide, with step by step instructions, diagrams and every option explained: it is worth reading before you set up your first election.

What you can run with it
------------------------

Anything where a group of people chooses someone, or says yes or no, on your CiviCRM site:

  * **Committee and board elections.** President, Secretary, Treasurer and three committee members, each with its own candidates, on one ballot.
  * **Annual general meetings.** Positions and yes or no questions together: a motion to change the constitution, to raise fees, or to allow proxy votes, with a pass threshold and a quorum.
  * **Volunteer roles.** Let members nominate each other, or put themselves forward, and have the nominee accept before they appear on the ballot.
  * **Regional, state or chapter delegates.** Give each region its own election and use a CiviCRM group, or a smart group built from addresses, so only the right people see it and vote.
  * **Award and grants panels.** Turn online nominating off and let the organisers choose the candidates, or use one group to nominate and a different group to be eligible to stand.
  * **Show and event picks.** Use "most votes win" for people's choice, steward or stallholder selections where each voter ticks the people they like.
  * **Ballots at any time of year.** Run several elections at once, each with its own dates and its own groups, and keep the results as a record.

How an election works
---------------------

1. **Set it up.** Choose the dates, who can take part, how votes are counted and what voters see. A quick set-up fills in a sensible timetable for you.
1. **Add the positions and questions.** Any number, in any order, with the seats to be filled and, if you want, write-ins.
1. **Nominations.** People nominate others or themselves. A nominee who has enough nominations, and seconds if you require them, is invited by email to accept or decline, and can add a statement and a link.
1. **Voting.** Voters see the candidates, their statements and photos, rank or tick their choices, review the ballot and submit it. If you allow it, they can change their vote until voting closes.
1. **Results.** On the date you set, the winners are published, with how each was decided, and kept as a historical record.

Everything an election does follows the stage it is in, so organisers and members always see where it is and what they can do next.

What you can configure
----------------------

Each election has its own settings, so a large annual election and a small workshop vote can sit side by side.

**Who takes part**

  * Separate groups for who may **vote**, who may **nominate** and who may **stand**, using CiviCRM Groups and Smart Groups. Anything you can express as a group becomes an electorate: country, region, membership type, committee, or any other criterion.
  * No CiviCRM Membership is needed: being in one of the election's groups is what counts.
  * People who are not logged in can take part with a **personal link** sent by email, if you allow it.

**How people get on the ballot**

  * Members nominate each other and themselves, or only the organisers choose the candidates.
  * The number of nominations, and seconds, a person needs before they can stand.
  * Nominees accept or decline, and candidates write a statement with an optional link to a longer statement or CV, which they can correct until voting ends.
  * Optional **write-ins**: voters add a person who is not listed, who is then invited to accept before voting closes.

**How votes are cast and counted**

  * **Ranked choice** (instant-runoff) or **most votes win**.
  * One or more seats for each position.
  * **Yes or no questions** (referendum questions) on the same ballot, with your own wording (For and Against, Approve and Reject), an optional Abstain, a pass threshold from a simple majority to two thirds or any percentage, a quorum, and the full resolution text.
  * Allow or disallow changing a vote.

**What people see**

  * Photos, written statements on the ballot or a click away, who nominated each candidate, and how names are shown, including nicknames.
  * A stage stepper and a single "what you can do now" button on each election.
  * A results page of your own, with a header, a footer, a logo, and an optional celebration when the winners appear.
  * Emails to nominees and people written in, using message templates you can edit in CiviCRM.

**Trust and privacy**

  * **Anonymise votes** when the results are generated, so nobody, including administrators, can tell who voted for whom.
  * One ballot for each person, enforced by the database. Results are generated once.
  * Results show winners, not counts, and a tie is shown as a tie.
  * A tamper-evident **audit log** with a fingerprint you can write down, and an integrity check on the election overview.
  * The settings that decide who votes and how votes count are locked once ballots exist.

**Running it**

  * Manage elections, positions, candidates, results and the audit log from searchable lists, with drag and drop ordering, duplicate, and activate or deactivate in one click.
  * An overview of each election: stage, timeline, turnout and integrity.
  * **Duplicate** last year's election to start this year's with the same positions.
  * Add it to a WordPress page with a short code, or use the pages CiviCRM provides on Drupal and Joomla.

Read the documentation
----------------------

The [documentation](https://github.com/agileware/au.com.agileware.elections/blob/master/docs/index.md) explains every screen and option in plain language, with workflow diagrams for each kind of election. Start with the first two chapters, then follow the third to set up your first election:

  * **Introduction**: [types of elections and options](https://github.com/agileware/au.com.agileware.elections/blob/master/docs/election_types.md) (positions and questions, ranked choice and most votes, who can take part and how people get on the ballot) and [workflow diagrams](https://github.com/agileware/au.com.agileware.elections/blob/master/docs/workflows.md) of the whole election lifecycle.
  * **Set up your site**: [initial set up](https://github.com/agileware/au.com.agileware.elections/blob/master/docs/setup.md) (scheduled jobs, permissions, personal links, the pages to create on WordPress, Drupal and Joomla), [roles and permissions](https://github.com/agileware/au.com.agileware.elections/blob/master/docs/roles_and_permissions.md) and the [site-wide settings](https://github.com/agileware/au.com.agileware.elections/blob/master/docs/admin_election_settings.md).
  * **Running an election**: [manage elections](https://github.com/agileware/au.com.agileware.elections/blob/master/docs/admin_manage_elections.md), [add an election](https://github.com/agileware/au.com.agileware.elections/blob/master/docs/admin_create_election.md), add [positions](https://github.com/agileware/au.com.agileware.elections/blob/master/docs/admin_add_position.md), [write-ins](https://github.com/agileware/au.com.agileware.elections/blob/master/docs/admin_write_ins.md) and [referendum questions](https://github.com/agileware/au.com.agileware.elections/blob/master/docs/admin_referendum.md), then [activate](https://github.com/agileware/au.com.agileware.elections/blob/master/docs/admin_activate_election.md) it, shape the [results page](https://github.com/agileware/au.com.agileware.elections/blob/master/docs/admin_results_page.md) and follow it through on the [election overview](https://github.com/agileware/au.com.agileware.elections/blob/master/docs/admin_election_overview.md).
  * **Taking part**: how people [nominate someone](https://github.com/agileware/au.com.agileware.elections/blob/master/docs/user_nominate.md), [accept a nomination](https://github.com/agileware/au.com.agileware.elections/blob/master/docs/user_accept_nomination.md), [vote](https://github.com/agileware/au.com.agileware.elections/blob/master/docs/user_vote.md) and [view the results](https://github.com/agileware/au.com.agileware.elections/blob/master/docs/user_view_results.md).
  * **Reference**: [emails and scheduled jobs](https://github.com/agileware/au.com.agileware.elections/blob/master/docs/reference_emails_and_jobs.md), and [security and audit](https://github.com/agileware/au.com.agileware.elections/blob/master/docs/reference_security_and_audit.md) (the guarantees, the audit log and the integrity check).

Elections is based on the voting features first built into [MemberDB](https://www.flamingspork.com/projects/memberdb/).

Viewing election results are shown below as an example.

![Elections](https://github.com/agileware/au.com.agileware.elections/raw/master/docs/images/user_view_results/03.gif)  

---

## Notes for the review

0. **Changes after the first review (2026-11-03):** added a one-line pointer to the documentation in the introduction and a "Read the documentation" section with a table of contents of the top-level pages (the five chapters of `docs/index.md`); removed the paragraph "A new election is added inactive...". All links are absolute GitHub links to `master`, like the rest of the README, so they work from GitHub and the CiviCRM extension directory; every page linked already exists on `master` (the 2.1 text of those pages arrives when pull request #49 is merged).

1. **File.** The README that GitHub shows is `docs/README.md` (the repository has no `README.md` at its root). Only the part above "Prerequisites" changes.
2. **Facts checked against the extension** (nothing promised that it does not do): the three CMSs; separate voting, nominating and candidate groups; smart groups; personal links; self nomination and seconds; nominee accept or decline with a statement and link; write-ins; ranked choice and most votes; seats; yes or no questions with wording, abstain, thresholds, quorum and resolution text; anonymise votes; one ballot per person enforced by the database; results generated once and showing winners not counts; ties shown honestly; audit log, fingerprint and integrity check; settings locked once ballots exist; the stage stepper; the results page header, footer, logo and celebration; editable email templates; duplicate; accessibility (keyboard, screen reader, phone, dark mode).
3. **Examples are ideas, not claims of legal fitness.** The use cases are the kinds of election the seeded test site already runs (committee, honey show stewards, award panel, regional delegates, constitution amendments, annual general meeting, grants committee). The text does not say the extension meets any law or constitution for binding votes.
4. **The picture** is the old 2.0 results screen. It stays until the docs pictures are re-recorded (Version 2.2); say if you would rather remove it from the README now.
5. **Old text dropped or merged:** the numbered list of seven points and the paragraph about CiviCRM Groups are folded into the new sections; "IRV" is now "ranked choice (instant-runoff)"; the line crediting MemberDB is kept.
6. **Length.** The new opening is about four times as long as the old one. The section headings use the underline style the rest of the README uses.
