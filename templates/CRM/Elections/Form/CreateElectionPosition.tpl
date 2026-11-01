<div class="crm-elections-helptext-block">
  <p>{ts}A position is a role people stand for. One or more people can be elected to it. Fields marked * are required.{/ts}</p>
</div>

<div class="crm-block crm-form-block crm-create-election-position-form-block crm-election-form-block-container">
  <table class="form-layout">
    <tr class="crm-create-election-position-form-block-name">
      <td class="label">{$form.name.label}</td>
      <td>{$form.name.html}
				<p class="description">{ts}The name of the position, shown on the nomination form and the ballot.{/ts}</p></td>
		</tr>
    <tr class="crm-create-election-position-form-block-name">
      <td class="label">{$form.position_type.label}</td>
      <td>{$form.position_type.html}
				<p class="description">{ts}A position is something people stand for. A question is a yes or no question voters answer.{/ts}</p></td>
		</tr>
    <tr class="crm-create-election-position-form-block-name election-position-only">
      <td class="label">{$form.quantity.label}</td>
      <td>{$form.quantity.html}
				<p class="description">{ts}The number of people to be elected to this position.{/ts}</p></td>
		</tr>
    <tr class="crm-create-election-position-form-block-name">
      <td class="label">{$form.sortorder.label}</td>
      <td>{$form.sortorder.html}
				<p class="description">{ts}The place of this position on the ballot.{/ts}</p></td>
		</tr>
    <tr class="election-question-only">
      <td class="label">{$form.yes_label.label}</td>
      <td>{$form.yes_label.html}<p class="description">{ts}Shown on the ballot instead of Yes, for example For or Approve.{/ts}</p></td>
    </tr>
    <tr class="election-question-only">
      <td class="label">{$form.no_label.label}</td>
      <td>{$form.no_label.html}<p class="description">{ts}Shown instead of No, for example Against or Reject.{/ts}</p></td>
    </tr>
    <tr class="election-question-only">
      <td class="label">{$form.abstain_label.label}</td>
      <td>{$form.abstain_label.html}<p class="description">{ts}Leave empty for no Abstain choice. An abstention counts towards the quorum and not towards the percentage.{/ts}</p></td>
    </tr>
    <tr class="election-question-only">
      <td class="label">{$form.pass_threshold_choice.label}</td>
      <td>{$form.pass_threshold_choice.html} <span class="election-threshold-custom">{$form.pass_threshold_custom.html} %</span>
        <p class="description">{ts}The share of Yes out of Yes plus No needed to pass, so a tie fails and abstentions are not counted.{/ts}</p></td>
    </tr>
    <tr class="election-question-only">
      <td class="label">{$form.quorum_percent.label}</td>
      <td>{$form.quorum_percent.html} %
        <p class="description">{ts}The share of the people allowed to vote who must take part, including abstentions. If fewer take part the result is &quot;no quorum, not decided&quot;; leave it empty for no quorum.{/ts}</p></td>
    </tr>
    <tr class="election-question-only">
      <td class="label">{$form.resolution_text.label}</td>
      <td>{$form.resolution_text.html}<p class="description">{ts}The full wording of the resolution, shown above the question.{/ts}</p></td>
    </tr>
    <tr class="crm-create-election-position-form-block-name election-position-only">
      <td class="label">{$form.write_ins_per_seat.label}</td>
      <td>{$form.write_ins_per_seat.html}
				<p class="description">{ts}How many people a voter can write in for each seat, from 0 to 10. 0 means write-ins are off.{/ts}</p></td>
		</tr>
		<tr class="crm-create-election-position-form-block-description">
      <td class="label">{$form.description.label}</td>
      <td>{$form.description.html}
				<p class="description">{ts}More about the position, shown on the nomination form and the ballot.{/ts}</p></td>
		</tr>

    <tr class="crm-create-election-position-form-block-buttons">
      <td colspan="2">
        <div class="crm-submit-buttons">
          {include file="CRM/common/formButtons.tpl" location="bottom"}
    {if !$isPopup}{include file="CRM/Elections/Frame/BackButtons.tpl"}{/if}
        </div>
      </td>
    </tr>
  </table>
</div>

{literal}
<script type="text/javascript">
  CRM.$(function ($) {
    function sync() {
      var question = $('#position_type').val() === 'referendum';
      $('.election-question-only').toggle(question);
      $('.election-position-only').toggle(!question);
      $('.election-threshold-custom').toggle($('#pass_threshold_choice').val() === 'custom');
    }
    $('#position_type, #pass_threshold_choice').on('change', sync);
    sync();
  });
</script>
{/literal}
