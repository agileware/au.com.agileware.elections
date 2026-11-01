<div class="crm-visibility-election-form-block crm-election-form-block-container">
    {if $withdrawing}
    <div class="messages warning no-popup election-visibility-message" role="status">
        <strong>{ts}This election is active and its nominations have started.{/ts}</strong>
        {ts}Deactivating it hides the election from everyone except administrators. It can then be edited again, and the change is recorded in the audit log.{/ts}
    </div>
    <table class="form-layout">
        <tr>
            <td class="label">{$form.confirm_name.label}</td>
            <td>{$form.confirm_name.html}<br><span class="description">{ts 1=$electionName|escape}The name is %1.{/ts}</span></td>
        </tr>
    </table>
    {else}
    <div class="messages status no-popup election-visibility-message" role="status">
        {if $election->is_visible}
            {ts 1=$electionName|escape}This hides the election &quot;%1&quot; from everyone except administrators.{/ts}
        {else}
            {ts 1=$electionName|escape}This makes the election &quot;%1&quot; visible to people from its Visible from date until its Visible until date.{/ts}
        {/if}
    </div>
    {/if}
    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
</div><!-- ending of crm block -->
