# Data model

The nine tables of release 2.1.0, defined in `schema/*.entityType.php` (the `entity-types-php` mixin creates them on install; upgrades are in [upgrades.md](upgrades.md)). Names of columns and API parameters keep the word `member` (for example `member_id`) even though screens say "people".

## Relationships

```
civicrm_election
  |-- civicrm_election_position            (election_id, cascade)         a position or a referendum question
  |     |-- civicrm_election_nomination    (election_position_id, cascade; member_nominee -> contact)
  |     |     |-- civicrm_election_nomination_seconder  (election_nomination_id, cascade; member_nominator -> contact)
  |     |     |-- civicrm_election_vote    (election_nomination_id, cascade; member_id -> contact, set null)
  |     |     `-- civicrm_election_result  (election_nomination_id, cascade; also election_position_id)
  |     |-- civicrm_election_option        (election_position_id, cascade)  Yes / No / Abstain of a question
  |     `-- civicrm_election_option_vote   (election_position_id and election_option_id, cascade; member_id set null)
  `-- civicrm_election_audit               (election_id is a plain number, not a foreign key)
```

Other links: `civicrm_election.results_logo_file_id` to `civicrm_file` and `nomination_message_template_id` to `civicrm_msg_template` (both set null); `created_by` of an election and a position to `civicrm_contact` (cascade).

## Tables

| Table | One row is | Key columns |
|---|---|---|
| `civicrm_election` | An election | `name`, `description`; eight dates (below); `is_visible` (active), `is_deleted` (soft delete), `has_results_generated`; counting and rules (`voting_method`, `anonymize_votes`, `allow_revote`, `required_nominations`); who takes part (`allowed_groups`, `nomination_groups`, `candidate_groups`); `allow_checksum_access`; `allow_online_nominations`; `send_emails`, `nomination_message_template_id`; display (`show_photos`, `show_statements`, `show_nominators`, `name_format`, `results_header_text`, `results_footer_text`, `results_logo_file_id`, `results_logo_alt`, `celebration_*`); `created_by`, `created_at`, `updated_at` |
| `civicrm_election_position` | A position people stand for, or a yes or no question | `election_id`, `name`, `description`, `quantity` (seats), `sortorder`, `write_ins_per_seat` (0 to 10), `position_type` (`election` or `referendum`), `pass_threshold`, `quorum_percent`, `resolution_text`, `result_status` |
| `civicrm_election_nomination` | A person named for a position (a nominee, then a candidate) | `election_position_id`, `member_nominee`, `is_eligible_candidate`, `has_accepted_nomination`, `has_rejected_nomination`, `has_withdrawn_nomination`, `comments` (the statement), `statement_url`, `rejection_comments`, `is_write_in`, `write_in_*_date` |
| `civicrm_election_nomination_seconder` | One person nominating or seconding a nomination | `member_nominator`, `election_nomination_id`, `description` (the reason) |
| `civicrm_election_vote` | One ballot row: a candidate and the rank the voter gave (plurality ticks are rank 1) | `election_nomination_id`, `rank`, `member_id`, `created_at` |
| `civicrm_election_option` | A choice on a question | `election_position_id`, `kind` (`yes`, `no`, `abstain`), `label`, `weight` |
| `civicrm_election_option_vote` | A voter's answer to a question | `election_option_id`, `election_position_id`, `member_id`, `created_at` |
| `civicrm_election_result` | A candidate's final rank in a position (no vote counts are stored) | `election_position_id`, `election_nomination_id`, `rank` |
| `civicrm_election_audit` | One audit log entry | `election_id`, `created_date`, `contact_id`, `action`, `details` (JSON), `prev_hash`, `entry_hash` |

### The election dates

`visibility_start_date`, `visibility_end_date`, `nomination_start_date`, `nomination_end_date`, `advertise_candidates_date`, `voting_start_date`, `voting_end_date`, `result_date`. Their required order is `CRM_Elections_BAO_Election::getDateRules()`; the browser form reads the same table. Dates are the site's clock time, without a time zone.

### Unique keys the database enforces

| Index | Columns | Guarantee |
|---|---|---|
| `UI_election_vote_member_nomination` | `member_id`, `election_nomination_id` | A voter has at most one row for a candidate |
| `UI_election_option_vote_member_position` | `member_id`, `election_position_id` | A voter has one answer for a question |
| `UI_election_nomination_position_nominee` | `election_position_id`, `member_nominee` | One nomination for each person and position |
| `UI_election_nomination_seconder_nominator_nomination` | `member_nominator`, `election_nomination_id` | One nominator row for each nominator and nomination |
| `UI_election_option_position_kind` | `election_position_id`, `kind` | One Yes, one No, one Abstain for each question |

## Computed fields (APIv4 spec providers)

These are not columns. They exist only in APIv4 `get` (so SearchKit can show and filter them), in `Civi/Api4/Service/Spec/Provider/`.

| Entity | Field | Meaning |
|---|---|---|
| `Election` | `stage` | Where the election is now (inactive, upcoming, nominations, nominations_closed, candidates, voting, voting_closed, results) worked out in SQL from the dates |
| `Election` | `is_locked`, `lock_reason` | Active with nominations started: the election cannot be edited or deleted and its positions cannot change |
| `ElectionNomination` | `nomination_status` | `nominee`, `candidate`, `declined` or `withdrawn` |
| `ElectionAudit` | `action_label`, `details_text`, `changed_by_name` | Words for the stored action, a sentence from the JSON, the contact's name |

## Stored versus derived

| Fact | Stored or derived | How |
|---|---|---|
| Stage of an election | **Derived**, never stored | Compared with the current time, in three places that must agree: `BAO/Election::getStage()` (PHP, keys `inactive`, `upcoming`, `nominations`, `advertise`, `voting`, `closed`, `results`, `deleted`), `ElectionSpecProvider::renderStage()` (SQL for lists, longer key set) and `Helper/ElectionStatus::steps()` (the stepper on the election page) |
| Active | Stored | `is_visible`. An election is shown to people only when `is_visible` is set and now is between the visibility dates |
| Locked | Derived | `is_visible` and `nomination_start_date` in the past |
| Candidate | Stored as two flags | `is_eligible_candidate` (enough nominators) and `has_accepted_nomination` |
| Declined versus withdrawn | **Both are `has_rejected_nomination = 1`**; `has_withdrawn_nomination` says which | Declined: said no before accepting. Withdrawn: pulled out after accepting. The two flags together are shown as `nomination_status`. Nominations refused before 2.1 are shown as declined because the data never said |
| Written in | Stored | `is_write_in` on an ordinary nomination; a write-in becomes a candidate when the person accepts |
| Position or question | Stored | `position_type` |
| Results generated | Stored | `has_results_generated`, set once by `Helper_Results::generateResult()` |
| Results published | Derived | `has_results_generated` and `result_date` has passed |
| Winner or tie | Derived at display | `BAO/ElectionResult::getRanksForPosition()` from ranks and seats; only ranks and `result_status` are stored |
| Who voted | Derived | From ballot rows (`member_id`) while they exist, otherwise from the `Vote` activities (one for each voter, `source_record_id` is the election id), which are kept when ballots are anonymised. The activity records that a person voted, never how |
| Deleted election | Stored | `is_deleted` (soft). Deleting a position is a real delete and cascades |
| Effective voting method | Derived | `VotingMethod::normalise()`: an empty or unknown value is ranked choice (`irv`) |
| Groups for nominating and standing | Derived | `Eligibility::groupsFor()`: the election's own list, or `allowed_groups` when empty |

`civicrm_election.result_status` (described in the schema as a manual results visibility flag) is not read or written by the code in 2.1.0.

## What is locked, and when

Enforced in the data layer (`elections_civicrm_pre`), so it applies to every write path.

| When | What cannot change | Where |
|---|---|---|
| Election active and nominations started | The Edit and Delete screens refuse (`canEdit()`, `canDelete()`; APIv3 `Election.create` refuses `is_deleted`). In the data layer: adding, changing, moving or deleting positions, and the wording of a question's choices | `BAO/Election::canEdit()`, `BAO/ElectionPosition::assertCanChange()`, `Guard::guardOption()` |
| Active election with nominations started | Deactivating, except through the Deactivate dialog (it asks for the election's name) | `Guard::guardElectionEdit()`, `$withdrawing` |
| Voting started | Who is standing (`BALLOT_FIELDS`, new nominations), except through the write-in process | `BAO/ElectionNomination::assertCanChange()`, `Guard::guardNominationCreate()` |
| Voting ended | Every nomination field, including statements | `BAO/ElectionNomination::assertCanChange()` |
| Any ballot row exists, or results generated | `allowed_groups`, `nomination_groups`, `candidate_groups`, `anonymize_votes`, `allow_revote`, `required_nominations`, `voting_method` | `Guard::LOCKED_ONCE_VOTED` |
| Results generated | Also `voting_start_date`, `voting_end_date`, `has_results_generated` | `Guard::LOCKED_ONCE_RESULTS` |
| Outside the voting window | Adding or removing ballot rows; ballot rows are never edited | `Guard::guardVote()`, `guardOptionVote()` |
| Always | Results rows (only while results are being generated); audit entries (no APIv4 write actions); a contact who is a candidate, nominator or voter in a running election cannot be deleted | `Guard::pre()`, `Civi/Api4/ElectionAudit.php` |
| A position with nominations | Cannot become a question | `Guard::guardPositionSettings()` |

Merging two contacts moves nominations, nominator rows and ballot rows to the kept contact (`elections_civicrm_merge`). Deleting a contact otherwise leaves ballots with no voter (`ON DELETE SET NULL`).
