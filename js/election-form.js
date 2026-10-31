/**
 * The election create/edit form: tabs, date order checks as dates change, and a timeline preview.
 * Without JavaScript the form still works: every section is shown and the server checks the dates.
 */
(function ($, CRM) {
  'use strict';

  var settings = (CRM.vars && CRM.vars.elections) || {};

  function parse(value) {
    if (!value) {
      return null;
    }
    var date = new Date(String(value).replace(' ', 'T'));
    return isNaN(date.getTime()) ? null : date;
  }

  function field($form, name) {
    return $form.find('input[name="' + name + '"]');
  }

  function values($form) {
    var result = {};
    $.each(settings.dateFields || [], function (i, name) {
      result[name] = parse(field($form, name).val());
    });
    return result;
  }

  /** The same checks as CRM_Elections_BAO_Election::compareDates(), using the rule table from the server. */
  function findProblems(dates) {
    var problems = {};
    $.each(settings.dateRules || [], function (i, rule) {
      var value = dates[rule.field];
      var other = dates[rule.other];
      if (problems[rule.field] || !value || !other) {
        return;
      }
      var failed = rule.rule === 'after' ? !(value > other) : value > other;
      if (failed) {
        problems[rule.field] = rule.message;
      }
    });
    return problems;
  }

  function showProblems($form, problems) {
    $.each(settings.dateFields || [], function (i, name) {
      var $field = field($form, name);
      var $wrapper = $field.closest('td');
      var $message = $wrapper.find('.election-date-problem');
      if (problems[name]) {
        if (!$message.length) {
          $message = $('<span class="crm-error election-date-problem" role="alert"></span>').appendTo($wrapper);
        }
        $message.text(problems[name]);
        $wrapper.find('input.crm-form-date').attr('aria-invalid', 'true');
      }
      else {
        $message.remove();
        $wrapper.find('input.crm-form-date').removeAttr('aria-invalid');
      }
    });
  }

  function format(date) {
    return date ? date.toLocaleString([], {dateStyle: 'medium', timeStyle: 'short'}) : '—';
  }

  function drawTimeline($form, dates, problems) {
    var $list = $form.find('.election-timeline-preview').empty();
    var now = new Date();
    $.each(settings.stages || [], function (i, stage) {
      var start = dates[stage.start];
      var end = stage.end ? dates[stage.end] : null;
      var $item = $('<li></li>');
      $('<span class="election-timeline-label"></span>').text(stage.label).appendTo($item);
      var range = format(start) + (stage.end ? ' → ' + format(end) : (stage.until ? ' → ' + format(dates[stage.until]) : ''));
      $('<span class="election-timeline-range"></span>').text(range).appendTo($item);
      var bad = problems[stage.start] || (stage.end && problems[stage.end]);
      if (bad) {
        $item.addClass('election-timeline-problem').append($('<span class="election-timeline-note"></span>').text(bad));
      }
      else if (start && (end || stage.until) && start <= now && now < (end || dates[stage.until])) {
        $item.addClass('election-timeline-current');
        $('<span class="election-timeline-note"></span>').text(settings.nowLabel || '').appendTo($item);
      }
      $list.append($item);
    });
  }

  function refresh($form) {
    var dates = values($form);
    var problems = findProblems(dates);
    showProblems($form, problems);
    drawTimeline($form, dates, problems);
    $form.find('#election-tab-dates').toggleClass('has-problem', !$.isEmptyObject(problems));
  }

  function buildTabs($form) {
    var $panels = $form.find('.election-form-panel');
    if ($panels.length < 2) {
      return;
    }
    var $list = $('<div class="election-form-tabs" role="tablist"></div>').insertBefore($panels.first());
    $panels.each(function (i) {
      var $panel = $(this);
      var id = $panel.attr('id').replace('election-panel-', '');
      $('<button type="button" role="tab" class="election-form-tab"></button>')
        .attr({id: 'election-tab-' + id, 'aria-controls': $panel.attr('id'), 'aria-selected': 'false', tabindex: '-1'})
        .text($panel.data('title'))
        .appendTo($list);
      $panel.attr({role: 'tabpanel', 'aria-labelledby': 'election-tab-' + id, hidden: true});
    });

    function activate($tab, focus) {
      $list.find('[role=tab]').attr({'aria-selected': 'false', tabindex: '-1'});
      $panels.prop('hidden', true);
      $tab.attr({'aria-selected': 'true', tabindex: '0'});
      $('#' + $tab.attr('aria-controls')).prop('hidden', false);
      if (focus) {
        $tab.trigger('focus');
      }
    }

    $list.on('click', '[role=tab]', function () {
      activate($(this), false);
    });
    $list.on('keydown', '[role=tab]', function (e) {
      var $tabs = $list.find('[role=tab]');
      var index = $tabs.index(this);
      var keys = {ArrowRight: index + 1, ArrowLeft: index - 1, Home: 0, End: $tabs.length - 1};
      if (e.key in keys) {
        e.preventDefault();
        activate($tabs.eq((keys[e.key] + $tabs.length) % $tabs.length), true);
      }
    });

    // Start on the first section that has a problem (after a failed save), otherwise the first.
    var $first = $panels.filter(function () {
      return $(this).find('.crm-error, input.error, select.error, textarea.error, .election-date-problem').length > 0;
    }).first();
    activate($list.find('[aria-controls=' + ($first.length ? $first : $panels.first()).attr('id') + ']'), false);
    $list.find('[role=tab]').each(function () {
      var $panel = $('#' + $(this).attr('aria-controls'));
      $(this).toggleClass('has-problem', $panel.find('.crm-error, input.error, select.error, textarea.error').length > 0);
    });

    // Before the form is checked, open the first section with an empty required field so that the
    // message appears where the person can see it.
    $form.find('.crm-submit-buttons button, .crm-submit-buttons input[type=submit]').on('click', function () {
      var missing = $panels.filter(function () {
        return $(this).find('input.required, select.required, textarea.required').filter(function () {
          return !$.trim($(this).val());
        }).length > 0;
      }).first();
      if (missing.length) {
        activate($list.find('[aria-controls=' + missing.attr('id') + ']'), false);
      }
    });
  }

  $(function () {
    var $form = $('.crm-create-election-form-block').closest('form');
    if (!$form.length) {
      return;
    }
    buildTabs($form);
    $form.on('change.electionDates', '.crm-hidden-date, input[name$="_date"]', function () {
      refresh($form);
    });
    refresh($form);

    // The choice of nomination email only matters when the emails are on.
    var $emails = $form.find('input[type=checkbox][name=send_emails]');
    var toggleTemplate = function () {
      $form.find('.election-nomination-email-row').toggle($emails.is(':checked'));
    };
    $emails.on('change', toggleTemplate);
    toggleTemplate();
  });
}(CRM.$, CRM));
