{if $resultsDisplay.has_logo|default:false || $resultsDisplay.header_text|default:''}
<div class="election-results-header">
    {if $resultsDisplay.has_logo}<img class="election-results-logo" src="{$resultsDisplay.logo_url|escape}" alt="{$resultsDisplay.logo_alt|escape}">{/if}
    {if $resultsDisplay.header_text}<p class="election-results-header-text">{$resultsDisplay.header_text|escape|nl2br}</p>{/if}
</div>
{/if}
