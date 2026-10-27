{if $isElectionAdmin and !$isFromShortCode}
    <a href="{crmURL p="civicrm/elections/create"}" class="button"><span><i class="crm-i fa-plus-circle" role="img" aria-hidden="true"></i> {ts}Create Election{/ts}</span></a>
    <div class="clear"></div><br>
{/if}
<div class="crm-content-block crm-block crm-elections-list-block">

    <div id="claim_level-wrapper" class="dataTables_wrapper">
        {assign var="rowCount" value=0}
        {if empty($elections)}
            <h2 class="error-heading">{ts}No elections found.{/ts}</h2>
        {/if}
        {foreach from=$elections key=errorId item=election}
            {assign var="rowCount" value=$rowCount+1}

            <div class="crm-accordion-wrapper">
                <div class="crm-accordion-header">
                    <span>{$election.name|escape}</span>
                    <span></span>
                </div><!-- /.crm-accordion-header -->
                <div class="crm-accordion-body">

                    <p>{$election.description|escape|nl2br}</p>

                    {assign var="viewBtnTxt" value=""}
                    {assign var="electionProgressTxt" value=""}

                    {if !$election.hasNominationsStarted}
                        {assign var="viewBtnTxt" value=""}
                        {assign var="electionProgressTxt" value="{ts}Nominations will open on{/ts}"}
                        {assign var="electionProgressDate" value=$election.nomination_start_date|crmDate}
                    {/if}
                    {if $election.isNominationsInProgress}
                        {assign var="viewBtnTxt" value="{ts escape='htmlattribute'}View and submit Nominations{/ts}"}
                        {assign var="electionProgressTxt" value="{ts}Nominations will close on{/ts}"}
                        {assign var="electionProgressDate" value=$election.nomination_end_date|crmDate}
                    {/if}

                    {if $election.hasNominationsStarted and (!$election.isNominationsInProgress) and !$election.advertiseCandidatesStarted}
                        {assign var="viewBtnTxt" value="{ts escape='htmlattribute'}View the Nominations{/ts}"}
                        {assign var="electionProgressTxt" value="{ts}Candidates will be advertised from{/ts}"}
                        {assign var="electionProgressDate" value=$election.advertise_candidates_date|crmDate}
                    {/if}

                    {if $election.advertiseCandidatesStarted and !$election.isVotingStarted}
                        {assign var="viewBtnTxt" value="{ts escape='htmlattribute'}View the Candidates{/ts}"}
                        {assign var="electionProgressTxt" value="{ts}Voting will open on{/ts}"}
                        {assign var="electionProgressDate" value=$election.voting_start_date|crmDate}
                    {/if}

                    {if $election.isVotingStarted and !$election.isVotingEnded}
                        {assign var="viewBtnTxt" value="{ts escape='htmlattribute'}Vote in the Election{/ts}"}
                        {assign var="electionProgressTxt" value="{ts}Voting will close on{/ts}"}
                        {assign var="electionProgressDate" value=$election.voting_end_date|crmDate}
                        {if !$election.isUserAllowedToVote}
                            {assign var="viewBtnTxt" value=""}
                            {assign var="electionProgressTxt" value=""}
                        {/if}
                    {/if}

                    {if $election.isVotingEnded and !$election.isResultsOut}
                        {assign var="viewBtnTxt" value="{ts escape='htmlattribute'}View the Candidates{/ts}"}
                        {assign var="electionProgressTxt" value="{ts}Election results will be available on{/ts}"}
                        {assign var="electionProgressDate" value=$election.result_date|crmDate}
                    {/if}

                    {if $election.isResultsOut}
                        {assign var="viewBtnTxt" value="{ts escape='htmlattribute'}View Election results{/ts}"}
                        {assign var="electionProgressTxt" value="{ts}Election results published on{/ts}"}
                        {assign var="electionProgressDate" value=$election.result_date|crmDate}
                    {/if}


                    {if $viewBtnTxt != ''}
                        {if !$viewAction}
                            <a href="{crmURL p="civicrm/elections/view" q="eid=`$election.id`"}" class="election-btn">{$viewBtnTxt}</a><br>
                        {else}
                            <a href="{$viewAction|escape}?eid={$election.id|escape}" class="election-btn">{$viewBtnTxt}</a><br>
                        {/if}
                    {/if}

                    {if $electionProgressTxt != ''}
                        <p>{$electionProgressTxt} <span class="crm-election-date">{$electionProgressDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span></p>
                    {/if}

                    {if $election.hasUserAlreadyVoted and !$election.isVotingEnded}
                        <p>
                            {capture assign=voteDate}<span class="crm-election-date">{$election.userVoteDate|crmDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span>{/capture}
                            {ts 1=$voteDate}You voted in this election on %1.{/ts}
                            {if $election.isUserAllowedToVote}
                                {ts}You can vote again if you wish to change your vote.{/ts}
                            {/if}
                        </p>
                    {/if}


                    {if !$isFromShortCode}
                        {if $isElectionAdmin}
                            {if !$election.is_visible}
                                <span class="error">{ts}Election is Inactive.{/ts}</span>&nbsp;&nbsp;
                            {else}
                                <span class="success">{ts}Election is Active.{/ts}</span>&nbsp;&nbsp;
                            {/if}
                            {if $election.is_visible}
                                <a href="{crmURL p="civicrm/elections/visibility" q="eid=`$election.id`"}" class="action-item crm-hover-button" title="{ts escape='htmlattribute'}Deactivate{/ts}">{ts}Deactivate{/ts}</a>
                            {else}
                                <a href="{crmURL p="civicrm/elections/visibility" q="eid=`$election.id`"}" class="action-item crm-hover-button" title="{ts escape='htmlattribute'}Activate{/ts}">{ts}Activate{/ts}</a>
                            {/if}<br><br>
                        {/if}

                        {if $isElectionAdmin and $election.canedit}
                            <a href="{crmURL p="civicrm/elections/positions" q="eid=`$election.id`"}" class="action-item crm-hover-button" title="{ts escape='htmlattribute'}Positions{/ts}">{ts}Positions{/ts}</a>
                        {/if}
                        {if $isElectionAdmin}
                            {if $election.canedit}<a href="{crmURL p="civicrm/elections/create" q="eid=`$election.id`"}" class="action-item crm-hover-button" title="{ts escape='htmlattribute'}Edit Election{/ts}">{ts}Edit{/ts}</a>{/if}
                            {if $election.candelete}<a href="{crmURL p="civicrm/elections/delete" q="eid=`$election.id`"}" class="action-item crm-hover-button small-popup" title="{ts escape='htmlattribute'}Delete Election{/ts}">{ts}Delete{/ts}</a>{/if}
                        {/if}

                        {if $isElectionAdmin}
                            {if $election.positions == 0}
                                <div class="messages status no-popup election-visibility-message">
                                    {ts}At least one Position must be defined before the Election can be active.{/ts}
                                </div>
                            {/if}
                        {/if}

                    {/if}


                </div><!-- /.crm-accordion-body -->
            </div><!-- ending of election wrapper -->
        {/foreach}
    </div>
</div>