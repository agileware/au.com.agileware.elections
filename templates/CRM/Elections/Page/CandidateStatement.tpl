{* Candidate statement. Expects $statementText, $statementUrl and $statementDisplay (link|inline). *}
{assign var=safeStatementUrl value=$statementUrl|default:''|elections_safe_url}
{if $statementText or $safeStatementUrl}
  {if $statementDisplay == 'inline'}
    <div class="election-statement">
      {if $statementText}<p>{$statementText|escape|nl2br}</p>{/if}
      {if $safeStatementUrl}<p><a href="{$safeStatementUrl|escape}" target="_blank" rel="noopener noreferrer">{ts}Read the full statement{/ts}</a></p>{/if}
    </div>
  {else}
    <details class="election-statement">
      <summary>{ts}Read statement{/ts}</summary>
      {if $statementText}<p>{$statementText|escape|nl2br}</p>{/if}
      {if $safeStatementUrl}<p><a href="{$safeStatementUrl|escape}" target="_blank" rel="noopener noreferrer">{ts}Read the full statement{/ts}</a></p>{/if}
    </details>
  {/if}
{/if}
