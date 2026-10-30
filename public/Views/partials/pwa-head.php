<link rel="manifest" href="<?= asset('manifest.webmanifest') ?>">
<meta name="theme-color" content="#006b3f">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Ujuzi">
<link rel="apple-touch-icon" href="<?= asset('assets/icons/icon-180.png') ?>">
<link rel="icon" type="image/png" sizes="192x192" href="<?= asset('assets/icons/icon-192.png') ?>">
<script defer src="<?= asset('assets/js/password-reveal.js') ?>"></script>
<script defer src="<?= asset('assets/js/top-scrollbar.js') ?>"></script>
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('<?= asset('sw.js') ?>').catch(function () {});
    });
  }
</script>
<script>
  // "Install app": opens the site full screen like a phone app — no address bar or links.
  (function () {
    var standalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
    var dismissed = false;
    try { dismissed = localStorage.getItem('ujuziInstallDismissed') === '1'; } catch (e) {}
    if (standalone || dismissed) { return; }
    var deferred = null;
    var isIos = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;
    function show(text, canInstall) {
      if (document.getElementById('ujuziInstall')) { return; }
      var bar = document.createElement('div');
      bar.id = 'ujuziInstall';
      bar.setAttribute('role', 'dialog');
      bar.setAttribute('aria-label', 'Install the app');
      bar.style.cssText = 'position:fixed;left:12px;right:12px;bottom:calc(76px + env(safe-area-inset-bottom));z-index:9999;display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:14px;background:#006b3f;color:#fff;font:600 13px/1.35 system-ui,sans-serif;box-shadow:0 8px 24px rgba(0,0,0,.25);max-width:480px;margin:0 auto;';
      bar.innerHTML = '<img src="<?= asset('assets/icons/icon-192.png') ?>" alt="" width="36" height="36" style="border-radius:8px;background:#fff">'
        + '<span style="flex:1">' + text + '</span>'
        + (canInstall ? '<button type="button" data-install style="padding:7px 12px;border-radius:9px;background:#fff;color:#006b3f;font-weight:800;border:0">Install</button>' : '')
        + '<button type="button" data-close aria-label="Not now" style="padding:4px 6px;background:none;border:0;color:#fff;font-size:18px">✕</button>';
      bar.addEventListener('click', function (e) {
        if (e.target.closest('[data-install]') && deferred) {
          deferred.prompt();
          deferred.userChoice.finally(function () { bar.remove(); deferred = null; });
        } else if (e.target.closest('[data-close]')) {
          try { localStorage.setItem('ujuziInstallDismissed', '1'); } catch (err) {}
          bar.remove();
        }
      });
      if (document.body) { document.body.appendChild(bar); } else { document.addEventListener('DOMContentLoaded', function () { document.body.appendChild(bar); }); }
    }
    window.addEventListener('beforeinstallprompt', function (e) {
      e.preventDefault();
      deferred = e;
      show('Install the Ujuzi app — opens full screen like a phone app.', true);
    });
    window.addEventListener('appinstalled', function () { var b = document.getElementById('ujuziInstall'); if (b) { b.remove(); } });
    if (isIos) {
      window.addEventListener('load', function () { show('Install the app: tap Share <b>⬆</b> then <b>Add to Home Screen</b>.', false); });
    }
  })();
</script>
