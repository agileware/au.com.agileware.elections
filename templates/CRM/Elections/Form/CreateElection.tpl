<div class="crm-elections-helptext-block">
    <p>{ts}Use this form to set the details and timing of the phases of your election. Fields marked * are required.{/ts}</p>
</div>

<div class="crm-block crm-form-block crm-create-election-form-block crm-election-form-block-container">
    <div class="election-form-panel" id="election-panel-details" data-title="{ts escape='htmlattribute'}Details{/ts}">
    <table class="form-layout">
        <tr class="crm-create-election-form-block-name">
            <td class="label">{$form.name.label}</td>
            <td>{$form.name.html}</td>
        </tr>
        <tr class="crm-create-election-form-block-description">
            <td class="label">{$form.description.label}</td>
            <td>{$form.description.html}</td>
        </tr>

    </table>
    </div>
    <div class="election-form-panel" id="election-panel-dates" data-title="{ts escape='htmlattribute'}Dates{/ts}">
    <p class="description election-timezone-note">{ts 1=$siteTimeZone}Times are shown in %1.{/ts}</p>
    <fieldset class="election-quick-setup">
        <legend>{ts}Quick set-up{/ts}</legend>
        <p class="description">{ts}Choose a cycle and when nominations start, and the dates below are filled in for you. You can still change any of them.{/ts}</p>
        <div class="election-quick-fields">
            <div class="election-quick-field">{$form.quick_cycle.label}<br>{$form.quick_cycle.html}</div>
            <div class="election-quick-field">{$form.quick_start.label}<br>{$form.quick_start.html}</div>
            <div class="election-quick-field"><label for="quick_start_time">{ts}Each phase starts at{/ts}</label><br><input type="time" id="quick_start_time" class="crm-form-text" value="09:00" step="300"></div>
            <div class="election-quick-field"><label for="quick_end_time">{ts}Each phase ends at{/ts}</label><br><input type="time" id="quick_end_time" class="crm-form-text" value="17:00" step="300"></div>
            <div class="election-quick-field"><label for="quick_results">{ts}Results are published{/ts}</label><br>
                <select id="quick_results" class="crm-form-select">
                    <option value="close">{ts}5 minutes after voting closes{/ts}</option>
                    <option value="morning">{ts}The morning after voting closes{/ts}</option>
                </select></div>
            <div class="election-quick-field"><label for="quick_visible_weeks">{ts}Election stays visible for (weeks after the results){/ts}</label><br><input type="number" id="quick_visible_weeks" class="crm-form-text" value="12" min="1" max="520" style="width: 5em"></div>
        </div>
        <p><button type="button" class="election-secondary-button election-quick-fill" id="election-quick-fill"><i class="crm-i fa-calendar-check-o" aria-hidden="true"></i> {ts}Fill in the dates{/ts}</button>
        <span class="election-quick-status" role="status" aria-live="polite"></span></p>
        <p class="description">{ts}Nominations and voting each run for whole weeks, ending at the end time on the last day. Candidates are advertised from 5 minutes after nominations close, and voting opens at the start time the next day. The election is visible from when nominations start.{/ts}</p>
    </fieldset>
    <table class="form-layout">
        <tr>
            <td colspan="2">
                <h2 class="election-form-group-heading">{ts}Visibility{/ts}</h2>
            </td>
        </tr>
        <tr class="crm-create-election-form-block-visibility">
            <td class="label">{$form.visibility_start_date.label}</td>
            <td>{$form.visibility_start_date.html}<br>
                <span class="description">{ts}People can see the election from this date.{/ts}</span></td>
        </tr>
        <tr class="crm-create-election-form-block-visibility">
            <td class="label">{$form.visibility_end_date.label}</td>
            <td>{$form.visibility_end_date.html}<br>
                <span class="description">{ts}After this date the election is hidden from people.{/ts}</span></td>
        </tr>


        <tr>
            <td colspan="2">
                <h2 class="election-form-group-heading">{ts}Nominations{/ts}</h2>
            </td>
        </tr>
        <tr class="crm-create-election-form-block-nominations">
            <td class="label">{$form.nomination_start_date.label}</td>
            <td>{$form.nomination_start_date.html}<br>
                <span class="description">{ts}From this date people can nominate each other.{/ts}</span></td>
        </tr>
        <tr class="crm-create-election-form-block-nominations">
            <td class="label">{$form.nomination_end_date.label}</td>
            <td>{$form.nomination_end_date.html}<br>
                <span class="description">{ts}People can nominate until this date.{/ts}</span></td>
        </tr>

        <tr>
            <td colspan="2">
                <h2 class="election-form-group-heading">{ts}Candidates{/ts}</h2>
            </td>
        </tr>
        <tr class="crm-create-election-form-block-nominations">
            <td class="label">{$form.advertise_candidates_date.label}</td>
            <td>{$form.advertise_candidates_date.html}<br>
                <span class="description">{ts}From this date people can see the candidates, before voting opens.{/ts}</span></td>
        </tr>

        <tr>
            <td colspan="2">
                <h2 class="election-form-group-heading">{ts}Voting{/ts}</h2>
            </td>
        </tr>
        <tr class="crm-create-election-form-block-voting">
            <td class="label">{$form.voting_start_date.label}</td>
            <td>{$form.voting_start_date.html}<br>
                <span class="description">{ts}Voters can vote from this date.{/ts}</span></td>
        </tr>
        <tr class="crm-create-election-form-block-voting">
            <td class="label">{$form.voting_end_date.label}</td>
            <td>{$form.voting_end_date.html}<br>
                <span class="description">{ts}Voters can vote until this date.{/ts}</span></td>
        </tr>

        <tr>
            <td colspan="2">
                <h2 class="election-form-group-heading">{ts}Results{/ts}</h2>
            </td>
        </tr>
        <tr class="crm-create-election-form-block-results">
            <td class="label">{$form.result_date.label}</td>
            <td>{$form.result_date.html}<br>
                <span class="description">{ts}From this date people can see the results.{/ts}</span></td>
        </tr>

        <tr class="election-timeline-row">
            <td colspan="2">
                <h2 class="election-form-group-heading">{ts}Timeline{/ts}</h2>
                <p class="description">{ts}How the dates above line up. Problems are marked as you change a date.{/ts}</p>
                <ol class="election-timeline-preview" aria-live="polite"></ol>
            </td>
        </tr>
    </table>
    </div>
    <div class="election-form-panel" id="election-panel-participation" data-title="{ts escape='htmlattribute'}Participation{/ts}">
    {if $groupsHidden}
    <div class="messages warning no-popup election-groups-hidden" role="alert">
        <strong>{ts}You cannot see any groups.{/ts}</strong>
        {ts}CiviCRM only shows groups to people whose role has the permission View All Contacts (or an ACL that allows the groups to be viewed), so the group fields below stay empty. Ask a site administrator to add that permission to your role, then reload this page. See Roles and permissions in the Elections guide.{/ts}
    </div>
    {/if}
    <table class="form-layout">

        <tr class="crm-create-election-form-block-results">
            <td class="label label-select-col">{$form.allowed_groups.label}</td>
            <td>
                {$form.allowed_groups.html}<br>
                <span class="description">{ts}People in these groups may vote. They may also nominate and stand, unless you choose other groups below.{/ts}</span>
            </td>
        </tr>

        <tr class="crm-create-election-form-block-results">
            <td class="label label-select-col">{$form.nomination_groups.label}</td>
            <td>
                {$form.nomination_groups.html}<br>
                <span class="description">{ts}Optional. People in these groups may nominate and second. Leave empty to use the voting groups.{/ts}</span>
            </td>
        </tr>

        <tr class="crm-create-election-form-block-results">
            <td class="label label-select-col">{$form.candidate_groups.label}</td>
            <td>
                {$form.candidate_groups.html}<br>
                <span class="description">{ts}Optional. People in these groups may be nominated and stand as candidates. Leave empty to use the voting groups.{/ts}</span>
            </td>
        </tr>

        <tr class="crm-create-election-form-block-results">
            <td class="label label-select-col">{$form.voting_method.label}</td>
            <td>
                {$form.voting_method.html}<br>
                <span class="description">{ts}Ranked choice: voters give each candidate a rank. Most votes win: voters tick candidates. It cannot be changed once people have voted.{/ts}</span>
            </td>
        </tr>

        <tr class="crm-create-election-form-block-results">
            <td class="label label-select-col">{$form.required_nominations.label}</td>
            <td>
                {$form.required_nominations.html}<br>
                <span class="description">{ts}The number of nominations a nominee needs to stand as a candidate.{/ts}</span>
            </td>
        </tr>

        <tr class="crm-create-election-form-block-results">
            <td class="label label-select-col">{$form.allow_online_nominations.label}</td>
            <td>
                {$form.allow_online_nominations.html}<br>
                <span class="description">{ts}When this is off there is no nomination form, and you add the candidates yourself on the Candidates screen.{/ts}</span>
            </td>
        </tr>

        <tr class="crm-create-election-form-block-results">
            <td class="label label-select-col">{$form.allow_revote.label}</td>
            <td>
                {$form.allow_revote.html}<br>
                <span class="description">{ts}People can change their vote until voting closes.{/ts}</span>
            </td>
        </tr>

        <tr class="crm-create-election-form-block-results">
            <td class="label label-select-col">{$form.allow_checksum_access.label}</td>
            <td>
                {$form.allow_checksum_access.html}<br>
                <span class="description">{ts}People can take part by using their personal link, without logging in.{/ts}</span>
            </td>
        </tr>


    </table>
    </div>
    <div class="election-form-panel" id="election-panel-display" data-title="{ts escape='htmlattribute'}Display{/ts}">
    <table class="form-layout">
        <tr>
            <td colspan="2">
                <p class="description">{ts}Choose what voters see about candidates in this election.{/ts}</p>
            </td>
        </tr>
        <tr class="crm-create-election-form-block-display">
            <td class="label">{$form.show_photos.label}</td>
            <td>
                {$form.show_photos.html}<br>
                <span class="description">{ts}Untick to hide candidate photos everywhere in this election.{/ts}</span>
            </td>
        </tr>
        <tr class="crm-create-election-form-block-display">
            <td class="label">{$form.show_statements.label}</td>
            <td>
                {$form.show_statements.html}<br>
                <span class="description">{ts}A candidate's statement is always on their own page. Choose whether it is also shown on the ballot.{/ts}</span>
            </td>
        </tr>
        <tr class="crm-create-election-form-block-display">
            <td class="label">{$form.show_nominators.label}</td>
            <td>
                {$form.show_nominators.html}<br>
                <span class="description">{ts}Untick to hide who nominated and seconded each candidate.{/ts}</span>
            </td>
        </tr>
        <tr class="crm-create-election-form-block-display">
            <td class="label label-select-col">{$form.name_format.label}</td>
            <td>
                {$form.name_format.html}<br>
                <span class="description">{ts}How candidate names are shown. Nicknames are only used for people who have one.{/ts}</span>
            </td>
        </tr>
        {include file="CRM/Elections/Form/ResultsDisplayFields.tpl" groupHeading=1}

    </table>
    </div>
    <div class="election-form-panel" id="election-panel-emails" data-title="{ts escape='htmlattribute'}Emails{/ts}">
    <table class="form-layout">
        <tr class="crm-create-election-form-block-results">
            <td class="label label-select-col">{$form.send_emails.label}</td>
            <td>
                {$form.send_emails.html}<br>
                <span class="description">{ts}Email a nominee when they have enough nominations to stand, asking them to accept or decline.{/ts}</span>
            </td>
        </tr>

        <tr class="crm-create-election-form-block-results election-nomination-email-row">
            <td class="label label-select-col">{$form.nomination_message_template_id.label}</td>
            <td>
                {$form.nomination_message_template_id.html}<br>
                <span class="description">{ts}The message used for the email. The default is &quot;Election - Invite nominee&quot;.{/ts}
                {if $canEditMessageTemplates}<a href="{$messageTemplatesUrl}" target="_blank" rel="noopener">{ts}Edit message templates (opens in a new tab){/ts}</a>{/if}</span><br>
                <span class="description">{ts}A template can use these variables: {ldelim}$nomineeName{rdelim}, {ldelim}$positionName{rdelim}, {ldelim}$electionName{rdelim}, {ldelim}$votingOpens{rdelim}, {ldelim}$votingCloses{rdelim}, {ldelim}$acceptUrl{rdelim} and {ldelim}$viewUrl{rdelim}.{/ts}</span>
            </td>
        </tr>
    </table>
    </div>
    <div class="election-form-panel" id="election-panel-privacy" data-title="{ts escape='htmlattribute'}Privacy{/ts}">
    <table class="form-layout">
        <tr class="crm-create-election-form-block-results">
            <td class="label label-select-col">{$form.anonymize_votes.label}</td>
            <td>
                {$form.anonymize_votes.html}<br>
                <span class="description">{ts}When the results are generated, nobody can tell who cast which vote.{/ts}</span>
            </td>
        </tr>
    </table>
    </div>
    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    {if !$isPopup}{include file="CRM/Elections/Frame/BackButtons.tpl"}{/if}
    </div>
</div>
