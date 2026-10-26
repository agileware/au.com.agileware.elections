{capture assign=tzLink}<a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a>{/capture}
<div class="crm-elections-notification-block">
    {if !$election->isNominationsStarted}
        {capture assign=eventDate}<span class="crm-election-date">{$election->nomination_start_date|crmDate} {$tzLink}</span>{/capture}
        <p>{ts 1=$eventDate}Nominations will open on %1{/ts}</p>
    {/if}
    {if $election->isNominationsInProgress}
        {capture assign=eventDate}<span class="crm-election-date">{$election->nomination_end_date|crmDate} {$tzLink}</span>{/capture}
        <p>{ts 1=$eventDate}Nominations will close on %1{/ts}</p>
    {/if}

    {if $election->isNominationsStarted and (!$election->isNominationsInProgress) and !$election->advertiseCandidatesStarted}
        {capture assign=eventDate}<span class="crm-election-date">{$election->advertise_candidates_date|crmDate} {$tzLink}</span>{/capture}
        <p>{ts}Nominations are closed.{/ts}</p>
        <p>{ts 1=$eventDate}Candidates will be advertised from %1{/ts}</p>
    {/if}

    {if $election->advertiseCandidatesStarted and !$election->isVotingStarted}
        {capture assign=advertiseDate}<span class="crm-election-date">{$election->advertise_candidates_date|crmDate} {$tzLink}</span>{/capture}
        {capture assign=eventDate}<span class="crm-election-date">{$election->voting_start_date|crmDate} {$tzLink}</span>{/capture}
        <p>{ts 1=$advertiseDate}Candidates have been advertised since %1{/ts}</p>
        <p>{ts 1=$eventDate}Voting will open on %1{/ts}</p>
    {/if}

    {if $hasUserAlreadyVoted  and !$election->isVotingEnded}
        {capture assign=eventDate}<span class="crm-election-date">{$userVoteDate|crmDate} {$tzLink}</span>{/capture}
        <div class="messages status no-popup">
            {ts 1=$eventDate}You voted in this election on %1.{/ts} {if $isUserAllowedToVote and $isAllowedToNominate}{ts}You can vote again if you wish to change your vote.{/ts}{/if}
        </div>
    {/if}

    {if $election->isVotingStarted and !$election->isVotingEnded and (!$hasUserAlreadyVoted or ($hasUserAlreadyVoted and $isUserAllowedToVote))}
        {capture assign=eventDate}<span class="crm-election-date">{$election->voting_end_date|crmDate} {$tzLink}</span>{/capture}
        <p>{ts 1=$eventDate}Voting will close on %1{/ts}</p>
    {/if}

    {if $election->isVotingEnded and !$election->isResultsOut}
        {capture assign=eventDate}<span class="crm-election-date">{$election->result_date|crmDate} {$tzLink}</span>{/capture}
        <p>{ts}Voting closed.{/ts}</p>
        <p>{ts 1=$eventDate}Results will be available on %1{/ts}</p>
    {/if}

    {if $election->isResultsOut}
        {capture assign=eventDate}<span class="crm-election-date">{$election->result_date|crmDate} {$tzLink}</span>{/capture}
        <p>{ts 1=$eventDate}Results published on %1{/ts}</p>
    {/if}
</div>
