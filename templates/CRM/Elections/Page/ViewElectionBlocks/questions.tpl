{if $questions}
<section class="election-questions">
  <h2>{ts}Questions{/ts}</h2>
  {foreach from=$questions item=question}
    <div class="election-question">
      <h3>{$question.name|escape}</h3>
      {if $question.resolution_text|default:''}<p class="election-question-resolution">{$question.resolution_text|default:''|escape|nl2br}</p>{/if}
      {if $question.description|default:''}<p>{$question.description|default:''|escape|nl2br}</p>{/if}
      <p class="election-question-choices">{$question.choicesLine|default:''|escape}</p>
    </div>
  {/foreach}
</section>
{/if}
