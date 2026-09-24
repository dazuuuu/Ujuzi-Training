<link rel="manifest" href="<?= asset('manifest.webmanifest') ?>">
<meta name="theme-color" content="#006b3f">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Ujuzi">
<link rel="apple-touch-icon" href="<?= asset('assets/icons/icon-180.png') ?>">
<link rel="icon" type="image/png" sizes="192x192" href="<?= asset('assets/icons/icon-192.png') ?>">
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('<?= asset('sw.js') ?>').catch(function () {});
    });
  }
</script>
