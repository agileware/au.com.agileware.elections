<div class="crm-elections-notification-block">
    {if !$election->isNominationsStarted}
        <p>{ts}Nominations will open on{/ts} <span class="crm-election-date">{$election->nomination_start_date|crmDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span></p>
    {/if}
    {if $election->isNominationsInProgress}
        <p>{ts}Nominations will close on{/ts} <span class="crm-election-date">{$election->nomination_end_date|crmDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span></p>
    {/if}

    {if $election->isNominationsStarted and (!$election->isNominationsInProgress) and !$election->advertiseCandidatesStarted}
        <p>{ts}Nominations are closed.{/ts}</p>
        <p>{ts}Advertise candidate will open on{/ts} <span class="crm-election-date">{$election->advertise_candidates_date|crmDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span></p>
    {/if}

    {if $election->advertiseCandidatesStarted and !$election->isVotingStarted}
        <p>{ts}Advertise candidates started on{/ts} <span class="crm-election-date">{$election->advertise_candidates_date|crmDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span></p>
        <p>{ts}Voting will open on{/ts} <span class="crm-election-date">{$election->voting_start_date|crmDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span></p>
    {/if}

    {if $hasUserAlreadyVoted  and !$election->isVotingEnded}
        <div class="messages status no-popup">
                {ts}You voted in this election on{/ts} <span class="crm-election-date">{$userVoteDate|crmDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span>. {if $isUserAllowedToVote and $isAllowedToNominate}You can vote again if you wish to change your vote.{/if}
        </div>
    {/if}

    {if $election->isVotingStarted and !$election->isVotingEnded and (!$hasUserAlreadyVoted or ($hasUserAlreadyVoted and $isUserAllowedToVote))}
        <p>{ts}Voting will close on{/ts} <span class="crm-election-date">{$election->voting_end_date|crmDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span></p>
    {/if}

    {if $election->isVotingEnded and !$election->isResultsOut}
        <p>{ts}Voting closed{/ts}</p>
        <p>{ts}Results will be available on{/ts} <span class="crm-election-date">{$election->result_date|crmDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span></p>
    {/if}

    {if $election->isResultsOut}
        <p>{ts}Results published on{/ts} <span class="crm-election-date">{$election->result_date|crmDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span></p>
    {/if}
</div>