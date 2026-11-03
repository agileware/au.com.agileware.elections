/**
 * The Copy button next to a code that is meant to be written down (the audit log fingerprint). It copies
 * the text with the clipboard when the browser allows it and otherwise selects the text so the person can
 * copy it themselves, and tells screen readers what happened.
 */
(function ($, CRM) {
  'use strict';

  var settings = CRM.vars.electionsCopy || {};
  var timer = null;

  function select(node) {
    var range = document.createRange();
    range.selectNodeContents(node);
    var selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);
  }

  function announce(button, label, message) {
    var $button = $(button);
    $button.find('.election-copy-label').text(label);
    $('#election-copy-status').text(message);
    window.clearTimeout(timer);
    timer = window.setTimeout(function () {
      $button.find('.election-copy-label').text(settings.copy || 'Copy');
      $('#election-copy-status').text('');
    }, 4000);
  }

  $(document).on('click', '.election-copy', function () {
    var button = this;
    var target = document.getElementById($(button).data('copy-target'));
    if (!target) {
      return;
    }
    var text = target.textContent.trim();
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(function () {
        announce(button, settings.copied || 'Copied', settings.copied || 'Copied');
      }, function () {
        select(target);
        announce(button, settings.copy || 'Copy', settings.failed || 'Select the fingerprint and copy it');
      });
      return;
    }
    select(target);
    var done = false;
    try {
      done = document.execCommand('copy');
    }
    catch (e) {
      done = false;
    }
    announce(button, done ? (settings.copied || 'Copied') : (settings.copy || 'Copy'), done ? (settings.copied || 'Copied') : (settings.failed || 'Select the fingerprint and copy it'));
  });
})(CRM.$, CRM);
