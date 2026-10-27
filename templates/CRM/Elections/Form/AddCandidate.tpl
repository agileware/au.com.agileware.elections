<div class="crm-block crm-form-block crm-add-candidate-form-block crm-election-form-block-container">
  <p>{ts}Use this when nominations are made outside the website. The person is added as an eligible candidate; tick "has accepted" if they have already agreed to stand, so they appear on the ballot. They must belong to one of the election's groups.{/ts}</p>
  <table class="form-layout">
    <tr><td class="label">{$form.position.label}</td><td>{$form.position.html}</td></tr>
    <tr><td class="label">{$form.contact.label}</td><td>{$form.contact.html}</td></tr>
    <tr><td class="label">{$form.accepted.label}</td><td>{$form.accepted.html}</td></tr>
    <tr><td class="label">{$form.statement.label}</td><td>{$form.statement.html}</td></tr>
  </table>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
