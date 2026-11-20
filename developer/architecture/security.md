# Security model

What the extension protects, the guarantees it makes, where each is implemented and which tests keep it in place. This page is for people changing the code: read it before adding a way to write ballots, results, nominations or settings. To report a vulnerability, follow [`SECURITY.md`](../../SECURITY.md) and do not open a public issue. The user-facing summary is [`docs/reference_security_and_audit.md`](../../docs/reference_security_and_audit.md).

## Goal and roles

The result of an election must be trustworthy: nobody can change the outcome, learn how another person voted, vote when they should not, or vote twice, and a change made outside the extension is detectable.

| Role | May do | The code makes sure they cannot |
|---|---|---|
| Visitor with no login or link | Nothing | Read pages, results or any API |
| Person on a personal link | Act as that one person in elections that allow it | Act as anyone else, or use the API |
| Logged-in person (`view Elections`) | Nominate, accept, vote as themselves | Act for another person, vote twice, read ballots |
| Nominee or candidate | Accept, decline, withdraw, edit their own statement | Touch another person's nomination |
| Organiser (`administer Elections`) | Set up elections, add candidates | Change rules after people voted, edit ballots or results, regenerate results, reveal who voted for whom |
| Anyone with database access | Everything | Be undetected: changes show up as an integrity mismatch |

Database-level tampering cannot be prevented by an application. The extension makes it **detectable** (fingerprints and a hash chain) and keeps ballot secrecy even from organisers once votes are anonymised.

## Guarantees and where they live

All write-path rules are in the data layer so they hold for forms, APIv3, APIv4, SearchKit inline edits and drag and drop. The entry point is `elections_civicrm_pre()` and `_post()` in `elections.php`, which call `CRM_Elections_Helper_Guard`.

