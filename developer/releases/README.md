# How to release

Written from the existing setup, 2026-11-15; not yet followed from scratch. The same steps were carried out for 2.0.0 and 2.1.0 (the **Cut release** runs of both succeeded), and the workflow files, `info.xml` and the GitHub rules were read for this page.

This page takes you from "the work for the next version is finished" to "the version is published and its issues are closed". The branches and the rules that protect them are in [branches-and-rules.md](branches-and-rules.md).

Everything outward-facing in this list (pushing, opening and merging a pull request, running **Cut release**, commenting on issues) waits for the maintainer's go-ahead. See [`AGENTS.md`](../../AGENTS.md).

## Before you start

- The work is on a branch cut from `origin/master` (for example `version-2.2`), with every pull request check green on it.
- Any database change has an upgrade step in `CRM_Elections_Upgrader` and a test in `UpgraderTest` (see [../architecture/upgrades.md](../architecture/upgrades.md)).
- The browser tests have been run on Drupal and Joomla as well as WordPress. GitHub Actions runs WordPress only; the other two are run on DDEV before each release (see [../testing/ci.md](../testing/ci.md) and [../testing/playwright.md](../testing/playwright.md)).
- The checks have also run against CiviCRM 6.19, not only 6.16 (see [../testing/cms-and-civicrm-differences.md](../testing/cms-and-civicrm-differences.md)).

## 1. Choose the version

Versions are `major.minor.patch`. 2.x is the supported line (`SECURITY.md` lists 2.x as supported and 1.x as not); fixes go into the next 2.x release, there is no 1.x patch release.

- New features or new database steps: raise the minor number (2.1.0 to 2.2.0).
- Fixes only: raise the patch number.
- A release that raises the minimum CiviCRM version, or breaks something an organisation relies on, needs a decision from the maintainer first (see [../decisions/platform-testing-and-release.md](../decisions/platform-testing-and-release.md)).

If the supported versions change, update the table in `SECURITY.md` in the same pull request.

## 2. Bump `info.xml` in a pull request to `master`

In `info.xml` set:

| Element | Value |
|---|---|
| `<version>` | The new version, for example `2.2.0`. **Cut release** refuses to run unless this equals the version you give it. |
| `<releaseDate>` | The planned release date. The workflow does not check it, so change it if the release slips. |
| `<develStage>` | `stable` |

Open the pull request from your branch to `master`. The description lists the features and changes only (what changed for administrators, for people taking part, fixes, upgrade steps, documentation, housekeeping), with no testing or environment notes. Refer to the issues with `Refs #n`, not `Closes #n`: issues are closed after the release, not on merge. See [../standards/git-and-pull-requests.md](../standards/git-and-pull-requests.md).

## 3. Checks and review

`master` accepts changes only through a pull request that has:

- the three required checks passing: `phpunit / phpunit`, `upgrade / upgrade` and `playwright / playwright` (the first runs the PHPUnit suite, the second upgrades a 1.3.2 site to the commit and checks nothing was lost, the third runs the Playwright browser tests on WordPress; the upgrade check is described in [../testing/upgrade-test.md](../testing/upgrade-test.md)),
- one approving review of the latest push, and every conversation resolved. You cannot approve your own pull request.

The pull request can be merged, squashed or rebased; 2.0.0 and 2.1.0 were squash merged. After the merge the same three workflows run again on `master`. Wait for them to pass before cutting the release.

## 4. Run Cut release

1. In GitHub, open the **Actions** tab, choose **Cut release** and **Run workflow** on the `master` branch.
2. Enter the version, exactly as in `info.xml` (`2.2.0`, no leading `v`).
3. Run it. It takes under a minute (about 20 seconds for 2.1.0).

From a terminal with the GitHub CLI the same run is:

```bash
gh workflow run cut-release.yml --ref master -f VERSION=2.2.0
```

### What it does

`.github/workflows/cut-release.yml` calls the shared workflow `civicrm-extension-cut-release.yml` in `agileware/ci-workflows` (at `@main`). In order, it:

