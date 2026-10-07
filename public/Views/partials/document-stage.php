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
    <div class="doc-field<?= $pos['show'] ? '' : ' is-hidden' ?>" data-field="<?= e($field) ?>"
         style="left:<?= $pos['x'] ?>%;top:<?= $pos['y'] ?>%;font-size:<?= $pos['size'] ?>cqw;font-weight:<?= $pos['bold'] ? 800 : 500 ?>;color:<?= e($pos['color']) ?>"><?= e($text) ?></div>
  <?php endforeach; ?>
</div>
<?php if ($docIsPdf): ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
  <script>
  (function () {
    if (!window.pdfjsLib) return;
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    document.querySelectorAll('canvas[data-pdf-src]').forEach(function (canvas) {
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
