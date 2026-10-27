<div class="crm-election-view-block">
    {include file="CRM/Elections/Page/WelcomeMessage.tpl"}

    <p>{$election->description|escape|nl2br}</p>

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
      {if $position.position_type|default:'election' == 'referendum'}
        <div class="crm-election-position-result-block election-result-position election-result-question">
            <h3>{$position.name|escape}</h3>
            {if $position.resolution_text}<p class="election-question-resolution">{$position.resolution_text|escape|nl2br}</p>{/if}
            <p class="election-question-outcome election-outcome-{$position.result_status|escape}">
                {if $position.result_status == $resultStatuses.results_passed}<span class="election-winner-badge">{ts}Passed{/ts}</span>
                {elseif $position.result_status == $resultStatuses.results_no_quorum}<span class="election-winner-badge election-winner-badge-tie">{ts}No quorum{/ts}</span>
                {else}<span class="election-winner-badge election-winner-badge-fail">{ts}Not passed{/ts}</span>{/if}
                {include file="CRM/Elections/Page/PositionResultTypeMessage.tpl"}
            </p>
        </div>
      {else}
        <div class="crm-election-position-result-block election-result-position">
            <h3>{if $position.quantity > 1}{ts 1=$position.name}Winners for %1{/ts}{else}{ts 1=$position.name}Winner for %1{/ts}{/if}</h3>
            <span class="winner-type-label">
                {include file="CRM/Elections/Page/PositionResultTypeMessage.tpl"}
             </span>
        </div>

        {if $position.result_status != $resultStatuses.results_no_nominations}
                {assign var="memberdisplayname" value="member_nominee.display_name"}
                {assign var='memberdisplayimage' value='member_nominee.image_URL'}
                <div class="election_row_style">
                {assign var="filled" value=0}
                {foreach from = $position.ranks key = k item = rank}
                    {if $filled < $position.quantity}
                        {assign var="groupTied" value=($filled + $rank|@count > $position.quantity)}
                        {foreach from = $rank item = member}
                            <div class="election-positions-rank-block">
                                <a href="{crmURL p="civicrm/elections/candidate" q="`$checksum_query['query']`&enid=`$position.candidates.$member.id`"}">
                                    <div class="crm-election-nominated-block">
                                        {assign var='profilePicUrl' value=$position.candidates.$member.$memberdisplayimage}
                                        {if $election->showPhotos}<img src="{$profilePicUrl|escape}" alt="" />{/if}
                                        {$position.candidates.$member.$memberdisplayname|escape}
                                        {if $groupTied}<span class="election-winner-badge election-winner-badge-tie">{ts}Tied{/ts}</span>{else}<span class="election-winner-badge">{ts}Winner{/ts}</span>{/if}
                                    </div>
                                </a>
                            </div><!-- ending of election-positions-rank-block -->
                        {/foreach}
                        {assign var="filled" value=$filled+$rank|@count}
                    {/if}
                {/foreach}
                </div>
        {/if}
      {/if}
    {/foreach}
</div>
