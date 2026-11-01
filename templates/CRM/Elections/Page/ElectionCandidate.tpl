{if $backToElectionUrl}<p class="election-back">{include file="CRM/Elections/Frame/BackToElection.tpl"}</p>{/if}
{include file="CRM/Elections/Page/WelcomeMessage.tpl"}
<div class="candidate-details-block">
    {if $election->showPhotos}
    <div class="candidate-details-left-block">
        {assign var='imgKey' value='member_nominee.image_URL'}
        {assign var='profilePicUrl' value=$nomination.$imgKey}
        <img src="{$profilePicUrl|escape}" alt="" /><br>
    </div><!-- ending of candidate-details-left-block -->
    {/if}

    <div class="candidate-details-right-block">
        {assign var='displayname' value='member_nominee.display_name'}
        <h2>{$nomination.$displayname|escape}</h2>
    </div><!-- ending of candidate-details-right-block -->
</div><!-- ending of candidate-details-block -->

{foreach from=$positions item=position}
    <div class="candidate-details-block">
        <div class="candidate-details-left-block">
            {foreach from=$position.nominations item=positionNomination}
                {if $positionNomination.member_nominee == $nomination.member_nominee}
                    {if $election->advertiseCandidatesStarted and $positionNomination.has_accepted_nomination == 1}
                        <h3>{ts 1=$position.name|escape}Candidate for %1{/ts}</h3>
                    {else}
                        <h3>{ts 1=$position.name|escape}Nominated for %1{/ts}</h3>
                    {/if}
                    {if $election->advertiseCandidatesStarted or $election->isNominationsStarted}
                        <p>
                        {if $positionNomination.has_accepted_nomination == 1}
                            <span class="election-chip election-chip-accepted">{ts}Accepted{/ts}</span><br><br>
                            {include file="CRM/Elections/Page/CandidateStatement.tpl" statementText=$positionNomination.comments|default:'' statementUrl=$positionNomination.statement_url|default:'' statementDisplay='inline'}
                        {elseif $positionNomination.has_rejected_nomination == 1}
                            <span class="election-chip election-chip-withdrawn">{ts}Withdrawn{/ts}</span><br><br>
                            {$positionNomination.rejection_comments|escape|nl2br}
                        {elseif $positionNomination.is_eligible_candidate == 1}
                            <span class="election-chip election-chip-waiting">{ts}Waiting for an answer{/ts}</span>
                        {else}
                            <span class="election-chip election-chip-waiting">{ts}Waiting for more nominators{/ts}</span>
                        {/if}
                        </p>
                    {/if}
                {/if}
            {/foreach}
        </div><!-- ending of candidate-details-left-block -->

        {if $election->showNominators}
        <div class="candidate-details-right-block">
            {foreach from=$position.nominations item=positionNomination}
                {if $positionNomination.member_nominee == $nomination.member_nominee and !empty($positionNomination.seconders)}
                    <h3>{ts}Was nominated by{/ts}</h3>
                    {foreach from=$positionNomination.seconders|default:[] item=seconder}
                        {assign var='nominatorName' value='member_nominator.display_name'}
                        <p>{$seconder.$nominatorName|escape}<br>
                        {$seconder.description|escape|nl2br}</p>
                    {/foreach}
                {/if}
            {/foreach}
        </div><!-- ending of candidate-details-right-block -->
        {/if}
    </div><!-- ending of candidate-details-block -->
{/foreach}
{if $canEditStatement}
<p class="election-candidate-actions"><a href="{crmURL p="civicrm/elections/candidate/statement" q="`$checksum_query['query']`&enid=`$nomination.id`"}" class="election-btn">{ts}Edit Statement{/ts}</a></p>
{/if}
