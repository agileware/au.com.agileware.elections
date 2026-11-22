## What this changes

<!-- Features and changes only. Name the GitHub issue it closes (Closes #N). -->

## How it was tested

<!-- Which suites ran, on which CMS and CiviCRM version (see developer/testing/). -->

- [ ] PHPUnit
- [ ] Playwright on WordPress, Drupal and Joomla
- [ ] CiviCRM 6.16 and 6.19

## Screens

<!-- Only when a screen changes: tick every box, or say why one does not apply. The sections are those of
     developer/standards/ui-standards.md, where this list comes from (section 14). -->

- [ ] The heading names the screen, and a description says what it is (not needed for dialogs and self-evident forms).
- [ ] Breadcrumb as 3.3 on full back end pages; **Back** and **Back to Manage Elections** in the right order and place.
- [ ] Buttons in the place and order of 3.2, labelled as 3.4, one primary, the right class family.
- [ ] Field labels as section 5: sentence case, unique on the form, the same label for the same value everywhere.
- [ ] Every word is in the glossary or is a plain word. No machine names, codes or truncated values. No "member".
- [ ] Capitalisation and punctuation as 2.3; no "...".
- [ ] Dates, times and the time zone as section 8.
- [ ] Empty, success, warning and error states written as section 7; refusals use `CRM_Elections_Exception`.
- [ ] Destructive actions have a confirmation as section 5.
- [ ] Headings, landmarks, names, keyboard, focus, contrast and announcements as section 10; axe and keyboard check at 375, 768 and 1280 px.
- [ ] Nothing shows how someone voted.
- [ ] Strings go through `ts()` with no joined sentences; user text is escaped.
- [ ] Checked on WordPress, Drupal and Joomla, and on CiviCRM 6.16 and 6.19.
- [ ] The documentation page and its screenshot show the same words.
- [ ] A database change has an upgrade step and a test (see [`developer/standards/git-and-pull-requests.md`](../developer/standards/git-and-pull-requests.md)).
