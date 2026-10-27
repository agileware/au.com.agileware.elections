{include file="CRM/Elections/Page/WelcomeMessage.tpl"}
<div class="candidate-details-block">
    {if $election->showPhotos}
    <div class="candidate-details-left-block">
        {assign var='imgKey' value='member_nominee.image_URL'}
        {assign var='profilePicUrl' value=$nomination.$imgKey}
        <img src="{$profilePicUrl}" alt="" /><br>
    </div><!-- ending of candidate-details-left-block -->
    {/if}

    <div class="candidate-details-right-block">
        {assign var='displayname' value='member_nominee.display_name'}
        <h2>{$nomination.$displayname}</h2>
    </div><!-- ending of candidate-details-right-block -->
</div><!-- ending of candidate-details-block -->

{foreach from=$positions item=position}
    <div class="candidate-details-block">
        <div class="candidate-details-left-block">
            {foreach from=$position.nominations item=positionNomination}
                {if $positionNomination.member_nominee == $nomination.member_nominee}
                    {if $election->advertiseCandidatesStarted and $positionNomination.has_accepted_nomination == 1}
                        <h3>{ts 1=$position.name}Candidate for %1{/ts}</h3>
                    {else}
                        <h3>{ts 1=$position.name}Nominated for %1{/ts}</h3>
                    {/if}
                    {if $election->advertiseCandidatesStarted or $election->isNominationsStarted}
                        <p>
                        {if $positionNomination.is_eligible_candidate == 1}
                            {ts}Candidate Status:{/ts}
                        {else}
                            {ts}Nomination Status:{/ts}
                        {/if}
                        {if $positionNomination.has_accepted_nomination == 1}
                            {ts}Accepted{/ts}<br><br>
                            {include file="CRM/Elections/Page/CandidateStatement.tpl" statementText=$positionNomination.comments|default:'' statementUrl=$positionNomination.statement_url|default:'' statementDisplay='inline'}
                        {elseif $positionNomination.has_rejected_nomination == 1}
                            {ts}Withdrawn{/ts}<br><br>
                            {$positionNomination.rejection_comments|escape|nl2br}
                        {else}
                            {if $election->isVotingStarted}
                                {ts}Not Accepted{/ts}
                            {else}
                                {ts}Pending{/ts}
                            {/if}
                        {/if}
                        </p>
                    {/if}
                {/if}
            {/foreach}
        </div><!-- ending of candidate-details-left-block -->

        {if $election->showNominators}
        <div class="candidate-details-right-block">
            {foreach from=$position.nominations item=positionNomination}
                <h3>{ts}Was nominated by{/ts}</h3>
                {if $positionNomination.member_nominee == $nomination.member_nominee}
                    {foreach from=$positionNomination.seconders|default:[] item=seconder}
                        {assign var='nominatorName' value='member_nominator.display_name'}
                        <p>{$seconder.$nominatorName}<br>
                        {$seconder.description}</p>
                    {/foreach}
                {/if}
            {/foreach}
        </div><!-- ending of candidate-details-right-block -->
        {/if}
    </div><!-- ending of candidate-details-block -->
{/foreach}
{if $canEditStatement}
<a href="{crmURL p="civicrm/elections/candidate/statement" q="`$checksum_query['query']`&enid=`$nomination.id`"}" class="election-btn">{ts}Edit statement{/ts}</a>
{/if}
<a href="{crmURL p="civicrm/elections/view" q="`$checksum_query['query']`"}" class="election-btn">{ts}Back{/ts}</a>
