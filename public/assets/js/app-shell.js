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

  // "View more" under a long list of course cards.
  document.addEventListener('click', function (event) {
    var button = event.target.closest && event.target.closest('[data-show-all]');
    if (!button) return;
    var grid = document.getElementById(button.getAttribute('data-show-all'));
    if (!grid) return;
    grid.querySelectorAll('[data-extra]').forEach(function (card) { card.hidden = false; });
    button.remove();
  });
})();
