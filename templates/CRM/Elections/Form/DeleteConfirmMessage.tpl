<div class="crm-election-form-block-container">
    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="top"}
    </div>
    <div class="messages status no-popup election-delete-message">
        <div class="icon inform-icon"></div>
        <span class="font-red bold">{ts}WARNING: This action cannot be undone.{/ts}</span><br>
        {ts}Click 'Delete' to continue.{/ts}
        {$form.eid.html}
    </div>
    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
</div>