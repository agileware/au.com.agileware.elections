# Roadmap

How planning works in this repository. Each version that is being planned has its own folder, `roadmap/<version>/`, with a **start here** page (`README.md`) and a page for any item that needs a longer write-up. When the version is released the folder moves to [`../history/<version>/`](../history/README.md), is frozen, and gets a summary page; what was left over goes into the next version's folder.

| Version | State | Start here |
|---|---|---|
| 2.0 | Released 2026-11-01 | [History](../history/2.0/README.md) |
| 2.1 | Released 2026-11-03 | [History](../history/2.1/README.md) |
| **2.2** | **Being built** | [roadmap/2.2/README.md](2.2/README.md) |
| 2.3 | Holds the Senate count (STV) and the small findings, moved from 2.2 | [roadmap/2.3/README.md](2.3/README.md) |
| 2.4 | Holds the mobile voting app discovery, moved from 2.2 | [roadmap/2.4/README.md](2.4/README.md) |

## What a version folder holds

- **`README.md`**: where things stand, the items in recommended order with what each needs, open decisions, traps, and a start-up checklist for the first session. Keep it current while the version is built: tick items off, move answered decisions to [`../decisions/`](../decisions/README.md) with their date.
- **One page per large item** (for example `stv.md` in 2.3): what is proposed, the problems to solve, the options, and the questions with a recommendation for each.

## Rules for planning

- The maintainer decides what is in a version and in what order. Bring a recommendation and the options, one decision at a time. Do not ask again what is decided.
- Record each decision in [`../decisions/`](../decisions/README.md) with its date, and say so in the roadmap page.
- Nothing outward-facing happens without the maintainer's go-ahead (see [`../../AGENTS.md`](../../AGENTS.md)).
- A feature that changes the database needs an upgrade step, and anything that touches ballots needs a security re-review ([../architecture/security.md](../architecture/security.md)).
