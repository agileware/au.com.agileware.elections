<div class="crm-election-view-block">
    <p>{$election->description|nl2br}</p>

    {include file="CRM/Elections/Page/ElectionHelpText.tpl"}

    {if empty($positions)}
        <div class="messages status no-popup">
            There were no positions added in this election.
        </div>
    {/if}

    <h2>Election Results</h2>
    <center>
        <a href="{crmURL p="civicrm/elections/summary" q="eid=`$election->id`"}">Click here to check election summary</a>
    </center>

    {foreach from = $positions key = k item = position}
        <div class="crm-election-position-result-block">
            <h3>Winner{if $position.quantity > 1}s{/if} for {$position.name}</h3>
            <span class="winner-type-label">
                {include file="CRM/Elections/Page/PositionResultTypeMessage.tpl"}
             </span>
        </div>

        {if $position.result_status != $resultStatuses.results_no_nominations}
                {assign var="positionscount" value=1}
                {assign var="memberdisplayname" value="member_nominee.display_name"}
                {assign var='memberdisplayimage' value='member_nominee.image_URL'}
                <div class="election_row_style">
                {foreach from = $position.ranks key = k item = rank}
                            {foreach from = $rank item = member}
                                {if $positionscount <= $position.quantity}
                                    <div class="election-positions-rank-block">
                                        <a href="{crmURL p="civicrm/elections/candidate" q="enid=`$position.candidates.$member.id`"}">
                                            <div class="crm-election-nominated-block">
                                                {assign var='profilePicUrl' value=$position.candidates.$member.$memberdisplayimage}
                                                <img src="{$profilePicUrl}" /><br><br>
                                                {$position.candidates.$member.$memberdisplayname}<br>
                                            </div>
                                        </a>
                                    </div><!-- ending of election-positions-rank-block -->
                                {/if}
                                {assign var="positionscount" value=$positionscount+1}
                            {/foreach}
                {/foreach}
                </div>
        {/if}
    {/foreach}
</div>