<div class="crm-election-view-block">
    {include file="CRM/Elections/Page/WelcomeMessage.tpl"}

    <p>{$election->description|escape|nl2br}</p>

    {include file="CRM/Elections/Page/ElectionStatus.tpl"}

    {if !$election->isNominationsStarted}
        {include file="CRM/Elections/Page/ViewElectionBlocks/positions.tpl"}
    {/if}

    {include file="CRM/Elections/Page/ViewElectionBlocks/questions.tpl"}

    {if $election->isNominationsStarted}
        {include file="CRM/Elections/Page/ViewElectionBlocks/nominations.tpl"}
    {/if}
</div>
