<div class="crm-elections-helptext-block">
    <p>{ts}Use this form to configure the basic information and timing for the various phases of your Election.{/ts}</p>
</div>

<div class="crm-block crm-form-block crm-create-election-form-block crm-election-form-block-container">
    <table class="form-layout">
        <tr>
            <td colspan="2">
                <h2>{ts}Election Information{/ts}</h2>
            </td>
        </tr>
        <tr class="crm-create-election-form-block-name">
            <td class="label">{$form.name.label}</td>
            <td>{$form.name.html}</td>
        </tr>
        <tr class="crm-create-election-form-block-description">
            <td class="label">{$form.description.label}</td>
            <td>{$form.description.html}</td>
        </tr>
        <tr>
            <td colspan="2">
                <h2>{ts}Visibility{/ts}</h2>
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
                <h2>{ts}Nominations{/ts}</h2>
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
                <h2>{ts}Advertise Candidates{/ts}</h2>
				<p class="description">{ts}Set the date from which candidates are advertised to voters before voting begins.{/ts}</p>
            </td>
        </tr>
        <tr class="crm-create-election-form-block-nominations">
            <td class="label">{$form.advertise_candidates_date.label}</td>
            <td>{$form.advertise_candidates_date.html}</td>
        </tr>
        <tr>
            <td colspan="2">
                <h2>{ts}Voting{/ts}</h2>
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
                <h2>{ts}Results{/ts}</h2>
				<p class="description">{ts}Set the date from which the final results are displayed to voters.{/ts}</p>
            </td>
        </tr>
        <tr class="crm-create-election-form-block-results">
            <td class="label">{$form.result_date.label}</td>
            <td>{$form.result_date.html}</td>
        </tr>
        <tr>
            <td colspan="2">
                <h2>{ts}Settings{/ts}</h2>
            </td>
        </tr>
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
        <tr class="crm-create-election-form-block-buttons">
            <td colspan="2">
                <div class="crm-submit-buttons">
                    {include file="CRM/common/formButtons.tpl" location="bottom"}
                </div>
            </td>
        </tr>
    </table>
</div>
