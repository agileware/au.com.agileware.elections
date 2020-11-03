Elections (au.com.agileware.elections) is a CiviCRM extension which provides on-line election functionality (nominations and voting) to CiviCRM. 

The following sections describe the concepts and processes.

# Initial Set Up
Before creating an election, follow these [initial set up steps](setup.md) to configure your website and CiviCRM to host elections. 

# Creating an Election
To create a new election, you should identify the date of each stage in the election's process as well as name and description of the election.   
- **Name and Description**: Title the election and provide a brief description of the reason for the election. For example, Name: 2020 Legislative Assembly election; Description: Election of the Legislative Assembly for the 5 electorates of the ACT: Brindabella, Ginninderra, Kurrajong, Murrumbidgee and Yerrabi.  
- **Visible Start Date**: The date that user can start seeing the election and the public will know about the election as well.  
- **Visible End Date**: The election will be invisible to the public after this date.  
- **Nomination Start Date**: Users allow to nominate from this date and it should be after **Visible Start Date** and before **Visible End Date**.    
- **Nomination End Date**: Users do not allow to nominate after this date and it should be after **Nomination Start Date** and before **Visible End Date**.     
- **Advertise Start Date**: Waiting for nominee to accept their position. This date should be after **Nomination End Date** and before **Visible End Date**.  
- **Voting Start Date**: Users can start voting from this date and it should be after **Advertise Start Date** and before **Visible End Date**.     
- **Voting End Date**: Users cannot vote from this date and it should be after **Voting Start Date** and before **Visible End Date**  
- **Results Start Date**: Users can check the election results from this date and it should be after **Voting End Date** and before **Visible End Date**.  

For more information, see [How to create a new election](admin_create_election.md)  

# Editing an Election  
To edit an election, you should follow the rules of date and time for each stage in **Create an election**  

For more information, see [How to edit an election](admin_edit_election.md)

# Deleting an Election  

To delete an election, see [How to delete an election](admin_delete_election.md)  

# Positions  

A person is nominated for one or more positions in an election.
Each election must have at least one position defined.
There are no limits to the seats which can be defined.
The CiviCRM administrator must define the positions available prior to the nomination stage for the election.  

## Adding a Position

A position only can be added before the **Nomination Start Date** or if the election is inactive.  
For more information, see [How to add a position](admin_add_position.md)  

## Editing a Position

A position only can be added before the **Nomination Start Date** or if the election is inactive.
For more information, see [How to edit a position](admin_edit_position.md)

## Deleting a Position

A position only can be deleted before the **Nomination Start Date** or if the election is inactive.
For more information, see [How to delete a position](admin_delete_position.md)  

# Nominations 

To nominate a user, see [How to nominate a user](user_nominate.md)
User cannot nominate after the **Nomination End Date**
A user can nominate any user including self, as set in the Settings for the Election
There is no limit for how many nominations a user can receive.
The minimum number of  nominations for a user to become a candidate is set in the Settings for the Election

# Accepting a Nomination  

A user must accept a nomination to become a candidate for any position.
For more information, see [How to accept a nomination](user_accept_nomination.md)

# Voting  

User can only vote after **Voting Start Date**.
For more information, see [How to vote](user_vote.md)

# Results  

Results are published after the **Results Start Date**. Elections results are then available on the website, see example [election results](user_view_results.md)  
Election results are calculated using the Instant-runoff voting (IRV) method, see https://en.wikipedia.org/wiki/Instant-runoff_voting
  