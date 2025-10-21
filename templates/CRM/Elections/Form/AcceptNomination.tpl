{if $checksum_authenticated }
    <div class="messages status no-popup">
        <i aria-hidden="true" class="crm-i fa-info-circle"></i>
        <span class="msg-text">You are acting as {$checksum_authenticated.display_name}.</span>
    </div>
{/if}
<div class="crm-elections-helptext-block">
    <p>{ts}To accept your candidacy, add any comments and press the "Accept" button.{/ts}</p>
</div>

<div class="crm-accept-nomination-form-block crm-election-form-block-container">
    {assign var='spKey' value='election_position_id.name'}

    <p>You're eligible as a candidate for {$electionNomination.$spKey}</p>

    <div class="crm-election-form-row">
        <div class="crm-election-form-col-left">
            <label>Election</label>
        </div>
        <div class="crm-election-form-col-right">
            {assign var='sKey' value='election_position_id.election_id.name'}
            {$electionNomination.$sKey}
        </div>
    </div><!-- ending of crm-election-form-row -->

    <div class="crm-election-form-row">
        <div class="crm-election-form-col-left">
            <label>Position</label>
        </div>
        <div class="crm-election-form-col-right">
            {$electionNomination.$spKey}
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
        <div class="crm-election-form-col-right">
            {include file="CRM/common/formButtons.tpl" location="bottom"}
        </div>
    </div><!-- ending of crm-election-form-row -->
</div>
