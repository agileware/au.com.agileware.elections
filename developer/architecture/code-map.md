# Code map

Every directory and class of the extension at release 2.1.0, and where to look to change something. Paths are relative to the repository root.

## Conventions

- **Key and namespace:** `au.com.agileware.elections`, civix short name `elections`. `info.xml` sets the class loader: `CRM_` classes are PSR-0 from the repository root (`CRM/Elections/Helper/Guard.php` is `CRM_Elections_Helper_Guard`), `Civi\` classes are PSR-4 from `Civi/`.
- **Layers in `CRM/Elections/`:** `BAO` (rules tied to one table), `DAO` (stubs), `Form` (QuickForm screens), `Page` (display pages), `Helper` (rules and presentation logic with no HTML, called by everything else). One class per file, named after the file.
- **Entities:** the table name is `civicrm_election_<thing>`; the entity is `Election<Thing>`; the DAO, BAO, APIv3 file (`api/v3/Election<Thing>.php`) and APIv4 class (`Civi/Api4/Election<Thing>.php`) share that name. Schema comes from `schema/*.entityType.php`, not from SQL files.
- **Functions in `elections.php`** (not classes) are shared helpers: `findElectionById()`, `findElectionForPage()`, `retrieveElectionIdFromUrl()`, `isElectionAdmin()`, `throwAccessDeniedPage()` and friends. Hook implementations are named `elections_civicrm_*`.
- **Refusals** are thrown as `CRM_Elections_Exception` (a `CRM_Core_Exception` whose message is safe to show). CiviCRM 6.19 hides the message of any other exception from AJAX callers.
- **Wording:** every string goes through `E::ts()` (`use CRM_Elections_ExtensionUtil as E;`). Rules for words are in [../standards/wording.md](../standards/wording.md).
- **Templates are Smarty 5.** Escape every value (`{$x|escape}`), give optional array keys a default (`{$x.key|default:''}`), and avoid retired modifier syntax such as `|@count`. The `smarty@1.0.3` mixin registers `templates/` automatically.
- **Minimum:** CiviCRM 6.16 (`info.xml` `<compatibility>`), with the mixins `ang-php`, `menu-xml`, `mgd-php`, `setting-php`, `entity-types-php` and `smarty`. Requires the `org.civicrm.afform` and `org.civicrm.search_kit` extensions.
- **Never edit generated files:** `elections.civix.php` and `mixin/` are civix output (the `civix-upgrade` workflow refreshes them).

## Directory by directory

| Path | Contents |
|---|---|
| `elections.php` | Hooks (`pre`, `post`, `merge`, `pageRun`, `buildForm`, `preProcess` for the shortcode dialog, `navigationMenu`, `coreResourceList`, `permission`, `permission_check`, `alterAPIPermissions`, `managed`, `container`), WordPress shortcode filters, and the shared helper functions above |
| `schema/` | One `*.entityType.php` for each of the nine tables |
| `CRM/Elections/DAO/` | Stubs extending `CRM_Elections_DAO_Base` (an alias set up in `elections.civix.php`); only property annotations |
| `CRM/Elections/BAO/` | See below |
| `CRM/Elections/Form/` | Back end and front end forms; all extend `Form/Base.php` |
| `CRM/Elections/Page/` | Display pages; all extend `Page/Base.php` |
| `CRM/Elections/Helper/` | Rules and presentation helpers; see below |
| `CRM/Elections/VotingMethod.php` | Voting method names, ballot rules and the plurality count |
| `CRM/Elections/Permission.php` | Access callback for public routes (permission or valid personal link) |
| `CRM/Elections/Upgrader.php` | Upgrade steps ([upgrades.md](upgrades.md)) |
| `CRM/Elections/Tokens.php` | Activity tokens (election name, position, nominator, nominee) |
| `CRM/Elections/Exception.php` | The refusal exception |
| `Civi/Api4/` | APIv4 entities, the `Duplicate`, `SuggestDates` and `ElectionPosition` guarded actions, and the spec providers |
| `Civi/Elections/` | `CompilerPass` registering the Action Provider and Form Processor integrations; `ActionProvider/VoteAction`, `IsUserAllowedToVoteAction`; `FormProcessor/NominationType` |
| `api/v3/` | APIv3 entities `Election`, `ElectionNomination`, `ElectionNominationSeconder`, `ElectionPosition`, `ElectionResult`, `ElectionVote`, and `ElectionNominee.getlist` |
| `Job/job.mgd.php` | The three scheduled jobs |
| `managed/` | Managed entities: SavedSearches and SearchDisplays of the lists, and the email message templates |
| `msg_tpl/` | Subject, text and HTML parts of the four email templates |
| `ang/` | Afforms (`afsearch*.aff.php` and `.aff.html`) and the Angular module `electionsLists` |
| `js/` | `election-form.js`, `election-ballot.js`, `election-celebrate.js`, `election-breadcrumb.js`, `election-copy.js` |
| `css/electionforms.css` | The one stylesheet for front end and back end |
| `templates/CRM/Elections/` | Smarty templates: `Page/`, `Form/`, `Frame/`, `Helper/Error.tpl` |
| `xml/Menu/elections.xml` | Routes |
| `settings/elections.setting.php` | Site settings (image source, candidate name format) |
| `tests/` | `phpunit/`, `playwright/`, `upgrade/` (see [../testing/README.md](../testing/README.md)) |
| `docs/` | User guide (mkdocs) |
| `developer/` | This guide; left out of releases |

## BAO classes

| Class | Holds |
|---|---|
| `BAO/Election.php` | Result status constants, `getStage()`, `getCounts()`, `getTurnout()`, `getNextStep()`, `duplicate()`, `canEdit()` / `canDelete()`, `assignStatues()` (which stage flags are true now), `getDateRules()` and `compareDates()`, `assertCanActivate()` |
| `BAO/ElectionPosition.php` | `DEFINING_FIELDS`, referendum options (`getOptions()`, `saveOptions()`), `assertCanChange()`, `assertItemCanChange()`, `duplicate()`, `findWithNominationsByElectionId()` |
| `BAO/ElectionNomination.php` | `BALLOT_FIELDS`, `STATEMENT_FIELDS`, `assertCanChange()`, `addCandidate()` (organiser adds a candidate) |
| `BAO/ElectionResult.php` | Reads results and candidates for display (`getResultsAndSummary()`), marks winners and ties |
| `BAO/ElectionNominationSeconder.php`, `ElectionOption.php`, `ElectionOptionVote.php`, `ElectionVote.php` | Empty subclasses; their rules are in `Helper/Guard.php` |

## Helper classes

| Class | Responsibility |
|---|---|
| `Guard` | `hook_civicrm_pre` and `_post` rules: locks, audit log writes, flags `$castingBallot`, `$writeIn`, `$withdrawing` |
| `Audit` | Hash-chained audit log: `log()`, `verify()`, `getHeadHash()`, `entries()`, `actionLabel()` |
| `Integrity` | Ballot and result fingerprints: `votesHash()`, `resultsHash()`, `recordGeneration()`, `verify()` |
| `Results` | Counting (ranked choice, most votes win), `generateResult()`, `removeVotes()` (anonymise) |
| `Referendum` | `resolve()` (check choices), `count()`, `decide()` (pass, fail, no quorum) |
| `WriteIn` | Write-in places, `resolve()`, `record()`, `logSummary()` |
| `Process` | `nominate()`, `accept()`, `withdraw()`, `vote()` with all rules applied |
| `Eligibility` | Who is acting (login or personal link), who may vote, nominate, stand; `hasVoted()` |
| `ElectionStatus` | Stage stepper, "what you can do now", a person's own nominations and ballot |
| `Frame`, `SettingsFrame`, `ListPage` | Back end page frame, settings page frame, settings for the Angular lists ([lists-and-frame.md](lists-and-frame.md)) |
| `Mailer` | Sends the four emails from message templates; builds personal links |
| `Candidate` | Name formats, statement link check |
| `Dates`, `TimeZone` | Quick date set-up (cycles), site time zone line |
| `ResultsDisplay` | Results page logo, header, footer, celebration effect, logo file handling |
| `Utils` | `localUrlOrNull()`, `safeHttpUrl()`, `safeImageUrl()`, back link, kept input, candidate pictures |
| `Visibility` | Tells organisers when their CiviCRM permissions hide groups or contacts from a form |

## Screens: route, class, template

| Route (`xml/Menu/elections.xml` unless noted) | Class | Template |
|---|---|---|
| `civicrm/elections` | `Page_Elections` | `Page/Elections.tpl` |
| `civicrm/elections/view` | `Page_ViewElection` | `Page/ViewElection.tpl` (or `ElectionResults.tpl` once results exist) |
| `civicrm/elections/summary` | `Page_ElectionSummary` | `Page/ElectionSummary.tpl` |
| `civicrm/elections/candidate` | `Page_ElectionCandidate` | `Page/ElectionCandidate.tpl` |
| `civicrm/elections/overview` | `Page_ElectionOverview` | `Page/ElectionOverview.tpl` |
| `civicrm/elections/positions` | `Page_ElectionPositions` | none: redirects to the SearchKit list |
| `civicrm/elections/results-logo` | `Page_ResultsLogo` | none: serves the logo file |
| `civicrm/elections/create`, `delete`, `duplicate`, `visibility`, `results-display` | `Form_CreateElection`, `DeleteElection`, `DuplicateElection`, `ElectionVisibility`, `ResultsDisplay` | `Form/*.tpl` |
| `civicrm/elections/positions/create`, `delete`, `duplicate` | `Form_CreateElectionPosition`, `DeleteElectionPosition`, `DuplicateElectionPosition` | `Form/*.tpl` |
| `civicrm/elections/candidates/add` | `Form_AddCandidate` | `Form/AddCandidate.tpl` |
| `civicrm/elections/nominations/create`, `accept`, `reject` | `Form_CreateElectionNomination`, `AcceptNomination`, `RejectNomination` | `Form/*.tpl` |
| `civicrm/elections/candidate/statement` | `Form_EditStatement` | `Form/EditStatement.tpl` |
| `civicrm/elections/vote` | `Form_VoteCandidates` | `Form/VoteCandidates.tpl` |
| `civicrm/admin/setting/elections` | CiviCRM's `CRM_Admin_Form_Generic` | settings from `settings/elections.setting.php` |
| `civicrm/elections/manage`, `position-list`, `position-list-active`, `candidate-list`, `result-list`, `audit-log` | Afforms in `ang/` | `ang/afsearch*.aff.html` |

## Where do I change X

| Task | Files |
|---|---|
| Add an election setting (a column) | `schema/Election.entityType.php`; a step in `CRM/Elections/Upgrader.php` and its test ([upgrades.md](upgrades.md)); the DAO annotation; `Form/CreateElection.php` and its template; `BAO/Election::duplicate()` field list; `Guard::AUDITED_ELECTION_FIELDS`; lock it in `Guard::LOCKED_ONCE_VOTED` if it changes how votes count |
| Change what is locked, and when | `Helper/Guard.php` (`LOCKED_ONCE_*`), `BAO/ElectionPosition::DEFINING_FIELDS`, `BAO/ElectionNomination::BALLOT_FIELDS`, `BAO/Election::canEdit()` |
| Add a voting method | `VotingMethod.php`, `Helper/Results.php`, `Form/VoteCandidates.php`, `js/election-ballot.js`; see [voting-methods.md](voting-methods.md) |
| Change the order or wording of election stages | `BAO/Election::getStage()`, `ElectionSpecProvider::renderStage()`, `Helper/ElectionStatus.php` (all three) and `BAO/Election::getNextStep()` |
| Change the election dates rules | `BAO/Election::getDateRules()` (also read by `js/election-form.js`); `Helper/Dates.php` for the quick set-up |
| Add a column or button to a back end list | `managed/SavedSearch_*.mgd.php`; a computed value goes in a spec provider in `Civi/Api4/Service/Spec/Provider/` ([lists-and-frame.md](lists-and-frame.md)) |
| Add a back end form | A class in `Form/` extending `Form_Base`, a template, a route in `xml/Menu/elections.xml`; use `Helper_Frame` for the breadcrumb and buttons |
| Change who may vote, nominate or stand | `Helper/Eligibility.php`; groups are columns of `civicrm_election` |
| Change how a ballot is checked or stored | `api/v3/ElectionVote.php`, `VotingMethod.php`, `Helper/Referendum.php`, `Helper/WriteIn.php` |
| Change how results are counted or shown | `Helper/Results.php`, `Helper/Referendum.php`, `BAO/ElectionResult.php`, `templates/CRM/Elections/Page/ElectionResults.tpl`, `PositionResultTypeMessage.tpl` |
| Change an email | `msg_tpl/*`, `managed/MessageTemplates.mgd.php`, `Helper/Mailer.php`; an upgrade step is needed to change text on sites that already have the template (see `upgrade_20120`) |
| Add a scheduled job | `Job/job.mgd.php` plus an APIv3 action in `api/v3/Election.php` |
| Change an access rule | `CRM/Elections/Permission.php`, `elections_civicrm_permission_check()`, `xml/Menu/elections.xml`, the `permissions()` method of the APIv4 class |
| Change the audit log | `Helper/Audit.php`, `Guard::AUDITED_ELECTION_FIELDS`; every new action needs a label in `Audit::actionLabel()` and `ElectionAuditSpecProvider::ACTIONS` (`FrameTest` checks it) |
| Change styles | `css/electionforms.css`; scoping rules in [lists-and-frame.md](lists-and-frame.md) |
| Change the Back buttons or breadcrumb | `Helper/Frame.php`, `templates/CRM/Elections/Frame/*.tpl` |
| Integrate with Form Processor or Action Provider | `Civi/Elections/` |
