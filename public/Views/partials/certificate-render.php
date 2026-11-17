<?php
/**
 * Draws one certificate from a payload (CertificateService::payload or
 * CertificateRegistry::issuedPayload): on the uploaded design, or the plain
 * platform certificate. Requires $payload.
 */
$skills = $payload['skills'] ?? [];
$template = $payload['template'] ?? null;
$isPdf = !empty($payload['is_pdf']);
$isImage = !empty($payload['is_image']);
$layout = $payload['layout'] ?? \App\Services\DocumentLayout::get('certificate');
?>
<?php $qrNumber = (string) ($payload['certificate_no'] ?? ($payload['registration_number'] ?? '')); ?>
<?php $qrUrl = $qrNumber !== '' ? \App\Core\Url::absolute('/student-lookup?reg=' . rawurlencode($qrNumber)) : ''; ?>
<div class="cert-qr-wrap" style="position:relative">

<?php if ($template): ?>
  <?php
    $docTemplate = $template;
    $docIsPdf = $isPdf;
    $docLayout = $layout;
    $docValues = [
      'name' => $payload['learner'] ?? '',
      'course' => $payload['course'] ?? '',
      'organisation' => $payload['course_organisation'] ?? '',
      'registration_number' => $payload['registration_number'] ?? '',
      'certificate_number' => !empty($payload['certificate_no']) ? 'Certificate No. ' . $payload['certificate_no'] : '',
      'date' => $payload['issued'] ?? '',
      'qr_code' => $qrUrl,
  ];
    require __DIR__ . '/document-stage.php';
  ?>
<?php else: ?>
<section class="certificate-stage <?= $template ? 'has-template' : 'no-template' ?>">
  <?php if ($template && $isImage): ?>
    <img class="certificate-bg" src="<?= e(imageUrl($template)) ?>" alt="">
  <?php elseif ($template && $isPdf): ?>
    <embed class="certificate-bg" src="<?= e(imageUrl($template)) ?>" type="application/pdf" />
  <?php endif; ?>
  <div class="certificate-overlay">
    <p class="certificate-kicker"><?= e(appName()) ?></p>
    <h2 class="certificate-name"><?= e($payload['learner'] ?? '') ?></h2>
    <?php if (!empty($payload['organisation'])): ?>
      <p class="certificate-org"><?= e($payload['organisation']) ?></p>
    <?php endif; ?>
    <?php if (!empty($payload['registration_number'])): ?>
      <p class="certificate-org">Reg. No. <?= e($payload['registration_number']) ?></p>
    <?php endif; ?>
    <p class="certificate-label">Course completed</p>
    <ul class="certificate-skills">
      <?php foreach ($skills as $skill): ?>
        <li><?= e($skill) ?></li>
      <?php endforeach; ?>
    </ul>
    <?php if (!empty($payload['course_organisation'])): ?>
      <p class="certificate-org">Offered by <?= e($payload['course_organisation']) ?></p>
    <?php endif; ?>
    <p class="certificate-date">Issued <?= e($payload['issued'] ?? '') ?></p>
    <?php if (!empty($payload['certificate_no'])): ?>
      <p class="certificate-org">Certificate No. <?= e($payload['certificate_no']) ?> · verify at <?= e(\App\Core\Url::absolute('/student-lookup')) ?></p>
    <?php endif; ?>
  </div>
</section>
  <?php $qrPos = $layout['qr_code'] ?? ['x' => 92, 'y' => 90, 'size' => 10, 'show' => true]; ?>
  <?php if ($qrUrl !== '' && $qrPos['show']): ?>
    <div class="doc-qr" data-qr="<?= e($qrUrl) ?>"
         style="position:absolute;left:<?= $qrPos['x'] ?>%;top:<?= $qrPos['y'] ?>%;transform:translate(-50%,-50%);width:<?= $qrPos['size'] ?>%;min-width:52px;max-width:120px;aspect-ratio:1;background:#fff;padding:4px;border-radius:6px;box-shadow:0 1px 4px rgba(0,0,0,.25);z-index:5;line-height:0" title="Scan to verify this certificate"></div>
  <?php endif; ?>
<?php endif; ?>
</div>
<?php if ($qrUrl !== '' && empty($GLOBALS['docQrJsLoaded'])): $GLOBALS['docQrJsLoaded'] = true; ?>
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
})();
</script>
<?php endif; ?>