1. Checks out `master` with its full history and fetches the existing `release` branch (it stops with an error if that branch does not exist).
2. Checks that `<version>` in `info.xml` equals the version you gave.
3. Makes the documentation structure standard: creates `mkdocs.yml` if it is missing, moves a root `README.md` to `docs/README.md`, rewrites relative links in `docs/README.md` to absolute GitHub links, adds the logo, and points the `Documentation` URL in `info.xml` at `docs/README.md`. If any of that changes a file it commits the change straight to `master` (this is why `master` must stay in the standard shape: see [branches-and-rules.md](branches-and-rules.md)).
4. Resets the `release` branch to the source commit, then removes the excluded paths (below) and `phpunit.xml.dist`.
5. Commits `Release <version>`, tags that commit with the version, and force pushes `release` and the tag.
6. Publishes a GitHub Release for the tag (marked latest) with notes generated from the pull requests merged since the previous tag.
7. Tells docs.civicrm.org to rebuild the documentation. This is best effort: a warning here does not undo the release.

The tag therefore points at the filtered commit on `release`, not at the commit on `master`.

### What is left out of the package

The extension directory on civicrm.org installs whatever the tag points at, in full, so development files must not be in it. `cut-release.yml` passes this list:

| Left out | Why |
|---|---|
| `.github` | Workflows and CI configuration |
| `tests` | PHPUnit, Playwright, upgrade tests and test data scripts |
| `.gitignore` | Development only |
| `CONTRIBUTING.md` | Development only |
| `developer` | This developer guide |
| `AGENTS.md` | Rules for developers and assistants |
| `phpunit.xml.dist` | Always removed by the shared workflow |

These ship: `docs/`, `SECURITY.md`, `LICENSE.txt`, `mkdocs.yml`, `info.xml` and all the extension code. A new development-only file or folder at the top of the repository must be added to `EXCLUDE_PATHS` in `.github/workflows/cut-release.yml` and to the list in `CONTRIBUTING.md`.

Do not run the **Init release** workflow: it refuses to run because `cut-release.yml` already exists. The one-off creation of the `release` branch is in [branches-and-rules.md](branches-and-rules.md).

## 5. Check afterwards

- The workflow run is green and the GitHub **Releases** page shows the new version as latest.
- `git fetch --tags origin`, then `git ls-tree --name-only <version>` shows no `.github`, `tests`, `developer`, `AGENTS.md`, `CONTRIBUTING.md` or `.gitignore`.
- `git show <version>:info.xml` has the right `<version>` and `<releaseDate>`.
- The release notes list the merged pull requests. Edit the release text on GitHub if it needs a plain summary: reuse the pull request description.
- The browser tests on `master` stay green; look at the next scheduled CodeQL run too.
- If the maintainer wants the review site on the release, deploy the tag, not the working copy, take a database snapshot first, and run the database upgrade. Never run the DDEV `deploy-elections` command on a site that should keep the release code: it copies the working copy.

If **Cut release** fails: a version mismatch means fixing `info.xml` through another pull request; a missing `release` branch means creating it by hand; a rejected push to `master` in the documentation step means the standard structure is broken (see [branches-and-rules.md](branches-and-rules.md)). Fix the cause and run it again; ask the maintainer before deleting a tag.

## 6. Close the issues

Each GitHub issue that the release answers gets a comment and is then closed, by the maintainer:

1. List the issues the pull request referred to (`Refs #n`).
2. For each, write a short comment in plain words: what the person asked for, what the release now does, and the version (`Released in 2.2.0`). The 2.0.0 comments are in [../history/2.0/archive/14-release-notes-and-issue-comments.md](../history/2.0/archive/14-release-notes-and-issue-comments.md) as examples.
3. Link the commit or pull request that delivered it. Commit messages are short and name the issue (for example `Add option to hide candidate photos (#44)`).
4. Close the issue after posting.

## Upgrade expectations

A release is only safe if a site on the previous version can upgrade in place:

- Every schema change has a numbered step in `CRM_Elections_Upgrader` (`upgrade_2xxxx`). The last used is `20130`, so **the next free step is `20140`**. Steps can be run again without harm (columns are added only when missing).
- Defaults keep existing elections as they were (for example a new setting is off, or equals the old behaviour, for elections that exist before the upgrade).
- Message templates a site has edited are never overwritten.
- The **upgrade** check installs release 1.3.2, seeds elections in several stages, upgrades to the commit and checks that nothing was lost and the old elections still run. It passed for 2.0.0 and 2.1.0.
- When a release adds steps, run the upgrade test on all three CMSs before releasing (see [../testing/upgrade-test.md](../testing/upgrade-test.md)), and add checks to `tests/upgrade/verify-v2.php` for what the new step changed.
- Tell people what the upgrade does in the pull request description and the release notes: which step numbers ran, what they changed, and what existing data looks like afterwards.
- Downgrades are not supported.
