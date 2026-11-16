# Architecture overview

How the pieces of the Elections extension (`au.com.agileware.elections`, release 2.1.0) fit together, and how a request travels through them. The details are in the other pages of this folder:

| Page | Covers |
|---|---|
| [code-map.md](code-map.md) | Every directory and class, and "where do I change X" |
| [data-model.md](data-model.md) | Tables, columns, what is stored and what is derived |
| [upgrades.md](upgrades.md) | `CRM_Elections_Upgrader` steps and how to add one |
| [voting-methods.md](voting-methods.md) | Counting, ballots, anonymising, results |
| [security.md](security.md) | The guarantees and where each is enforced |
| [lists-and-frame.md](lists-and-frame.md) | Back end page frame, SearchKit lists, shared CSS |

## The idea in one paragraph

The extension is a CiviCRM module built with civix. People who take part (voters, nominees, candidates) use ordinary CiviCRM pages and forms that are made public with a personal link or a login; organisers use back end screens, some written as Smarty pages and forms and some as SearchKit lists. The rules that protect an election are **not** in the screens: they are in the data layer (`hook_civicrm_pre` in `elections.php`, which calls `CRM_Elections_Helper_Guard`, the BAO `assertCanChange` methods and the APIv3 `addvotes` action), so they hold whether a change comes from a form, APIv3, APIv4 or an inline edit in a SearchKit list.

## The layers

```
route (xml/Menu, or an Afform server_route)
  -> Page / Form (CRM/Elections/Page, Form)        access checks, assigns variables, draws a template
       -> Helper classes (CRM/Elections/Helper)     rules and presentation logic, no HTML
            -> APIv3 / APIv4 / BAO                   read and write rows
                 -> hook_civicrm_pre / _post         Guard: locks, audit log
                      -> database (schema/*.entityType.php)
```

Business rules live in `Helper` classes (`Process`, `Eligibility`, `Referendum`, `WriteIn`, `Results`, `Guard`) so tests can call them without drawing a page. Pages and forms are thin.

## Request flows

### A back end screen: SearchKit list

Manage Elections, Positions, Candidates, Results and Audit Log.

1. The browser opens an Afform route (`ang/afsearch*.aff.php`, `server_route`, for example `civicrm/elections/manage`). Access needs `access CiviCRM` and `administer Elections`.
2. `elections_civicrm_pageRun` sees a `CRM_Afform_Page_AfformBase` page and calls `CRM_Elections_Helper_Frame::applyToListPage()` for the breadcrumb.
3. The Afform (`ang/afsearch*.aff.html`) holds the action bar and a `crm-search-display-table`. The Angular module `ang/electionsLists.js` completes the table (caption, card layout on a phone, Back link).
4. The table runs the SavedSearch and SearchDisplay from `managed/SavedSearch_*.mgd.php` through APIv4. Computed columns (`stage`, `is_locked`, `nomination_status`, readable audit text) come from the spec providers in `Civi/Api4/Service/Spec/Provider`.
5. Inline edits and drag and drop are APIv4 writes. They reach `hook_civicrm_pre` like any other write, so a locked election refuses them with a `CRM_Elections_Exception` message.

See [lists-and-frame.md](lists-and-frame.md).

### A back end screen: Smarty page or form

Overview, the create and edit forms, duplicate, delete, activate, add candidate, results page settings.

1. A route in `xml/Menu/elections.xml` names a `CRM_Elections_Page_*` or `CRM_Elections_Form_*` class (`administer Elections`).
2. The class reads the election with `retrieveElectionIdFromUrl()` and `findElectionForPage()` (functions in `elections.php`), checks `isElectionAdmin()`, and on refusal calls `throwAccessDeniedPage()`, which swaps in `templates/CRM/Elections/Helper/Error.tpl` and sends the HTTP status.
3. `CRM_Elections_Helper_Frame::assign()` gives it the breadcrumb and the Back addresses; a form's `postProcess()` calls `Process`, a BAO method or `civicrm_api3()`.
4. Templates are in `templates/CRM/Elections/{Page,Form}`.

### A front end page

The list of elections, one election, a candidate, nominating, accepting, declining, voting, the results.

1. The route is `is_public` with `access_callback` `CRM_Elections_Permission::check`, which grants access to people with `view Elections` or with a valid `cid` and `cs` (personal link).
2. The page or form finds the election, then applies the election's own rules: `allow_checksum_access` for people not logged in, `isVisible` for non-administrators, the stage dates.
3. `CRM_Elections_Helper_Eligibility::getActingContactId()` names the person (logged in, or the personal link). `CRM_Elections_Helper_ElectionStatus` works out the stage stepper and "what you can do now".
4. `elections_civicrm_pageRun` and `elections_civicrm_buildForm` add the `Referrer-Policy: same-origin` header and `<meta name="referrer">` to every page that extends `CRM_Elections_Page_Base` or `CRM_Elections_Form_Base`.
5. On WordPress, the `[civicrm component="elections"]` and `electioninfo` shortcodes are mapped to these routes by the filters in `elections.php`.

### A ballot

1. `CRM_Elections_Form_VoteCandidates` builds the ballot for the election's voting method and posts to `civicrm_api3('ElectionVote', 'addvotes')` (`api/v3/ElectionVote.php`).
2. `addvotes` checks the election is active and open, the voter is in the voting groups, takes a per-voter lock (`data.elections.vote.<election>.<voter>`), checks `allow_revote`, resolves referendum choices (`Helper_Referendum::resolve`) and write-ins (`Helper_WriteIn::resolve`), validates ranks (`_civicrm_api3_election_vote_validate_ballot`, `CRM_Elections_VotingMethod`).
3. In one transaction it deletes the voter's earlier ballot (if re-voting), records new write-ins, sets `Guard::$castingBallot` and creates the rows (`ElectionVote.create`, `ElectionOptionVote` through APIv4). `Guard::pre()` refuses a ballot row created any other way.
4. After the commit, write-in invitation emails are sent. The form then creates a `Vote` activity (the record that a person voted, never how).

Details in [voting-methods.md](voting-methods.md).

### Results generation

1. The scheduled job **Elections Results Job** (`Job/job.mgd.php`) calls APIv3 `Election.generateresults`: every election whose `result_date` has passed and whose `has_results_generated` is 0.
2. `CRM_Elections_Helper_Results::generateResult()` refuses unless voting has ended, takes a lock, and in one transaction deletes any partial results, counts each position, writes `civicrm_election_result` rows and `result_status`, sets `has_results_generated`, writes the write-in summary and the fingerprints to the audit log, then anonymises the ballots if the election says so.
3. The election overview (`Page_ElectionOverview`) shows the integrity check; the public results come from `CRM_Elections_BAO_ElectionResult::getResultsAndSummary()`.

### Scheduled jobs

| Job (managed in `Job/job.mgd.php`) | APIv3 action | Frequency | What it does |
|---|---|---|---|
| Elections Results Job | `Election.generateresults` | Always | Generate due results |
| Elections Write-in Reminders | `Election.sendwriteinreminders` | Hourly | Remind written-in people who have not answered, before voting closes |
| Elections Write-in Closed Notices | `Election.sendwriteinclosednotices` | Always | Tell written-in people who never answered that voting closed |

The jobs are created with `update => never`, so a site's own schedule is kept. They run only when CiviCRM's cron runs.

## Compatibility

CiviCRM 6.16 or later (`info.xml`), Smarty 5, WordPress, Drupal 11 and Joomla 5. Behaviour differs between 6.16 and 6.19 in places that matter to this code: see [../testing/cms-and-civicrm-differences.md](../testing/cms-and-civicrm-differences.md).
