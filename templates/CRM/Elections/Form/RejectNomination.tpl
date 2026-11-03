{if $backToElectionUrl}{include file="CRM/Elections/Frame/BackToElection.tpl"}{/if}
{include file="CRM/Elections/Page/WelcomeMessage.tpl"}
<div class="crm-elections-helptext-block">
  {if $isCandidate}
    <p>{ts}Add a comment if you like, then select Withdraw.{/ts}</p>
  {else}
    <p>{ts}Add a comment if you like, then select Decline.{/ts}</p>
  {/if}
  <p>{ts}You cannot be nominated for this position again. People who view the election can see your comment.{/ts}</p>
</div>

<div class="crm-accept-nomination-form-block crm-election-form-block-container">
  {assign var='spKey' value='election_position_id.name'}

  {if $isCandidate}
    <p>{ts 1=$electionNomination.$spKey|escape}You are a candidate for %1.{/ts}</p>
  {else}
    <p>{ts 1=$electionNomination.$spKey|escape}You have been nominated for %1.{/ts}</p>
  {/if}

  <div class="crm-election-form-row">
    <div class="crm-election-form-col-left">
      <strong class="election-static-label">{ts}Election{/ts}</strong>
    </div>
    <div class="crm-election-form-col-right">
      {assign var='sKey' value='election_position_id.election_id.name'}
      {$electionNomination.$sKey|escape}
    </div>
  </div><!-- ending of crm-election-form-row -->

  <div class="crm-election-form-row">
    <div class="crm-election-form-col-left">
      <strong class="election-static-label">{ts}Position{/ts}</strong>
    </div>
    <div class="crm-election-form-col-right">
      {$electionNomination.$spKey|escape}
    </div>
  </div><!-- ending of crm-election-form-row -->

  <div class="crm-election-form-row">
    <div class="crm-election-form-col-left">
      {$form.nominationcomments.label}
    </div>
    <div class="crm-election-form-col-right custom_accept">
      {$form.nominationcomments.html}
    </div>
  </div><!-- ending of crm-election-form-row -->

  <div class="crm-election-form-row">
    <div class="crm-election-form-col-right">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
  </div><!-- ending of crm-election-form-row -->
</div>
