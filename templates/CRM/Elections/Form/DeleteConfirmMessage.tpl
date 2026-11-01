<div class="crm-election-form-block-container">
    {if $deleteBlocked}
    <div class="messages error no-popup election-delete-message" role="alert">
        {$deleteBlocked|escape}
    </div>
    {else}
    <div class="messages warning no-popup election-delete-message" role="alert">
        {$deleteMessage|escape}
    </div>
    {/if}
    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
</div>
