{* Candidate statement. Expects $statementText, $statementUrl and $statementDisplay (link|inline). *}
{if $statementText or $statementUrl}
  {if $statementDisplay == 'inline'}
    <div class="election-statement">
      {if $statementText}<p>{$statementText|escape|nl2br}</p>{/if}
      {if $statementUrl}<p><a href="{$statementUrl|escape}" target="_blank" rel="noopener noreferrer">{ts}Read the full statement{/ts}</a></p>{/if}
    </div>
  {else}
    <details class="election-statement">
      <summary>{ts}Read statement{/ts}</summary>
      {if $statementText}<p>{$statementText|escape|nl2br}</p>{/if}
      {if $statementUrl}<p><a href="{$statementUrl|escape}" target="_blank" rel="noopener noreferrer">{ts}Read the full statement{/ts}</a></p>{/if}
    </details>
  {/if}
{/if}
