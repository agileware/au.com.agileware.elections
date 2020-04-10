<div class="crm-visibility-election-form-block crm-election-form-block-container">
    <div class="messages status no-popup election-visibility-message">
        {if $election->is_visible}
            {ts}This will restrict all pages for this election to administrators.{/ts}
        {else}
            {ts}This will remove the administrator restriction on all pages for this election.{/ts}
        {/if}
        {$form.eid.html}
    </div>
    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
</div><!-- ending of crm block -->
