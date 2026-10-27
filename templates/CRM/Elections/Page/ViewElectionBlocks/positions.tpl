<h2>{ts}Positions{/ts}</h2>
{if empty($positions)}
    <p class="no-result-message">{ts}Positions are not added yet.{/ts}</p>
{/if}
{foreach from =$positions key=k item=position}
    <h3>{$position.name|escape} ({$position.quantity|escape})</h3>
    <p>{$position.description|escape|nl2br}</p>
{/foreach}
