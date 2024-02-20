<h2>
    {if $election->advertiseCandidatesStarted}
        Candidates
    {else}
        Existing Nominations
    {/if}
</h2>
{if empty($nominations) and empty($positions)}
    <p class="no-result-message">Positions are not added yet to be nominated.</p>
{/if}

{if !empty($nominations)}
{foreach from = $nominations key = k item = nomination}
    <div class="crm-election-position-nominations-block">

        {assign var="positionKey" value="Nominations"}
        {if $election->advertiseCandidatesStarted}
            {assign var="positionKey" value="Candidates"}
        {/if}

        <h3>{$positionKey} for {$nomination.name}</h3>

        {if empty($nomination.nominations) and !$election->advertiseCandidatesStarted}
            <p>There are no existing nominations</p>
        {/if}
        {assign var="candidatesCount" value=0}
    <div class="election_row_style">
        {foreach from = $nomination.nominations key = sk item = seconder}

            {if (!$election->advertiseCandidatesStarted and $election->isNominationsInProgress and $seconder.has_rejected_nomination == 0) or (!$election->advertiseCandidatesStarted and !$election->isNominationsInProgress and $seconder.seconders|@count >= 2) or ($election->advertiseCandidatesStarted and !$election->isVotingStarted and $seconder.is_eligible_candidate == 1 and $seconder.has_rejected_nomination == 0) or ($election->isVotingStarted and $seconder.has_accepted_nomination == 1)}
                {assign var='scKey' value='member_nominee.display_name'}
                {assign var='scImgKey' value='member_nominee.image_URL'}

                <div class="crm-election-nominated-block">
                    {if $election->isNominationsStarted}
                        <a href="{crmURL p="civicrm/elections/candidate" q="enid=`$seconder.id`"}">
                    {/if}

                        {assign var="candidatesCount" value=$candidatesCount+1}
                        <div class="crm-election-nominated-block inside">
                            {assign var='profilePicUrl' value=$seconder.$scImgKey}
                            <img src="{$profilePicUrl}" /><br><br>
                            {$seconder.$scKey}<br>

                            {if !$election->isVotingStarted and ($seconder.is_eligible_candidate == 1)}
                                Candidate Status:
                                {if $seconder.has_accepted_nomination == 1}
                                    Accepted<br><br>
                                    {$seconder.comments|nl2br}
                                {elseif $seconder.has_rejected_nomination == 1}
                                    Withdrawn<br><br>
                                    {$seconder.rejection_comments|nl2br}
                                {else}
                                    Pending
                                {/if}
                            {/if}

                        </div>
                    {if $election->isNominationsStarted}
                        </a>
                    {/if}
                    {if $election->isNominationsInProgress and $election->required_nominations >= 2 and $seconder.seconders|@count < 2 and $seconder.is_eligible_candidate != 1}
                        <div class="clearfix"></div>
                        <input type="button" value="{ts}Need Second{/ts}" onclick="window.location.href='{crmURL p="civicrm/elections/nominations/create" q="eid=`$election->id`&enid=`$seconder.id`"}'" class="election-action-button" />
                    {/if}
                    {if $election->isNominationsInProgress and $seconder.has_rejected_nomination == 0 and $seconder.has_accepted_nomination == 0 and ($seconder.seconders|@count >= 2 or $seconder.is_eligible_candidate == 1) }
                        <div class="clearfix"></div>
                        <input type="button" value="{ts}Nominate{/ts}" onclick="window.location.href='{crmURL p="civicrm/elections/nominations/create" q="eid=`$election->id`&enid=`$seconder.id`"}'" class="election-action-button" />
                    {/if}

                </div>
            {/if}

        {/foreach}
    </div>
        {if empty($candidatesCount) and $election->advertiseCandidatesStarted}
            <p>There are no eligible candidates.</p>
        {/if}
        {if empty($candidatesCount) and !$election->advertiseCandidatesStarted and !empty($nomination.nominations)}
            <p>There are no eligible nominations.</p>
        {/if}
    </div><div class="clearfix"></div>
{/foreach}
{/if}