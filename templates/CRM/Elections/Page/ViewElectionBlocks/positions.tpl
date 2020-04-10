<h2>Positions</h2>
{if $positions|@count == 0}
    <p class="no-result-message">{ts}No positions are currently available.{/ts}</p>
{/if}
{foreach from =$positions key=k item=position}
    <h3>{$position.name} ({$position.quantity})</h3>
    <p>{$position.description|nl2br}</p>
{/foreach}