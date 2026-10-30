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
?>
<?php if ($template): ?>
  <?php
    $docTemplate = $template;
    $docIsPdf = $isPdf;
    $docLayout = $payload['layout'] ?? \App\Services\DocumentLayout::get('certificate');
    $docValues = [
      'name' => $payload['learner'] ?? '',
      'course' => $payload['course'] ?? '',
      'organisation' => $payload['course_organisation'] ?? '',
      'registration_number' => $payload['registration_number'] ?? '',
      'certificate_number' => !empty($payload['certificate_no']) ? 'Certificate No. ' . $payload['certificate_no'] : '',
      'date' => $payload['issued'] ?? '',
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
<?php endif; ?>

