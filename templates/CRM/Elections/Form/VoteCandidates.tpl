<div class="crm-election-form-block-container">
  {if $checksum_authenticated }
    <div class="messages status no-popup">
      <i aria-hidden="true" class="crm-i fa-info-circle"></i>
      <span class="msg-text">You are voting as {$checksum_authenticated.display_name}.</span>
    </div>
  {/if}
  <div class="crm-elections-helptext-block">
    <p>{ts}Use this form to vote for your preferred candidates in each position.{/ts}</p>
		<p>{ts}Votes are numbered from 1 onwards, where 1 is your highest preference, followed by 2 and so on.{/ts}</p>
		<p>{ts}For positions where multiple seats are available, you only need to vote once, with your order of preferences being considered across all seats.{/ts}</p>
  </div>
  {assign var='imgKey' value='member_nominee.image_URL'}

  {foreach from=$positions item=position}
    {if $position.candidates|@count > 0}
      <div class="crm-vote-election-form-block">
          <p><strong>Vote for {$position.name}. Please number of the boxes from 1 to {$position.candidates|@count} in the order of your preferences.</strong></p>
            {foreach from=$position.candidates item=candidate}
              {assign var="cKey" value="candidate_`$candidate.id`"}
                <div class="vote-candidate-row">
                  <div class="left-profile-block">
                    {assign var='profilePicUrl' value=$candidate.$imgKey}
                    <a href="{crmURL p="civicrm/elections/candidate" q="enid=`$candidate.id`"}">
                        <img src="{$profilePicUrl}" />
                    </a>
                  </div>
                  <div class="right-info-block">
                    {$form.$cKey.label}
                    {$form.$cKey.html}
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
