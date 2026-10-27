# Write-ins

A write-in lets a voter name a person who is not on the ballot. It is off unless you turn it on for a position.

## Turning it on

Edit the position and set **Write-ins per seat** to a number from 1 to 10 (0 turns it off). A position with 2 seats and 3 write-ins per seat gives each voter up to 6 write-in places. Positions can only be changed before the election starts.

## What a voter sees

On the ballot, under the candidates of that position, a **Write in a candidate** box with one place to start and an **Add another write-in** button up to the limit. The voter types at least three letters of a name and picks a person. Only people in the election's candidate groups (the voting groups unless you set candidate groups) are offered, only names are shown, and the number of searches a minute is limited, so nobody can browse the member list. In a ranked choice election the voter also gives the person a rank; in a most votes election the person counts as one of the voter's choices.

The voter is told that the vote counts only if the person accepts before voting closes, and that otherwise it is dropped (the ballot moves on to their next choice).

## What happens to the person written in

- The first time anyone writes them in they are emailed straight away (the message template **Elections - Person written in by a voter**), with links to accept or decline. Other voters who write them in later do not send another email.
- A reminder (**Elections - Reminder to a person written in**) is sent once, in the day before voting closes, to anyone who has not answered. It comes from the scheduled job **Elections Write-in Reminders**, which runs hourly.
- If they **accept** they become a candidate like any other and are shown on the ballot to everyone who votes from then on. Votes already written in for them count. They cannot withdraw afterwards.
- If they **decline**, or do not answer before voting closes, the votes written in for them are dropped. A person who declined cannot be written in again for that position.
- If voting closes and they have not answered, they are emailed once (**Elections - Voting closed before a person written in answered**) to say the opportunity to accept has passed and they can no longer accept. The email has no accept link. It comes from the scheduled job **Elections Write-in Closed Notices**. It is only sent for elections that closed in the last 14 days.
- If they open an accept or decline link after voting has closed, they see a message explaining that the opportunity has passed, that they are not on the ballot and that nothing more is needed, with a button to the election.
- A person who is already a candidate for the position is simply voted for. A person who was nominated but never accepted cannot be written in.

People who voted before the person accepted only see the new candidate if they vote again, so write-ins work best with **Allow Members to Change Vote** turned on. The election overview warns you when write-ins are on and changing a vote is off.

## What the records show

The election overview lists the people written in, with their position and where they stand. It never shows who wrote them in or how many votes they have. To protect the secrecy of the ballot, the record of a write-in does not keep the time it was made, nothing is added to the audit log when it happens, and the audit log has one summary line when results are generated (how many were written in, accepted, declined and unanswered). A person written in did not go through nominating and seconding; that is by design.

Write-ins are the only way anyone can be added to the ballot once voting has started.
