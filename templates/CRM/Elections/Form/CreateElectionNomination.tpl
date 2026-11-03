<div class="crm-election-form-block-container">

  {if $backToElectionUrl}{include file="CRM/Elections/Frame/BackToElection.tpl"}{/if}

  {include file="CRM/Elections/Page/WelcomeMessage.tpl"}

  <div class="crm-elections-helptext-block">
    <p>{ts}Choose a position, then search for the person you want to nominate by name or email. Only people who can vote in this election can be nominated.{/ts}</p>
  </div>

  <div class="crm-election-form-row">
    <div class="crm-election-form-col-left full">
      {$form.position.label}
    </div>
    <div class="crm-election-form-col-right half">
      {$form.position.html}
    </div>
  </div><!-- ending of crm-election-form-row -->

  <div class="crm-election-form-row">
    <div class="crm-election-form-col-left full">
      {$form.contact.label}
    </div>
    <div class="crm-election-form-col-right half">
      {$form.contact.html}
    </div>
  </div><!-- ending of crm-election-form-row -->

  <div class="crm-election-form-row">
    <div class="crm-election-form-col-left full">
      {$form.reason.label}
    </div>
    <div class="crm-election-form-col-right half">
      {$form.reason.html}
    </div>
  </div><!-- ending of crm-election-form-row -->

  <div class="crm-election-form-row">
    <div class="crm-election-form-col-left full">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
    <div class="crm-election-form-col-right half">

    </div>
  </div><!-- ending of crm-election-form-row -->

</div>

{literal}
  <script>
    CRM.$.fn.select2.defaults.formatInputTooShort = function() {
        return '{/literal}{ts escape='js'}Type a name or email{/ts}{literal}';
    };
  </script>
{/literal}
