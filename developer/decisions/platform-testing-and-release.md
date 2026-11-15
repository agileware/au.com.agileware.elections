# Decisions: platform, testing and release

Which CiviCRM versions and CMSs are supported, how the tests run, how releases are cut, and how the project is organised. The index of all decisions is [`README.md`](README.md). The plan's IDs (`D12`, `D15` and so on) are kept. `PLAT1`, `REL1` to `REL3` and `ENV1` were not numbered in the plan; they are recorded here because a developer needs them, and carry the date they were decided.

Each entry gives the decision, why, the date and where it shows.

## Platform

### D12 – Minimum CiviCRM version is 6.16
- **Decision.** Version 2 requires CiviCRM 6.16 or later. Sites on older CiviCRM stay on extension 1.x or upgrade CiviCRM first.
- **Why.** Version 2 was built and tested on 6.16.5 (WordPress) and 6.19.x (Drupal and Joomla); the code is written for Smarty 5.
- **Date.** 2026-10-27.
- **In the code.** `info.xml` (`<ver>6.16</ver>`); the README. Test on 6.16 and 6.19 both (see [`../environments/civicrm-versions.md`](../environments/civicrm-versions.md)).

### PLAT1 – WordPress, Drupal 11 and Joomla 5 are all supported
- **Decision.** Every change that touches a screen is tested on all three CMSs, with the same markup and no CMS-specific code beyond the recorded exceptions (the Joomla breadcrumb, `SD6`).
- **Why.** It was the project brief from the start; the CMSs differ in breadcrumb handling, button heights and sessions.
- **Date.** October 2026.
- **In the code.** [`../testing/cms-and-civicrm-differences.md`](../testing/cms-and-civicrm-differences.md).

## Testing

### D18 – Playwright runs inside the DDEV web container
- **Decision.** The browser tests run in the DDEV web container (with `BASE_URL=http://localhost`).
- **Why.** Node is not installed on the host.
- **Date.** October 2026.
- **In the code.** `tests/playwright/` and its README; [`../testing/playwright.md`](../testing/playwright.md).

### ENV1 – Chromium's libraries live in the DDEV image; the browser lives in DDEV's global cache
- **Decision.** Each DDEV site that runs Playwright installs Chromium's system libraries when the image is built (`.ddev/web-build/Dockerfile.playwright`) and keeps the browser itself outside the container (`.ddev/config.playwright.yaml`).
- **Why.** The browser tests failed after every `ddev stop` and `ddev start`, because the libraries and the browser were lost with the container.
- **Date.** 2026-11-02.
- **In the code.** The kit in [`../ddev-kit/README.md`](../ddev-kit/README.md); [`../environments/browser-testing-setup.md`](../environments/browser-testing-setup.md).

### D19 – Drupal 11 and Joomla workflows on GitHub Actions
- **Decision.** Contribute Drupal and Joomla workflow variants to `agileware/ci-workflows` (it runs WordPress only), after WordPress is green. DDEV in Actions is the recommended way, and a `WP_THEME`-style input comes first. Until then both CMSs are tested on DDEV before every release.
- **Why.** The browser tests of the other two CMSs should run on every pull request.
- **Date.** Decided October 2026; moved to version 2.2 on 2026-11-03.
- **Status.** Decided, not yet built. **In the code.** `.github/workflows/frontend-tests.yml` (WordPress only); see [`../testing/ci.md`](../testing/ci.md).

### G2 – CI runs the browser tests on the Astra theme
- **Decision.** The shared Playwright workflow keeps its default theme (Astra, through its `WP_THEME` input, default `astra`) because the suite passes on it, so callers do not set `WP_THEME`.
- **Date.** 2026-10-28 (plan 15). **In the code.** `agileware/ci-workflows`, `playwright-tests.yml`.

### G5 – The extension has its own `mkdocs.yml` navigation
- **Decision.** Commit a `mkdocs.yml` with the documentation chapters before cutting a release, so the generated stub is not used.
- **Date.** 2026-10-28 (plan 15). **In the code.** `mkdocs.yml` (`nav`).

## Releases and branches

