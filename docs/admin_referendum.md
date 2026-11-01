# Referendum questions

A position can be a **referendum question** instead of a seat people stand for: a yes or no question that voters answer on the same ballot as the positions. One election can hold both.

## Adding a question

**Add Position**, then set **Type** to *Referendum question*. The seats and write-in fields go away and these appear:

- **Wording of Yes / No / Abstain**: what the voter sees, for example For and Against, or Approve and Reject. Yes and No always exist. Abstain only appears if you give it wording. An abstention counts towards the quorum and not towards the percentage.
- **Passes when**: the share of Yes out of Yes plus No needed to pass. *Simple majority* means more than half, so a tie fails. *Two thirds*, *three quarters* or another percentage mean at least that share.
- **Quorum** (optional): the share of the people allowed to vote who must take part (abstentions count). If fewer take part the result is "no quorum, not decided". Leave it empty for no quorum.
- **Resolution**: the full wording, shown above the question on the election page, the ballot and the results.

The question and its wording are frozen once the election starts, like every position.

## On the ballot

Each question is a set of radio buttons. A voter chooses one, or leaves the question unanswered, and can change their answer as long as changing a vote is allowed. The review step shows the answer before anything is saved. Voters are not asked to rank anything.

## Results

The results page shows **Passed**, **Not passed** or **No quorum** for each question, with the resolution text. In line with the rest of the results pages it does not show counts. The audit log, the integrity check and anonymising treat the answers like every other ballot row: with anonymised votes the voter and the time of each answer are removed when the results are generated.

## Good to know

- A question has no nominations, candidates or write-ins.
- A voter who answers only the question and no position still has a valid ballot.
- Copying an election or a position keeps the question, its wording, threshold, quorum and resolution.
