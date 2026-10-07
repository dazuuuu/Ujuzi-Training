// App shell behaviour shared by the portals and Super Admin.
(function () {
  var bar = document.getElementById('appProgress');

  // A loading bar while the next page loads — for normal link clicks and form submits.
  function startLoading() {
    if (bar) bar.classList.add('is-loading');
  }
  document.addEventListener('click', function (event) {
    var link = event.target.closest && event.target.closest('a[href]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    if (link.target === '_blank' || link.hasAttribute('download')) return;
    var href = link.getAttribute('href');
    if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return;
    if (link.origin && link.origin !== window.location.origin) return;
    if (/\/export(\?|$)/.test(link.pathname + link.search)) return; // downloads don't navigate away
    startLoading();
  });
  document.addEventListener('submit', function (event) {
    if (!event.defaultPrevented && !(event.target.getAttribute('action') || '').match(/\/export/)) startLoading();
  });

  // Coming back with the browser's Back button restores the page from cache — reset the bar.
  window.addEventListener('pageshow', function () {
    if (bar) bar.classList.remove('is-loading');
  });

  // "View more details" on long course cards.
  document.addEventListener('click', function (event) {
    var button = event.target.closest && event.target.closest('.course-card-more');
    if (!button) return;
    var card = button.closest('.course-tile, .course-card');
    var open = card.classList.toggle('is-expanded');
    button.textContent = open ? 'Show less' : 'View more details';
    button.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  // Reject reasons: a preset fills the reason box.
  document.addEventListener('click', function (event) {
    var preset = event.target.closest && event.target.closest('.reject-preset');
    if (!preset) return;
    var box = preset.closest('form').querySelector('textarea[name="body"]');
    box.value = preset.getAttribute('data-text');
    box.focus();
  });

  // "View more" under a long list of course cards.
  document.addEventListener('click', function (event) {
    var button = event.target.closest && event.target.closest('[data-show-all]');
    if (!button) return;
    var grid = document.getElementById(button.getAttribute('data-show-all'));
    if (!grid) return;
    grid.querySelectorAll('[data-extra]').forEach(function (card) { card.hidden = false; });
    button.remove();
  });

  // Account menu: closes on a click elsewhere or Escape.
  document.addEventListener('click', function (event) {
    document.querySelectorAll('details.user-menu[open]').forEach(function (menu) {
      if (!menu.contains(event.target)) menu.removeAttribute('open');
    });
  });
  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('details.user-menu[open]').forEach(function (menu) { menu.removeAttribute('open'); });
  });

  // Private amounts stay blurred; "See" shows them for 10 seconds.
  document.addEventListener('click', function (event) {
    var button = event.target.closest && event.target.closest('[data-reveal]');
    if (!button) return;
    var target = document.getElementById(button.getAttribute('data-reveal'));
    if (!target) return;
    clearTimeout(target._hideTimer);
    target.classList.remove('is-private');
    button.hidden = true;
    target._hideTimer = setTimeout(function () {
      target.classList.add('is-private');
      button.hidden = false;
    }, 10000);
  });

  // Tabs (course overview, description, materials …).
  document.addEventListener('click', function (event) {
    var tab = event.target.closest && event.target.closest('[role="tab"][data-tab]');
    if (!tab) return;
    var list = tab.closest('[role="tablist"]');
    var group = list.getAttribute('data-tabs');
    list.querySelectorAll('[role="tab"]').forEach(function (other) {
      var on = other === tab;
      other.setAttribute('aria-selected', on ? 'true' : 'false');
      other.tabIndex = on ? 0 : -1;
    });
    document.querySelectorAll('[data-tab-panel="' + group + '"]').forEach(function (panel) {
      panel.hidden = panel.id !== tab.getAttribute('aria-controls');
    });
  });
})();
