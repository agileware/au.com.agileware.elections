/**
 * Completes the SearchKit lists of the Elections extension (plan/20-ui-standards.md, sections 3.2, 4 and 10).
 *
 * SearchKit draws the tables from its own templates, which an extension cannot change, so this adds what the
 * standard asks for around them: a caption and column scopes, a label on every cell for the card layout on a phone,
 * a name for each row button ("Edit Honey Show 2027"), the Back button going to the page the person came from, the
 * time zone line above lists with dates, and for the Positions list a name and a keyboard way to reorder.
 *
 * A list uses it with the attribute election-list-table on its crm-search-display-table. Everything the person can
 * do without this script is still there (the buttons are written in the list's own page).
 */
(function (angular, $, CRM) {
  'use strict';

  var ts = CRM.ts('au.com.agileware.elections');
  var settings = CRM.electionsLists || {};

  // What each list is called for a screen reader, and whether it shows dates (so the time zone line is shown).
  var pages = {
    manage: {caption: ts('Elections'), dates: true},
    positions: {caption: ts('Positions'), reorder: true},
    locked: {caption: ts('Positions')},
    candidates: {caption: ts('Candidates')},
    results: {caption: ts('Results')},
    audit: {caption: ts('Audit Log'), dates: true}
  };

  // A page the person has just finished with: Back does not return to a form that was submitted.
  var formRoutes = /civicrm\/elections\/(create|positions\/create|candidates\/add|candidate\/statement|results-display|visibility|duplicate|delete)(?![\w-])/;

  function words(node) {
    return (node.textContent || '').replace(/\s+/g, ' ').trim();
  }

  /**
   * The page the person came from, when it is a page of this site that is not a form and not this page itself.
   */
  function referrerUrl() {
    var url;
    if (!document.referrer) {
      return null;
    }
    try {
      url = new URL(document.referrer, window.location.href);
    }
    catch (e) {
      return null;
    }
    var here = window.location.pathname + window.location.search;
    var there = url.pathname + url.search;
    if (url.origin !== window.location.origin || there === here) {
      return null;
    }
    var decoded = there;
    try {
      decoded = decodeURIComponent(there);
    }
    catch (e) {
      // Keep the address as it is.
    }
    return formRoutes.test(decoded) ? null : url.href;
  }

  angular.module('electionsLists', []).directive('electionListTable', function ($timeout) {
    return {
      restrict: 'A',
      link: function (scope, element, attrs) {
        var host = element[0];
        var config = pages[attrs.electionListTable] || {};
        var announcer = null;
        var queued = false;

        host.classList.add('election-list-table');

        // The line above a list with dates: which time zone the times are in, once, and not in every cell.
        if (config.dates && settings.timeZone && !(host.previousElementSibling && host.previousElementSibling.classList.contains('election-time-zone-note'))) {
          var note = document.createElement('p');
          note.className = 'election-time-zone-note';
          note.textContent = ts('Times are shown in %1.', {1: settings.timeZone});
          host.parentNode.insertBefore(note, host);
        }

        function announce(message) {
          if (!announcer) {
            announcer = document.createElement('div');
            announcer.className = 'election-visually-hidden';
            announcer.setAttribute('role', 'status');
            announcer.setAttribute('aria-live', 'polite');
            host.parentNode.appendChild(announcer);
          }
          // A different text each time, so the same sentence is read again.
          announcer.textContent = '';
          $timeout(function () {
            announcer.textContent = message;
          }, 50, false);
        }

        // Back goes to the page the person came from; the address in the page (the Election Overview) is the fallback.
        function setBack() {
          var back = referrerUrl();
          var link = host.parentNode.querySelector('.election-list-actions .election-back');
          if (back && link && link.getAttribute('href') !== back) {
            link.setAttribute('href', back);
          }
        }

        function rowName(row) {
          var cell = row.querySelector('td[data-field-name]');
          if (!cell) {
            return '';
          }
          var value = cell.querySelector('.crm-search-field-value');
          return words(value || cell);
        }

        function move(row, direction) {
          var ctrl = angular.element(host).controller('crmSearchDisplayTable');
          var key = row.getAttribute('data-entity-id');
          if (!ctrl || !ctrl.results) {
            return;
          }
          var from = -1;
          ctrl.results.forEach(function (item, index) {
            if (String(item.key) === key) {
              from = index;
            }
          });
          var to = from + direction;
          if (from < 0 || to < 0 || to >= ctrl.results.length) {
            return;
          }
          var name = rowName(row);
          var moved = ctrl.results[from];
          scope.$apply(function () {
            ctrl.results.splice(from, 1);
            ctrl.results.splice(to, 0, moved);
            // The same save a drag makes (SearchKit keeps it private, but runs it from this event).
            if (ctrl.draggableOptions && ctrl.draggableOptions.stop) {
              ctrl.draggableOptions.stop({}, {item: {sortable: {model: moved, index: from}}});
            }
          });
          announce(ts('%1 moved to place %2 of %3.', {1: name, 2: to + 1, 3: ctrl.results.length}));
          // The row moved, so the browser may have dropped the focus: put it back on the same button.
          $timeout(function () {
            var again = host.querySelector('tr[data-entity-id="' + key + '"]');
            var button = again && again.querySelector('.election-move-' + (direction < 0 ? 'up' : 'down'));
            if (button && button.disabled) {
              button = again.querySelector('.election-move-' + (direction < 0 ? 'down' : 'up'));
            }
            if (button) {
              button.focus();
            }
          }, 100, false);
        }

        function moveButton(kind, label, icon) {
          var button = document.createElement('button');
          button.type = 'button';
          button.className = 'btn btn-default btn-sm election-move election-move-' + kind;
          button.setAttribute('data-label', label);
          var glyph = document.createElement('i');
          glyph.className = 'crm-i ' + icon;
          glyph.setAttribute('role', 'img');
          glyph.setAttribute('aria-hidden', 'true');
          button.appendChild(glyph);
          return button;
        }

        // Make the table what the standard asks for. Safe to run again and again: it only sets what is missing or
        // different, so it does not trigger itself through the observer.
        function enhance() {
          queued = false;
          setBack();
          var table = host.querySelector('table');
          if (!table) {
            return;
          }

          if (!table.querySelector('caption') && config.caption) {
            var caption = document.createElement('caption');
            caption.className = 'election-visually-hidden';
            caption.textContent = config.caption;
            table.insertBefore(caption, table.firstChild);
          }

          var labels = [];
          Array.prototype.forEach.call(table.querySelectorAll('thead th'), function (th, index) {
            if (!th.getAttribute('scope')) {
              th.setAttribute('scope', 'col');
            }
            var label = th.querySelector('.crm-search-display-table-column-label');
            labels[index] = label ? words(label) : '';
            // The first column of the Positions list holds the drag handle: say so, not "Bulk row actions".
            var hidden = th.querySelector('.sr-only');
            if (config.reorder && index === 0 && hidden && !label && words(hidden) !== ts('Reorder')) {
              hidden.textContent = ts('Reorder');
            }
          });

          var rows = table.querySelectorAll('tbody tr[data-entity-id]');
          Array.prototype.forEach.call(rows, function (row, rowIndex) {
            var name = rowName(row);

            // A label for every cell, shown in front of the value in the card layout on a phone.
            Array.prototype.forEach.call(row.children, function (cell, index) {
              cell.setAttribute('data-label', labels[index] || '');
              var empty = !words(cell) && !cell.querySelector('a, button, input, img');
              cell.classList.toggle('election-cell-empty', empty);
            });

            // Each row button says which row it is for.
            if (name) {
              Array.prototype.forEach.call(row.querySelectorAll('td.crm-search-col-type-buttons a'), function (link) {
                var text = words(link);
                if (text) {
                  link.setAttribute('aria-label', ts('%1 %2', {1: text, 2: name}));
                }
              });
            }

            // The drag handle has a name, and a row can be moved from the keyboard.
            var handle = row.querySelector('.crm-draggable');
            if (handle) {
              handle.setAttribute('title', ts('Drag to reorder'));
              handle.setAttribute('role', 'img');
              handle.setAttribute('aria-label', ts('Drag to reorder'));
              var buttons = row.querySelector('.election-move-buttons');
              if (!buttons) {
                buttons = document.createElement('span');
                buttons.className = 'election-move-buttons';
                buttons.appendChild(moveButton('up', ts('Move up'), 'fa-arrow-up'));
                buttons.appendChild(moveButton('down', ts('Move down'), 'fa-arrow-down'));
                handle.parentNode.appendChild(buttons);
              }
              var up = buttons.querySelector('.election-move-up');
              var down = buttons.querySelector('.election-move-down');
              up.disabled = rowIndex === 0;
              down.disabled = rowIndex === rows.length - 1;
              [up, down].forEach(function (button) {
                var label = button.getAttribute('data-label');
                var full = name ? ts('%1 %2', {1: label, 2: name}) : label;
                button.setAttribute('aria-label', full);
                button.setAttribute('title', label);
              });
            }
          });
        }

        function schedule() {
          if (!queued) {
            queued = true;
            window.requestAnimationFrame(enhance);
          }
        }

        host.addEventListener('click', function (event) {
          var button = event.target.closest ? event.target.closest('.election-move') : null;
          if (!button || button.disabled) {
            return;
          }
          move(button.closest('tr'), button.classList.contains('election-move-up') ? -1 : 1);
        });

        // SearchKit draws the table after the data arrives, and again after every sort, page change and edit.
        new MutationObserver(schedule).observe(host, {childList: true, subtree: true});
        // The action bar next to the table is drawn by Angular too, so the first pass waits for it.
        $timeout(enhance, 0, false);
      }
    };
  });
})(angular, CRM.$, CRM);
