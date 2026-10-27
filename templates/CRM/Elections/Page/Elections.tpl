{if $isElectionAdmin and !$isFromShortCode}
    <a href="{crmURL p="civicrm/elections/create"}" class="button"><span><i class="crm-i fa-plus-circle" role="img" aria-hidden="true"></i> {ts}Create Election{/ts}</span></a>
    <div class="clear"></div><br>
{/if}
<div class="crm-content-block crm-block crm-elections-list-block">
    {if empty($elections)}
        <h2 class="error-heading">{ts}No elections found.{/ts}</h2>
    {/if}
    <div class="election-card-list">
        {foreach from=$elections item=election}
            <article class="election-card election-card-{$election.card.stage|escape}">
                <header class="election-card-header">
                    <h2 class="election-card-title">{$election.name|escape}</h2>
                    <span class="election-badge election-badge-{$election.card.stage|escape}">{$election.card.stageLabel|escape}</span>
                </header>
                {if $election.description}<p class="election-card-description">{$election.description|escape|nl2br}</p>{/if}
                <p class="election-card-line">{$election.card.line|escape} <a class="election-card-timezone" target="_blank" rel="noopener noreferrer" href="{$siteTimeZoneConvertUrl|escape}">({$siteTimeZone|escape})</a></p>
                {if $election.hasUserAlreadyVoted and !$election.isVotingEnded}
                    <p class="election-card-line">
                        {ts 1=$election.userVoteDate|crmDate}You voted in this election on %1.{/ts}
                        {if $election.isUserAllowedToVote}{ts}You can vote again if you wish to change your vote.{/ts}{/if}
                    </p>
                {/if}
                {if $election.card.button != ''}
                    <p class="election-card-actions">
                        {if !$viewAction}
                            <a href="{crmURL p="civicrm/elections/view" q="eid=`$election.id`"}" class="election-btn">{$election.card.button|escape}</a>
                        {else}
                            <a href="{$viewAction|escape}?eid={$election.id|escape}" class="election-btn">{$election.card.button|escape}</a>
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


            </article>
        {/foreach}
    </div>
</div>
