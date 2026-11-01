{if $backToElectionUrl}{include file="CRM/Elections/Frame/BackToElection.tpl"}{/if}
{include file="CRM/Elections/Page/WelcomeMessage.tpl"}
<div class="crm-elections-helptext-block">
    <p>{ts}Write a short statement for voters, then select Accept. Select Decline if you do not want to stand. You can change your statement until voting starts.{/ts}</p>
</div>

<div class="crm-accept-nomination-form-block crm-election-form-block-container">
    {assign var='spKey' value='election_position_id.name'}

    <p>{ts 1=$electionNomination.$spKey|escape}You have been nominated for %1.{/ts}</p>

    <div class="crm-election-form-row">
        <div class="crm-election-form-col-left">
            <strong class="election-static-label">{ts}Election{/ts}</strong>
        </div>
        <div class="crm-election-form-col-right">
            {assign var='sKey' value='election_position_id.election_id.name'}
            {$electionNomination.$sKey|escape}
        </div>
    </div><!-- ending of crm-election-form-row -->

    <div class="crm-election-form-row">
        <div class="crm-election-form-col-left">
            <strong class="election-static-label">{ts}Position{/ts}</strong>
        </div>
        <div class="crm-election-form-col-right">
            {$electionNomination.$spKey|escape}
        </div>
    </div><!-- ending of crm-election-form-row -->

    <div class="crm-election-form-row">
        <div class="crm-election-form-col-left">
            {$form.nominationcomments.label}
        </div>
        <div class="crm-election-form-col-right custom_accept">
            {$form.nominationcomments.html}
        </div>
    </div><!-- ending of crm-election-form-row -->

    <div class="crm-election-form-row">
        <div class="crm-election-form-col-left">
            {$form.statement_url.label}
        </div>
        <div class="crm-election-form-col-right">
            {$form.statement_url.html}
            <div class="description">{ts}A link to a longer statement or CV.{/ts}</div>
        </div>
    </div><!-- ending of crm-election-form-row -->

    <div class="crm-election-form-row">
        <div class="crm-election-form-col-right election-answer-buttons">
            {include file="CRM/common/formButtons.tpl" location="bottom"}
            <a class="election-btn election-btn-quiet election-decline" href="{$declineUrl|escape}"><span>{ts}Decline{/ts}</span></a>
        </div>
    </div><!-- ending of crm-election-form-row -->
</div>
