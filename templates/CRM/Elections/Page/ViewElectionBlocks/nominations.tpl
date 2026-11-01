<h2>
    {if $election->advertiseCandidatesStarted}
        {ts}Candidates{/ts}
    {else}
        {ts}Nominations{/ts}
    {/if}
</h2>
{if empty($nominations) or empty($positions)}
    <p class="no-result-message">{ts}There are no positions yet.{/ts}</p>
{/if}

{foreach from = $nominations key = k item = nomination}
  {if $nomination.position_type|default:'election' != 'referendum'}
    <div class="crm-election-position-nominations-block">

        <h3>{if $election->advertiseCandidatesStarted}{ts 1=$nomination.name|escape}Candidates for %1{/ts}{else}{ts 1=$nomination.name|escape}Nominations for %1{/ts}{/if}</h3>

        {if empty($nomination.nominations) and !$election->advertiseCandidatesStarted}
            <p>{ts}There are no nominations yet.{/ts}</p>
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
                                    {if $election->showPhotos}<img src="{$profilePicUrl|escape}" alt=""/>{/if}
                                    {$seconder.$scKey|escape}<br>

                                    {if !$election->isVotingStarted and ($seconder.is_eligible_candidate == 1)}
                                        {if $seconder.has_accepted_nomination == 1}
                                            <span class="election-chip election-chip-accepted">{ts}Accepted{/ts}</span>
                                            <br>
                                            <br>
                                            {$seconder.comments|escape|nl2br}
                                        {elseif $seconder.has_rejected_nomination == 1}
                                            <span class="election-chip election-chip-withdrawn">{ts}Withdrawn{/ts}</span>
                                            <br>
                                            <br>
                                            {$seconder.rejection_comments|escape|nl2br}
                                        {else}
                                            <span class="election-chip election-chip-waiting">{ts}Waiting for an answer{/ts}</span>
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
                                <a href="{crmURL p="civicrm/elections/nominations/create" q="`$checksum_query['query']`&enid=`$seconder.id`"}" class="election-btn election-btn-quiet">{ts}Second Nomination{/ts}<span class="election-visually-hidden">: {$seconder.$scKey|escape}</span></a>
                            {/if}
                            {if $election->isNominationsInProgress and $seconder.has_rejected_nomination == 0 and $seconder.has_accepted_nomination == 0 and $seconder.is_eligible_candidate == 1}
                                <div class="clearfix"></div>
                                <a href="{crmURL p="civicrm/elections/nominations/create" q="`$checksum_query['query']`&enid=`$seconder.id`"}" class="election-btn election-btn-quiet">{ts}Nominate{/ts}<span class="election-visually-hidden">: {$seconder.$scKey|escape}</span></a>
                            {/if}

                            {/if}
                        </div>
                    {/if}

                {/foreach}
            </div>
            {if empty($candidatesCount) or (empty($nomination.nominations) and !$election->advertiseCandidatesStarted) or ($election->advertiseCandidatesStarted and empty($candidatesCount))}
                {if $election->advertiseCandidatesStarted}
                    <p>{ts}There are no candidates yet.{/ts}</p>
                {else}
                    <p>{ts}There are no nominations yet.{/ts}</p>
                {/if}
            {/if}
        {/if}
    </div>
    <div class="clearfix"></div>
  {/if}
{/foreach}
