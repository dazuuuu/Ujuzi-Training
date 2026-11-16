<?php
/** Requires $inbox, $sent, $canSend. */
require __DIR__ . '/../layout-header.php';
?>
<div class="space-y-5">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="font-serif-heading text-3xl font-bold">Announcements</h1>
      <p class="mt-1 text-sm font-medium text-neutral-600">Messages and documents sent to you.</p>
    </div>
    <?php if ($canSend): ?><a href="<?= url('/account/announcements/new') ?>" class="btn-primary">New announcement</a><?php endif; ?>
  </div>
  <section class="space-y-2">
    <?php foreach ($inbox as $a): ?>
      <a href="<?= url('/account/announcements/' . (int) $a['id']) ?>" class="flex items-start gap-3 rounded-xl border bg-white p-4 no-underline" style="border-color:var(--ke-line);<?= $a['read_at'] ? '' : 'border-left:4px solid var(--ke-red)' ?>">
        <span class="mt-0.5 text-neutral-500"><?= icon('bell', 'h-5 w-5') ?></span>
        <span class="min-w-0 flex-1">
          <span class="block text-sm <?= $a['read_at'] ? 'font-semibold' : 'font-black' ?> text-gray-900"><?= e($a['title']) ?><?= $a['attachment_path'] ? ' 📎' : '' ?></span>
          <span class="block truncate text-xs text-neutral-500"><?= e(trim(($a['author_first'] ?? '') . ' ' . ($a['author_last'] ?? ''))) ?> · <?= e($a['organisation_name'] ?? '') ?> · <?= e(date('j M Y H:i', strtotime((string) $a['created_at']))) ?></span>
        </span>
        <?php if (!$a['read_at']): ?><span class="rounded-full bg-red-600 px-2 py-0.5 text-[10px] font-black text-white">NEW</span><?php endif; ?>
      </a>
    <?php endforeach; ?>
    <?php if (!$inbox): ?><p class="rounded-xl border border-dashed p-6 text-center text-sm font-semibold text-neutral-500" style="border-color:var(--ke-line)">No announcements yet.</p><?php endif; ?>
  </section>
  <?php if ($canSend): ?>
    <section class="space-y-2">
      <h2 class="text-sm font-black uppercase tracking-wide text-neutral-600">Sent by you</h2>
      <?php foreach ($sent as $a): ?>
        <a href="<?= url('/account/announcements/' . (int) $a['id']) ?>" class="flex justify-between gap-3 rounded-xl border bg-white p-3 text-sm no-underline" style="border-color:var(--ke-line)">
          <span class="font-bold text-gray-900"><?= e($a['title']) ?></span>
          <span class="text-xs text-neutral-500"><?= (int) $a['recipient_count'] ?> recipients · <?= e(date('j M Y', strtotime((string) $a['created_at']))) ?></span>
        </a>
      <?php endforeach; ?>
      <?php if (!$sent): ?><p class="text-sm text-neutral-500">You haven't sent any yet.</p><?php endif; ?>
    </section>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
