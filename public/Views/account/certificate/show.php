<?php
$payload = $payload ?? [];
$skills = $payload['skills'] ?? [];
$template = $payload['template'] ?? null;
$isPdf = !empty($payload['is_pdf']);
$isImage = !empty($payload['is_image']);
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-4 print:hidden">
  <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Certificate</p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold">Your skills certificate</h1>
      <p class="mt-1 text-sm font-medium text-neutral-600">One certificate lists every skill you have completed. Print or save as PDF to keep a copy that matches the official design.</p>
    </div>
    <button type="button" class="btn-primary" onclick="window.print()">Print / save as PDF</button>
  </div>
</div>

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
    <p class="certificate-label">Skills completed</p>
    <ul class="certificate-skills">
      <?php foreach ($skills as $skill): ?>
        <li><?= e($skill) ?></li>
      <?php endforeach; ?>
    </ul>
    <p class="certificate-date">Issued <?= e($payload['issued'] ?? '') ?></p>
  </div>
</section>

<?php require __DIR__ . '/../layout-footer.php'; ?>
