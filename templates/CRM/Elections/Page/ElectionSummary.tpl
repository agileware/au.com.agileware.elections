<p>{$election->description|nl2br}</p>

<div class="crm-elections-helptext-block">
    <p>{ts}The votes have been counted for this election.  A breakdown of the nominations and results for each position is shown below.{/ts}</p>
</div>

<p>{ts}Results published on{/ts} <span class="crm-election-date">{$election->result_date|crmDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span></p>

{foreach from=$positions item=position}
    <h2>{$position.name}</h2>
    <p>{include file="CRM/Elections/Page/PositionResultTypeMessage.tpl"}</p>

    {if $position.result_status != $resultStatuses.results_no_nominations}

        {assign var="memberdisplayname" value="member_nominee.display_name"}

        <div class="election-position-summary-row">

            <div class="election-position-summary-block">
                <h3>Elected</h3>
                <ul>
                    {foreach from=$position.candidates item=candidate}
                        {if $candidate.is_winner}
                            <li>{$candidate.$memberdisplayname}</li>
                        {/if}
                    {/foreach}
                </ul>
            </div><!-- ending of election-position-summary-block -->

            <div class="election-position-summary-block">
                <h3>Candidates</h3>
                <ul>
                    {foreach from=$position.candidates item=candidate}
                        <li>{$candidate.$memberdisplayname}</li>
                    {/foreach}
                </ul>
            </div><!-- ending of election-position-summary-block -->

            <div class="election-position-summary-block">
                <h3>Nominations</h3>
                <ul>
                    {foreach from=$position.nominations item=nomination}
                        <li>{$nomination.$memberdisplayname}</li>
                    {/foreach}
                </ul>
            </div><!-- ending of election-position-summary-block -->
        </div><!-- ending of election-position-summary-row --><div class="clearfix"></div>

    {/if}

{/foreach}
