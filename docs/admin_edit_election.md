# How to edit an election

An election can only be edited before its **Nomination Start Date** has passed while it is [active](admin_activate_election.md). Once nominations for an active election have started, its details can no longer be edited.

To edit an election, you should be a user who gains admin access for CiviCRM and follow these steps:

1. Go to **Elections**  
![Click Elections](images/admin_election/01.gif)  
2. Go to **Edit**  
![Click Edit](images/admin_edit/03.gif)  
3. Edit information of an existing election    
- **Name and Description**: to edit the name and description of the election.  
- **Visible Start Date**: to edit the date and time when user can start seeing it in the election section.  
- **Visible End Date**: to edit the date and time when the admin wants to hide the election from the election page.  
- **Nomination Start Date**: to edit the nomination start date. This date should be after **Visible Start Date** and before **Visible End Date**  
- **Nomination End Date**: to edit the nomination end date. This date should be after **Nomination Start Date** and before **Visible End Date**  
- **Advertise Start Date**: to edit advertise start date. This date should be after **Nomination End Date** and before **Visible End Date**  
- **Voting Start Date**: to edit voting start date. This date should be after **Advertise Start Date** and before **Visible End Date**  
- **Voting End Date**: to edit voting end date. This date should be after **Voting Start Date** and before **Visible End Date**  
- **Results Start Date**: to edit results start date. This date should be after **Voting End Date** and before **Visible End Date**
- **Anonymise Votes**: to edit whether votes are anonymised once results are published.
- **Allow Members to Change Vote**: to edit whether a member can revote to change their vote before **Voting End Date**.
- **Allow non-logged in access**: to edit whether people can participate using a personalised link without logging in. See [Participating without logging in](setup.md#participating-without-logging-in).
- **Number of Required Nominations**: to edit the number of nominations a person needs to become an eligible candidate. See [How to second a nomination](user_second_nomination.md).
- **Allowed by Groups**: to edit the CiviCRM Groups and Smart Groups permitted to participate in this election.