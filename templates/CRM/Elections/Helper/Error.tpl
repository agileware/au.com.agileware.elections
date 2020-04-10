<div class="messages status no-popup election-visibility-message">
    {$errormessage}
    {if $nonmember}
        <br><strong>{ts}Become a member now{/ts}</strong>
    {/if}
</div>
{if $return_button_text and $return_button_action}
    <input type="button" value="{ts}{$return_button_text}{/ts}" onclick="window.location.href='{$return_button_action}'" class="crm-form-submit default validate" />
{/if}