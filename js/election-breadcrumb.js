/**
 * Completes the breadcrumb of a SearchKit screen that was opened with the election only in the address after
 * the #. The server draws Manage Elections and the page; this adds the election between them, as a link to its
 * overview. The name is put in as text, never as markup.
 */
(function ($, CRM) {
  'use strict';

  var settings = CRM.vars.electionsFrame || {};
  var match = /[?&]eid=(\d+)/.exec(window.location.hash);
  if (!match || !settings.manageUrl) {
    return;
  }
  var electionId = parseInt(match[1], 10);

  CRM.api4('Election', 'get', {select: ['name'], where: [['id', '=', electionId]], limit: 1}).then(function (result) {
    if (!result.length) {
      return;
    }
    var name = String(result[0].name || '').replace(/&lt;/g, '<').replace(/&gt;/g, '>');
    var crumbs = document.querySelector('.crm-container .breadcrumb');
    if (!crumbs) {
      return;
    }
    var manage = document.createElement('a');
    manage.href = settings.manageUrl;
    manage.textContent = settings.manageLabel;
    var election = document.createElement('a');
    election.href = settings.overviewUrl + electionId;
    election.textContent = name;
    var page = document.createElement('span');
    page.setAttribute('aria-current', 'page');
    page.textContent = settings.pageLabel;
    var separator = function () {
      var icon = document.createElement('i');
      icon.className = 'crm-i fa-angle-double-right';
      icon.setAttribute('role', 'img');
      icon.setAttribute('aria-hidden', 'true');
      return icon;
    };
    while (crumbs.firstChild) {
      crumbs.removeChild(crumbs.firstChild);
    }
    [manage, separator(), election, separator(), page].forEach(function (node) {
      crumbs.appendChild(node);
      crumbs.appendChild(document.createTextNode(' '));
    });
  });
})(CRM.$, CRM);
