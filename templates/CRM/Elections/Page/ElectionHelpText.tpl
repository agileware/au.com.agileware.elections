{* Shown above the results of an election: the results page is only drawn once results have been generated, so these are the only two stages it can be in. *}
<div class="crm-elections-helptext-block">
    {if $election->isVotingEnded and !$election->isResultsOut}
       <p>{ts 1=$election->voting_end_date|crmDate}Voting closed on %1. The candidates are shown below.{/ts}</p>
    {/if}

    {if $election->isResultsOut}
       <p>{ts}The votes have been counted for this election. The winners are shown below.{/ts}</p>
    {/if}
</div>
