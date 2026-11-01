{if $checksum_authenticated }
    <div class="messages status no-popup welcome-message">
        <p class="msg-text"><i aria-hidden="true" class="crm-i fa-info-circle"></i> {capture assign=loginLink}<a href="{$login_url|escape}">{ts}Log in{/ts}</a>{/capture}{ts 1=$checksum_authenticated.display_name|escape 2=$loginLink}You are taking part as %1. Not you? %2{/ts}</p>
    </div>
{/if}
