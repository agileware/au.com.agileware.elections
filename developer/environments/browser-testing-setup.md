# Chromium in a DDEV site

Written from the existing setup, 2026-11-15; not yet followed from scratch. The result (the browser and its folders in the global cache, the same two files on three sites) was read from the machine on that date.

The Playwright suite runs inside the DDEV web container. Chromium needs system libraries and fonts, and the browser itself is large. Anything installed by hand inside a running DDEV container is lost when the site is stopped, which once broke the browser tests after every `ddev stop` and `ddev start`. The fix is two small files in the site's `.ddev` folder. They belong to the DDEV project, not to the extension repository.

| Piece | Kept where | Survives `ddev stop`, restart and rebuild |
|---|---|---|
| System libraries and fonts | In the web image, installed by a `web-build` Dockerfile | Yes, they are part of the image |
| The Chromium browser | DDEV's global cache volume, `/mnt/ddev-global-cache/ms-playwright` | Yes, and shared by every site on the machine |
| `node_modules` of the test suite | The deployed extension copy, `tests/playwright/node_modules` | Yes: `deploy-elections` does not delete it |

The copies of both files that new sites start from are in [`../ddev-kit/`](../ddev-kit/README.md) (in its `files/` folder: `Dockerfile.playwright` and `config.playwright.yaml`; the README there says where each goes).

## The two files

### `.ddev/web-build/Dockerfile.playwright`

DDEV builds the web image from every `Dockerfile.*` in `.ddev/web-build/`. This one installs the libraries Playwright reports as missing, for the Debian release of the DDEV 1.25 web image (Debian 13):

```dockerfile
RUN apt-get update \
 && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends at-spi2-common libasound2-data libasound2t64 libatk-bridge2.0-0t64 ... xserver-common xvfb \
 && (DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends fonts-freefont-ttf fonts-ipafont-gothic fonts-liberation fonts-noto-color-emoji fonts-tlwg-loma-otf fonts-unifont fonts-wqy-zenhei || true) \
 && rm -rf /var/lib/apt/lists/*
```

The full package list is in the file (48 library packages). Two details matter:

- The fonts are in their own command ending in `|| true`, so a font package that is missing from a Debian release does not break the image build.
- Libraries and fonts are separate from the browser download on purpose.

The WordPress, Drupal and Joomla test sites carry the same Dockerfile and the same `config.playwright.yaml`, byte for byte. The review site has neither: it does not run Playwright.

### `.ddev/config.playwright.yaml`

```yaml
web_environment:
  - PLAYWRIGHT_BROWSERS_PATH=/mnt/ddev-global-cache/ms-playwright
```

`/mnt/ddev-global-cache` is mounted from the Docker volume `ddev-global-cache`, which every DDEV site on the machine shares and DDEV keeps across stops and rebuilds. Playwright reads `PLAYWRIGHT_BROWSERS_PATH` for where to store and find browsers, so the browser is downloaded once for all sites. On 2026-11-15 the folder held `chromium-1248`, `chromium_headless_shell-1248` and `ffmpeg-1013`. Sites on different CPU architectures cannot share the folder.

## Apply and check

Do this for each site that runs Playwright. Replace `<site>` with the site folder and `<ext>` with the deployed extension, for example `/var/www/html/httpdocs/CRM/extensions/au.com.agileware.elections` (Drupal: `.../web/CRM/...`, Joomla: `.../CRM/...`).

1. Put the two files in the site's `.ddev` folder, then rebuild the image:

   ```bash
   cd <workspace>/<site> && ddev restart
   ```

2. Deploy the extension so the tests are in the site: `ddev deploy-elections`.

3. Install the suite's packages and the browser, once:

   ```bash
   ddev exec 'cd <ext>/tests/playwright && npm install && npx playwright install chromium'
   ```

4. Prove it survives a stop:

   ```bash
   cd <workspace>/<site> && ddev stop && ddev start
   ```

5. Check the libraries and the browser are still there. The first number must be `1`, and the folder must list `chromium-...`:

   ```bash
   ddev exec 'dpkg -l | grep -c "^ii  libnspr4"; ls $PLAYWRIGHT_BROWSERS_PATH'
   ```

6. Run one spec. Inside the container use `BASE_URL=http://localhost`:

   ```bash
   ddev exec 'cd <ext>/tests/playwright && BASE_URL=http://localhost npx playwright test specs/backend/frame.spec.ts --reporter=list'
   ```

   On Drupal and Joomla also set `CMS=drupal` or `CMS=joomla` (and `CIVI_CV=jcv` on Joomla). The full run is described in [`../testing/playwright.md`](../testing/playwright.md).

No reinstall is needed after a stop and start. The browser is downloaded once for the whole machine, so on the second and later sites step 3 finds it in the cache.

## Making a package list for another system

The list in the Dockerfile is what Playwright itself reports for Debian 13. For another Debian release or another CPU, start the site and ask Playwright:

```bash
ddev exec 'sudo apt-get update >/dev/null 2>&1; cd <ext>/tests/playwright && npx playwright install-deps --dry-run chromium'
```

It prints "Missing system dependencies" and the package names. Put those names in the `apt-get install` line and keep the font packages in their own `|| true` command. `npx playwright install --with-deps chromium` installs the same packages at run time, but they are lost at the next stop: do not rely on it, and do not install the libraries from a post-start hook or by hand.

## Troubleshooting

| Symptom | Cause and fix |
|---|---|
| `error while loading shared libraries: libnspr4.so` (or another `.so`) when Chromium starts | The libraries are not in the image. The Dockerfile is missing, wrong for this Debian release, or the image was not rebuilt: run `ddev restart` and recheck with the `dpkg` command above |
| `Executable doesn't exist at .../ms-playwright/chromium-...` | The browser is not in the cache, or `PLAYWRIGHT_BROWSERS_PATH` is not set. Check `ddev exec 'echo $PLAYWRIGHT_BROWSERS_PATH'`; if empty, `config.playwright.yaml` was not read (wrong folder or no `ddev restart`). Then run `npx playwright install chromium` |
| Browser works, then fails after a Playwright upgrade | A new Playwright version wants a newer browser build: run `npx playwright install chromium` again. Old folders in the cache can be deleted |
| `ENOTFOUND cdn.playwright.dev` during the browser download | A temporary DNS failure. Run the install again |
| Tests cannot reach the site (`ECONNREFUSED`) | `BASE_URL` was not set. The config default port is not served in the container; use `BASE_URL=http://localhost` |
| Many timeouts, or a failure on the first run after a deploy only | Several DDEV sites are running and the machine is overloaded, or the cache flush from the deploy is still settling. Stop the other sites; the failure on the first run only is a known flake and the retry passes |
| `npm install` or `playwright` not found after a deploy | The deploy rsync keeps `node_modules`, so it should survive; check you are in the deployed `tests/playwright`, not the source repository |

The error texts in the first column are Playwright's usual wording and were not captured from these sites. The causes and fixes come from the problems met when the pattern was set up.

## Not covered here

Running the whole suite, the order of its projects and what each spec checks are in [`../testing/playwright.md`](../testing/playwright.md). Never run two test jobs on one site at the same time.
