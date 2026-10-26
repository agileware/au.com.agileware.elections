<div class="crm-election-view-block">
    {include file="CRM/Elections/Page/WelcomeMessage.tpl"}

    <p>{$election->description|nl2br}</p>

    {include file="CRM/Elections/Page/ElectionHelpText.tpl"}

    {if empty($positions)}
        <div class="messages status no-popup">
            {ts}There were no positions added in this election.{/ts}
        </div>
    {/if}

    <h2>{ts}Election Results{/ts}</h2>
    <center>
        <a href="{crmURL p="civicrm/elections/summary" q="`$checksum_query['query']`"}">{ts}View the Election summary{/ts}</a>
    </center>

    {foreach from = $positions key = k item = position}
        <div class="crm-election-position-result-block">
            <h3>{if $position.quantity > 1}{ts 1=$position.name}Winners for %1{/ts}{else}{ts 1=$position.name}Winner for %1{/ts}{/if}</h3>
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
                                        <a href="{crmURL p="civicrm/elections/candidate" q="`$checksum_query['query']`&enid=`$position.candidates.$member.id`"}">
                                            <div class="crm-election-nominated-block">
                                                {assign var='profilePicUrl' value=$position.candidates.$member.$memberdisplayimage}
                                                {if $election->showPhotos}<img src="{$profilePicUrl}" alt="" /><br><br>{/if}
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
