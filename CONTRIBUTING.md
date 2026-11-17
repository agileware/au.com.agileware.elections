# Contributing

The developer guide ([`developer/`](developer/README.md)) explains how to set up test sites on WordPress, Drupal and Joomla, seed them with elections and run every test suite. [`AGENTS.md`](AGENTS.md) is the short list of rules for developers and AI assistants.

## Tests

Every push to `master`, and every pull request, runs three GitHub workflows (reusable workflows from [agileware/ci-workflows](https://github.com/agileware/ci-workflows)):

- **PHPUnit tests**: the headless PHPUnit suite (`tests/phpunit`).
- **frontend tests**: the Playwright browser tests (`tests/playwright`), a full mock council election on WordPress, plus the back end screens.
- **upgrade tests**: installs the last 1.x release, seeds elections in every stage, upgrades to the commit being tested and checks nothing was lost (`tests/upgrade`).

See [`developer/testing/`](developer/testing/README.md) for running each suite on a local DDEV site.

## Message templates

Every message template the extension provides is titled **Election - ** followed by a verb that says what the email does (for example *Election - Invite nominee*). Add the template in `managed/MessageTemplates.mgd.php` with its files in `msg_tpl/`, and list it in `docs/reference_emails_and_jobs.md`.

## Wording

Everyone who takes part is called "people" (voters, nominees, candidates, organisers), never "members": a membership is not needed. `WordingTest` fails if "member" or "membership" appears in anything a person reads. Database columns and API parameters (`member_id`) keep their names.

## Versioning and releases

- The extension version lives in the `<version>` element of `info.xml`. Bump it, and `<releaseDate>`, on `master` as part of any release.
- Releases are cut from a `release` branch, not `master` directly, so that `.github`, `tests`, `developer`, `AGENTS.md`, `.gitignore` and this file never reach client sites (civicrm.org's extension directory installs whatever a version tag points at, in full).
- To cut a release: bump `<version>`/`<releaseDate>` on `master` and merge that, then run **Cut release** from the Actions tab with a version matching what you just bumped. It builds the filtered `release` branch, tags it, and publishes the GitHub Release.
- The `release` branch exists (it was made for the first cut) and each cut resets it to the filtered tree, so never commit to it by hand. Details: [`developer/releases/`](developer/releases/README.md).
