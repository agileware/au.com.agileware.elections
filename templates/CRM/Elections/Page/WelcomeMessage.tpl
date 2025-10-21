{if $checksum_authenticated }
    <div class="messages status no-popup welcome-message">
        <h2 class="msg-text"><i aria-hidden="true" class="crm-i fa-info-circle"></i>Welcome, {$checksum_authenticated.display_name}.</h2>
        <p><span class="msg-text">Not {$checksum_authenticated.display_name}, or want to do this for a different person? <a href={$login_url}>Login here.</a></span></p>
    </div>
{/if}
