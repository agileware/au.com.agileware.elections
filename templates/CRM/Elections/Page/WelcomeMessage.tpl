{if $checksum_authenticated }
    <div class="messages status no-popup welcome-message">
        <h2 class="msg-text"><i aria-hidden="true" class="crm-i fa-info-circle"></i>{ts 1=$checksum_authenticated.display_name}Welcome, %1.{/ts}</h2>
        <p><span class="msg-text">{capture assign=loginLink}<a href="{$login_url}">{ts}Login here.{/ts}</a>{/capture}{ts 1=$checksum_authenticated.display_name 2=$loginLink}Not %1, or want to do this for a different person? %2{/ts}</span></p>
    </div>
{/if}
