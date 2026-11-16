> **Historical.** Written during version 2.0; may be out of date. Current guidance: [Decisions](../../../decisions/voting-and-elections.md).

Covers: version 2.0, the analysis of the open GitHub issues, 2026-10-27 (status as of 2026-10-27). Copied from the project plan with names, paths and account names removed; screenshots under `plan/evidence/` are not kept (image not kept).

# 03 – Open GitHub issues analysis

Source: https://github.com/agileware/au.com.agileware.elections/issues (11 open issues, retrieved 2026-10-27). Issues #3, #27, #28, #42–#45 are from the same reporter (The reporter) and overlap heavily.

## Summary table (highest → lowest priority)

| Pri | Issue | Title | Type | Overlaps | v2 action | Status (2026-10-27) |
|---|---|---|---|---|---|---|
| 1 | [#43](https://github.com/agileware/au.com.agileware.elections/issues/43) | Need for Candidate Statements | Feature | #27, #3(3), #45 | Candidate statement: admin view/edit + proper field | **Done** (Phase 2, 3) |
| 2 | [#45](https://github.com/agileware/au.com.agileware.elections/issues/45) | Display candidate comments on ballot | Feature | #43, #27 | Per-election "show statements on ballot/results" option | **Done** |
| 3 | [#44](https://github.com/agileware/au.com.agileware.elections/issues/44) | Don't display photos | Feature | #3(5) | Per-election "show candidate photos" option | **Done** |
| 4 | [#42](https://github.com/agileware/au.com.agileware.elections/issues/42) | Use of Nick Name | Feature | – | Candidate display-name format setting | **Done** |
| 5 | [#46](https://github.com/agileware/au.com.agileware.elections/issues/46) | Wording/Grammar? | Bug (copy) | – | Fix wording, wrap all strings in `{ts}` | **Done** |
| 6 | [#28](https://github.com/agileware/au.com.agileware.elections/issues/28) | How to "Second" a nomination? | Question → Feature | #3 (answered), #4 | Document seconding; option to hide nominator info | **Done** (including the D7 switch to turn online nominating off) |
| 7 | [#3](https://github.com/agileware/au.com.agileware.elections/issues/3) | Accepting nominations | Question + 5 feature asks | #43, #44, #27, #29 | Split into: plurality voting, admin edit of candidates, statements, write-ins, no photos | **Done**: admin edit, statements, no photos, plurality voting, write-ins (D8) |
| 8 | [#4](https://github.com/agileware/au.com.agileware.elections/issues/4) | Nominations process feature request | Feature | #28 | Separate eligibility groups for nominating and voting | **Done** (Phase 6, D9: nomination and candidate groups) |
| 9 | [#5](https://github.com/agileware/au.com.agileware.elections/issues/5) | Referenda | Feature | #3 (voting methods) | Referendum/question position type | **Done** (Phase 7, D10: yes or no questions with threshold and quorum) |
| 10 | [#27](https://github.com/agileware/au.com.agileware.elections/issues/27) | Adding custom fields to the Nomination entity? | Question | #43 | Answer by providing statement field; evaluate custom-field support | **Done** (answered by statements; reply on release) |
| 11 | [#29](https://github.com/agileware/au.com.agileware.elections/issues/29) | Accept/Reject Nomination? | Question | #3 | Documentation + notification email (see below) | **Done**: docs, and emails to nominees (D11, first piece) |

Status key: **Done** = built, tested and documented on `version-2`; **Open** = planned for the phase shown. The decisions still needed for the open items are in [07-decisions-log.md](../../../decisions/README.md).

Prioritisation rationale: #43/#45/#44/#42/#46 come from a real site upgrading from v1.2.6 and unblock their upgrade; they are small, share schema/UI work and are low risk. #3/#4/#5 are larger structural features (voting methods, group model, new position type) and follow once the settings/display foundation exists. Questions #27/#29 are resolved mostly through documentation and by the statement feature.

---

## Per-issue analysis

### #43 Need for Candidate Statements — *Priority 1*
**Request:** The nomination `comments` field is used as a candidate statement (qualifications, goals, link to CV). Administrators cannot view or edit it.

**Overlap/duplicates:** Same underlying need as **#27** (custom fields / "position statements"), **#3 point 3** (candidate bios/CV; maintainer replied "UI changes pending in another branch"), and the display side is **#45**. Treat #43 + #27 + #3(3) as **one feature** ("Candidate statement"); #45 is its ballot-display counterpart.

**Proposed solution:**
- Keep storing the statement on the nomination (`civicrm_election_nomination.comments`) so no data migration is needed, but give it a proper label "Candidate statement" and treat it as a first-class field:
  - Admin: statement shown and editable on the nomination/candidate admin screen (new SearchKit/Afform "Candidates" list with inline edit, see Phase 3).
  - Candidate: can enter/edit the statement when accepting, and later edit until Voting Start Date.
  - Optional **statement link/CV URL** field (new column `statement_url`) so a link does not have to be pasted into free text. *(see decision D1)*
  - Plain-text with line breaks by default; allow simple formatting (WYSIWYG → sanitised HTML)? *(decision D2)*
- Answers #27: custom fields on Nomination are not supported/tested; statements are the supported route. Evaluate whether `entity-types-php` entities can be extended with CiviCRM custom groups (`extends: ElectionNomination` is not supported by core for non-core entities) – likely **not**; document as won't-do unless requested.

**Confirmation prompt:** *Is reusing the existing `comments` field as the candidate statement correct (with a new optional CV/link field), rather than a new separate field? Should statements be plain text or allow formatting?*

### #45 Display candidate comments on ballot — *Priority 2*
**Request:** Show nomination comments (statement) on the ballot via an election option; the reporter shows them in a `CRM.confirm()` popup "View Candidate Statement and CV". Also applies to `ViewElectionBlocks/nominations.tpl`.

**Solution:** New per-election option **Show candidate statements on the ballot** (`show_statements`): none / link-to-statement (modal, accessible dialog) / inline expandable (details/summary). Add `comments` (and `statement_url`) to `findElectionCandidates()` and to the nominations block. Default **off for upgraded elections** (no behaviour change) and **on for new elections**. *(decision D3)*

### #44 Don't display photos — *Priority 3*
**Request:** Option to hide candidate photos (avoid appearance/ethnicity bias). Applies to `ElectionCandidate.tpl`, `ElectionResults.tpl`, and by extension `VoteCandidates.tpl`, `nominations.tpl`.

**Overlap:** **#3 point 5** (same request in 2020 – "this would be a configurable setting, per election").

**Solution:** Per-election option **Show candidate photos** (`show_photos`, default yes so existing behaviour is retained) honoured by all templates via a single Smarty assignment (`$election->showPhotos`). The global *Image Source* setting remains for sources. Consider an optional "Show photos: Never / Only on candidate profile / Everywhere" – kept simple (boolean) unless confirmed otherwise. *(decision D4)*

**Confirmation prompt:** *Should "hide photos" also hide them from the nominations (stage 1) screens and results, or only from the ballot?* Proposed: hide **everywhere within that election**.

### #42 Use of Nick Name — *Priority 4*
**Request:** Show nickname on the ballot, e.g. `Edward "Butch" Smith`. The hack concatenates first, nick and last names in `NominationType::getOptions`.

**Solution:** Central helper `CRM_Elections_Helper_Candidate::displayName($contact, $format)` used by every template/form/result. Per-election (or global) **Candidate name format**:
1. Display name (default, unchanged)
2. First "Nick" Last
3. Nick Last (no first name)
4. Nick name only when set, else display name
Add `member_nominee.nick_name`, `first_name`, `last_name` to API returns. Option stored on the election (`name_format`) with a global default setting. *(decision D5)* The reporter's hack also has a bug (empty `nick_name` ⇒ stray join, `implode` array order); v2 does it properly with proper escaping.

### #46 Wording/Grammar — *Priority 5 (quick win)*
**Request:** "Please number of the boxes from 1 to N" → "Please number the Candidates/Options from 1 to N". Maintainer agreed to remove "of".

**Solution:** Reword to: "Vote for {position}. Please number the {candidates} from 1 to N in the order of your preferences." In v2 the voting screen is redesigned, so a full copy review and `{ts}` wrapping of all template strings is done alongside (**D6**: a wording pass over all UI strings). Because referenda (#5) use "Options", copy is generalised ("candidates" vs "options") by position type.

### #28 How to "Second" a nomination? — *Priority 6*
**Q&A:** Answered: seconding is optional, controlled by **Number of Required Nominations**; a second person nominates the same nominee. Follow-up (2026-10-12): The reporter's organisation handles nominations offline and only needs candidate listing, so wants an option to **hide nominator information** (`nominations.tpl`).

**Overlap:** relates to **#4** (separate nomination phase/group) and to a related "admin-only nominations" workflow.

**Solution:**
- Documentation: improve `docs/user_second_nomination.md`.
- Per-election option **Show nominators** (`show_nominators`, default yes).
- Consider an **Admin-managed nominations** mode: online nominations disabled (no nomination form), admin adds candidates directly through the back-end Candidates screen (nomination auto-eligible and accepted). *(decision D7)* This covers the reporter's actual workflow ("board decides offline").

### #3 Accepting nominations — *Priority 7*
Original question (how does a nominee accept?) was answered in the thread and in **#29**: the nominee accepts from their own profile; testing with Drupal masquerade failed (not an extension bug). Five feature asks were made:

| # | Ask | Status/plan |
|---|---|---|
| 1 | Vote tally method: straight (plurality) vs IRV, per election | **New feature – Phase 5.** Per-election `voting_method`: IRV (default, existing), Plurality/first-past-the-post (select up to N), maybe Approval. Maintainer already agreed ("have the ability to change the voting method per election"). |
| 2 | Admin edit of candidate records | Candidates are Contacts; nomination data not exposed in the back end → **Phase 3 Candidates screen** (SearchKit + inline edit). |
| 3 | Candidate bios / CV | Duplicate of **#43** (same solution). |
| 4 | Write-in option | **New feature – Phase 5.** Per-position setting `write_ins_per_seat` (0 to 10, 0 = off); the voter picks a contact from a group (decided, D8). See below. |
| 5 | No photos | Duplicate of **#44**. |

**Write-ins – design proposal:** per position `write_ins_per_seat` (0 to 10; contact from a group, decided in D8). Votes are stored against a new `election_write_in` record (free text) or against a contact. A write-in cannot win without admin review: results list write-ins; admin can "promote" a write-in to a nomination (contact picker) before the result is final. Needs plurality *and* IRV handling (write-ins count as candidates ranked by voter). *(decision D8)*
**Decided (D8, 2026-10-27):** the voter picks a contact from a group (no free text); the person is notified immediately and must accept before voting closes; once accepted they are a candidate and appear on the ballot for later voters; no review stage. See 07-decisions-log.md for the details and defaults.

### #4 Nominations process feature request — *Priority 8*
**Request:** Open nominations to one group (e.g. executive council) and open the election (voting) to a different group (all members). "Would it be possible/desirable to have each phase controlled by a separate group permission?"

**Overlap:** #28 (nomination process). Currently `allowed_groups` governs both nominating and voting (`isLoggedInMemberAllowedToVote` and nominating checks).

**Solution:** Keep `allowed_groups` as the **voting/participation** group and add `nomination_groups` (who may nominate/second; empty = same as voting groups) and optionally `candidate_groups` (who may be nominated; empty = anyone eligible by voting groups). Upgrade sets the new columns empty ⇒ unchanged behaviour. *(decision D9)*
**Decided (D9, 2026-10-27):** yes: `nomination_groups` and `candidate_groups` are both added; empty means the same as the voting groups. Rules are in 07-decisions-log.md.

### #5 Referenda — *Priority 9*
**Request:** Allow up/down votes on matters (e.g. by-law changes) in addition to electing people.

**Solution:** Add **position type** `position_type`: `election` (default) / `referendum`. A referendum "position" is a question with configurable options (default Yes / No / Abstain); options are stored as nominations without a nominee contact or in a new `election_option` table (decision D10 – recommended new `election_option` table to avoid overloading nominee semantics). Voting is single choice. Results: counts per option, pass/fail with an optional threshold (simple majority, two-thirds…). Anonymisation and group restrictions work unchanged. Referenda share an election with positions so one ballot can contain both.
**Decided (D10, 2026-10-27):** a new `election_option` table; pass threshold per question (simple majority by default, two-thirds, 75% or custom), explicit Abstain, optional quorum (off by default), ties fail. Details in 07-decisions-log.md.

### #27 Adding custom fields to the Nomination entity? — *Priority 10*
**Q&A:** Not designed for custom fields; statements solve the stated need (see #43). Plan: answer with the statement feature; document that custom fields on Nominations are not supported. Evaluate Afform-based nomination forms (Phase 4/5) as a long-term route to collecting extra data: an Afform could submit to the `ElectionNomination` APIv4 entity with extra fields. Reply and close when #43 ships.

### #29 Accept/Reject Nomination? — *Priority 11*
**Q&A:** Accept/Withdraw buttons exist on the nominee's own profile; email notification is via scheduled reminder on an activity. Follow-up gap: no immediate notification is sent when someone is nominated.
**Solution:** Documentation update + **notification emails**: message templates (`Nomination received`, `Nomination seconded`, `Candidate confirmed`, `Voting opens`) using CiviCRM message templates and existing tokens, optionally sent on nomination creation (election option **Email nominees**). *(decision D11)* Marked as a stretch goal for v2.

---

## Duplicates / overlap map

```
Candidate statement:   #43 ≈ #27 ≈ #3(3)   ──display──▶ #45
Hide photos:           #44 ≡ #3(5)
Hide nominators:       #28 (follow-up)   ~ admin-managed nominations (D7)
Voting method/write-in:#3(1), #3(4)
Admin candidate edit:  #3(2) ≈ #43 (admin edit)
Separate groups:       #4
Question type:         #5   ~ voting-method framework (#3(1))
Answered/doc only:     #3 (orig. question), #29, #27 (partly), #28 (partly)
Wording:               #46 (+ #5 forces generic "option" wording)
```

When v2 ships: comment on each issue with the release and commits, then close. Duplicates (#3 sub-items) are referenced from #3 and closed together.
