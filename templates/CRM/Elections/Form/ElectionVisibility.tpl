<div class="crm-visibility-election-form-block crm-election-form-block-container">
    {if $withdrawing}
    <div class="messages warning no-popup election-visibility-message">
        <strong>{ts}This election is active and its nominations have started.{/ts}</strong>
        {ts}Withdrawing it hides every page of the election from everyone except administrators, and it can then be edited again. The withdrawal is written to the audit log.{/ts}
    </div>
    <table class="form-layout">
        <tr>
            <td class="label">{$form.confirm_name.label}</td>
            <td>{$form.confirm_name.html}<br><span class="description">{ts 1=$election->name|escape}The name is: %1{/ts}</span></td>
        </tr>
    </table>
    {else}
    <div class="messages status no-popup election-visibility-message">
        {if $election->is_visible}
            {ts}This will restrict all pages for this election to administrators.{/ts}
        {else}
            {ts}This will remove the administrator restriction on all pages for this election.{/ts}
        {/if}
    </div>
    {/if}
    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
</div><!-- ending of crm block -->
