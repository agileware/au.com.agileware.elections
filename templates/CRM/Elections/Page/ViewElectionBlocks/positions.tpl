<h2>{ts}Positions{/ts}</h2>
{if empty($positions)}
    <p class="no-result-message">{ts}There are no positions yet.{/ts}</p>
{/if}
{foreach from =$positions key=k item=position}
  {if $position.position_type|default:'election' != 'referendum'}
    <h3>{$position.name|escape} ({$position.quantity|escape})</h3>
    <p>{$position.description|escape|nl2br}</p>
  {/if}
{/foreach}
