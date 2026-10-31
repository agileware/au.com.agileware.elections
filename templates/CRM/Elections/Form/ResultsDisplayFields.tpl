        <tr>
            <td colspan="2">
                <h3>{ts}Results page{/ts}</h3>
                <p class="description">{ts}A logo, a header and a footer for the page that shows the results.{/ts}</p>
            </td>
        </tr>
        <tr class="crm-election-results-header">
            <td class="label">{$form.results_header_text.label}</td>
            <td>
                {$form.results_header_text.html}<br>
                <span class="description">{ts}Plain text shown above the results, for example &quot;Congratulations to our new committee&quot;. Up to 200 characters.{/ts}</span>
            </td>
        </tr>
        <tr class="crm-election-results-logo">
            <td class="label">{$form.results_logo.label}</td>
            <td>
                {if $resultsLogo.has_logo|default:false}
                    <img class="election-results-logo-current" src="{$resultsLogo.logo_url|escape}" alt="{$resultsLogo.logo_alt|escape}"><br>
                    {$form.results_remove_logo.html} {$form.results_remove_logo.label}<br>
                {/if}
                {$form.results_logo.html}<br>
                <span class="description">{ts}A PNG, JPEG, GIF or WebP picture up to 2 MB and 4000 by 4000 pixels. It is shown above the results, up to 120 pixels high. A new picture replaces the current one.{/ts}</span>
            </td>
        </tr>
        <tr class="crm-election-results-logo-alt">
            <td class="label">{$form.results_logo_alt.label}</td>
            <td>
                {$form.results_logo_alt.html}<br>
                <span class="description">{ts}What the logo shows, for people who cannot see it. Needed when there is a logo.{/ts}</span>
            </td>
        </tr>
        <tr class="crm-election-results-footer">
            <td class="label">{$form.results_footer_text.label}</td>
            <td>
                {$form.results_footer_text.html}<br>
                <span class="description">{ts}Plain text shown below the results, for example thanks or what happens next. Up to 1000 characters.{/ts}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <h3>{ts}Celebration{/ts}</h3>
            </td>
        </tr>
        <tr class="crm-election-celebration">
            <td class="label">{$form.celebration_enabled.label}</td>
            <td>
                {$form.celebration_enabled.html}<br>
                <span class="description">{ts}Shows a short celebration, such as confetti or fireworks, over the results page each time it is opened for a few days after the results are published. Nothing is shown to people whose device asks for reduced motion.{/ts}</span>
            </td>
        </tr>
        <tr class="crm-election-celebration-effect election-celebration-option">
            <td class="label">{$form.celebration_effect.label}</td>
            <td>
                {$form.celebration_effect.html}
                <button type="button" class="crm-button election-celebration-preview" id="election-celebration-preview"><i class="crm-i fa-play" aria-hidden="true"></i> {ts}Preview{/ts}</button>
            </td>
        </tr>
        <tr class="crm-election-celebration-days election-celebration-option">
            <td class="label">{$form.celebration_days.label}</td>
            <td>
                {$form.celebration_days.html}<br>
                <span class="description">{ts}For how many days after the results are published the celebration is shown, from 1 to 30. After that the page is shown without it.{/ts}</span>
            </td>
        </tr>
