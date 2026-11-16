# Wording: people, not members

How the extension names the people who take part, which words it avoids, how `WordingTest` enforces that, and how to write help text, emails and error messages. The glossary of election words (position, nominee, candidate and so on) is in [`ui-standards.md`](ui-standards.md#2-glossary-and-action-words); this page covers the rules for writing and the check that guards them.

## The rule

Everyone who takes part in an election is **people**: voters, nominees, candidates and organisers. The extension never says **member**, **members** or **membership** in anything a person reads. Not everyone who votes is a member of an organisation, and the words suggest that a CiviCRM Membership is needed, which it is not. What matters is being in one of the election's groups.

| Say | Not |
|---|---|
| People in these groups may vote | Members of these groups may vote |
| Allow people to change their vote | Allow members to change their vote |
| You may only see this page if you are allowed to take part | You may only see this page if you are a member |
| Being in a group, including a smart group, is checked at the moment someone acts | Group membership is checked |

Other words to use as written: **voter** (not user), **person** and **people** (not persons, contacts), **organiser** for the staff who run an election, **nominee** before a person accepts and **candidate** after. Use **contact** only for a CiviCRM contact record or a permission name such as "View All Contacts".

## Exceptions

| What | Why it keeps the word |
|---|---|
| Database columns and API parameters, such as `member_id` and `member_nominee` | Renaming would break existing sites and integrations for no benefit to a reader. The schema comments say what they mean. `WordingTest` does not scan DAO files |
| A proper name, such as the "Beekeepers Association of Australia" test site | It is a name, not a description of a person |
| The sentence "No CiviCRM Membership is needed" | The one allowed explanation (`WordingTest::ALLOWED`). Add another explaining sentence to that list only if it is truly needed |
| Files outside what the test scans (`developer/`, `AGENTS.md`, `CONTRIBUTING.md`, `tests/`, comments in PHP) | The test looks at what people read. Keep the developer guide on the rule as well, and use "member" only for a column or a quoted name |
| History pages in `developer/history/` | They record what was written at the time |

## What the test checks

`tests/phpunit/CRM/Elections/WordingTest.php` runs in the headless PHPUnit suite and in CI. It reads what a person can read, not every line of code:

- **Where it looks:** `README.md`, `elections.php`, and the `templates`, `msg_tpl`, `docs`, `ang`, `js`, `managed`, `settings`, `xml`, `CRM`, `Civi`, `api` and `Job` folders (`.tpl`, `.md`, `.html`, `.js`, `.php` and `.xml` files; DAO, `vendor` and `node_modules` are skipped).
- **In PHP** only strings passed to `ts()` or `E::ts()` are read. **In Smarty and HTML** the text on the screen is read (what is inside `{ts}` and plain text, not variables or markup). **In JavaScript** every quoted string is read. Other files are read whole.
- **Test 1, `testNothingAPersonReadsSaysMemberOrMembership`:** fails on `member`, `members`, `membership` or `memberships` (any case, whole word), after removing the allowed sentence. The message lists each hit with some context.
- **Test 2, `testNothingAPersonReadsUsesWordsTheStandardsRuleOut`:** fails on `please`, `click here`, `Return to Elections` and `rejected nomination`. It skips `js/`, `docs/` and `README.md`, because scripts hold code as well as words and the documentation may ask readers for something.

A word that the test does not catch is still wrong. Check the glossary word by word when you write text (a different word for the same thing is an error even if it reads well), and a Playwright assertion that quotes old wording must change in the same commit.

When the test fails, change the text. Do not add to the allowed list to make it pass.

## Writing help text

Help text sits under a field and says what the field does and what the choice means.

- A full sentence that ends with a full stop, at most two sentences. Labels, headings and buttons have no full stop.
- Say the effect, not the mechanism: "Voters see the candidate's name and statement. Photos are shown only if the box above is ticked." Not "Controls the show_photos flag."
- Give an example when the value is a format: "For example, Against or Reject." Examples of people use the invented Robert Irwin with the nickname Rob (the name format help).
- A checkbox label says what happens when it is ticked ("Allow people to change their vote"). It is not a question.
- Do not use jargon: checksum, API, entity, token, hash. Say **personal link** and **audit log fingerprint**.
- Say the consequence of a choice that locks something ("Once nominations start, the election cannot be edited").
- Keep the same words in the form, the list column and the documentation, and quote the screen's exact title and button text in `docs/`.

## Writing emails

The message templates are in `msg_tpl/` (subject, text and HTML parts for each) and registered in `managed/MessageTemplates.mgd.php`.

- **Title:** `Election - <verb phrase>`, a plain hyphen, then a short sentence-case verb phrase: "Election - Invite nominee", "Election - Invite written-in person", "Election - Remind written-in person", "Election - Close written-in invitation". New templates follow this rule and are listed in `docs/reference_emails_and_jobs.md`.
- **Subject:** what the person is asked to do: "Accept or decline your nomination for <position> in <election>" (the shipped nominee email).
- **Body, in this order:** who is writing and for which election; what the person is asked to do; by when, with the time zone ("Times are shown in Australia/Sydney."); one link or button using the screen's own word (**Accept Nomination**); what happens if they do nothing. Never any choice a person made on the ballot.
- **Voice:** second person, plain, no "please" for a normal step.
- **Escape every variable** (`{$name|escape}`); election and position names can contain markup.
- **Changing the shipped wording needs an upgrade step.** CiviCRM's managed entities never overwrite a message template that already exists, so installed sites keep the old text. `upgrade_20120` shows the pattern: it rewrites the reserved original always, and the editable copy only when it still matches the reserved original, so a site's own edits are kept.

## Writing error messages

A person reads these on a screen, so they follow [section 7 of the standards](ui-standards.md#7-messages-notices-and-errors).

- Plain sentences in this order: **what is wrong or not allowed**, **why**, **what to do instead**. Do not blame the person.
  - Good: "Voting method cannot be changed once people have voted or the results are generated." (the pattern of `CRM_Elections_Helper_Guard`, which names the setting by its label)
  - Bad: "Invalid value." and "Error: Member is not allowed to vote in given election."
- Name the field or the thing: "Voting must close after it opens", not "Date error".
- Say how to get access when access is refused: "You need to be logged in to see this election. Log in, or use your personal link."
- Never show a column name (`voting_method`), a code, a stack trace or a database message. Technical detail goes to the CiviCRM log (`Civi::log()`).
- Every message goes through `ts()` with `%1`, `%2` for variable parts, so it can be translated. Do not join pieces of sentences.
- **Throw `CRM_Elections_Exception` for every refusal a person should read.** On CiviCRM 6.19 an AJAX caller sees only a generic "Sorry an error occurred" for any other exception; on 6.16 the message shows either way, so a plain exception passes on 6.16 and fails on 6.19. Test the message on both versions.
- Success messages are one past-tense sentence naming the thing: "Election saved."
