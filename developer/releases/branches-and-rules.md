# Branches and repository rules

Reference for how the branches of `agileware/au.com.agileware.elections` are used, which rules protect them, and what the security settings of the repository are. Read on 2026-11-15 from the GitHub API, the workflow files and `SECURITY.md`. Anything that could not be read with a non-administrator token is marked **Not verified**. How to release is in [README.md](README.md).

## Branches

| Branch | Purpose | Who writes to it |
|---|---|---|
| `master` | The default branch. Holds the next release candidate: after a release it holds exactly what was released (plus the developer guide and tests, which the package leaves out). | Pull requests only. |
| `release` | The filtered tree that is published: the same files as `master` minus the development-only paths. Tags point at its commits. Nobody develops on it. | Only the **Cut release** workflow. |
| Version branches (`version-2.1`, `version-2.2`, ...) | The work for one version, cut from `origin/master`, merged by pull request. Delete after the merge. | Developers. |

Notes:

- `release` is reset to the source commit on every cut and then force pushed, so anything committed to it by hand is lost. Earlier tags are not ancestors of the new `release` tip; the workflow gives GitHub the previous tag explicitly so the generated release notes are right.
- The `version-2` branch of the 2.0 work was deleted after 2.0.0. `version-2.1` was still on the remote on 2026-11-15.
- Before the first cut the `release` branch has to exist. It was created once by hand: branch `release` from `master`, remove `.github`, `tests`, `developer`, `AGENTS.md`, `.gitignore` and `CONTRIBUTING.md`, commit and push. The first cut resets it anyway. Do not use the **Init release** workflow (it refuses to run because `cut-release.yml` exists).

## The `master` rules

A repository ruleset named `master` (id 26294835, created 2026-11-01) is active on the default branch. Read from the API on 2026-11-15:

| Rule | Setting |
|---|---|
| Restrict deletions | On |
| Block force pushes (non-fast-forward) | On |
| Pull request required | One approving review; approval of the most recent push (so the last pusher cannot approve); all conversations resolved; merge, squash and rebase are allowed; stale approvals are not dismissed on a new push; no code owner review |
| Required status checks | `phpunit / phpunit`, `upgrade / upgrade`, `playwright / playwright` (all from GitHub Actions). The branch does not have to be up to date with `master` before merging. |

The three checks come from the workflows `phpunit-tests.yml`, `upgrade-tests.yml` and `frontend-tests.yml`. They run on every pull request and on every push to `master`. Their jobs are named `phpunit`, `upgrade` and `playwright`; renaming a job or its workflow would stop the required check from ever reporting, so change the ruleset first.

**Bypass.** The ruleset's bypass list is not visible to a non-administrator token. The project record says only organisation administrators can bypass (the repository administrator role was removed from the list). **Not verified.**

### The Actions bot and `master`

The **Cut release** workflow pushes straight to `master` in one case only: when its documentation step changes `mkdocs.yml`, `docs/` or the `Documentation` URL in `info.xml`. 2.0.0 did this once (commit "Standardise extension docs structure"), before the ruleset existed. 2.1.0 had nothing to change, so it pushed nothing to `master`.

So the record shows that the workflow can force push `release` (2.1.0 did), but **no run has shown that the Actions bot can push to `master` under the ruleset. Not verified.** Keep `master` in the standard shape so the step never needs to:

- no `README.md` in the repository root (the user guide opening is `docs/README.md`),
- links in `docs/README.md` written as absolute URLs (the workflow rewrites relative ones, which would be a change to push),
- `mkdocs.yml` and `docs/logo/agileware-logo.png` present,
- the `Documentation` URL in `info.xml` pointing at `docs/README.md` on the default branch.

If a cut ever fails at the documentation step with a rejected push, the cause is this rule: make the documentation change in a pull request, merge it, and run the cut again. Do not widen the ruleset's bypass without the maintainer's decision.

## The `release` branch

The GitHub API on 2026-11-15 listed only the `master` ruleset and reported `release` as not protected. The project record says a `release` ruleset was set up (no deletion, force push allowed for the workflow only) but it was never visible to the read-only token. **Not verified: whether a `release` rule exists.** If one is wanted, it should block deletion and block everyone except `github-actions[bot]` from force pushing, because every cut is a force push.

## Repository security settings

| Setting | State | How it was established |
|---|---|---|
| Security policy (`SECURITY.md`) | In the repository: 2.x supported, 1.x not; report by e-mail to Agileware's security address or by private vulnerability reporting; acknowledgement within 2 business days, progress updates at least every 5 business days. It is linked from `docs/`. | Read the file. |
| Private vulnerability reporting | On | Read from the API on 2026-11-15. |
| Code scanning (CodeQL) | Running: the CodeQL workflow runs on pull requests, pushes to `master` and on a schedule, all passing, with "dynamic" (default setup) workflows. | Workflow runs listed with the GitHub CLI. The setting page itself could not be read. |
| Dependabot alerts and security updates | Reported as on by the maintainer. There is no `dependabot.yml` in `.github`, so there are no version update pull requests. | **Not verified**: the API refuses a non-administrator token. |
| Secret scanning and push protection | Reported as on by the maintainer. | **Not verified**: same reason. |
| Repository visibility | Public | API. |

Where the settings live: the repository's **Settings**, **Code security** page. Changing any of them is a GitHub settings change and needs the maintainer's go-ahead.

## Secrets the workflows use

The test workflows need the `DOCKERHUB_USER` and `DOCKERHUB_TOKEN` secrets on the repository (the container image they use is private). The names are listed here; the values are never written down anywhere. **Cut release** needs no secret: it uses the workflow's own token (`contents: write`). The unused **Init release** workflow would need a `WORKFLOW_TOKEN` secret.
