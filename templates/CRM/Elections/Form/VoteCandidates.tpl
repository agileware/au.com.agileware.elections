<div class="crm-election-form-block-container">
  {include file="CRM/Elections/Page/WelcomeMessage.tpl"}
  <div class="crm-elections-helptext-block">
    {foreach from=$instructions item=line}<p>{$line|escape}</p>{/foreach}
  </div>
  {assign var='imgKey' value='member_nominee.image_URL'}

  {foreach from=$positions item=position}
    {if $position.candidates|@count > 0}
      <div class="crm-vote-election-form-block" data-position="{$position.name|escape}" data-seats="{$position.seats|escape}">
          <p><strong>{$position.prompt|escape}</strong></p>
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