| Guarantee | Implemented in | Kept by |
|---|---|---|
| **One ballot per person**, by the database | Unique keys `UI_election_vote_member_nomination`, `UI_election_option_vote_member_position` ([data-model.md](data-model.md)); a lock per voter in `ElectionVote.addvotes` (`data.elections.vote.<election>.<voter>`); a re-vote replaces the old ballot in one transaction | `SecurityTest` (the database refuses a second row; a re-vote replaces the ballot), `tampering.spec.ts` (two ballots sent at once leave one) |
| **Ballot rows have one way in** | `Guard::guardVote()`, `guardOptionVote()`: rows are created only while `Guard::$castingBallot` is set by `addvotes` (which checks eligibility, candidates and the method's rules), only while voting is open, never edited, removed only while voting is open | `SecurityTest::testABallotRowCanOnlyBeAddedThroughTheBallotCheck`, `testBallotsCanOnlyBeAddedWhileVotingIsOpen`, `ReferendumTest` |
| **Who is standing is fixed once voting starts** | `BAO/ElectionNomination::assertCanChange()`, `Guard::guardNominationCreate()`; the only exception is the write-in process (`Guard::$writeIn`) | `SecurityTest`, `WriteInTest` |
| **Nominators count once** | Unique key `UI_election_nomination_seconder_nominator_nomination`; a lock per person and position (`data.elections.nominate.<position>.<nominee>`); nominators counted as distinct people in `Process::nominate()` | `SecurityTest::testANominatorCanNeverCountTwice`, `tampering.spec.ts` |
| **Results are written once, by the count** | `Guard::pre()` refuses `ElectionResult` writes unless `Results::$writing`; `Results::generateResult()` runs once, after voting ended, under a lock, in one transaction, and `has_results_generated` cannot be reset | `SecurityTest::testResultsCannotBeWrittenOrResetByAnythingElse`, `testResultsAreNotGeneratedBeforeVotingEndsAndOnlyOnce` |
| **Rules are locked once ballots exist** | `Guard::LOCKED_ONCE_VOTED` and `LOCKED_ONCE_RESULTS`; positions and question wording locked when the election starts (`BAO/ElectionPosition::assertCanChange()`) | `SecurityTest::testSettingsThatDecideWhoVotes...`, `AdminRulesTest`, `ReferendumTest` |
| **Hash-chained audit log** | `Helper/Audit::log()` stores a SHA-256 of the entry and the previous entry, under a database lock so entries chain in order; `verify()` checks the whole chain; the APIv4 entity is read only (`Civi/Api4/ElectionAudit.php`) | `SecurityTest::testChangesAreRecordedWithWhoAndWhatAndTheChainIsChecked`, `testAnEntryCannotBeRemovedFromTheMiddleOfTheLog`, `testTheAuditLogCannotBeChangedThroughTheApi` |
| **Fingerprints and the integrity check** | `Helper/Integrity::recordGeneration()` writes a fingerprint of the ballots (sorted `candidate:rank` lines, so it still works after anonymising) and of the results into the audit log; `Integrity::verify()` compares them and checks the chain; the overview shows the result and the fingerprint of the latest entry for noting down outside the site | `SecurityTest::testChangesBehindTheExtensionsBackAreFound` |
| **Ballot secrecy** | `Results::removeVotes()` removes the voter, sets every ballot time to the time voting closed and shuffles the rows; ballots are never written to the audit log; creating a write-in leaves no audit entry and carries the time voting opened; screens show counts only; a person's own ballot is not shown when the election anonymises | `SecurityTest::testAnonymisingLeavesNothingThatIdentifiesAVoter`, `WriteInTest::testNothingShowsWhoWroteWhomIn`, `ElectionStatusTest::testNobodyIsShownTheirOwnBallot...` |
| **Contacts in a running election are kept** | `Guard::guardContactDelete()` refuses deletion; foreign keys set the voter to NULL rather than deleting ballots; `elections_civicrm_merge()` moves election records to the kept contact | `SecurityTest::testAContactInARunningElectionCannotBeDeleted`, `testMergingContactsMovesWhatTheyDidInElections` |
| **Access checks** | Routes name a permission; public routes add `CRM_Elections_Permission::check` (login with `view Elections`, or a valid personal link); every APIv4 entity declares `administer Elections` in `permissions()`; APIv3 entities declare none of their own, so CiviCRM's default applies (`AdminRulesTest` shows it refuses ordinary participants), except `ElectionNominee.getlist`, which needs `view Elections`; `Process` and the forms check eligibility and stage on every action, and `Process::loadOwnNomination()` ties nominee actions to the acting person | `AdminRulesTest::testMembersCannotReadBallotsThroughTheApis`, `tampering.spec.ts` |
| **Personal links** | `Eligibility::getActingContactId()` and `retrieveContactChecksumFromUrl()` accept a `cid` and `cs` pair only after CiviCRM's `Contact.validateChecksum`; an election must set `allow_checksum_access`; `Mailer::link()` adds a checksum only when the election allows it; the nominee search takes the pair from the page's own address only for that one request (`elections_civicrm_permission_check()`) | `tampering.spec.ts` (a link that does not belong to the person is refused; nothing from the API for a link) |
| **Output is escaped** | Every value in `templates/` goes through `|escape`; names and breadcrumbs are escaped in `Frame::breadcrumbMarkup()`; JavaScript inserts names as text, never markup; email variables are escaped | `xss.spec.ts` on all three CMSs, `MailerTest::testNamesInTheEmailAreEscaped` |
| **Addresses are checked** | `Utils::localUrlOrNull()` (an address on this site), `safeHttpUrl()`, `safeImageUrl()`, `Candidate::isValidStatementUrl()` (also enforced on write in `Guard::pre()`) | `SecurityTest::testOnlyAddressesOnThisSiteAreAcceptedFromARequest`, `testOnlyHttpAndHttpsAddressesBecomeLinks`, `testPictureAddressesAreHttpOrPathsOnly`, `xss.spec.ts` |
| **Personal links stay on this site** | `elections_send_referrer_policy()` sends `Referrer-Policy: same-origin` and adds `<meta name="referrer" content="same-origin">` on every page and form that extends `Page_Base` or `Form_Base` (the meta tag is for hosts that overwrite the header) | `tampering.spec.ts` (checks the meta tag) |
| **Forms need their key** | CiviCRM's form key (`qfKey`); a form kept open past its stage does nothing, because the stage is checked again when it is sent | `SecurityTest::testBallotsCanOnlyBeAddedWhileVotingIsOpen`, `CouncilPersonaTest::testNominationsCloseWithTheNominationPeriod`, `testVotingOnlyWhileVotingIsOpen` |
| **Written-in people can be searched only narrowly** | `ElectionNominee.getlist` with `write_in`: voters only, voting open, candidate groups only, names only, at least three letters, 30 searches a minute for each person | `WriteInTest::testTheSearchNeedsAFewLettersAndShowsOnlyNames`, `testTheSearchIsLimitedAndOnlyForVotersWhileVotingIsOpen` |

Suites are described in [../testing/phpunit.md](../testing/phpunit.md) and [../testing/playwright.md](../testing/playwright.md). `tests/playwright/specs/security/` holds `xss.spec.ts` and `tampering.spec.ts`; a guard in the shared Playwright fixtures also fails any test whose page shows PHP or template errors.

## Extending it safely

1. **Write through the hooks.** Create and change rows with the BAO, APIv3 or APIv4, so `hook_civicrm_pre` runs. Raw SQL skips the guards; the only raw writes to ballot tables are in `Results::removeVotes()` (anonymising, inside the results transaction) and the upgrade steps.
2. **A new kind of ballot row is a new door.** Every rule that protects `civicrm_election_vote` must cover it: guard, one-per-person key, `Eligibility::hasVoted()`, turnout, fingerprint (`Integrity::votesHash()`), anonymising, contact delete and merge, upgrade step. `civicrm_election_option_vote` is the worked example.
3. **Never record how anyone voted**, or anything that points to it (a time, an acting contact, an order). Do not add ballot or write-in creation to the audit log.
4. **A new locked setting** goes in `Guard::LOCKED_ONCE_VOTED` (and a label in `Guard::fieldLabel()`); a new audited field goes in `Guard::AUDITED_ELECTION_FIELDS`; a new audit action needs a label in `Audit::actionLabel()` and `ElectionAuditSpecProvider::ACTIONS`.
5. **Escape on output.** Every template value gets `|escape` (`|nl2br` after it where needed); build markup in JavaScript from text nodes; check any address from a request with `Utils::localUrlOrNull()`, and any link a person types with `safeHttpUrl()`.
6. **A new route** needs a permission, and for a public route the `access_callback` and `is_public` as the others have; on the page, call `findElectionForPage()`, check the stage, and refuse with `throwAccessDeniedPage()` so a person learns nothing about an election they cannot see.
7. **A new API action** gets an explicit permission; refusals thrown from code that a SearchKit list can reach must be `CRM_Elections_Exception` so the reason reaches the person on CiviCRM 6.19.
8. **Lock concurrent actions** that must happen once (`Civi::lockManager()->acquire('data.elections....')`) and do them in a transaction.
9. **Add a test** next to the matching one above: a PHPUnit test that tries the forbidden thing through the API and expects a refusal, and a Playwright case when a screen is involved. Run both CiviCRM 6.16 and 6.19.

CiviCRM itself provides sessions, form tokens and the checksum; keep CiviCRM, the CMS and PHP up to date. Operating advice for organisers (noting the fingerprint, short link expiry) is in the user guide page linked at the top.
