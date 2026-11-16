# Working on this extension (for AI assistants and developers)

Elections for CiviCRM (`au.com.agileware.elections`): nominations, ranked choice or most-votes ballots, yes or no questions, write-ins and results, on WordPress, Drupal and Joomla. Start with [`developer/README.md`](developer/README.md); it explains how the guides, reference pages and history are organised.

## Rules

- **Branches and merging.** `master` takes changes only through a pull request with the checks `phpunit / phpunit`, `upgrade / upgrade` and `playwright / playwright` passing and one review. Never push to `master`. Do not tag or run **Cut release** unless the maintainer asks.
- **Commits.** Short and descriptive, referencing the GitHub issue where there is one. No AI or assistant attribution lines in commits or pull request text. A pull request description lists the features and changes only.
- **Wording.** Everyone who takes part is "people" (voters, nominees, candidates, organisers), never "member" or "membership", except in proper names. `WordingTest` fails on it in anything a person reads, including `docs/`. Database columns and API parameters keep their names.
- **Database changes** need an upgrade step in `CRM_Elections_Upgrader` (the next free number is above the last `upgrade_2xxxx`) and a test in `UpgraderTest`, so existing installs upgrade.
- **Test on both CiviCRM 6.16 and 6.19.** They behave differently (6.19 hides AJAX error messages unless a `CRM_Elections_Exception` is thrown, and needs `is_public` for front end pages).
- **Test every screen as the people who use it,** on all three CMSs for anything that changes a screen. The front end is tested as personas through personal links, not only as an administrator.
- **Templates are Smarty 5.** Back end screens follow the user interface standards in [`developer/standards/ui-standards.md`](developer/standards/ui-standards.md); wording rules are in [`developer/standards/wording.md`](developer/standards/wording.md).
- **Never** commit passwords, personal links or API tokens.
- **Run long jobs in the background** with a log file and check the log; do not poll in a loop. Never run two test jobs on one site, and do not deploy while one runs.
- **Anything outward-facing** (pushing, opening or merging pull requests, GitHub settings, posting comments) waits for the maintainer's go-ahead.
- **Where to look.** Test sites and how to run every suite: `developer/environments/`, `developer/ddev-kit/` and `developer/testing/`. How the code fits together: `developer/architecture/`. What was decided and why: `developer/decisions/`. What is planned next: `developer/roadmap/`.
