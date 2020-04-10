<div class="candidate-details-block">
    <div class="candidate-details-left-block">
        {assign var='imgKey' value='member_nominee.image_URL'}
        {assign var='profilePicUrl' value=$nomination.$imgKey}
        <img src="{$profilePicUrl}" /><br>
    </div><!-- ending of candidate-details-left-block -->

    <div class="candidate-details-right-block">
        {assign var='displayname' value='member_nominee.display_name'}
        <h2>{$nomination.$displayname}</h2>
    </div><!-- ending of candidate-details-right-block -->
</div><!-- ending of candidate-details-block -->

{foreach from=$positions item=position}
    <div class="candidate-details-block">
        <div class="candidate-details-left-block">
            {foreach from=$position.nominations item=positionNomination}
                {if $positionNomination.member_nominee == $nomination.member_nominee}
                    {assign var="cTerm" value="Nominated"}
                    {if $election->advertiseCandidatesStarted and $positionNomination.has_accepted_nomination == 1}
                        {assign var="cTerm" value="Candidate"}
                    {/if}

                    <h3>{$cTerm} for {$position.name}</h3>
                    {if $election->advertiseCandidatesStarted or $election->isNominationsStarted}
                        {if $positionNomination.is_eligible_candidate == 1}
                            {ts}Candidate{/ts}
                        {else}
                            {ts}Nomination{/ts}
                        {/if}
                        {ts}Status:{/ts}
                        {if $positionNomination.has_accepted_nomination == 1}
                            {ts}Accepted{/ts}<br><br>
                            {$positionNomination.comments|nl2br}
                        {elseif $positionNomination.has_rejected_nomination == 1}
                            {ts}Withdrawn{/ts}<br><br>
                            {$positionNomination.rejection_comments|nl2br}
                        {else}
                            {if $election->isVotingStarted}
                                {ts}Not Accepted{/ts}
                            {else}
                                {ts}Pending{/ts}
                            {/if}
                        {/if}
                    {/if}
                {/if}
            {/foreach}
        </div><!-- ending of candidate-details-left-block -->

        <div class="candidate-details-right-block">
            <h3>{ts}Was nominated by{/ts}</h3>
            {foreach from=$position.nominations item=positionNomination}
                {if $positionNomination.member_nominee == $nomination.member_nominee}
                    {foreach from=$positionNomination.seconders item=seconder}
                        {assign var='nominatorName' value='member_nominator.display_name'}
                        <p>{$seconder.$nominatorName}<br>
                        {$seconder.description}</p>
                    {/foreach}
                {/if}
            {/foreach}
        </div><!-- ending of candidate-details-right-block -->
    </div><!-- ending of candidate-details-block -->
{/foreach}
