<div class="crm-visibility-election-form-block crm-election-form-block-container">
    <div class="messages status no-popup election-visibility-message">
        {if $election->is_visible}
            This will restrict all pages for this election to administrators.
        {else}
            This will remove the administrator restriction on all pages for this election.
        {/if}
        {$form.eid.html}
    </div>
    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
</div><!-- ending of crm block -->
