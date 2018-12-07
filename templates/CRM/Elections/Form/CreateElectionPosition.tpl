<div class="crm-elections-helptext-block">
  <p>{ts}Use this form to add a position to be nominated for and voted on in your election.{/ts}</p>
  <p>{ts}Positions describe an elected role to which one more persons may be elected.{/ts}</p>
</div>

<div class="crm-block crm-form-block crm-create-election-position-form-block crm-election-form-block-container">
  <table class="form-layout">
    <tr class="crm-create-election-position-form-block-name">
      <td class="label">{$form.name.label}</td>
      <td>{$form.name.html}
				<p class="description">{ts}Name of the position, to be displayed on the nomination and voting forms.{/ts}</p></td>
		</tr>
    <tr class="crm-create-election-position-form-block-name">
      <td class="label">{$form.quantity.label}</td>
      <td>{$form.quantity.html}
				<p class="description">{ts}Number of persons who are to be elected to the position{/ts}</p></td>
		</tr>
    <tr class="crm-create-election-position-form-block-name">
      <td class="label">{$form.sortorder.label}</td>
      <td>{$form.sortorder.html}
				<p class="description">{ts}Order that the position is shown in on the ballot.{/ts}</p></td>
		</tr>
		<tr class="crm-create-election-position-form-block-description">
      <td class="label">{$form.description.label}</td>
      <td>{$form.description.html}
				<p class="description">{ts}Additional information about the position to be shown on the nomination and voting forms.{/ts}</p></td>
		</tr>

    <tr class="crm-create-election-position-form-block-buttons">
      <td colspan="2">
        <div class="crm-submit-buttons">
          {include file="CRM/common/formButtons.tpl" location="bottom"}
        </div>
      </td>
    </tr>
  </table>
</div>
