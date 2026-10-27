{include file="CRM/Elections/Page/WelcomeMessage.tpl"}

<p>{$election->description|escape|nl2br}</p>

<div class="crm-elections-helptext-block">
    <p>{ts}The votes have been counted for this election.  A breakdown of the nominations and results for each position is shown below.{/ts}</p>
</div>

{capture assign=resultDate}<span class="crm-election-date">{$election->result_date|crmDate} <a target="_blank" href="{$siteTimeZoneConvertUrl}">({$siteTimeZone})</a></span>{/capture}
<p>{ts 1=$resultDate}Results published on %1{/ts}</p>

{foreach from=$positions item=position}
    <h2>{$position.name|escape}</h2>
    {if $position.resolution_text|default:''}<p class="election-question-resolution">{$position.resolution_text|escape|nl2br}</p>{/if}
    <p>{include file="CRM/Elections/Page/PositionResultTypeMessage.tpl"}</p>

    {if $position.position_type|default:'election' != 'referendum' and $position.result_status != $resultStatuses.results_no_nominations}

        {assign var="memberdisplayname" value="member_nominee.display_name"}

        <div class="election-position-summary-row">

            <div class="election-position-summary-block">
                <h3>{ts}Elected{/ts}</h3>
                <ul>
                    {foreach from=$position.candidates item=candidate}
                        {if $candidate.is_winner|default:false}
                            <li>{$candidate.$memberdisplayname|escape}</li>
                        {elseif $candidate.is_tied|default:false}
                            <li>{$candidate.$memberdisplayname|escape} {ts}(tied){/ts}</li>
                        {/if}
                    {/foreach}
                </ul>
            </div><!-- ending of election-position-summary-block -->

            <div class="election-position-summary-block">
                <h3>{ts}Candidates{/ts}</h3>
                <ul>
                    {foreach from=$position.candidates item=candidate}
                        <li>{$candidate.$memberdisplayname|escape}</li>
                    {/foreach}
                </ul>
            </div><!-- ending of election-position-summary-block -->

            <div class="election-position-summary-block">
                <h3>{ts}Nominations{/ts}</h3>
                <ul>
                    {foreach from=$position.nominations item=nomination}
                        <li>{$nomination.$memberdisplayname|escape}</li>
                    {/foreach}
                </ul>
            </div><!-- ending of election-position-summary-block -->
        </div><!-- ending of election-position-summary-row --><div class="clearfix"></div>

    {/if}

{/foreach}
