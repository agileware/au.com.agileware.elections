<h2>
    {if $election->advertiseCandidatesStarted}
        {ts}Candidates{/ts}
    {else}
        {ts}Existing Nominations{/ts}
    {/if}
</h2>
{if empty($nominations) or empty($positions)}
    <p class="no-result-message">{ts}Positions are not added yet to be nominated.{/ts}</p>
{/if}

{foreach from = $nominations key = k item = nomination}
    <div class="crm-election-position-nominations-block">

        <h3>{if $election->advertiseCandidatesStarted}{ts 1=$nomination.name}Candidates for %1{/ts}{else}{ts 1=$nomination.name}Nominations for %1{/ts}{/if}</h3>

        {if empty($nomination.nominations) and !$election->advertiseCandidatesStarted}
            <p>{ts}There are no existing nominations{/ts}</p>
        {/if}
        {assign var="candidatesCount" value=0}
        {if !empty($nomination.nominations)}
            <div class="election_row_style">
                {foreach from = $nomination.nominations key = sk item = seconder}

                    {if (!$election->advertiseCandidatesStarted and $election->isNominationsInProgress and $seconder.has_rejected_nomination == 0) or (!$election->advertiseCandidatesStarted and !$election->isNominationsInProgress and $seconder.is_eligible_candidate == 1) or ($election->advertiseCandidatesStarted and !$election->isVotingStarted and $seconder.is_eligible_candidate == 1 and $seconder.has_rejected_nomination == 0) or ($election->isVotingStarted and $seconder.has_accepted_nomination == 1)}
                        {assign var='scKey' value='member_nominee.display_name'}
                        {assign var='scImgKey' value='member_nominee.image_URL'}
                        <div class="crm-election-nominated-block">
                            {if $election->isNominationsStarted}
                            <a href="{crmURL p="civicrm/elections/candidate" q="`$checksum_query['query']`&enid=`$seconder.id`"}">
                                {/if}

                                {assign var="candidatesCount" value=$candidatesCount+1}
                                <div class="crm-election-nominated-block inside">
                                    {assign var='profilePicUrl' value=$seconder.$scImgKey}
                                    {if $election->showPhotos}<img src="{$profilePicUrl}" alt=""/><br><br>{/if}
                                    {$seconder.$scKey}<br>

                                    {if !$election->isVotingStarted and ($seconder.is_eligible_candidate == 1)}
                                        {ts}Candidate Status:{/ts}
                                        {if $seconder.has_accepted_nomination == 1}
                                            {ts}Accepted{/ts}
                                            <br>
                                            <br>
                                            {$seconder.comments|escape|nl2br}
                                        {elseif $seconder.has_rejected_nomination == 1}
                                            {ts}Withdrawn{/ts}
                                            <br>
                                            <br>
                                            {$seconder.rejection_comments|escape|nl2br}
                                        {else}
                                            {ts}Pending{/ts}
                                        {/if}
                                    {/if}

                                </div>
                                {if $election->isNominationsStarted}
                            </a>
                            {/if}
                            {if $election->showStatements and $election->isVotingStarted and $seconder.has_accepted_nomination == 1}
                                {include file="CRM/Elections/Page/CandidateStatement.tpl" statementText=$seconder.comments|default:'' statementUrl=$seconder.statement_url|default:'' statementDisplay=$election->statementDisplay}
                            {/if}
                            {if $isAllowedToNominate}
                            {if $election->isNominationsInProgress and $election->required_nominations >= 2 and $seconder.is_eligible_candidate != 1}
                                <div class="clearfix"></div>
                                <input type="button" value="{ts escape='htmlattribute'}Need Second{/ts}"
                                       onclick="window.location.href='{crmURL p="civicrm/elections/nominations/create" q="`$checksum_query['query']`&enid=`$seconder.id`"}'"
                                       class="election-action-button"/>
                            {/if}
                            {if $election->isNominationsInProgress and $seconder.has_rejected_nomination == 0 and $seconder.has_accepted_nomination == 0 and $seconder.is_eligible_candidate == 1}
                                <div class="clearfix"></div>
                                <input type="button" value="{ts escape='htmlattribute'}Nominate{/ts}"
                                       onclick="window.location.href='{crmURL p="civicrm/elections/nominations/create" q="`$checksum_query['query']`&enid=`$seconder.id`"}'"
                                       class="election-action-button"/>
                            {/if}

                            {/if}
                        </div>
                    {/if}

                {/foreach}
            </div>
            {if empty($candidatesCount) or (empty($nomination.nominations) and !$election->advertiseCandidatesStarted) or ($election->advertiseCandidatesStarted and empty($candidatesCount))}
                {if $election->advertiseCandidatesStarted}
                    <p>{ts}There are no eligible candidates.{/ts}</p>
                {else}
                    <p>{ts}There are no eligible nominations.{/ts}</p>
                {/if}
            {/if}
        {/if}
    </div>
    <div class="clearfix"></div>
{/foreach}
