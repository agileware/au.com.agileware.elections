<div class="crm-block crm-form-block crm-duplicate-election-form-block crm-election-form-block-container">
  <p>{ts 1=$election->name}Copy "%1" with its positions as a new inactive election. Nominations, votes and results are not copied. All dates move by the same amount, so the stages keep their spacing.{/ts}</p>
  <table class="form-layout">
    <tr>
      <td class="label">{$form.name.label}</td>
      <td>{$form.name.html}</td>
    </tr>
    <tr>
      <td class="label">{$form.visibility_start_date.label}</td>
      <td>{$form.visibility_start_date.html}</td>
    </tr>
  </table>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
