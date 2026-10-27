{if $position.result_status == $resultStatuses.results_majority}
    <p>{ts}Winner by clear majority.{/ts}</p>
{/if}

{if $position.result_status == $resultStatuses.results_no_majority}
    <p>{ts}The result was a tie, so the tied candidates are listed and none of them is chosen over the others.{/ts}</p>
{/if}

{if $position.result_status == $resultStatuses.results_most_votes}
    <p>{ts}Elected by the most votes.{/ts}</p>
{/if}

{if $position.result_status == $resultStatuses.results_no_nominations}
    <p>{ts}There are not enough candidates for this position to generate a result.{/ts}</p>
{/if}

{if $position.result_status == $resultStatuses.results_more_seats}
    <p>{ts}Not all seats were filled for this position.{/ts}</p>
{/if}

{if $position.result_status == $resultStatuses.results_equal_seats}
    <p>{ts}The number of candidates remaining equals the number of position holders. These candidates are the winners.{/ts}</p>
{/if}
