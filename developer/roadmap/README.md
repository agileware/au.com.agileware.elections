# Roadmap

How planning works in this repository. Each version that is being planned has its own folder, `roadmap/<version>/`, with a **start here** page (`README.md`) and a page for any item that needs a longer write-up. When the version is released the folder moves to [`../history/<version>/`](../history/README.md), is frozen, and gets a summary page; what was left over goes into the next version's folder.

| Version | State | Start here |
|---|---|---|
| 2.0 | Released 2026-11-01 | [History](../history/2.0/README.md) |
| 2.1 | Released 2026-11-03 | [History](../history/2.1/README.md) |
| 2.2 | Released 2026-11-22 | [History](../history/2.2/README.md) |
| **2.3** | **Next.** Holds the Senate count (STV), the small findings moved from 2.2, and CiviCRM 6.16 in GitHub CI | [roadmap/2.3/README.md](2.3/README.md) |
| 2.4 | Holds the mobile voting app discovery, moved from 2.2 | [roadmap/2.4/README.md](2.4/README.md) |

## Potential future features

Ideas that are noted but not planned for any version. Each waits for a real requirement (an organisation that needs it, a GitHub issue, or the maintainer's decision) before it is placed in a version.

| Idea | Where it came from | State |
|---|---|---|
| **Approval voting**: voters tick any number of candidates and the most ticks win. It would be an option on **most votes win** ("voters may tick any number") rather than a new counting method: a setting, a database column with an upgrade step, and tests. | Not from any user request or requirement. An AI assistant suggested it in the first version 2 plan (2026-10-27) while planning the "straight up elections" request in issue #3; see D14 in [../decisions/voting-and-elections.md](../decisions/voting-and-elections.md). | Deferred 2026-11-22 until there is a requirement. |

## What a version folder holds

- **`README.md`**: where things stand, the items in recommended order with what each needs, open decisions, traps, and a start-up checklist for the first session. Keep it current while the version is built: tick items off, move answered decisions to [`../decisions/`](../decisions/README.md) with their date.
- **One page per large item** (for example `stv.md` in 2.3): what is proposed, the problems to solve, the options, and the questions with a recommendation for each.

## Rules for planning

- The maintainer decides what is in a version and in what order. Bring a recommendation and the options, one decision at a time. Do not ask again what is decided.
- Record each decision in [`../decisions/`](../decisions/README.md) with its date, and say so in the roadmap page.
- Nothing outward-facing happens without the maintainer's go-ahead (see [`../../AGENTS.md`](../../AGENTS.md)).
- A feature that changes the database needs an upgrade step, and anything that touches ballots needs a security re-review ([../architecture/security.md](../architecture/security.md)).
