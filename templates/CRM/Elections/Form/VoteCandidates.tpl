<div class="crm-election-form-block-container">
  {include file="CRM/Elections/Page/WelcomeMessage.tpl"}
  <div class="crm-elections-helptext-block">
    {foreach from=$instructions item=line}<p>{$line|escape}</p>{/foreach}
  </div>
  {assign var='imgKey' value='member_nominee.image_URL'}

  {foreach from=$positions item=position}
    {if $position.isQuestion|default:false}
      {assign var="qKey" value=$position.questionKey}
      <div class="crm-vote-election-form-block election-question-block" data-type="question" data-position="{$position.name|escape}">
        <fieldset class="election-question-fieldset">
          <legend>{$position.name|escape}</legend>
          {if $position.resolution_text}<p class="election-question-resolution">{$position.resolution_text|escape|nl2br}</p>{/if}
          {if $position.description}<p>{$position.description|escape|nl2br}</p>{/if}
          <div class="election-question-choices">{$form.$qKey.html}</div>
        </fieldset>
      </div><br>
    {elseif $position.candidates|@count > 0 or $position.writeInPlaces > 0}
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
            {if $position.writeInPlaces > 0}
              <fieldset class="election-writein" data-places="{$position.writeInPlaces|escape}">
                <legend>{ts}Write in a candidate{/ts}</legend>
                <p class="election-writein-note">{if $votingMethod == 'plurality'}{ts}Know someone who is not listed? Type a name to write them in. They are told straight away and must accept before voting closes. Your vote for them counts only if they accept; if they do not, it is dropped.{/ts}{else}{ts}Know someone who is not listed? Type a name and give them a rank to write them in. They are told straight away and must accept before voting closes. Your vote for them counts only if they accept; if they do not, your ballot moves to your next choice.{/ts}{/if}</p>
                {foreach from=$position.writeInRows item=row}
                  {assign var="pKey" value=$row.person}
                  {assign var="rKey" value=$row.rank}
                  <div class="election-writein-row" data-row="{$row.number|escape}">
                    <div class="election-writein-person">{$form.$pKey.label}{$form.$pKey.html}</div>
                    {if $votingMethod != 'plurality'}<div class="election-writein-rank">{$form.$rKey.label}{$form.$rKey.html}</div>{/if}
                  </div>
                {/foreach}
                {if $position.writeInPlaces > 1}<button type="button" class="election-writein-add election-btn election-btn-quiet" hidden>{ts}Add another write-in{/ts}</button>{/if}
              </fieldset>
            {/if}
      </div><br>
    {/if}
  {/foreach}

  <div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

  <br>
  <p class="election-info-text"><em>{ts}If you have already voted, your existing votes will be replaced.{/ts}</em></p>
</div>
