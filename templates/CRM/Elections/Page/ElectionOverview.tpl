<div class="crm-election-overview">
  <p>
    <a class="button" href="{crmURL p='civicrm/elections/manage' q='reset=1'}"><span><i class="crm-i fa-arrow-left" role="img" aria-hidden="true"></i> {ts}Back to Manage Elections{/ts}</span></a>
  </p>

  <p><span class="election-stage election-stage-{$stage.key}">{$stage.label}</span></p>
  {if $election->description}<p>{$election->description|escape|nl2br}</p>{/if}

  <div class="messages status no-popup election-next-step">
    <strong>{ts}Next:{/ts}</strong> {$nextStep}
  </div>

  <div class="election-overview-grid">
    <div class="election-overview-block">
      <h3>{ts}Timeline{/ts}</h3>
      <ol class="election-timeline">
        {foreach from=$timeline item=step}
          <li class="{if $step.passed}election-timeline-passed{else}election-timeline-upcoming{/if}">
            <span class="election-timeline-label">{$step.label}</span>
            <span class="election-timeline-date">{$step.date|crmDate}</span>
          </li>
        {/foreach}
      </ol>
    </div>

    <div class="election-overview-block">
      <h3>{ts}Numbers{/ts}</h3>
      <table class="election-counts">
        <tr><th>{ts}Positions{/ts}</th><td class="election-count-positions">{$counts.positions}</td></tr>
        <tr><th>{ts}Nominations{/ts}</th><td class="election-count-nominations">{$counts.nominations}</td></tr>
        <tr><th>{ts}Eligible candidates{/ts}</th><td class="election-count-eligible">{$counts.eligible}</td></tr>
        <tr><th>{ts}Candidates standing{/ts}</th><td class="election-count-candidates">{$counts.candidates}</td></tr>
        <tr><th>{ts}Ballots cast{/ts}</th><td class="election-count-ballots">{$counts.ballots}</td></tr>
      </table>
    </div>
  </div>

  <p class="election-overview-actions">
    <a class="button" href="{crmURL p='civicrm/elections/position-list' q='reset=1'}#/?eid={$election->id}"><span><i class="crm-i fa-list-ol" role="img" aria-hidden="true"></i> {ts}Positions{/ts}</span></a>
    <a class="button" href="{crmURL p='civicrm/elections/candidate-list' q='reset=1'}#/?eid={$election->id}"><span><i class="crm-i fa-users" role="img" aria-hidden="true"></i> {ts}Candidates{/ts}</span></a>
    {if $canEdit}<a class="button" href="{crmURL p='civicrm/elections/create' q="eid=`$election->id`"}"><span><i class="crm-i fa-pencil" role="img" aria-hidden="true"></i> {ts}Edit settings{/ts}</span></a>{/if}
    <a class="button" href="{crmURL p='civicrm/elections/view' q="eid=`$election->id`"}"><span><i class="crm-i fa-eye" role="img" aria-hidden="true"></i> {ts}View as a voter{/ts}</span></a>
    <a class="button crm-popup" href="{crmURL p='civicrm/elections/duplicate' q="eid=`$election->id`"}"><span><i class="crm-i fa-copy" role="img" aria-hidden="true"></i> {ts}Duplicate{/ts}</span></a>
  </p>
</div>
