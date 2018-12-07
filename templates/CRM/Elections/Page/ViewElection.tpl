<div class="crm-election-view-block">
    {if $isElectionAdmin and !$isElectionRunning}
        <a href="{crmURL p="civicrm/elections/create" q="eid=`$election->id`"}" class="button"><span><i class="crm-i fa-edit"></i> {ts}Edit Election{/ts}</span></a>&nbsp;&nbsp;
        <a href="{crmURL p="civicrm/elections/delete" q="eid=`$election->id`"}" class="button delete"><span><i class="crm-i fa-trash"></i> {ts}Delete Election{/ts}</span></a>
        <div class="clear"></div><br>
    {/if}

    <p>{$election->description|nl2br}</p>

    {include file="CRM/Elections/Page/ElectionHelpText.tpl"}

    {include file="CRM/Elections/Page/ElectionActions.tpl"}
    {include file="CRM/Elections/Page/ElectionNotificationMessages.tpl"}

    {if !$election->isNominationsStarted}
        {include file="CRM/Elections/Page/ViewElectionBlocks/positions.tpl"}
    {/if}

    {if $election->isNominationsStarted}
        {include file="CRM/Elections/Page/ViewElectionBlocks/nominations.tpl"}
    {/if}
</div>