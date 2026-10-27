# Types of elections and options

An election is a set of **positions**, and each position is one of two kinds. How the votes are cast and counted, who may take part and how people get on the ballot are all options you choose when you set the election up. This chapter helps you choose; the other chapters show how to set each one up.

## The two kinds of position

| Kind | What voters do | How it is decided | Set up in |
|---|---|---|---|
| **Election position** | Choose candidates for one or more seats | By the election's **voting method** (below) | [How to add a position](admin_add_position.md) |
| **Referendum question** | Answer a yes or no question (and, if you allow it, abstain) | By a pass threshold and an optional quorum | [Referendum questions](admin_referendum.md) |

One election can hold both kinds. A voter's ballot lists the positions and the questions together, and they can answer any of them or none.

## Voting methods for election positions

The voting method is a setting of the whole election (**Participation** tab). It cannot be changed once people have voted.

| | **Ranked choice** (default) | **Most votes win** |
|---|---|---|
| On the ballot | Voters number the candidates in order of preference (1, 2, 3 ...) | Voters tick up to as many candidates as there are seats |
| How it is counted | Instant-runoff voting: the candidate with the fewest first choices is dropped and their votes move to the voters' next choice, until the seats are filled | The candidates with the most ticks fill the seats |
| Best when | There are several candidates and you want the winner to have broad support | A simple show-of-hands style election, or the same vote as your constitution describes |
| Ties | If candidates are level across the last seat the position is shown as a **tie** and every tied candidate is listed; the extension never picks one of them | |

Both methods work with several seats for a position, with write-ins and with every other option below. See [How to vote](user_vote.md) for what the voter sees, and [How to view election results](user_view_results.md) for the result messages.

## Getting people onto the ballot

| Option | What it does | Where |
|---|---|---|
| **Nominations by members** (default) | Members nominate, others second until the **Number of Required Nominations** is reached, and the nominee accepts or withdraws | [Nominations](user_nominate.md), [Seconding](user_second_nomination.md), [Accepting](user_accept_nomination.md) |
| **Candidates chosen by the organisers** | Turn **Allow people to nominate online** off. There is no nomination form; an administrator adds the candidates | [Candidates](admin_candidates.md) |
| **Write-ins** | A voter names a person who is not on the ballot. The person must accept before voting closes, and then appears on the ballot | [Write-ins](admin_write_ins.md) |

## Who can take part

| Option | What it does |
|---|---|
| **Allowed by Groups** | The groups (and smart groups) whose members may vote |
| **Nomination Groups** (optional) | A different set of people may nominate and second. Empty means the voting groups |
| **Candidate Groups** (optional) | A different set of people may stand. Empty means the voting groups |
| **Allow non-logged in access** | People act through a personal link in an email, without a website login. See [Participating without logging in](setup.md#participating-without-logging-in) |

Typical combinations: a *club* election where every member can vote, nominate and stand (just *Allowed by Groups*); a *board* election where every member votes but only people on a *Candidates* group can stand; an election where only a *Committee* group may nominate.

## Other options

| Option | What it does |
|---|---|
| **Anonymise Votes** | When the results are published, the link between a person and their ballot is removed |
| **Allow Members to Change Vote** | A member can vote again until voting closes; the latest vote replaces the earlier one. Works best with write-ins |
| **Number of Required Nominations** | How many people must nominate someone before they can stand |
| **Email people about their nominations** | The nominee is emailed when they have enough nominations. See [Emails and scheduled jobs](reference_emails_and_jobs.md) |
| **Display options** | Show or hide photos, statements, nominators and choose the name format |

## Choosing

1. *Do people choose between candidates, or answer a question?* Candidates: add **Election positions**. A question: add a **Referendum question**. Both: add both.
2. *How should a winner be chosen?* Ranked choice or most votes (one setting per election).
3. *Who gets on the ballot?* Members nominate, or the organisers add candidates, and decide whether voters can write people in.
4. *Who takes part?* Choose the groups, and whether people use a personal link.
5. *How private should the ballot be?* Decide on **Anonymise Votes** and whether voters can change their vote.

The [workflows chapter](workflows.md) shows what happens for each of these choices from start to finish.
