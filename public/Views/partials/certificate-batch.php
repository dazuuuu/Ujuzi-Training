<?php
/**
 * Many certificates on one page, one per printed sheet: "Download" saves
 * them all as one PDF (the browser's Save as PDF). Requires $payloads
 * (list of certificate payloads), $batchTitle and $backUrl.
 */
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($batchTitle) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/tailwind.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app-shell.css') ?>">
  <style>
    body.cert-batch { background: #eef0ee; margin: 0; padding: 16px; font-family: "Plus Jakarta Sans", sans-serif; }
    .batch-bar { max-width: 1000px; margin: 0 auto 16px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between; }
    .cert-page { max-width: 1000px; margin: 0 auto 24px; }
    .cert-caption { font-size: 12px; font-weight: 700; color: #555; margin-bottom: 4px; }
    @page { size: A4 landscape; margin: 0; }
    @media print {
      body.cert-batch { background: #fff; padding: 0; }
      body.cert-batch * { visibility: visible !important; }
      body.cert-batch .batch-bar, body.cert-batch .cert-caption { display: none !important; }
      body.cert-batch .cert-page { margin: 0; max-width: none; break-after: page; page-break-after: always; }
      body.cert-batch .cert-page:last-child { break-after: auto; page-break-after: auto; }
      body.cert-batch .doc-stage, body.cert-batch .certificate-stage { position: relative !important; box-shadow: none; max-width: none; }
    }
  </style>
</head>
<body class="cert-batch">
  <div class="batch-bar">
    <div>
      <a href="<?= url($backUrl) ?>" class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">← Back</a>
      <h1 class="font-serif-heading text-2xl font-bold"><?= e($batchTitle) ?></h1>
      <p class="text-sm font-semibold text-neutral-600"><?= count($payloads) ?> certificate<?= count($payloads) === 1 ? '' : 's' ?> — one per page. Press Download and choose "Save as PDF".</p>
    </div>
    <button type="button" class="btn-primary" onclick="window.print()">Download all (save as PDF)</button>
  </div>
  <?php if (!$payloads): ?>
    <p class="text-center font-bold text-neutral-500">No certificates to show.</p>
  <?php endif; ?>
  <?php foreach ($payloads as $payload): ?>
    <div class="cert-page">
      <p class="cert-caption"><?= e(($payload['learner'] ?? '') . ' — ' . ($payload['course'] ?? '')) ?></p>
      <?php require __DIR__ . '/certificate-render.php'; ?>
    </div>
  <?php endforeach; ?>
</body>
</html>
