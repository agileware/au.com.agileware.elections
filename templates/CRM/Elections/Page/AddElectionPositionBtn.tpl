{if $isElectionAdmin and (!$isElectionRunning or !$election->is_visible)}
    <a href="{crmURL p="civicrm/elections/positions/create" q="eid=`$election->id`"}" class="button election-nav-action-button"><span><i class="crm-i fa-plus-circle" role="img" aria-hidden="true"></i> {ts}Add Position{/ts}</span></a>
    <a href="{crmURL p="civicrm/elections/create" q="eid=`$election->id`"}" class="button election-nav-action-button"><span>{ts}Edit Election Settings{/ts}</span></a>
    <a href="{crmURL p="civicrm/elections"}" class="button election-nav-action-button"><span>{ts}View Elections{/ts}</span></a>
{/if}

{if $isElectionAdmin and (!$isElectionRunning or !$election->is_visible)}
    <div class="clear"></div><br>
{/if}