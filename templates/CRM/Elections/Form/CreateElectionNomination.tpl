<div class="crm-election-form-block-container">

  {include file="CRM/Elections/Page/WelcomeMessage.tpl"}

  <div class="crm-elections-helptext-block">
    <p>{ts}Use this form to nominate someone to a given position in this election by selecting the relevant position and then searching for your nominee by name or email. Only persons that can vote in this election may be nominated.{/ts} </p>
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
      <label>{$form.reason.label}</label>
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
        return 'Start typing a name or email...';
    };
  </script>
{/literal}