### REL1 – Only 2.x is supported; there is no 1.3.4
- **Decision.** Elections 1.x is no longer supported, so only 2.x gets fixes and security patches. There is no 1.3.4, and a fix-only 2.0.1 was not made: the 2.1 fixes went into 2.1.
- **Date.** 2026-11-01.
- **In the code.** `SECURITY.md` (supported versions: 2.x).

### REL2 – `master` is protected by a ruleset
- **Decision.** A pull request with one approval (of the most recent push, conversations resolved) and the checks `phpunit / phpunit`, `upgrade / upgrade` and `playwright / playwright`; no deleting `master`, no force push; the Actions bot may push for Cut release. Merging is the maintainer's.
- **Why.** Cut release needs to push its docs commit and reset `release`; the first and second cuts (2.0.0 and 2.1.0) proved the bot is not blocked.
- **Date.** 2026-11-01 (settled by 2.1.0 on 2026-11-03).
- **In the code.** GitHub ruleset on the repository, described in [`../standards/git-and-pull-requests.md`](../standards/git-and-pull-requests.md). Not verified: the ruleset for the `release` branch (the account used to check had no admin access).

### REL3 – Releases are cut by the workflow from `release`
- **Decision.** The **Cut release** workflow builds the `release` branch from `master` minus `.github`, `tests`, `.gitignore`, `CONTRIBUTING.md`, `developer` and `AGENTS.md`, tags it and publishes the GitHub Release. `<version>` in `info.xml` must match what is run. The **Init release** workflow is not used (it refuses once `cut-release.yml` exists).
- **Why.** The civicrm.org extension directory installs whatever a version tag points at, in full, so development files must not be in the tagged tree.
- **Date.** Used for 2.0.0 on 2026-11-01, proved again for 2.1.0 on 2026-11-03.
- **In the code.** `.github/workflows/cut-release.yml`; [`../releases/README.md`](../releases/README.md).

## Project organisation

### D16 – The plan was its own git repository
- **Decision.** The working plan lived in a separate git repository, with every change committed. The DDEV site is not versioned.
- **Date.** October 2026. **Status.** Replaced by D30: the plan is archived and its useful content is in this developer guide.

### D15 – Project directory name
- **Decision.** The project folder was the existing workspace folder, not the shorter name in the first instructions.
- **Date.** October 2026. **Status.** Done; only of interest to someone reading the plan archive.

### D30 – A shared developer guide in the extension repository
- **Decision.** Re-organise the project plans, environments and tests into the `developer/` guide in the extension repository: guides, reference pages and history, with the 2.0 and 2.1 plans kept as history. It is public after a security and contributor check. The 47 MB of evidence images stay in the archived plan repository, with only a few optimised images committed. The kit builds test sites for WordPress, Drupal and Joomla from scratch. Planning for 2.2 starts in the new structure (`developer/roadmap/`).
- **Why.** Anyone starting cold should be able to follow the work already done without redoing it.
- **Date.** 2026-11-15.
- **In the code.** `developer/`, `AGENTS.md`; `developer/` and `AGENTS.md` are left out of releases by `cut-release.yml`.

## Open decisions

### Test data scripts in the repository
- **Question.** Ship the CMS-independent core of the Beekeepers site's seeding scripts (eight kinds of election, a stage mover) in the repository, run with `cv scr`, so any new site can get an election of each kind at a chosen stage in one command? Logins and pages stay a separate optional step.
- **Status.** Proposed in the 2.2 handover on 2026-11-04. `tests/seed/` is being added with this guide (see [`../testing/seeding.md`](../testing/seeding.md)); confirm with the maintainer that this is the agreed outcome.

### Version 2.2 branch name and push rule
- **Question.** What is the 2.2 branch called (suggested `version-2.2`, cut from `origin/master`) and is it pushed as it goes? The 2.1 rule (kept local until finished, one release pull request) applied to 2.1 only.
- **Status.** Ask the maintainer at the start of 2.2.

### D19, D13, D14, U14
D19 is above (decided, not built). D13 and D14 are in [`voting-and-elections.md`](voting-and-elections.md#open-decisions); U14 is in [`interface-and-wording.md`](interface-and-wording.md#open-decisions).
