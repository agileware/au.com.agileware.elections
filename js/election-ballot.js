/**
 * The ballot: checks the ranks as they are chosen, and shows the choices for review before the vote is
 * saved. Without JavaScript the ballot still works: the server checks everything again.
 */
(function ($, CRM) {
  'use strict';

  var text = (CRM.vars && CRM.vars.electionsBallot) || {};
  var plurality = text.method === 'plurality';

  /** The groups of rows on the ballot, one for each position: the candidates, and the places for write-ins. */
  function positions($form) {
    return $form.find('.crm-vote-election-form-block').map(function () {
      var $block = $(this);
      var title = $block.attr('data-position') || $.trim($block.find('> .election-position-prompt').first().text());
      if ($block.attr('data-type') === 'question') {
        // A referendum question: one choice, no ranks.
        return {$block: $block, title: title, question: true, rows: [], seats: 1};
      }
      var rows = $block.find('.vote-candidate-row').map(function () {
        var $row = $(this);
        return {
          writeIn: false,
          $field: $row.find(plurality ? 'input[type="checkbox"][name^="candidate_"]' : 'input[name^="candidate_"]'),
          name: $.trim($row.find('label').first().text())
        };
      }).get();
      $block.find('.election-writein-row').each(function () {
        var $row = $(this);
        var $person = $row.find('input[name^="writein_"]');
        rows.push({
          writeIn: true,
          $person: $person,
          $field: $row.find('input[name^="writeinrank_"]'),
          get name() {
            var chosen = $.trim($row.find('.select2-chosen').first().text()) || $person.val();
            return (text.writtenIn || '%1').replace('%1', chosen);
          }
        });
      });
      return {$block: $block, title: title, rows: rows, seats: parseInt($block.data('seats'), 10) || 1};
    }).get();
  }

  /** Has the voter chosen this row (ticked, ranked, or named a person to write in)? */
  function isChosen(row) {
    if (row.writeIn) {
      return $.trim(String(row.$person.val() || '')) !== '';
    }
    return rankOf(row) !== null;
  }

  function rankOf(row) {
    if (plurality) {
      var picked = row.writeIn ? $.trim(String(row.$person.val() || '')) !== '' : row.$field.prop('checked');
      return picked ? 1 : null;
    }
    var value = parseInt(row.$field.val(), 10);
    return isNaN(value) ? null : value;
  }

  /** What is wrong with the ranks in one position, in plain words (empty when nothing is). */
  function problemsIn(position) {
    if (plurality) {
      var ticked = $.grep(position.rows, isChosen).length;
      return {problems: ticked > position.seats ? [text.tooMany.replace('%1', position.seats)] : [], ranked: ticked};
    }
    var problems = [];
    var seen = {};
    var ranked = 0;
    $.each(position.rows, function (i, row) {
      var rank = rankOf(row);
      if (row.writeIn && isChosen(row) && rank === null) {
        problems.push(text.writeInRank);
      }
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
      if (position.question) {
        return;
      }
      var result = problemsIn(position);
      var $status = position.$block.find('.election-rank-status');
      if (!$status.length) {
        $status = $('<p class="election-rank-status" aria-live="polite"></p>').insertAfter(position.$block.find('> .election-position-prompt').first());
      }
      $status.toggleClass('election-rank-problem', result.problems.length > 0);
      var counted = plurality ? text.chosen.replace('%1', result.ranked).replace('%2', position.seats) : text.counted.replace('%1', result.ranked).replace('%2', $.grep(position.rows, function (r) { return !r.writeIn; }).length);
      $status.text(result.problems.length ? result.problems.join(' ') : counted);
      anyProblem = anyProblem || result.problems.length > 0;
    });
    return !anyProblem;
  }

  /** The choices, in order, for each position; positions with no choice are named together on one line. */
  function summary($form) {
    var $list = $('<div class="election-review-list"></div>');
    var skipped = [];
    var unanswered = [];
    $.each(positions($form), function (i, position) {
      if (position.question) {
        var $picked = position.$block.find('input[type="radio"]:checked');
        if (!$picked.length) {
          unanswered.push(position.title);
          return;
        }
        var $answer = $('<section class="election-review-position"></section>');
        $('<h3></h3>').text(text.positionTitle.replace('%1', position.title)).appendTo($answer);
        var picked = $.trim($('label[for="' + $picked.attr('id') + '"]').first().text()) || $picked.val();
        $('<ul></ul>').append($('<li></li>').text(picked)).appendTo($answer);
        $list.append($answer);
        return;
      }
      var ranked = $.grep(position.rows, function (row) { return rankOf(row) !== null && isChosen(row); });
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
        $('<li></li>').text(plurality ? row.name : text.rankedRow.replace('%1', row.name).replace('%2', rankOf(row))).appendTo($ol);
      });
      $list.append($section);
    });
    if (skipped.length) {
      $('<p class="election-review-none"></p>').text((plurality ? text.noChoice : text.noRank).replace('%1', skipped.join(', '))).appendTo($list);
    }
    if (unanswered.length) {
      $('<p class="election-review-none"></p>').text(text.noAnswer.replace('%1', unanswered.join(', '))).appendTo($list);
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

  /**
   * Drag to rank, for a ranked ballot. The rank drop-downs stay the record of the vote (and the way to vote
   * without JavaScript): dragging a candidate, or the Move Up, Move Down, Add to Ranking and Remove from Ranking
   * buttons, only set those drop-downs. Ranked candidates are listed first, in rank order, then a "Not ranked
   * yet" line (always there, so a candidate can be dropped below it), then the others in ballot order. Ranks a write-in already holds are left to the write-in.
   */
  function enhanceRanking($form) {
    $form.find('.crm-vote-election-form-block:not([data-type="question"])').each(function () {
      var $block = $(this);
      var $list = $block.find('.election-rank-list');
      var $rows = $list.children('.vote-candidate-row');
      if ($rows.length < 2) {
        return;
      }
      $block.addClass('election-rank-enhanced');
      $rows.each(function (i) { $(this).attr('data-ballot-order', i); });
      $('<p class="election-rank-hint"></p>').text(text.dragHint).insertBefore($list);
      var $announce = $('<p class="election-rank-announce election-visually-hidden" aria-live="assertive"></p>').insertAfter($list);
      var $divider = $('<p class="election-rank-divider"></p>').text(text.notRanked);

      function field($row) { return $row.find('input[name^="candidate_"]').first(); }
      function nameOf($row) { return $.trim($row.find('label').first().text()); }
      function rankOfRow($row) {
        var value = parseInt(field($row).val(), 10);
        return isNaN(value) ? null : value;
      }
      /** Ranks given to this position's write-ins, which drag to rank leaves alone. */
      function writeInRanks() {
        var taken = {};
        $block.find('input[name^="writeinrank_"]').each(function () {
          var value = parseInt($(this).val(), 10);
          if (!isNaN(value)) {
            taken[value] = true;
          }
        });
        return taken;
      }
      function ranked() {
        return $list.children('.vote-candidate-row').filter(function () { return rankOfRow($(this)) !== null; }).get();
      }

      /** Ranks the rows in this order (1, 2, 3, skipping a write-in's rank) and clears the rest. */
      function assign(order) {
        var taken = writeInRanks();
        var next = 1;
        var inOrder = {};
        $.each(order, function (i, row) {
          while (taken[next]) {
            next++;
          }
          inOrder[$(row).attr('data-candidate')] = next++;
        });
        $list.children('.vote-candidate-row').each(function () {
          var $row = $(this);
          var rank = inOrder[$row.attr('data-candidate')];
          var value = rank ? String(rank) : '';
          if (String(field($row).val() || '') !== value) {
            field($row).val(value).trigger('change', [true]);
          }
        });
        arrange();
      }

      /** Puts ranked rows first in rank order, the divider, then the others in ballot order, and sets the tools. */
      function arrange() {
        var rows = $list.children('.vote-candidate-row').get();
        rows.sort(function (a, b) {
          var ra = rankOfRow($(a));
          var rb = rankOfRow($(b));
          if (ra !== null && rb !== null && ra !== rb) {
            return ra - rb;
          }
          if ((ra === null) !== (rb === null)) {
            return ra === null ? 1 : -1;
          }
          return $(a).attr('data-ballot-order') - $(b).attr('data-ballot-order');
        });
        var count = 0;
        $divider.detach();
        $.each(rows, function (i, row) {
          var $row = $(row);
          var rank = rankOfRow($row);
          if (rank === null && !$divider.parent().length) {
            $list.append($divider);
          }
          $list.append($row);
          if (rank !== null) {
            count++;
          }
        });
        // The line stays even when everyone is ranked: dropping a candidate below it takes them out of the ranking.
        if (!$divider.parent().length) {
          $list.append($divider);
        }
        $.each(rows, function (i, row) {
          tools($(row), rankOfRow($(row)), i, count, rows.length);
        });
      }

      /** A button whose spoken name starts with its visible words and ends with the candidate (": name"). */
      function button(cls, label, name) {
        return $('<button type="button" class="election-btn election-btn-quiet"></button>').addClass(cls).text(label)
          .append($('<span class="election-visually-hidden"></span>').text(': ' + name));
      }

      /** The rank badge and buttons of one row. Rebuilt each time, so they always match its place. */
      function tools($row, rank, index, rankedCount) {
        var name = nameOf($row);
        var $tools = $row.children('.election-rank-tools');
        if (!$tools.length) {
          $tools = $('<div class="election-rank-tools"></div>').prependTo($row);
          $('<span class="election-rank-handle" aria-hidden="true"></span>').attr('title', text.dragHandle.replace('%1', name)).appendTo($tools);
          $('<span class="election-rank-badge"></span>').appendTo($tools);
          $('<span class="election-rank-buttons"></span>').appendTo($tools);
        }
        $row.toggleClass('election-ranked', rank !== null);
        $tools.find('.election-rank-badge').text(rank !== null ? text.rankBadge.replace('%1', rank) : text.unranked);
        var $buttons = $tools.find('.election-rank-buttons');
        $buttons.empty();
        if (rank !== null) {
          if (index > 0) {
            $buttons.append(button('election-rank-up', text.moveUp, name).attr('data-action', 'up'));
          }
          if (index < rankedCount - 1) {
            $buttons.append(button('election-rank-down', text.moveDown, name).attr('data-action', 'down'));
          }
          $buttons.append(button('election-rank-remove', text.removeRank, name).attr('data-action', 'remove'));
        }
        else {
          $buttons.append(button('election-rank-add', text.addRank, name).attr('data-action', 'add'));
        }
      }

      function say($row) {
        var rank = rankOfRow($row);
        $announce.text(rank !== null ? text.nowRank.replace('%1', nameOf($row)).replace('%2', rank) : text.nowUnranked.replace('%1', nameOf($row)));
      }

      /** Moves a row to a place in the ranking (0 is first), or out of it (null). */
      function place(row, at) {
        var order = $.grep(ranked(), function (r) { return r !== row; });
        if (at !== null) {
          order.splice(Math.max(0, Math.min(at, order.length)), 0, row);
        }
        assign(order);
        say($(row));
      }

      $list.on('click', '.election-rank-buttons button', function () {
        var row = $(this).closest('.vote-candidate-row')[0];
        var action = $(this).attr('data-action');
        var order = ranked();
        var at = $.inArray(row, order);
        switch (action) {
          case 'up': place(row, at - 1); break;
          case 'down': place(row, at + 1); break;
          case 'add': place(row, order.length); break;
          case 'remove': place(row, null); break;
        }
        check($form);
        // The buttons were rebuilt: keep the focus on the row that moved, on the same button if it is still there.
        var $buttons = $(row).find('.election-rank-buttons button');
        var $same = $buttons.filter('[data-action="' + action + '"]');
        ($same.length ? $same : $buttons.first()).trigger('focus');
      });

      // A rank chosen in a drop-down puts the row in its place straight away (but not while it is being set here).
      $list.on('change', 'input[name^="candidate_"]', function (e, fromHere) {
        if (!fromHere) {
          arrange();
        }
      });
      $block.on('change', 'input[name^="writeinrank_"]', function () { arrange(); });

      // Dragging, with the mouse, a pen or a finger (pointer events), from the handle.
      $list.on('pointerdown', '.election-rank-handle', function (je) {
        var e = je.originalEvent || je;
        if (e.button !== undefined && e.button !== 0) {
          return;
        }
        je.preventDefault();
        var $row = $(this).closest('.vote-candidate-row');
        var row = $row[0];
        // The row stays where it is, dimmed; a dashed box shows where it will go (no moving copy, so no colours to guess on a dark theme).
        var $placeholder = $('<div class="election-rank-placeholder" aria-hidden="true"></div>');
        $row.addClass('election-rank-dragging').after($placeholder);
        this.setPointerCapture && this.setPointerCapture(e.pointerId);
        var handle = this;

        function move(ev) {
          // The placeholder goes before the first row (or the divider) whose middle is below the pointer.
          var target = null;
          $list.children('.vote-candidate-row, .election-rank-divider').each(function () {
            if (this === row) {
              return;
            }
            var box = this.getBoundingClientRect();
            if (ev.clientY < box.top + box.height / 2) {
              target = this;
              return false;
            }
          });
          if (target) {
            $placeholder.insertBefore(target);
          }
          else {
            $list.append($placeholder);
          }
        }
        function end() {
          $(handle).off('.electionDrag');
          $row.removeClass('election-rank-dragging');
          // Where it was dropped: among the ranked rows (above the divider) it takes that place; below, it is unranked.
          var before = $placeholder.prevAll('.vote-candidate-row').not(row).filter('.election-ranked').length;
          var aboveDivider = $placeholder.index() < $divider.index();
          $placeholder.remove();
          place(row, aboveDivider ? before : null);
          check($form);
        }
        $(handle).on('pointermove.electionDrag', function (ev) { move(ev.originalEvent); })
          .on('pointerup.electionDrag pointercancel.electionDrag', end);
      });

      arrange();
    });
  }

  $(function () {
    var $form = $('.crm-vote-election-form-block').first().closest('form');
    if (!$form.length || !text.confirm) {
      return;
    }
    // With JavaScript the button opens the review window first; the vote is sent from there.
    var $submit = $form.find('button[type="submit"].default').first();
    $submit.contents().filter(function () { return this.nodeType === 3 && $.trim(this.nodeValue) !== ''; }).last().replaceWith(' ' + text.review);
    $form.on('change', 'input[name^="candidate_"], input[name^="writein"]', function () {
      check($form);
    });
    // Write-in places: one to start, a button adds the next (without JavaScript they are all shown).
    $form.find('.election-writein').each(function () {
      var $set = $(this);
      var $rows = $set.find('.election-writein-row');
      var $add = $set.find('.election-writein-add');
      function sync() {
        var hidden = $rows.filter(':hidden');
        $add.prop('hidden', hidden.length === 0);
      }
      $rows.each(function (i) {
        if (i > 0 && $.trim(String($(this).find('input[name^="writein_"]').val() || '')) === '') {
          $(this).prop('hidden', true);
        }
      });
      $add.on('click', function () {
        var $next = $rows.filter(':hidden').first();
        $next.prop('hidden', false);
        $next.find('input, select, .select2-focusser').filter(':visible').first().trigger('focus');
        sync();
      });
      sync();
    });
    if (!plurality) {
      enhanceRanking($form);
    }
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
