# How to create a new election

To create a new election, you should be a user who gains admin access for CiviCRM and follow these steps: 

1. Go to **Elections**  
![Click Elections](images/admin_election/01.gif)
2. Go to **Create Election**  
![Click Create Election](images/admin_election/02.gif)
3. Enter information about the new election  
- **Name and Description**: enter the name and description of the new election. It helps users to know more about the election that they will participate.  
![Set Name and description](images/admin_election/03.gif)
- **Visible Start Date**: set the date and time when user can start seeing it in the election section.  
- **Visible End Date**: set the date and time when the admin wants to hide the election from the election page.  
- **Nomination Start Date**: This date allows user to start nominating. This date should be after **Visible Start Date** and before **Visible End Date**  
- **Nomination End Date**: This date is to prevent user from nominating. This date should be after **Nomination Start Date** and before **Visible End Date**  
- **Advertise Start Date**: This date is to let user know that it waiting for nominees to accept their positions. This date should be after **Nomination End Date** and before **Visible End Date**  
- **Voting Start Date**: This date is to allow user to vote. This date should be after **Advertise Start Date** and before **Visible End Date**  
- **Voting End Date**: This date is to prevent user from voting. This date should be after **Voting Start Date** and before **Visible End Date**  
- **Results Start Date**: This date is to let users know when the results will be available. This date should be after **Voting End Date** and before **Visible End Date**  
![Set Election Dates](images/admin_election/04.gif)
4. Configure the election's settings:
- **Anonymise Votes**: If set to Yes, votes are anonymised once the election results are published, so it is no longer possible to see how an individual voted.
- **Allow Members to Change Vote**: If set to Yes, a member can vote again to change their earlier vote at any time before **Voting End Date**.
- **Allow non-logged in access**: If enabled, people can nominate, accept nominations and vote using a personalised link, without needing to log in. See [Participating without logging in](setup.md#participating-without-logging-in).
- **Number of Required Nominations**: The number of people who must nominate a person for a position before they become an eligible candidate. See [How to second a nomination](user_second_nomination.md).
- **Allowed by Groups**: The CiviCRM Groups and Smart Groups whose members are permitted to nominate, accept nominations and vote in this election.
5. Click **Create** to create a new election.

The election is created inactive. At least one [Position](admin_add_position.md) must be added, and the election must be [activated](admin_activate_election.md), before it is visible to end users.