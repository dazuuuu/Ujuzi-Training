<?php
$payload = $payload ?? [];
$template = $payload['template'] ?? null;
$isPdf = !empty($payload['is_pdf']);
$isImage = !empty($payload['is_image']);
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-4 print:hidden">
  <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Attachment</p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold">Your recommendation letter</h1>
      <p class="mt-1 text-sm font-medium text-neutral-600">Issued after your attachment provider approved your attachment. Print or save as PDF.</p>
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
    <?php if (!empty($payload['course'])): ?>
      <p class="certificate-org">Attachment: <?= e($payload['course']) ?></p>
    <?php endif; ?>
    <?php if (!empty($payload['organisation']) || !empty($payload['provider'])): ?>
      <p class="certificate-label">Hosted by</p>
      <p class="certificate-org"><?= e($payload['organisation'] ?: $payload['provider']) ?><?= !empty($payload['branch']) ? ' — ' . e($payload['branch']) : '' ?></p>
    <?php endif; ?>
    <p class="certificate-date">Issued <?= e($payload['issued'] ?? '') ?></p>
  </div>
</section>

<?php require __DIR__ . '/../layout-footer.php'; ?>
