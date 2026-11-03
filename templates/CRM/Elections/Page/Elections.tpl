{* The list of elections: one card for each, with its stage, one line about the dates that matter now, and one button for the next step. Administrators use the back end, so there are no administrator tools here. *}
<div class="crm-content-block crm-block crm-elections-list-block">
    {if empty($elections)}
        <div class="messages status no-popup election-visibility-message">
            {if $isElectionAdmin}
                {capture assign=manageLink}<a href="{crmURL p="civicrm/elections/manage" q="reset=1"}">{ts}Manage Elections{/ts}</a>{/capture}
                {ts 1=$manageLink}There are no elections to show yet. Add one in %1.{/ts}
            {else}
                {ts}There are no elections to show yet.{/ts}
            {/if}
        </div>
    {else}
        <p class="election-timezone-note">{$siteTimeZoneLine|escape}</p>
    {/if}
    <div class="election-card-list">
        {foreach from=$elections item=election}
            <article class="election-card election-card-{$election.card.stage|escape}">
                <header class="election-card-header">
                    <h2 class="election-card-title">{$election.name|escape}</h2>
                    <span class="election-badge election-badge-{$election.card.stage|escape}">{$election.card.stageLabel|escape}</span>
                </header>
                {if $election.description}<p class="election-card-description">{$election.description|escape|nl2br}</p>{/if}
                <p class="election-card-line">{$election.card.line|escape}</p>
                {if $election.card.voteLine}
                    <p class="election-card-line">{$election.card.voteLine|escape}</p>
                {/if}
                <p class="election-card-actions">
                    {if !$viewAction}
                        <a href="{crmURL p="civicrm/elections/view" q="eid=`$election.id``$personalLinkQuery`"}" class="election-btn">{$election.card.button|escape}<span class="election-visually-hidden">: {$election.name|escape}</span></a>
                    {else}
                        <a href="{$viewAction|escape}?eid={$election.id|escape}{$personalLinkQuery|escape}" class="election-btn">{$election.card.button|escape}<span class="election-visually-hidden">: {$election.name|escape}</span></a>
                    {/if}
                </p>
            </article>
        {/foreach}
    </div>
</div>
