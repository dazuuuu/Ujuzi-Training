<?php
/**
 * An uploaded certificate / letter design with the student's details written
 * where Super Admin placed them (DocumentLayout). Requires $docTemplate
 * (path), $docIsPdf, $docLayout (field => position) and $docValues
 * (field => text). Optional $docEditable (Super Admin's placement editor)
 * and $docType.
 */
$docEditable = !empty($docEditable);
$src = imageUrl((string) $docTemplate);
?>
<div class="doc-stage<?= $docEditable ? ' is-editable' : '' ?>" <?= $docEditable ? 'data-doc-editor="' . e($docType ?? '') . '"' : '' ?>>
  <?php if ($docIsPdf): ?>
    <canvas class="doc-bg" data-pdf-src="<?= e($src) ?>" aria-label="Document design"></canvas>
    <p class="doc-loading">Loading the design…</p>
  <?php else: ?>
    <img class="doc-bg" src="<?= e($src) ?>" alt="">
  <?php endif; ?>
  <?php foreach ($docLayout as $field => $pos):
    $text = (string) ($docValues[$field] ?? '');
    if (!$docEditable && (!$pos['show'] || $text === '')) { continue; }
  ?>
    <?php if ($field === 'qr_code'): ?>
      <div class="doc-field doc-qr<?= $pos['show'] ? '' : ' is-hidden' ?>" data-field="qr_code" data-qr="<?= e($text) ?>"
           style="left:<?= $pos['x'] ?>%;top:<?= $pos['y'] ?>%;width:<?= $pos['size'] ?>%;aspect-ratio:1;background:#fff;padding:3px;border-radius:4px;box-shadow:0 1px 4px rgba(0,0,0,.2);line-height:0" title="Scan to verify"></div>
    <?php else: ?>
    <div class="doc-field<?= $pos['show'] ? '' : ' is-hidden' ?>" data-field="<?= e($field) ?>"
         style="left:<?= $pos['x'] ?>%;top:<?= $pos['y'] ?>%;font-size:<?= $pos['size'] ?>cqw;font-weight:<?= $pos['bold'] ? 800 : 500 ?>;color:<?= e($pos['color']) ?>"><?= e($text) ?></div>
    <?php endif; ?>
  <?php endforeach; ?>
</div>
<?php if ($docIsPdf): ?>
  <?php if (empty($GLOBALS['docPdfJsLoaded'])): $GLOBALS['docPdfJsLoaded'] = true; ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
  <?php endif; ?>
  <script>
  (function () {
    if (!window.pdfjsLib) return;
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    document.querySelectorAll('canvas[data-pdf-src]:not([data-pdf-drawn])').forEach(function (canvas) {
      canvas.setAttribute('data-pdf-drawn', '1');
      pdfjsLib.getDocument(canvas.getAttribute('data-pdf-src')).promise.then(function (pdf) {
        return pdf.getPage(1);
      }).then(function (page) {
        var viewport = page.getViewport({ scale: 2.5 });
        canvas.width = viewport.width;
        canvas.height = viewport.height;
        return page.render({ canvasContext: canvas.getContext('2d'), viewport: viewport }).promise;
      }).then(function () {
        var loading = canvas.parentNode.querySelector('.doc-loading');
        if (loading) loading.remove();
      }).catch(function () {
        var loading = canvas.parentNode.querySelector('.doc-loading');
        if (loading) loading.textContent = 'The design could not be shown. Try uploading it as a PNG or JPG image.';
      });
    });
  })();
  </script>
<?php endif; ?>
<?php if (empty($GLOBALS['docQrJsLoaded'])): $GLOBALS['docQrJsLoaded'] = true; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
<script>
(function () {
  function draw() {
    if (typeof qrcode === 'undefined') return;
    document.querySelectorAll('.doc-qr[data-qr]:not([data-done])').forEach(function (el) {
      var text = el.getAttribute('data-qr');
      if (!text) { return; }
      var q = qrcode(0, 'M'); q.addData(text); q.make();
      el.innerHTML = q.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
      var svg = el.querySelector('svg'); if (svg) { svg.style.width = '100%'; svg.style.height = '100%'; }
      el.setAttribute('data-done', '1');
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', draw); else draw();
  window.addEventListener('load', draw);
  document.addEventListener('doc-qr-refresh', draw);
})();
</script>
<?php endif; ?>
