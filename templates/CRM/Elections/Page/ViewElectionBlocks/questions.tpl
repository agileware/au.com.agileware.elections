{if $questions}
<section class="election-questions">
  <h2>{ts}Questions{/ts}</h2>
  {foreach from=$questions item=question}
    <div class="election-question">
      <h3>{$question.name|escape}</h3>
      {if $question.resolution_text}<p class="election-question-resolution">{$question.resolution_text|escape|nl2br}</p>{/if}
      {if $question.description}<p>{$question.description|escape|nl2br}</p>{/if}
      <p class="election-question-choices">{ts}You can choose:{/ts} {foreach from=$question.options item=option name=choices}{$option.label|escape}{if !$smarty.foreach.choices.last}, {/if}{/foreach}</p>
    </div>
  {/foreach}
</section>
{/if}
