<div class="crm-block crm-form-block crm-duplicate-election-position-form-block crm-election-form-block-container">
  <p>{ts 1=$position.name}Copy the position "%1" to the end of the list.{/ts}</p>
  <table class="form-layout">
    <tr>
      <td class="label">{$form.name.label}</td>
      <td>{$form.name.html}</td>
    </tr>
  </table>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
