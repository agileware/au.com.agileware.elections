<div class="crm-election-view-block">
    {if $isElectionAdmin and !$isElectionRunning}
        <a href="{crmURL p="civicrm/elections/create" q="eid=`$election->id`"}" class="button"><span><i class="crm-i fa-edit" role="img" aria-hidden="true"></i> {ts}Edit Election{/ts}</span></a>&nbsp;&nbsp;
        <a href="{crmURL p="civicrm/elections/delete" q="eid=`$election->id`"}" class="button delete"><span><i class="crm-i fa-trash" role="img" aria-hidden="true"></i> {ts}Delete Election{/ts}</span></a>
        <div class="clear"></div><br>
    {/if}

    {include file="CRM/Elections/Page/WelcomeMessage.tpl"}

    <p>{$election->description|escape|nl2br}</p>

    {include file="CRM/Elections/Page/ElectionStatus.tpl"}

    {if !$election->isNominationsStarted}
        {include file="CRM/Elections/Page/ViewElectionBlocks/positions.tpl"}
    {/if}

    {if $election->isNominationsStarted}
        {include file="CRM/Elections/Page/ViewElectionBlocks/nominations.tpl"}
    {/if}
</div>
