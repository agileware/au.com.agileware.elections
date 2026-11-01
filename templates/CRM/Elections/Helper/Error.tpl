{* Access denied, not found and "voting closed" pages. Expects $errormessage (already translated). Optional: $errorKind
   (error, the default, or status for a page that is not a refusal) and $return_button_text with $return_button_action,
   the one link away from the page (already translated, so it is printed as it is). One message only. *}
{assign var=errorClass value=$errorKind|default:'error'}
<div class="messages {if $errorClass == 'status'}status{else}error{/if} no-popup election-visibility-message">
    {$errormessage|escape}
</div>
{if $return_button_text and $return_button_action}
    <p class="election-error-actions"><a href="{$return_button_action|escape}" class="election-btn election-btn-quiet">{$return_button_text|escape}</a></p>
{/if}
