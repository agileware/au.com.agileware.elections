# Mobile voting web app (2.4 item 1)

Written 2026-11-22 from the maintainer's idea of the same day and some research. Discovery only: nothing here is decided or built. Each question at the end has a recommendation for the maintainer to confirm or change.

## The idea

A single page web app for voting, fast to load and to submit, that works the same on WordPress, Drupal and Joomla. People do not log in: they ask for a voting link and the link is their way in.

The home page works in one of two ways, chosen by the site:

1. **List of elections.** The home page lists the elections that are open for voting. Each has **Request my voting link**.
2. **Election code.** The home page asks for the code of the election (printed on a notice, sent in a newsletter, or in a QR code). Once the code is entered, the person can request their voting link for that election.

Either way, the person enters their email address and gets an email with a voting link that only they can use. The link opens their ballot in the app; they vote and see the receipt.

The app has its own simple design that the site can adjust (banner, logo, colours, text, pictures, instructions), and optional QR codes so people can scan a printed notice and go straight to the election.

## How it fits with what exists

- **The pages stay.** Today people vote on CiviCRM pages, logged in or with a personal link (`cid` and `cs`, CiviCRM's checksum) that the extension emails (`Mailer::link()`, only when the election sets `allow_checksum_access`). The app is a second way in, not a replacement. The existing pages are also the fallback when JavaScript is off.
- **The rules stay on the server.** Who may vote, the stage dates, one ballot per person, revoting and anonymising are enforced in the data layer (`Guard`, the BAO checks, `addvotes`) and in `Eligibility` and `Process` (see [../../architecture/security.md](../../architecture/security.md)). The app only calls them; it decides nothing itself.
- **CiviCRM already has the building blocks** (checked in 6.16.5 and 6.19.5 source):
  - `authx` and the `crypto.jwt` service make signed, expiring tokens for a contact.
  - Afform uses them for page tokens (`Civi\Afform\PageTokenCredential`, `?_aff=Bearer …`, then an `X-Civi-Auth` header on each AJAX call), and its email verification links expire after 10 minutes (`Civi\Afform\Tokens`).
  - The personal checksum lasts `checksum_timeout` days (site wide, default 7) and can be used many times.

## Recommended design

### One route, its own page

A public route, for example `civicrm/elections/app` (the site can give it a short address such as `/vote`), sends a complete HTML page of its own: the app shell, its own stylesheet and one script. It does not go through the CMS theme. That is what makes it fast and the same on all three CMSs: no theme CSS or scripts to load, no CMS layout widths (the Joomla and Olivero width problems found in 2.2 do not apply), and one design to test.

A later option is an **embed**: the same app as a custom element (`<civicrm-election-app>`) placed in a CMS page by a WordPress shortcode, a Drupal block or a Joomla module, for sites that want it inside their own page. A custom element keeps its styles in its own shadow DOM, so the theme cannot break it and it cannot break the theme.

### Its own design, adjustable

**Recommendation: the app has its own design, and the site can override it.** CMS themes differ too much to rely on (2.2 needed fixes for Olivero, Cassiopeia, Twenty Twenty-Five and Riverlea dark mode), and a standalone page cannot load the theme anyway. The design uses CSS variables (colours, font, corner radius, spacing), follows the user interface standards ([../../standards/ui-standards.md](../../standards/ui-standards.md)) and follows the device's light or dark setting.

Settings, in the extension's settings page (CiviCRM settings, so no new table):

| Setting | Notes |
|---|---|
| App on or off | Off by default. |
| Home page mode | List of elections, or election code. |
| Title, banner image, logo and its alt text | Pictures uploaded the way the results logo is. |
| Introduction and instructions | Rich text, shown on the home page and above the ballot. |
| Footer text | Contact details, privacy note. |
| Colours | Main and accent colour; contrast checked when saved. |
| Extra CSS | For sites that want more; loaded after the app's own. |

Per election: **Show in the voting app** (yes or no), and optional app instructions for that election.

### Framework

**Recommendation: Svelte 5, built with Vite, compiled output committed to the extension** (`js/app/`), so a site needs no build tools, as today.

| Option | Why or why not |
|---|---|
| **Svelte 5** | Compiles to plain JavaScript with a very small runtime (about 2 to 3 KB compressed in published comparisons), and can compile to a custom element for the embed. |
| Preact (with `htm`) | About 3 to 4 KB, familiar React style; it can even run without a build step. A good second choice. |
| SolidJS, Lit | Similar sizes. Lit is web components first; Solid has a smaller community. |
| AngularJS (Afform) | Already in CiviCRM, but large and old, and loads CiviCRM's whole front end. Against the "fast to load" goal. |

Target: the shell and script under 50 KB compressed together, first view in under a second on a mid-range phone on 4G. No fonts, scripts or images from other sites (privacy, and a strict Content Security Policy).

### How the app talks to the server

A few small JSON endpoints under the app's route, written in PHP on the extension's own helpers (`Eligibility`, `Process`, `ElectionStatus`, the vote-saving code), rather than APIv4. APIv4 needs `administer Elections` for these entities, and opening it to people with a token would be a bigger change to security. Each endpoint returns only what the app needs, with output escaped the same way the pages do.

| Endpoint | Does |
|---|---|
| `elections` | The open elections shown in the app (list mode), or one by code. Public: names, stage, dates only. |
| `request-link` | Takes election and email; always gives the same answer. |
| `session` | Exchanges the emailed link for a short session (see below). |
| `ballot` | The person's ballot: positions, candidates, statements and pictures as the election allows, whether they have voted. |
| `vote` | Saves the ballot through the existing rules; returns the receipt. |

### Requesting the voting link

The flow and the defences, from the research on email sign-in links:

1. The person enters their email address (and the election code in code mode). Optionally a CAPTCHA, using CiviCRM's reCAPTCHA settings where the site has them.
2. **Always the same answer**, whether or not the address is known or may vote: "If you can vote in this election, we have sent you a link. Check your email." This stops anyone finding out who is on the voting list.
3. **Limits**: a few requests per address and per IP address in an hour, and a quiet minimum time between emails to one address. CiviCRM has no general rate limiter, so this needs a small table (below).
4. The server finds the contacts with that email who may vote in the election (in `allowed_groups`, not deleted, not already voted unless revoting is allowed) and sends one email per contact. A shared family address gets one link per person, each named in the email.
5. **The link is a signed token, not the checksum**: a JWT from CiviCRM's `crypto.jwt` with the contact, the election and an expiry (recommended 60 minutes; a setting), scope `elections-app`. It works only for that election, unlike a checksum, which opens everything for 7 days.
6. **Mail scanners do not use it up.** Many work mail systems open links before the person does. Opening the link only loads the app; the app then sends the token to `session` by POST (a scanner that only fetches the page does nothing). `session` marks the token used and returns a short session for the ballot (for example 30 minutes, renewed while the page is open).
7. **Used once.** Each token has an ID; `session` refuses an ID already used, in one atomic database write. If the person needs to come back, they request a new link.

### QR codes

- **Recommended: a poster QR code per election.** The election overview gets **QR code for the voting app**: a printable SVG or PNG of the app address with the election code, with the address printed under it in plain text so people can check where it goes (advice against QR code phishing: show the destination). The code is not a secret, so a QR code of it is safe to print.
- Made on the server or in the browser with a small, dependency-free MIT library (for example `uqr` or `lean-qr`, a few KB). CiviCRM has no QR library of its own.
- **Not recommended for now: personal QR codes** (a person's voting link as a QR code in the email or on screen). It would be a credential in a picture, which mail filters cannot check and a camera can capture over a shoulder. Could be an option later.

### Offline, installing, privacy

- A small service worker caches **only the app shell**, so it opens quickly and shows "You are offline" with no signal. **Ballots are never queued or saved offline**: voting needs a connection, and the app says so.
- A web app manifest lets people add it to their home screen. Optional; no push notifications.
- Nothing about the ballot is kept in the browser after submitting: no `localStorage` of choices, session token in memory only, `Referrer-Policy: same-origin` (as the pages have), and the page is never cached by proxies.

### Accessibility

The same standards as the pages: keyboard, screen reader, 200 percent zoom, forced colours, 44 px touch targets, and no time limit that ends a ballot without warning (the session renews while the page is open; if it does end, the choices stay on screen and the person can request a new link without losing them). Ranked ballots reuse the 2.2 drag-to-rank behaviour, with buttons as well as dragging.

## Database changes (each needs an upgrade step and a test)

| Change | Why |
|---|---|
| `civicrm_election.app_code` (short public code, unique, for example 6 letters and digits without look-alikes) | The code people type or scan. The numeric ID would work but shows how many elections the site has, and is easy to mistype. |
| `civicrm_election.show_in_app` (boolean, default no) and `app_instructions` (text) | Per election settings. |
| New table `civicrm_election_app_request` (hashed email, hashed IP, election, time) | Rate limits. Old rows removed by the scheduled job. |
| New table `civicrm_election_app_token` (token ID, contact, election, used at) | One use per link. Old rows removed by the scheduled job. |

## Testing

- PHPUnit for the endpoints: same answer for known and unknown addresses, the limits, a token for one election refused for another, a used or expired token refused, voting rules unchanged (reuse the `SecurityTest` and `CouncilPersonaTest` cases through the endpoints).
- Playwright with phone projects (375 px, touch) on the three CMSs, as personas: request a link, read it from Mailpit, vote, revote where allowed, and the refusals. The layout and accessibility page walk covers the app's screens.
- A size check in CI that fails when the app's script grows past its budget.

## Questions for the maintainer

| # | Question | Recommendation |
|---|---|---|
| M1 | Own design or the site's theme? | Own design with settings and extra CSS; embed in a theme page later. |
| M2 | Which framework? | Svelte 5 with Vite, built files committed. Preact is the second choice. |
| M3 | Home page: list, code, or the site chooses? | The site chooses; list by default, and only elections set to **Show in the voting app** appear. |
| M4 | What is the "election ID" people enter? | A new short public code, not the database ID. |
| M5 | Link: new single-use token or the existing checksum? | New token, one election, 60 minutes, used once; the checksum links in the emails stay as they are. |
| M6 | Must the election also allow personal links (`allow_checksum_access`) to be in the app? | No: the app has its own per-election switch, because it does not use the checksum. |
| M7 | QR codes: which? | Poster QR per election only; no personal QR codes for now. |
| M8 | Should nominations, seconding and results be in the app too? | Not at first: voting only, with a link to the results page once results are published. |
| M9 | Installable (manifest and service worker)? | Yes, shell only; no offline ballots. |
| M10 | Which release? | Discovery and these answers in 2.4 (as moved on 2026-11-21); build after STV (2.3). |

## Sources

- CiviCRM source, 6.16.5 and 6.19.5: `settings/Core.setting.php` (`checksum_timeout`, default 7 days), `ext/afform/core/Civi/Afform/Tokens.php` and `PageTokenCredential.php`, `ext/authx`.
- Email sign-in link practice: [Auth0, passwordless best practices](https://auth0.com/docs/authenticate/passwordless/best-practices), [Stytch, email magic links](https://stytch.com/docs/consumer-auth/authentication/magic-links/overview), [MojoAuth, protecting magic link endpoints](https://mojoauth.com/blog/otp-magic-link-abuse-protection).
- How other voting services do it: [ElectionBuddy, voter authentication](https://electionbuddy.com/blog/2025/04/25/voter-authentication-methods-online/), [OpaVote, online elections](https://www.opavote.com/help/online-elections), [Simply Voting](https://www.simplyvoting.com/?p=17).
- Framework sizes (secondary sources; measure our own build): [PkgPulse, Lit vs Svelte](https://www.pkgpulse.com/guides/lit-vs-svelte-2026), [TodoMVC size comparison](https://dev.to/this-is-learning/javascript-framework-todomvc-size-comparison-504f).
- QR codes: [uqr](https://github.com/unjs/uqr), [lean-qr](https://github.com/davidje13/lean-qr); phishing advice from the [Australian Cyber Security Centre](https://www.cyber.gov.au/node/2327) and [TechTarget](https://www.techtarget.com/cybersecurity/feature/QR-code-phishing-14-quishing-prevention-tips).
