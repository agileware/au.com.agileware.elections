{include file="CRM/Elections/Page/WelcomeMessage.tpl"}
<div class="crm-elections-helptext-block">
    <p>{ts}Write a short statement for voters: your experience, what you want to achieve, and optionally a link to a longer statement or CV.{/ts}</p>
</div>

<div class="crm-edit-statement-form-block crm-election-form-block-container">
    <div class="crm-election-form-row">
        <div class="crm-election-form-col-left">
            <strong class="election-static-label">{ts}Position{/ts}</strong>
        </div>
        <div class="crm-election-form-col-right">
            {assign var='spKey' value='election_position_id.name'}
            {$electionNomination.$spKey|escape}
        </div>
    </div>

    <div class="crm-election-form-row">
        <div class="crm-election-form-col-left">
            {$form.comments.label}
        </div>
        <div class="crm-election-form-col-right">
            {$form.comments.html}
        </div>
    </div>

    <div class="crm-election-form-row">
        <div class="crm-election-form-col-left">
            {$form.statement_url.label}
        </div>
        <div class="crm-election-form-col-right">
            {$form.statement_url.html}
            <div class="description">{ts}A link to a longer statement or CV.{/ts}</div>
        </div>
    </div>

    <div class="crm-election-form-row">
        <div class="crm-election-form-col-right crm-submit-buttons">
            {include file="CRM/common/formButtons.tpl" location="bottom"}
            {if $isAdminFrame}
                {include file="CRM/Elections/Frame/BackButtons.tpl"}
            {elseif $backToElectionUrl}
                {include file="CRM/Elections/Frame/BackToElection.tpl"}
            {/if}
        </div>
    </div>
</div>
