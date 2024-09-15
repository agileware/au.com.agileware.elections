{if $position.result_status == $resultStatuses.results_majority}
    <p>Winner by clear majority.</p>
{/if}

{if $position.result_status == $resultStatuses.results_no_majority}
    <p>There was no clear majority winner between two candidates at the end of the result process.</p>
{/if}

{if $position.result_status == $resultStatuses.results_no_nominations}
    <p>There are not enough candidates for this position to generate a result.</p>
{/if}

{if $position.result_status == $resultStatuses.results_more_seats}
    <p>Not all seats were filled for this position.</p>
{/if}

{if $position.result_status == $resultStatuses.results_equal_seats}
    <p>The number of candidates remaining equals the number of position holders. These candidates are the winners.</p>
{/if}
