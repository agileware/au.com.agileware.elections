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
      <td class="label">{$form.position_type.label}</td>
      <td>{$form.position_type.html}
				<p class="description">{ts}An election position is one that people stand for. A referendum question is a yes or no question that voters answer.{/ts}</p></td>
		</tr>
    <tr class="crm-create-election-position-form-block-name election-position-only">
      <td class="label">{$form.quantity.label}</td>
      <td>{$form.quantity.html}
				<p class="description">{ts}Number of persons who are to be elected to the position{/ts}</p></td>
		</tr>
    <tr class="crm-create-election-position-form-block-name">
      <td class="label">{$form.sortorder.label}</td>
      <td>{$form.sortorder.html}
				<p class="description">{ts}Order that the position is shown in on the ballot.{/ts}</p></td>
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
      <td>{$form.abstain_label.html}<p class="description">{ts}Optional. Leave empty for no Abstain choice. An abstention counts towards the quorum and not towards the percentage.{/ts}</p></td>
    </tr>
    <tr class="election-question-only">
      <td class="label">{$form.pass_threshold_choice.label}</td>
      <td>{$form.pass_threshold_choice.html} <span class="election-threshold-custom">{$form.pass_threshold_custom.html} %</span>
        <p class="description">{ts}The share of Yes out of Yes plus No needed to pass. A tie fails. Abstentions are not counted in the share.{/ts}</p></td>
    </tr>
    <tr class="election-question-only">
      <td class="label">{$form.quorum_percent.label}</td>
      <td>{$form.quorum_percent.html} %
        <p class="description">{ts}Optional. The share of the people allowed to vote who must take part (including abstentions). If fewer take part the result is &quot;no quorum, not decided&quot;. Leave empty for no quorum.{/ts}</p></td>
    </tr>
    <tr class="election-question-only">
      <td class="label">{$form.resolution_text.label}</td>
      <td>{$form.resolution_text.html}<p class="description">{ts}The full wording of the resolution, shown above the question.{/ts}</p></td>
    </tr>
    <tr class="crm-create-election-position-form-block-name election-position-only">
      <td class="label">{$form.write_ins_per_seat.label}</td>
      <td>{$form.write_ins_per_seat.html}
				<p class="description">{ts}How many people a voter can write in for each seat (0 to 10). 0 means write-ins are off. A person written in is told straight away and must accept before voting closes; votes for them count only if they do. Works best with &quot;Allow people to change their vote&quot; on, so people who voted earlier can vote again and see new candidates.{/ts}</p></td>
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
