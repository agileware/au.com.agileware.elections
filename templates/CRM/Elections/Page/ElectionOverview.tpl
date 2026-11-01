<div class="crm-election-overview">
  <div class="election-action-bar" role="group" aria-label="{ts escape='htmlattribute'}Actions{/ts}">
    {include file="CRM/Elections/Frame/BackButtons.tpl"}
    {if $canEdit}
      <a class="button election-action-primary" href="{crmURL p='civicrm/elections/create' q="eid=`$election->id`"}"><span><i class="crm-i fa-pencil" role="img" aria-hidden="true"></i> {ts}Edit Election{/ts}</span></a>
    {else}
      <span class="button election-action-primary election-action-disabled" aria-disabled="true" title="{$editReason|escape}"><span><i class="crm-i fa-pencil" role="img" aria-hidden="true"></i> {ts}Edit Election{/ts}<span class="election-visually-hidden"> ({$editReason|escape})</span></span></span>
    {/if}
    <a class="button" href="{crmURL p='civicrm/elections/positions' q="reset=1&eid=`$election->id`"}"><span><i class="crm-i fa-list-ol" role="img" aria-hidden="true"></i> {ts}Positions{/ts}</span></a>
    <a class="button" href="{crmURL p='civicrm/elections/candidate-list' q='reset=1'}#/?eid={$election->id}"><span><i class="crm-i fa-users" role="img" aria-hidden="true"></i> {ts}Candidates{/ts}</span></a>
    <a class="button" href="{crmURL p='civicrm/elections/results-display' q="reset=1&eid=`$election->id`"}"><span><i class="crm-i fa-image" role="img" aria-hidden="true"></i> {ts}Results Page{/ts}</span></a>
    {if $hasResults}<a class="button" href="{crmURL p='civicrm/elections/result-list' q='reset=1'}#/?eid={$election->id}"><span><i class="crm-i fa-trophy" role="img" aria-hidden="true"></i> {ts}Results{/ts}</span></a>{/if}
    <a class="button" href="{crmURL p='civicrm/elections/audit-log' q='reset=1'}#/?eid={$election->id}"><span><i class="crm-i fa-shield" role="img" aria-hidden="true"></i> {ts}Audit Log{/ts}</span></a>
    <a class="button" href="{crmURL p='civicrm/elections/view' q="eid=`$election->id`"}"><span><i class="crm-i fa-eye" role="img" aria-hidden="true"></i> {ts}View as a Voter{/ts}</span></a>
    {if $isActive}
      <a class="button crm-popup" href="{crmURL p='civicrm/elections/visibility' q="eid=`$election->id`"}"><span><i class="crm-i fa-toggle-off" role="img" aria-hidden="true"></i> {ts}Deactivate{/ts}</span></a>
    {else}
      <a class="button crm-popup" href="{crmURL p='civicrm/elections/visibility' q="eid=`$election->id`"}"><span><i class="crm-i fa-toggle-on" role="img" aria-hidden="true"></i> {ts}Activate{/ts}</span></a>
    {/if}
  </div>

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

  {if $writeInsWithoutRevote}
  <div class="messages status no-popup election-writein-warning">{ts}Write-ins are allowed but people cannot change their vote. People who voted before a person written in accepted will not see them on the ballot. Consider turning on &quot;Allow people to change their vote&quot;.{/ts}</div>
  {/if}
  {if $writeIns}
  <div class="election-overview-writeins">
    <h3>{ts}People written in{/ts}</h3>
    <p class="description">{ts}A person written in must accept before voting closes. Votes for anyone who does not accept are dropped. This list never shows who wrote someone in.{/ts}</p>
    <table class="election-writein-table">
      <thead><tr><th>{ts}Position{/ts}</th><th>{ts}Person{/ts}</th><th>{ts}Status{/ts}</th></tr></thead>
      <tbody>
        {foreach from=$writeIns item=w}<tr><td>{$w.position|escape}</td><td>{$w.name|escape}</td><td>{$w.status|escape}</td></tr>{/foreach}
      </tbody>
    </table>
  </div>
  {/if}
  {if $eligibleRoles}
  <div class="election-overview-roles">
    <h3>{ts}Who can take part{/ts}</h3>
    <ul>
      <li>{ts 1=$eligibleVoters}%1 people may vote{/ts}</li>
      {foreach from=$eligibleRoles item=role}<li>{$role.label|escape}: {$role.count|escape}</li>{/foreach}
    </ul>
  </div>
  {/if}

  {if $stage.key == 'voting' or $stage.key == 'closed' or $stage.key == 'results' or $counts.ballots > 0}
  <div class="election-overview-turnout">
    <h3>{ts}Turnout{/ts}</h3>
    <p class="election-turnout-summary">
      {ts 1=$counts.ballots}%1 ballots cast{/ts}{if $eligibleVoters > 0}, {ts 1=$eligibleVoters 2=$turnoutPercent}out of %1 people allowed to vote (%2%){/ts}{/if}
    </p>
    <table class="election-turnout-table">
      <thead>
        <tr><th>{ts}Position{/ts}</th><th>{ts}Candidates standing{/ts}</th><th>{ts}Ballots with a choice{/ts}</th></tr>
      </thead>
      <tbody>
        {foreach from=$turnout item=row}
          <tr>
            <td>{$row.name|escape}</td>
            <td>{if $row.isQuestion}<span class="description">{ts}Question{/ts}</span>{else}{$row.candidates}{/if}</td>
            <td>{if $row.ballots === NULL}<span class="description">{ts}Not shown: votes are anonymised{/ts}</span>{else}{$row.ballots}{/if}</td>
          </tr>
        {/foreach}
      </tbody>
    </table>
    <p class="description">{ts}Only counts are shown here, never who voted for whom.{/ts}</p>
  </div>
  {/if}

  <div class="election-overview-integrity">
    <h3>{ts}Integrity{/ts}</h3>
    <div class="messages {if $integrity.status == 'ok'}success{elseif $integrity.status == 'mismatch'}error{else}status{/if} no-popup election-integrity-{$integrity.status|escape}">
      {$integrity.message|escape}
    </div>
  </div>

  <div class="election-overview-audit">
    <h3 id="election-audit-heading">{ts}Audit log{/ts}</h3>
    <p class="description">{ts}The audit log records every change to this election: when it happened, who made it and what kind of change it was. Entries cannot be changed or removed.{/ts}</p>
    <p class="election-fingerprint-row">
      <strong id="election-fingerprint-label">{ts}Audit log fingerprint{/ts}</strong>
      <code id="election-fingerprint" class="election-fingerprint" aria-labelledby="election-fingerprint-label">{$auditHead|escape}</code>
      <button type="button" class="button election-copy" data-copy-target="election-fingerprint"><span><i class="crm-i fa-clipboard" role="img" aria-hidden="true"></i> <span class="election-copy-label">{ts}Copy{/ts}</span></span></button>
      <span class="election-visually-hidden" id="election-copy-status" role="status" aria-live="polite"></span>
    </p>
    <p class="description">{ts}Note the fingerprint down somewhere outside the website, to be able to show later that nothing was removed from the log.{/ts}</p>
    {if $auditRecent}
    <table class="election-audit-recent" aria-labelledby="election-audit-heading">
      <thead><tr><th scope="col">{ts}When{/ts}</th><th scope="col">{ts}Who{/ts}</th><th scope="col">{ts}What{/ts}</th></tr></thead>
      <tbody>
        {foreach from=$auditRecent item=entry}
          <tr><td>{$entry.date|crmDate}</td><td>{if $entry.who}{$entry.who|escape}{else}{ts}System{/ts}{/if}</td><td>{$entry.label|escape}</td></tr>
        {/foreach}
      </tbody>
    </table>
    {else}
    <p class="election-audit-empty">{ts}There are no audit log entries for this election yet.{/ts}</p>
    {/if}
  </div>

</div>
