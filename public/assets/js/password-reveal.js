/*
 * Password reveal — adds a show/hide eye button to every password field
 * across the app. Self-contained, idempotent, and watches for fields added
 * after load (e.g. forms revealed by JS). Loaded site-wide from pwa-head.php
 * and from the few standalone pages that don't include it.
 */
(function () {
  'use strict';
  if (window.__pwRevealLoaded) { return; }
  window.__pwRevealLoaded = true;

  var EYE = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>';
  var EYE_OFF = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.9 17.9A10.3 10.3 0 0 1 12 19c-6.5 0-10-7-10-7a18.4 18.4 0 0 1 5.1-5.9m3.2-1A10.3 10.3 0 0 1 12 5c6.5 0 10 7 10 7a18.5 18.5 0 0 1-2.2 3.2M9.9 9.9a3 3 0 0 0 4.2 4.2"/><path d="M1 1l22 22"/></svg>';

  if (!document.getElementById('pw-reveal-style')) {
    var css = document.createElement('style');
    css.id = 'pw-reveal-style';
    css.textContent =
      '.pw-reveal-wrap{position:relative;display:block;}' +
      '.pw-reveal-wrap > input{padding-right:2.6rem !important;}' +
      '.pw-reveal-btn{position:absolute;top:0;right:0;height:100%;width:2.5rem;display:flex;' +
      'align-items:center;justify-content:center;border:0;background:transparent;cursor:pointer;' +
      'color:#6b7280;padding:0;opacity:.75;}' +
      '.pw-reveal-btn:hover{opacity:1;}.pw-reveal-btn:focus{outline:none;opacity:1;}';
    (document.head || document.documentElement).appendChild(css);
  }

  function enhance(input) {
    if (!input || input.type !== 'password' || input.dataset.pwReveal === '1') { return; }
    if (input.disabled || input.readOnly) { return; }
    input.dataset.pwReveal = '1';

    var wrap = document.createElement('span');
    wrap.className = 'pw-reveal-wrap';
    var parent = input.parentNode;
    if (!parent) { return; }
    parent.insertBefore(wrap, input);
    wrap.appendChild(input);

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'pw-reveal-btn';
    btn.setAttribute('aria-label', 'Show password');
    btn.setAttribute('aria-pressed', 'false');
    btn.title = 'Show password';
    btn.innerHTML = EYE;
    wrap.appendChild(btn);

    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.innerHTML = show ? EYE_OFF : EYE;
      var label = show ? 'Hide password' : 'Show password';
      btn.setAttribute('aria-label', label);
      btn.setAttribute('aria-pressed', show ? 'true' : 'false');
      btn.title = label;
      input.focus();
    });
  }

  function scan(root) {
    var nodes = (root || document).querySelectorAll('input[type="password"]');
    for (var i = 0; i < nodes.length; i++) { enhance(nodes[i]); }
  }

  function start() {
    scan(document);
    if (window.MutationObserver) {
      new MutationObserver(function (mutations) {
        for (var m = 0; m < mutations.length; m++) {
          var added = mutations[m].addedNodes;
          for (var n = 0; n < added.length; n++) {
            var node = added[n];
            if (node.nodeType !== 1) { continue; }
            if (node.matches && node.matches('input[type="password"]')) { enhance(node); }
            else if (node.querySelectorAll) { scan(node); }
          }
        }
      }).observe(document.documentElement, { childList: true, subtree: true });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
