# Workflows

The diagrams in this chapter show how an election moves from start to finish, and how each kind of election differs. They follow each other: the election lifecycle (1), how people get on the ballot (2 to 4), how votes are cast (5 and 6) and how the results are decided (7).

## 1. Election lifecycle

An election moves through a fixed sequence of stages, each opened by a date set when the election was created - see [Creating an election](admin_create_election.md). The diagram shows the stages in order, the date that opens each one, and what happens in each.

```mermaid
flowchart TD
    A["Election created<br/>(inactive)"] --> B{"At least one position added?<br/>Admin activates the election"}
    B -->|Yes| C["Visible<br/>from Visible Start Date<br/>Election is listed on the website"]
    C --> D["Nominations<br/>Nomination Start Date to Nomination End Date<br/>People nominate and second candidates<br/>(skipped when the organisers choose the candidates)"]
    D --> E["Advertise Candidates<br/>Advertise Candidates Date to Voting Start Date<br/>Eligible candidates accept or withdraw"]
    E --> F["Voting<br/>Voting Start Date to Voting End Date<br/>Permitted people vote on positions and questions<br/>Write-ins are answered until voting closes"]
    F --> G["Voting closed<br/>People written in who did not answer are told<br/>the opportunity has passed"]
    G --> H["Results<br/>from Results Start Date<br/>Results are calculated by the scheduled job and published"]
    H --> I["Hidden<br/>after Visible End Date<br/>Removed from the website, results remain as a record"]
```

An election which is not [activated](admin_activate_election.md), or which has no positions, is never shown to end users, regardless of the dates. The settings of an election can only be changed before its Nomination Start Date, and several of them are locked once the first vote has been cast.

## 2. Nominations by people

The default way to get on the ballot. Each nomination for a position follows this path.

```mermaid
flowchart TD
    N0{"Is the person allowed to nominate?<br/>Nomination Groups, or the voting groups"} -->|No| N0x["Refused"]
    N0 -->|Yes| N1["Nomination submitted<br/>position and nominee<br/>only between Nomination Start Date and Nomination End Date"]
    N1 --> N1a{"Is the nominee in the Candidate Groups,<br/>or the voting groups?"}
    N1a -->|No| N0x
    N1a -->|Yes| N2{"Does the nomination have<br/>Number of Required Nominations?"}
    N2 -->|No| N3["Needs a second<br/>Need Second button is shown"]
    N3 -->|"Someone else seconds it"| N2
    N2 -->|Yes| N4["Nominee is an Eligible Candidate<br/>emailed if emails are on"]
    N4 --> N5{"What does the nominee do<br/>before Voting Start Date?"}
    N5 -->|Accepts| N6["Candidate<br/>appears on the ballot"]
    N5 -->|"Withdraws, or does not answer"| N7["Not a candidate<br/>not on the ballot"]
```

A person nominating twice for the same position, or seconding twice, counts once. A withdrawn nomination cannot be nominated again for the same position. See [How to nominate](user_nominate.md), [How to second](user_second_nomination.md), [How to accept](user_accept_nomination.md) and [How to withdraw](user_withdraw_nomination.md).

## 3. Candidates chosen by the organisers

When **Allow people to nominate online** is off, there is no nomination form and no seconding.

```mermaid
flowchart TD
    O1["Administrator adds a candidate<br/>Candidates screen, until voting starts<br/>person must be in the Candidate Groups or the voting groups"] --> O2{"Has the candidate<br/>already agreed to stand?"}
    O2 -->|"Yes: Has accepted is ticked"| O4["Candidate<br/>appears on the ballot"]
    O2 -->|No| O3["Emailed and asked to accept or withdraw<br/>before Voting Start Date"]
    O3 -->|Accepts| O4
    O3 -->|"Withdraws, or does not answer"| O5["Not on the ballot"]
```

See [Candidates](admin_candidates.md).

## 4. Write-ins

A position with **Write-ins per seat** above 0 lets voters add a person while voting is open. This is the only way anyone can join the ballot once voting has started.

```mermaid
flowchart TD
    W1["Voter writes a person in<br/>at least three letters, a person in the Candidate Groups<br/>up to the write-in limit for the position"] --> W2{"Already a candidate<br/>for this position?"}
    W2 -->|Yes| W3["Counts as a vote for that candidate"]
    W2 -->|No| W4["First time written in:<br/>person is emailed<br/>with links to accept or decline"]
    W4 --> W5{"Answer before voting closes?"}
    W5 -->|Accepts| W6["Becomes a candidate<br/>appears on the ballot for later voters<br/>votes already written in count"]
    W5 -->|Declines| W7["Votes written in are dropped<br/>cannot be written in again"]
    W5 -->|"No answer"| W8["Reminder in the last day<br/>then at voting close"]
    W8 --> W9["Voting closes<br/>person is told the opportunity has passed<br/>accepting is no longer possible<br/>votes written in are dropped"]
```

