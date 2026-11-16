<?php /** Requires $a. */ require __DIR__ . '/../layout-header.php'; ?>
<article class="max-w-3xl space-y-4 rounded-xl border bg-white p-6" style="border-color:var(--ke-line)">
  <a href="<?= url('/account/announcements') ?>" class="text-xs font-black underline" style="color:var(--ke-green)">← All announcements</a>
  <h1 class="font-serif-heading text-2xl font-bold"><?= e($a['title']) ?></h1>
  <p class="text-xs font-semibold text-neutral-500">From <?= e(trim(($a['author_first'] ?? '') . ' ' . ($a['author_last'] ?? ''))) ?> · <?= e($a['organisation_name'] ?? '') ?> · <?= e(date('j M Y H:i', strtotime((string) $a['created_at']))) ?></p>
  <?php if (trim((string) $a['body']) !== ''): ?><div class="whitespace-pre-line text-sm leading-relaxed text-gray-800"><?= e($a['body']) ?></div><?php endif; ?>
  <?php if ($a['attachment_path']): ?>
    <?php $ext = strtolower(pathinfo((string) $a['attachment_path'], PATHINFO_EXTENSION)); $fileUrl = imageUrl($a['attachment_path']); ?>
    <?php if (in_array($ext, ['jpg', 'jpeg', 'png'], true)): ?><img src="<?= e($fileUrl) ?>" alt="" class="max-h-[70vh] rounded-lg border"><?php endif; ?>
    <a href="<?= e($fileUrl) ?>" target="_blank" rel="noopener" class="btn-secondary inline-flex">📎 Open <?= e($a['attachment_name'] ?: 'attachment') ?></a>
  <?php endif; ?>
</article>
<?php require __DIR__ . '/../layout-footer.php'; ?>
