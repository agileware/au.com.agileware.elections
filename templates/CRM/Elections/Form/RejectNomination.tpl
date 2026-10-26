{include file="CRM/Elections/Page/WelcomeMessage.tpl"}
<div class="crm-elections-helptext-block">
  <p>{ts}To withdraw your candidacy, add any comments and press the "Withdraw" button.{/ts}</p>
</div>

<div class="crm-accept-nomination-form-block crm-election-form-block-container">
  {assign var='spKey' value='election_position_id.name'}

  {if $electionNomination.is_eligible_candidate}
    <p>{ts 1=$electionNomination.$spKey}You're eligible as a candidate for %1{/ts}</p>
  {/if}

  <div class="crm-election-form-row">
    <div class="crm-election-form-col-left">
      <label>{ts}Election{/ts}</label>
    </div>
    <div class="crm-election-form-col-right">
      {assign var='sKey' value='election_position_id.election_id.name'}
      {$electionNomination.$sKey}
    </div>
  </div><!-- ending of crm-election-form-row -->

  <div class="crm-election-form-row">
    <div class="crm-election-form-col-left">
      <label>{ts}Position{/ts}</label>
    </div>
    <div class="crm-election-form-col-right">
      {$electionNomination.$spKey}
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
    <div class="crm-election-form-col-left">
      {include file="CRM/common/formButtons.tpl" location="bottom"}&nbsp;
    </div>
    <div class="crm-election-form-col-right">

    </div>
  </div><!-- ending of crm-election-form-row -->
</div>
