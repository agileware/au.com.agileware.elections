{* Candidate statement. Expects $statementText, $statementUrl and $statementDisplay (link|inline). *}
{* Only an http or https address becomes a link, so a javascript: or data: address stored in the field never does. *}
{assign var=statementUrlStart value=$statementUrl|default:''|lower|truncate:8:''}
{if $statementUrlStart == 'https://' or $statementUrlStart|truncate:7:'' == 'http://'}{assign var=safeStatementUrl value=$statementUrl}{else}{assign var=safeStatementUrl value=''}{/if}
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
