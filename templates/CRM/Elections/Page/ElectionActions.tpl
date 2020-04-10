{if !$election->isVotingEnded or $election->isResultsOut}
    <div class="crm-elections-action-block">
        {if $election->isNominationsInProgress or ($election->isNominationsStarted and !$election->isVotingStarted)}
          {foreach from = $nominations key = k item = nomination}
                {foreach from = $nomination.nominations key = sk item = seconder}
                        {if $seconder.is_eligible_candidate == 1 and $seconder.has_accepted_nomination == 0 and $seconder.has_rejected_nomination == 0 and $loggedInContactId == $seconder.member_nominee}
                            <div class="messages status no-popup">
                                <span class="eligible-block">{ts}You're eligible candidate for{/ts} {$nomination.name}</span> <input type="button" value="{ts}Accept Nomination{/ts}" onclick="window.location.href='{crmURL p="civicrm/elections/nominations/accept" q="enid=`$seconder.id`"}'" class="election-action-button" /> <span><input type="button" value="{ts}Withdraw nomination{/ts}" onclick="window.location.href='{crmURL p="civicrm/elections/nominations/reject" q="enid=`$seconder.id`"}'" class="election-action-button" /></span>
                            </div>
                        {/if}
                        {if $seconder.is_eligible_candidate == 1 and $seconder.has_accepted_nomination == 1 and $seconder.has_rejected_nomination == 0 and $loggedInContactId == $seconder.member_nominee}
                            <div class="messages status no-popup">
                                <span class="eligible-block">{ts}You've accepted the nomination for{/ts} {$nomination.name}</span> <input type="button" value="{ts}Withdraw Nomination{/ts}" onclick="window.location.href='{crmURL p="civicrm/elections/nominations/reject" q="enid=`$seconder.id`"}'" class="election-action-button" />
                            </div>
                        {/if}
                        {if $election->isNominationsInProgress and $seconder.is_eligible_candidate == 0 and $seconder.has_rejected_nomination == 0 and $loggedInContactId == $seconder.member_nominee}
                            <div class="messages status no-popup">
                                <span class="eligible-block">{ts}You're nominated for{/ts} {$nomination.name}</span> <input type="button" value="{ts}Withdraw Nomination{/ts}" onclick="window.location.href='{crmURL p="civicrm/elections/nominations/reject" q="enid=`$seconder.id`"}'" class="election-action-button" />
                            </div>
                        {/if}
                {/foreach}
          {/foreach}
        {/if}
        {if $election->isNominationsInProgress and $isAllowedToNominate}
            <input type="button" value="{ts}Submit Nomination{/ts}" onclick="window.location.href='{crmURL p="civicrm/elections/nominations/create" q="eid=`$election->id`"}'" class="election-action-button" />
        {/if}
        {if $election->isVotingStarted and !$election->isVotingEnded and $isUserAllowedToVote and $isAllowedToNominate}
            <input type="button" value="{ts}Vote Now{/ts}" onclick="window.location.href='{crmURL p="civicrm/elections/vote" q="eid=`$election->id`"}'" class="election-action-button" />
        {/if}
        {if $election->isResultsOut}
            <input type="button" value="{ts}Check Results{/ts}" onclick="window.location.href='{crmURL p='civicrm/elections/results' q="eid=`$election->id`"}'" class="election-action-button" />
        {/if}
    </div>
{/if}