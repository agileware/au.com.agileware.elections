# Standards

The rules for how changes to the extension look, read and are merged. These are reference pages: they describe the rules that are in force now, and change with the code. The reasons behind them are in [`../decisions/`](../decisions/README.md).

| Page | Read it when |
|---|---|
| [`ui-standards.md`](ui-standards.md) | You add or change a screen, a message, a label, a button, a list, a form or an email. Contains the glossary, the page frame, button placement and order, the breadcrumb, lists, forms, the front end, accessibility and the **checklist for reviewing a pull request** |
| [`wording.md`](wording.md) | You write any text a person reads: "people, not members", the list of words, `WordingTest`, how to write help text, emails and error messages |
| [`git-and-pull-requests.md`](git-and-pull-requests.md) | You branch, commit, open a pull request or change the database: branches, commit and pull request rules, the required checks and review, upgrade steps and tests, how a release is cut |

Where the three apply:

- **Changing a screen or text:** `ui-standards.md` and `wording.md`, then the checklist at the end of `ui-standards.md` in your pull request.
- **Changing the database:** `git-and-pull-requests.md` (an upgrade step and a test), and [`../architecture/upgrades.md`](../architecture/upgrades.md).
- **Anything:** `git-and-pull-requests.md` for the branch, commit and review rules, and the short list in [`AGENTS.md`](../../AGENTS.md).

When a page and the code disagree, trust the code and fix the page in the same pull request. Rules that the code cannot meet are listed as exceptions in `ui-standards.md` (section 13).
