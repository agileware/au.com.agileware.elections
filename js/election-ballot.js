/**
 * The ballot: checks the ranks as they are chosen, and shows the choices for review before the vote is
 * saved. Without JavaScript the ballot still works: the server checks everything again.
 */
(function ($, CRM) {
  'use strict';

  var text = (CRM.vars && CRM.vars.electionsBallot) || {};
  var plurality = text.method === 'plurality';

  /** The groups of rows on the ballot, one for each position. */
  function positions($form) {
    return $form.find('.crm-vote-election-form-block').map(function () {
      var $block = $(this);
      var title = $block.attr('data-position') || $.trim($block.find('> p').first().text());
      var rows = $block.find('.vote-candidate-row').map(function () {
        var $row = $(this);
        return {
          $field: $row.find(plurality ? 'input[type="checkbox"][name^="candidate_"]' : 'input[name^="candidate_"]'),
          name: $.trim($row.find('label').first().text())
        };
      }).get();
      return {$block: $block, title: title, rows: rows, seats: parseInt($block.data('seats'), 10) || 1};
    }).get();
  }

  function rankOf(row) {
    if (plurality) {
      return row.$field.prop('checked') ? 1 : null;
    }
    var value = parseInt(row.$field.val(), 10);
    return isNaN(value) ? null : value;
  }

  /** What is wrong with the ranks in one position, in plain words (empty when nothing is). */
  function problemsIn(position) {
    if (plurality) {
      var ticked = $.grep(position.rows, function (row) { return rankOf(row) !== null; }).length;
      return {problems: ticked > position.seats ? [text.tooMany.replace('%1', position.seats)] : [], ranked: ticked};
    }
    var problems = [];
    var seen = {};
    var ranked = 0;
    $.each(position.rows, function (i, row) {
      var rank = rankOf(row);
      if (rank === null) {
        return;
      }
      ranked++;
      if (seen[rank]) {
        problems.push(text.duplicate.replace('%1', rank));
      }
      seen[rank] = true;
    });
    if (ranked > 0 && !seen[1]) {
      problems.push(text.needFirst);
    }
    return {problems: $.unique(problems), ranked: ranked};
  }

  function check($form) {
    var anyProblem = false;
    $.each(positions($form), function (i, position) {
      var result = problemsIn(position);
      var $status = position.$block.find('.election-rank-status');
      if (!$status.length) {
        $status = $('<p class="election-rank-status" aria-live="polite"></p>').appendTo(position.$block.find('> p').first().parent()).insertAfter(position.$block.find('> p').first());
      }
      $status.toggleClass('election-rank-problem', result.problems.length > 0);
      var counted = plurality ? text.chosen.replace('%1', result.ranked).replace('%2', position.seats) : text.counted.replace('%1', result.ranked).replace('%2', position.rows.length);
      $status.text(result.problems.length ? result.problems.join(' ') : counted);
      anyProblem = anyProblem || result.problems.length > 0;
    });
    return !anyProblem;
  }

  /** The choices, in order, for each position; positions with no choice are named together on one line. */
  function summary($form) {
    var $list = $('<div class="election-review-list"></div>');
    var skipped = [];
    $.each(positions($form), function (i, position) {
      var ranked = $.grep(position.rows, function (row) { return rankOf(row) !== null; });
      var title = position.title;
      if (!ranked.length) {
        skipped.push(title);
        return;
      }
      ranked.sort(function (a, b) { return rankOf(a) - rankOf(b); });
      var $section = $('<section class="election-review-position"></section>');
      $('<h3></h3>').text(text.positionTitle.replace('%1', title)).appendTo($section);
      var $ol = $(plurality ? '<ul></ul>' : '<ol></ol>').appendTo($section);
      $.each(ranked, function (j, row) {
        $('<li></li>').text(plurality ? row.name : row.name + ' \u2013 ' + text.rank.replace('%1', rankOf(row))).appendTo($ol);
      });
      $list.append($section);
    });
    if (skipped.length) {
      $('<p class="election-review-none"></p>').text(text.noPreference.replace('%1', skipped.join(', '))).appendTo($list);
    }
    return $list;
  }

  function review($form, submitter) {
    var dialog = document.createElement('dialog');
    dialog.className = 'election-review-dialog';
    dialog.setAttribute('aria-labelledby', 'election-review-title');
    var $dialog = $(dialog);
    $('<h2 id="election-review-title" tabindex="-1"></h2>').text(text.reviewTitle).appendTo($dialog);
    $('<p></p>').text(text.reviewIntro).appendTo($dialog);
    $dialog.append(summary($form));
    var $buttons = $('<p class="election-review-buttons"></p>').appendTo($dialog);
    var $back = $('<button type="button" class="election-review-back"></button>').text(text.back).appendTo($buttons);
    var $confirm = $('<button type="button" class="election-review-confirm"></button>').text(text.confirm).appendTo($buttons);
    // Inside the CiviCRM container, so it takes the same colours and variables as the rest of the election.
    ($form.closest('.crm-container')[0] || document.body).appendChild(dialog);
    $back.on('click', function () { dialog.close(); });
    $confirm.on('click', function () {
      $form.data('electionConfirmed', true);
      dialog.close();
      if ($form[0].requestSubmit) {
        $form[0].requestSubmit(submitter);
      }
      else {
        $form[0].submit();
      }
    });
    $(dialog).on('close', function () {
      $(dialog).remove();
      if (!$form.data('electionConfirmed')) {
        $(submitter).trigger('focus');
      }
    });
    dialog.showModal();
    // Start at the top, so the title and the choices are read first; the buttons are next in the tab order.
    $dialog.find('h2').trigger('focus');
  }

  $(function () {
    var $form = $('.crm-vote-election-form-block').first().closest('form');
    if (!$form.length || !text.confirm) {
      return;
    }
    $form.on('change', 'input[name^="candidate_"]', function () {
      check($form);
    });
    check($form);
    $form.on('submit', function (e) {
      if ($form.data('electionConfirmed')) {
        return;
      }
      var submitter = e.originalEvent && e.originalEvent.submitter;
      if (!check($form)) {
        e.preventDefault();
        e.stopImmediatePropagation();
        $form.find('.election-rank-problem').first().trigger('focus');
        return false;
      }
      if (typeof HTMLDialogElement === 'function') {
        e.preventDefault();
        e.stopImmediatePropagation();
        review($form, submitter || $form.find('button[type=submit]')[0]);
        return false;
      }
    });
  });
}(CRM.$, CRM));
