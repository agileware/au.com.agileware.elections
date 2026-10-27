<div class="crm-elections-helptext-block">
    <p>{ts}Use this form to configure the basic information and timing for the various phases of your Election.{/ts}</p>
</div>

<div class="crm-block crm-form-block crm-create-election-form-block crm-election-form-block-container">
    <div class="election-form-panel" id="election-panel-election" data-title="{ts escape='htmlattribute'}Election{/ts}">
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
    <table class="form-layout">
        <tr>
            <td colspan="2">
                <h3>{ts}Visibility{/ts}</h3>
				<p class="description">{ts}Set the range of dates when this election can be seen by voters.{/ts}</p>
            </td>
        </tr>
        <tr class="crm-create-election-form-block-visibility">
            <td class="label">{$form.visibility_start_date.label}</td>
            <td>{$form.visibility_start_date.html}</td>
        </tr>
        <tr class="crm-create-election-form-block-visibility">
            <td class="label">{$form.visibility_end_date.label}</td>
            <td>{$form.visibility_end_date.html}</td>
        </tr>


        <tr>
            <td colspan="2">
                <h3>{ts}Nominations{/ts}</h3>
				<p class="description">{ts}Set the range of dates when voters can submit nominations.{/ts}</p>
            </td>
        </tr>
        <tr class="crm-create-election-form-block-nominations">
            <td class="label">{$form.nomination_start_date.label}</td>
            <td>{$form.nomination_start_date.html}</td>
        </tr>
        <tr class="crm-create-election-form-block-nominations">
            <td class="label">{$form.nomination_end_date.label}</td>
            <td>{$form.nomination_end_date.html}</td>
        </tr>

        <tr>
            <td colspan="2">
                <h3>{ts}Advertise Candidates{/ts}</h3>
				<p class="description">{ts}Set the date from which candidates are advertised to voters before voting begins.{/ts}</p>
            </td>
        </tr>
        <tr class="crm-create-election-form-block-nominations">
            <td class="label">{$form.advertise_candidates_date.label}</td>
            <td>{$form.advertise_candidates_date.html}</td>
        </tr>

        <tr>
            <td colspan="2">
                <h3>{ts}Voting{/ts}</h3>
				<p class="description">{ts}Set the range of dates when voters can submit their votes.{/ts}</p>
            </td>
        </tr>
        <tr class="crm-create-election-form-block-voting">
            <td class="label">{$form.voting_start_date.label}</td>
            <td>{$form.voting_start_date.html}</td>
        </tr>
        <tr class="crm-create-election-form-block-voting">
            <td class="label">{$form.voting_end_date.label}</td>
            <td>{$form.voting_end_date.html}</td>
        </tr>

        <tr>
            <td colspan="2">
                <h3>{ts}Results{/ts}</h3>
				<p class="description">{ts}Set the date from which the final results are displayed to voters.{/ts}</p>
            </td>
        </tr>
        <tr class="crm-create-election-form-block-results">
            <td class="label">{$form.result_date.label}</td>
            <td>{$form.result_date.html}</td>
        </tr>

        <tr class="election-timeline-row">
            <td colspan="2">
                <h3>{ts}Timeline{/ts}</h3>
                <p class="description">{ts 1=$siteTimeZone}How the dates above line up (times are in %1). Problems are marked as you change a date.{/ts}</p>
                <ol class="election-timeline-preview" aria-live="polite"></ol>
            </td>
        </tr>
    </table>
    </div>
    <div class="election-form-panel" id="election-panel-participation" data-title="{ts escape='htmlattribute'}Participation{/ts}">
    <table class="form-layout">

        <tr class="crm-create-election-form-block-results">
            <td class="label label-select-col">{$form.required_nominations.label}</td>
            <td>
                {$form.required_nominations.html}<br>
                <span class="description">{ts}Enter the number of nominations required for a person to become eligible candidate in this election.{/ts}</span>
            </td>
        </tr>

        <tr class="crm-create-election-form-block-results">
            <td class="label label-select-col">{$form.allowed_groups.label}</td>
            <td>
                {$form.allowed_groups.html}<br>
                <span class="description">{ts}Select the groups whose members are allowed to vote & nominate in this election.{/ts}</span>
            </td>
        </tr>

        <tr class="crm-create-election-form-block-results">
            <td class="label label-select-col">{$form.allow_online_nominations.label}</td>
            <td>
                {$form.allow_online_nominations.html}<br>
                <span class="description">{ts}On: people in the nomination groups nominate and second on the website during the nomination period. Off: there is no nomination form and the organisers choose the candidates themselves (Candidates screen). Candidates added that way are asked to accept, and the rest of the election works as usual.{/ts}</span>
            </td>
        </tr>

        <tr class="crm-create-election-form-block-results">
            <td class="label label-select-col">{$form.send_emails.label}</td>
            <td>
                {$form.send_emails.html}<br>
                <span class="description">{ts}Email a nominee when they have enough nominations to stand, asking them to accept or withdraw. The emails come from the message templates called &quot;Elections&quot; (Administer, Communications, Message Templates), which you can edit.{/ts}</span>
            </td>
        </tr>

        <tr class="crm-create-election-form-block-results">
            <td class="label label-select-col">{$form.nomination_groups.label}</td>
            <td>
                {$form.nomination_groups.html}<br>
                <span class="description">{ts}Optional. Members of these groups may nominate and second. Leave empty to use the voting groups. Group membership, including smart groups, is checked at the moment someone acts.{/ts}</span>
            </td>
        </tr>

        <tr class="crm-create-election-form-block-results">
            <td class="label label-select-col">{$form.candidate_groups.label}</td>
            <td>
                {$form.candidate_groups.html}<br>
                <span class="description">{ts}Optional. Members of these groups may be nominated and stand as candidates. Leave empty to use the voting groups. People who already voted keep their vote whatever happens to their membership later.{/ts}</span>
            </td>
        </tr>

        <tr class="crm-create-election-form-block-results">
            <td class="label label-select-col">{$form.voting_method.label}</td>
            <td>
                {$form.voting_method.html}<br>
                <span class="description">{ts}Ranked choice: voters number the candidates in order of preference and the count passes votes on until someone has a majority. Most votes win: voters tick up to as many candidates as there are seats and the candidates with the most votes win. This cannot be changed once people have voted.{/ts}</span>
            </td>
        </tr>

        <tr class="crm-create-election-form-block-results">
            <td class="label label-select-col">{$form.allow_revote.label}</td>
            <td>
                {$form.allow_revote.html}<br>
                <span class="description">{ts}Select &quot;Yes&quot; to allow members to change their vote right up until the close of voting in this election.{/ts}</span>
            </td>
        </tr>

        <tr class="crm-create-election-form-block-results">
            <td class="label label-select-col">{$form.anonymize_votes.label}</td>
            <td>
                {$form.anonymize_votes.html}<br>
                <span class="description">{ts}Select &quot;Yes&quot; to anonymise votes when results are generated for this election.{/ts}</span>
            </td>
        </tr>

        <tr class="crm-create-election-form-block-results">
            <td class="label label-select-col">{$form.allow_checksum_access.label}</td>
            <td>
                {$form.allow_checksum_access.html}<br>
                <span class="description">{ts}Allow Members to access Election pages and perform actions when not-logged in. Requires the user to have a valid checksum URL.{/ts}</span>
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
                <span class="description">{ts}Untick to hide candidate photos everywhere in this election (nominations, ballot, candidate pages and results).{/ts}</span>
            </td>
        </tr>
        <tr class="crm-create-election-form-block-display">
            <td class="label">{$form.show_statements.label}</td>
            <td>
                {$form.show_statements.html}<br>
                <span class="description">{ts}Candidates write a statement when they accept a nomination. It is always shown on the candidate's own page; choose whether it is also shown on the ballot and, once voting starts, in the candidate list.{/ts}</span>
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
                <span class="description">{ts}How candidate names are shown. Nicknames are only used for contacts that have one.{/ts}</span>
            </td>
        </tr>

    </table>
    </div>
    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
</div>