If the person opens their accept or decline link after voting has closed, they see a message explaining that the opportunity has passed and that nothing more is needed. Nothing records who wrote anyone in. See [Write-ins](admin_write_ins.md).

## 5. A voter's path through the ballot

```mermaid
flowchart TD
    V1["Voter opens the election<br/>Voting Start Date to Voting End Date"] --> V2{"In the voting groups?"}
    V2 -->|No| V2x["Cannot vote"]
    V2 -->|Yes| V3["Ballot lists the positions and questions"]
    V3 --> V4{"Voting method"}
    V4 -->|"Ranked choice"| V5["Number the candidates 1, 2, 3 ...<br/>each rank used once, one candidate ranked 1"]
    V4 -->|"Most votes win"| V6["Tick up to as many candidates<br/>as there are seats"]
    V3 --> V7["Referendum question:<br/>choose Yes, No or Abstain, or leave it blank"]
    V3 --> V8["Optional: write in a person<br/>where the position allows it"]
    V5 --> V9["Review: choices are shown, nothing is saved yet"]
    V6 --> V9
    V7 --> V9
    V8 --> V9
    V9 -->|"Go back and change"| V3
    V9 -->|"Confirm my vote"| V10["Vote recorded<br/>Your vote is shown to the voter only"]
    V10 -->|"Change of vote allowed,<br/>before Voting End Date"| V3
```

## 6. A ballot is saved

Whatever the kind of election, every vote is saved the same way, which is what keeps the ballot trustworthy.

```mermaid
flowchart TD
    S1["Confirm my vote"] --> S2{"Checks on the server<br/>voting open, voter allowed, candidates standing,<br/>ranks or ticks valid, answers valid"}
    S2 -->|Fails| S2x["Nothing is saved<br/>a message says why"]
    S2 -->|Passes| S3["Voter's earlier ballot is replaced in one step<br/>one ballot per voter, enforced by the database"]
    S3 --> S4["A Vote activity records that the person voted<br/>never how"]
```

## 7. From ballots to results

After the Voting End Date and on or after the Results Start Date the scheduled job counts the ballots once.

```mermaid
flowchart TD
    R1["Results job runs<br/>Elections Results Job"] --> R2["Ballot and result fingerprints written to the audit log"]
    R2 --> R3{"Anonymise Votes?"}
    R3 -->|Yes| R4["Voter, time and order removed from every ballot"]
    R3 -->|No| R5["Ballots kept as they are"]
    R4 --> R6["Results published"]
    R5 --> R6
    R6 --> R7{"Kind of position"}
    R7 -->|"Ranked choice"| R8["Instant-runoff count<br/>Winner by clear majority, or remaining candidates fill the seats"]
    R7 -->|"Most votes win"| R9["Most votes fill the seats<br/>level on the last seat: a tie, every tied candidate listed"]
    R7 -->|"Referendum question"| R10["Passed, Not passed or No quorum<br/>no counts shown"]
```

Results are generated once and cannot be regenerated or edited. The election overview checks the fingerprints and shows whether anything has changed. See [How to view election results](user_view_results.md) and [Security and audit](reference_security_and_audit.md).

## 8. A referendum question

```mermaid
flowchart TD
    Q1["Administrator adds a position of type Referendum question<br/>wording, pass threshold, optional quorum, resolution"] --> Q2["Voters answer on the ballot<br/>Yes, No, or Abstain if offered, or leave it unanswered"]
    Q2 --> Q3{"Quorum set?"}
    Q3 -->|"Yes, and fewer people took part"| Q4["No quorum<br/>not decided"]
    Q3 -->|"No, or enough took part"| Q5{"Share of Yes out of Yes plus No<br/>reaches the threshold?"}
    Q5 -->|Yes| Q6["Passed"]
    Q5 -->|No| Q7["Not passed"]
```

Simple majority means more than half, so a tie is *Not passed*. Other thresholds (two thirds, three quarters) mean at least that share. Abstentions count towards the quorum and not towards the percentage. See [Referendum questions](admin_referendum.md).
