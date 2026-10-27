{* The stage stepper and the "what you can do now" panel. Expects $electionSteps and $electionNextAction. *}
<nav class="election-stepper" aria-label="{ts}Election stages{/ts}">
  <ol class="election-stepper-list">
    {foreach from=$electionSteps item=step}
      <li class="election-step election-step-{$step.state|escape}"{if $step.state == 'current'} aria-current="step"{/if}>
        <span class="election-step-marker" aria-hidden="true"></span>
        <span class="election-step-label">{$step.label|escape}<span class="election-visually-hidden"> ({$step.stateLabel|escape})</span></span>
        <span class="election-step-date">{$step.from|crmDate}{if $step.to} &ndash; {$step.to|crmDate}{/if}</span>
      </li>
    {/foreach}
  </ol>
  <p class="election-stepper-note">{ts}Times are shown in{/ts} <a target="_blank" rel="noopener noreferrer" href="{$siteTimeZoneConvertUrl|escape}">{$siteTimeZone|escape}</a></p>
</nav>

<section class="election-next-action election-next-{$electionNextAction.type|escape}" aria-label="{ts}What you can do now{/ts}">
  <p class="election-next-message">{$electionNextAction.message|escape}</p>
  {if $electionNextAction.actions}
    <p class="election-next-buttons">
      {foreach from=$electionNextAction.actions item=action}
        <a href="{$action.url|escape}" class="election-btn">{$action.label|escape}</a>
      {/foreach}
    </p>
  {/if}
</section>

{if $myNominations}
  <section class="election-my-nominations" aria-label="{ts}Your nominations{/ts}">
    <h2 class="election-my-nominations-title">{ts}Your nominations{/ts}</h2>
    <ul class="election-my-nomination-list">
      {foreach from=$myNominations item=nomination}
        <li class="election-my-nomination">
          <div class="election-my-nomination-head">
            <strong class="election-my-nomination-position">{$nomination.position|escape}</strong>
            <span class="election-chip election-chip-{$nomination.state|escape}">{$nomination.stateLabel|escape}</span>
          </div>
          {if $nomination.actions}
            <p class="election-my-nomination-actions">
              {foreach from=$nomination.actions item=action}
                <a href="{$action.url|escape}" class="election-btn{if $action.quiet} election-btn-quiet{/if}">{$action.label|escape}</a>
              {/foreach}
            </p>
          {/if}
        </li>
      {/foreach}
    </ul>
  </section>
{/if}

{if $ownBallot}
  <section class="election-your-vote" aria-label="{ts}Your vote{/ts}">
    <h2 class="election-your-vote-title">{ts}Your vote{/ts}</h2>
    <p class="election-your-vote-note">{ts}This is what was recorded. Only you can see it here.{/ts}</p>
    <div class="election-your-vote-positions">
      {foreach from=$ownBallot item=choice}
        <div class="election-your-vote-position">
          <h3>{$choice.position|escape}</h3>
          {if $election->voting_method == 'plurality'}<ul>{else}<ol>{/if}
            {foreach from=$choice.candidates item=candidateName}<li>{$candidateName|escape}</li>{/foreach}
          {if $election->voting_method == 'plurality'}</ul>{else}</ol>{/if}
        </div>
      {/foreach}
    </div>
  </section>
{/if}
