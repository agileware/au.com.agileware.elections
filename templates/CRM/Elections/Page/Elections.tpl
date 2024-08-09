{if $isElectionAdmin and !$isFromShortCode}
    <a href="{crmURL p="civicrm/elections/create"}" class="button"><span><i class="crm-i fa-plus-circle"></i> {ts}Create Election{/ts}</span></a>
    <div class="clear"></div><br>
{/if}
<div class="crm-content-block crm-block crm-elections-list-block">

    <div id="claim_level-wrapper" class="dataTables_wrapper">
        {assign var="rowCount" value=0}
        {if empty($elections)}
            <h2 class="error-heading">No elections found.</h2>
        {/if}
        {foreach from=$elections key=errorId item=election}
            {assign var="rowCount" value=$rowCount+1}

            <div class="crm-accordion-wrapper">
                <div class="crm-accordion-header">
                    <span>{$election.name}</span>
                    <span></span>
                </div><!-- /.crm-accordion-header -->
                <div class="crm-accordion-body">

                    <p>{$election.description|nl2br}</p>

                    {assign var="viewBtnTxt" value=""}
                    {assign var="electionProgressTxt" value=""}

                    {if !$election.hasNominationsStarted}
                        {assign var="viewBtnTxt" value=""}
                        {assign var="electionProgressTxt" value="Nominations will open on "}
                        {assign var="electionProgressDate" value=$election.nomination_start_date|crmDate}
                    {/if}
                    {if $election.isNominationsInProgress}
                        {assign var="viewBtnTxt" value="View & Submit Nominations"}
                        {assign var="electionProgressTxt" value="Nominations will close on "}
                        {assign var="electionProgressDate" value=$election.nomination_end_date|crmDate}
                    {/if}

                    {if $election.hasNominationsStarted and (!$election.isNominationsInProgress) and !$election.advertiseCandidatesStarted}
                        {assign var="viewBtnTxt" value="View The Nominations"}
                        {assign var="electionProgressTxt" value="Advertise candidate will open on "}
                        {assign var="electionProgressDate" value=$election.advertise_candidates_date|crmDate}
                    {/if}

                    {if $election.advertiseCandidatesStarted and !$election.isVotingStarted}
                        {assign var="viewBtnTxt" value="View The Candidates"}
                        {assign var="electionProgressTxt" value="Voting will open on "}
                        {assign var="electionProgressDate" value=$election.voting_start_date|crmDate}
                    {/if}

                    {if $election.isVotingStarted and !$election.isVotingEnded}
                        {assign var="viewBtnTxt" value="Vote in The Election"}
                        {assign var="electionProgressTxt" value="Voting will close on "}
                        {assign var="electionProgressDate" value=$election.voting_end_date|crmDate}
                        {if !$election.isUserAllowedToVote}
                            {assign var="viewBtnTxt" value=""}
                            {assign var="electionProgressTxt" value=""}
                        {/if}
                    {/if}

                    {if $election.isVotingEnded and !$election.isResultsOut}
                        {assign var="viewBtnTxt" value="View The Candidates"}
                        {assign var="electionProgressTxt" value="Election results will be available on "}
                        {assign var="electionProgressDate" value=$election.result_date|crmDate}
                    {/if}

                    {if $election.isResultsOut}
                        {assign var="viewBtnTxt" value="View Election Results"}
                        {assign var="electionProgressTxt" value="Election results published on "}
                        {assign var="electionProgressDate" value=$election.result_date|crmDate}
                    {/if}


                    {if $viewBtnTxt != ''}
                        {if !$viewAction}
                            <input type="button" value="{$viewBtnTxt}" onclick="window.location.href='{crmURL p="civicrm/elections/view" q="eid=`$election.id`"}'" class="election-action-button" /><br>
                        {else}
                            <input type="button" value="{$viewBtnTxt}" onclick="window.location.href='{$viewAction}?eid={$election.id}'" class="election-action-button" /><br>
                        {/if}
                    {/if}

                    {if $electionProgressTxt != ''}
                        <p>{$electionProgressTxt} <span class="crm-election-date">{$electionProgressDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span></p>
                    {/if}

                    {if $election.hasUserAlreadyVoted and !$election.isVotingEnded}
                        <p>
                            You voted in this election on <span class="crm-election-date">{$election.userVoteDate|crmDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span>.
                            {if $election.isUserAllowedToVote}
                                You can vote again if you wish to change your vote.
                            {/if}
                        </p>
                    {/if}


                    {if !$isFromShortCode}
                        {if $isElectionAdmin}
                            {if !$election.is_visible}
                                <span class="error">Election is Inactive.</span>&nbsp;&nbsp;
                            {else}
                                <span class="success">Election is Active.</span>&nbsp;&nbsp;
                            {/if}
                            {if $election.is_visible}
                                <a href="{crmURL p="civicrm/elections/visibility" q="eid=`$election.id`"}" class="action-item crm-hover-button" title="Deactivate">Deactivate</a>
                            {else}
                                <a href="{crmURL p="civicrm/elections/visibility" q="eid=`$election.id`"}" class="action-item crm-hover-button" title="Activate">Activate</a>
                            {/if}<br><br>
                        {/if}

                        {if $isElectionAdmin and $election.canedit}
                            <a href="{crmURL p="civicrm/elections/positions" q="eid=`$election.id`"}" class="action-item crm-hover-button" title="Positions">Positions</a>
                        {/if}
                        {if $isElectionAdmin}
                            {if $election.canedit}<a href="{crmURL p="civicrm/elections/create" q="eid=`$election.id`"}" class="action-item crm-hover-button" title="Edit Election">Edit</a>{/if}
                            {if $election.candelete}<a href="{crmURL p="civicrm/elections/delete" q="eid=`$election.id`"}" class="action-item crm-hover-button small-popup" title="Delete Election">Delete</a>{/if}
                        {/if}

                        {if $isElectionAdmin}
                            {if $election.positions == 0}
                                <div class="messages status no-popup election-visibility-message">
                                    At least one Position must be defined before the Election can be active.
                                </div>
                            {/if}
                        {/if}

                    {/if}


                </div><!-- /.crm-accordion-body -->
            </div><!-- ending of election wrapper -->
        {/foreach}
    </div>
</div>