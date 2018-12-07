<div class="crm-elections-notification-block">
    {if !$election->isNominationsStarted}
        <p>Nominations will open on <span class="crm-election-date">{$election->nomination_start_date|crmDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span></p>
    {/if}
    {if $election->isNominationsInProgress}
        <p>Nominations will close on <span class="crm-election-date">{$election->nomination_end_date|crmDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span></p>
    {/if}

    {if $election->isNominationsStarted and (!$election->isNominationsInProgress) and !$election->advertiseCandidatesStarted}
        <p>Nominations are closed.</p>
        <p>Advertise candidate will open on <span class="crm-election-date">{$election->advertise_candidates_date|crmDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span></p>
    {/if}

    {if $election->advertiseCandidatesStarted and !$election->isVotingStarted}
        <p>Advertise candidates started on <span class="crm-election-date">{$election->advertise_candidates_date|crmDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span></p>
        <p>Voting will open on <span class="crm-election-date">{$election->voting_start_date|crmDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span></p>
    {/if}

    {if $hasUserAlreadyVoted  and !$election->isVotingEnded}
        <div class="messages status no-popup">
            You voted in this election on <span class="crm-election-date">{$userVoteDate|crmDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span>. {if $isUserAllowedToVote and $isAllowedToNominate}You can vote again if you wish to change your vote.{/if}
        </div>
    {/if}

    {if $election->isVotingStarted and !$election->isVotingEnded and (!$hasUserAlreadyVoted or ($hasUserAlreadyVoted and $isUserAllowedToVote))}
        <p>Voting will close on <span class="crm-election-date">{$election->voting_end_date|crmDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span></p>
    {/if}

    {if $election->isVotingEnded and !$election->isResultsOut}
        <p>Voting closed</p>
        <p>Results will be available on <span class="crm-election-date">{$election->result_date|crmDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span></p>
    {/if}

    {if $election->isResultsOut}
        <p>Results published on <span class="crm-election-date">{$election->result_date|crmDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span></p>
    {/if}
</div>