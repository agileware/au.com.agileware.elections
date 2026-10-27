{if !$election->isVotingEnded or $election->isResultsOut}
    <div class="crm-elections-action-block">
        {if $election->isNominationsInProgress or ($election->isNominationsStarted and !$election->isVotingStarted)}
          {if !empty($nominations)}
          {foreach from = $nominations key = k item = nomination}
                {foreach from = $nomination.nominations|default:[] key = sk item = seconder}
                        {if $seconder.is_eligible_candidate == 1 and $seconder.has_accepted_nomination == 0 and $seconder.has_rejected_nomination == 0 and $loggedInContactId == $seconder.member_nominee}
                            <div class="messages status no-popup">
                                <span class="eligible-block">{ts 1=$nomination.name}You are an eligible candidate for %1{/ts}</span> <a href="{crmURL p="civicrm/elections/nominations/accept" q="`$checksum_query['query']`&enid=`$seconder.id`"}" class="election-btn">{ts}Accept Nomination{/ts}</a> <span><a href="{crmURL p="civicrm/elections/nominations/reject" q="`$checksum_query['query']`&enid=`$seconder.id`"}" class="election-btn">{ts}Withdraw Nomination{/ts}</a></span>
                            </div>
                        {/if}
                        {if $seconder.is_eligible_candidate == 1 and $seconder.has_accepted_nomination == 1 and $seconder.has_rejected_nomination == 0 and $loggedInContactId == $seconder.member_nominee}
                            <div class="messages status no-popup">
                                <span class="eligible-block">{ts 1=$nomination.name}You have accepted the nomination for %1{/ts}</span> <a href="{crmURL p="civicrm/elections/nominations/reject" q="`$checksum_query['query']`&enid=`$seconder.id`"}" class="election-btn">{ts}Withdraw Nomination{/ts}</a>
                            </div>
                        {/if}
                        {if $election->isNominationsInProgress and $seconder.is_eligible_candidate == 0 and $seconder.has_rejected_nomination == 0 and $loggedInContactId == $seconder.member_nominee}
                            <div class="messages status no-popup">
                                <span class="eligible-block">{ts 1=$nomination.name}You have been nominated for %1{/ts}</span> <a href="{crmURL p="civicrm/elections/nominations/reject" q="`$checksum_query['query']`&enid=`$seconder.id`"}" class="election-btn">{ts}Withdraw Nomination{/ts}</a>
                            </div>
                        {/if}
                {/foreach}
          {/foreach}
          {/if}
        {/if}
        {if $election->isNominationsInProgress and $isAllowedToNominate}
            <a href="{crmURL p="civicrm/elections/nominations/create" q="`$checksum_query['query']`"}" class="election-btn">{ts}Submit Nomination{/ts}</a>
        {/if}
        {if $election->isVotingStarted and !$election->isVotingEnded and $isUserAllowedToVote and $isAllowedToNominate}
            <a href="{crmURL p="civicrm/elections/vote" q="`$checksum_query['query']`"}" class="election-btn">{ts}Vote Now{/ts}</a>
        {/if}
        {if $election->isResultsOut}
            <a href="{crmURL p='civicrm/elections/results' q="eid=`$election->id`"}" class="election-btn">{ts}Check Results{/ts}</a>
        {/if}
    </div>
{/if}
