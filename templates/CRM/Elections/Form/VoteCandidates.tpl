<div class="crm-election-form-block-container">
  {include file="CRM/Elections/Page/WelcomeMessage.tpl"}
  <div class="crm-elections-helptext-block">
    <p>{ts}Use this form to vote for your preferred candidates in each position.{/ts}</p>
		<p>{ts}Votes are numbered from 1 onwards, where 1 is your highest preference, followed by 2 and so on.{/ts}</p>
		<p>{ts}For positions where multiple seats are available, you only need to vote once, with your order of preferences being considered across all seats.{/ts}</p>
  </div>
  {assign var='imgKey' value='member_nominee.image_URL'}

  {foreach from=$positions item=position}
    {if $position.candidates|@count > 0}
      <div class="crm-vote-election-form-block">
          {assign var='candidateCount' value=$position.candidates|@count}
          <p><strong>{ts 1=$position.name 2=$candidateCount}Vote for %1. Please number the candidates from 1 to %2 in the order of your preferences.{/ts}</strong></p>
            {foreach from=$position.candidates item=candidate}
              {assign var="cKey" value="candidate_`$candidate.id`"}
                <div class="vote-candidate-row{if !$election->showPhotos} no-photo{/if}">
                  {if $election->showPhotos}
                  <div class="left-profile-block">
                    {assign var='profilePicUrl' value=$candidate.$imgKey}
                    <a href="{crmURL p="civicrm/elections/candidate" q="enid=`$candidate.id`"}" aria-label="{ts 1=$form.$cKey.label|strip_tags|trim}About %1{/ts}">
                        <img src="{$profilePicUrl|escape}" alt="" />
                    </a>
                  </div>
                  {/if}
                  <div class="right-info-block">
                    {$form.$cKey.label}
                    {$form.$cKey.html}
                    {if $election->showStatements}
                      {include file="CRM/Elections/Page/CandidateStatement.tpl" statementText=$candidate.comments|default:'' statementUrl=$candidate.statement_url|default:'' statementDisplay=$election->statementDisplay}
                    {/if}
                  </div>
                </div><!-- ending of vote-candidate-row --> <div class="clearfix"></div>

            {/foreach}
      </div><br>
    {/if}
  {/foreach}

  <div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

  <br>
  <p class="election-info-text"><em>{ts}If you have already voted, your existing votes will be replaced.{/ts}</em></p>
</div>
