# Contributing

## Tests

Every push to `master` and `version-2`, and every pull request, runs three GitHub workflows (reusable workflows from [agileware/ci-workflows](https://github.com/agileware/ci-workflows)):

- **PHPUnit tests**: the headless PHPUnit suite (`tests/phpunit`).
- **frontend tests**: the Playwright browser tests (`tests/playwright`), a full mock council election on WordPress, plus the back end screens.
- **upgrade tests**: installs the last 1.x release, seeds elections in every stage, upgrades to the commit being tested and checks nothing was lost (`tests/upgrade`).

See `tests/playwright/README.md` for running the browser tests on a local DDEV site.

## Versioning and releases

- The extension version lives in the `<version>` element of `info.xml`. Bump it, and `<releaseDate>`, on `master` as part of any release.
- Releases are cut from a `release` branch, not `master` directly, so that `.github`, `tests`, `.gitignore` and this file never reach client sites (civicrm.org's extension directory installs whatever a version tag points at, in full).
- To cut a release: bump `<version>`/`<releaseDate>` on `master` and merge that, then run **Cut release** from the Actions tab with a version matching what you just bumped. It builds the filtered `release` branch, tags it, and publishes the GitHub Release.
- The `release` branch has to exist before the first cut. Create it once by hand: branch `release` from `master`, remove `.github`, `tests`, `.gitignore` and `CONTRIBUTING.md`, commit and push (the first cut resets it to the filtered tree anyway). Do not use the **Init release** workflow: it refuses to run because `cut-release.yml` is already in the repository.
