<div class="crm-elections-helptext-block">
    {if !$election->isNominationsStarted}
        <p>{ts}Nominations have not yet started for this election. The positions that will be open for nomination are listed below.{/ts}</p>
    {/if}
    {if $election->isNominationsInProgress}
			<p>{ts}This Election is currently in the Nominations phase.{/ts}</p>
			<p>{ts}You can submit nominations for this Election using the button below, and see existing nominations for each position. If you have been nominated for any positions, you can see these and have the option of withdrawing them. Nominations that require one or persons to second them will also be marked as such – press the "Need second" button to add your nominations{/ts}</p>
    {/if}
    {if $election->isNominationsStarted and (!$election->isNominationsInProgress) and !$election->advertiseCandidatesStarted}
        <p>{ts}The Nominations period for this Election is now closed. Eligible nominations are listed below.{/ts}</p>
    {/if}
    {if $election->advertiseCandidatesStarted and (!$election->isVotingStarted)}
        <p>{ts}The Nominations period for this Election is now closed. The nominated candidates for each position are listed below.{/ts}</p>
    {/if}
    {if $election->isVotingStarted and !$election->isVotingEnded}
        <p>{ts}Voting is now open for this Election. Use the "Vote now" button to submit your preferences from the candidates listed below.{/ts}</p>
    {/if}

    {if $election->isVotingEnded and !$election->isResultsOut}
       <p>{ts}Voting has closed for this election. The candidates are shown below.{/ts}</p>
    {/if}

    {if $election->isResultsOut}
       <p>{ts}The votes have been counted for this election. The winners are shown below.{/ts}</p>
    {/if}
</div>
