<div class="messages status no-popup election-visibility-message">
    {$errormessage|escape}
    {if $nonmember}
        <br><strong>{ts}Become a Member Now{/ts}</strong>
    {/if}
</div>
{if $return_button_text and $return_button_action}
    <a href="{$return_button_action|escape}" class="election-btn">{ts}{$return_button_text}{/ts}</a>
{/if}
