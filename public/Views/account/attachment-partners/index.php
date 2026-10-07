<?php
/** Requires $pending, $partners and $declined (AttachmentAudience::requestsForOrganisation entries). */
require __DIR__ . '/../layout-header.php';

$statusStyle = [
    'approved' => 'background:#ecf7f0;color:var(--ke-green)',
    'pending' => 'background:#fffbeb;color:#92400e',
    'rejected' => 'background:#fef2f2;color:var(--ke-red)',
];
$statusLabel = ['approved' => 'Approved', 'pending' => 'Waiting', 'rejected' => 'Declined'];

/** One provider card with its categories and the approve / decline form. */
$card = static function (array $request, bool $open) use ($statusStyle, $statusLabel): void {
    $pid = (int) $request['provider_user_id'];
    ?>
    <article class="learn-card space-y-3 p-4">
      <div class="flex flex-wrap items-start justify-between gap-2">
        <div class="min-w-0">
          <h3 class="font-bold text-gray-800">
            <?php if ($request['organisation_id'] > 0): ?>
              <a href="<?= url('/account/organisations/' . (int) $request['organisation_id']) ?>" class="underline"><?= e($request['name']) ?></a>
            <?php else: ?><?= e($request['name']) ?><?php endif; ?>
          </h3>
          <p class="text-xs font-semibold text-neutral-500">
            <?= e($request['email']) ?><?= !empty($request['phone']) ? ' · ' . e($request['phone']) : '' ?><?= !empty($request['location']) ? ' · ' . e($request['location']) : '' ?>
          </p>
        </div>
        <p class="text-xs font-bold text-neutral-500">Asked <?= e(date('j M Y', strtotime((string) $request['requested_at']))) ?></p>
      </div>
      <form method="post" action="<?= url('/account/attachment-partners/' . $pid . '/approve') ?>" class="space-y-3">
        <?= csrfField() ?>
        <p class="text-[11px] font-black uppercase tracking-wider text-neutral-500">Categories it asked for</p>
        <div class="flex flex-wrap gap-2">
          <?php foreach ($request['categories'] as $category): ?>
            <label class="inline-flex items-center gap-2 rounded-lg border px-2.5 py-1.5 text-sm font-semibold" style="border-color:var(--ke-line)">
              <input type="checkbox" name="category_ids[]" value="<?= (int) $category['id'] ?>" class="h-4 w-4" <?= $category['status'] !== 'rejected' ? 'checked' : '' ?>>
              <?= e($category['name']) ?>
              <span class="rounded-full px-1.5 py-0.5 text-[10px] font-black uppercase" style="<?= $statusStyle[$category['status']] ?>"><?= $statusLabel[$category['status']] ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="flex flex-wrap gap-2">
          <button type="submit" class="btn-primary"><?= $open ? 'Approve ticked categories' : 'Save categories' ?></button>
          <button type="submit" formaction="<?= url('/account/attachment-partners/' . $pid . '/reject') ?>" class="btn-danger" onclick="return confirm('Decline this organisation? Your students won\'t see it.');"><?= $open ? 'Decline' : 'Remove partner' ?></button>
        </div>
      </form>
    </article>
    <?php
};
?>

<div class="space-y-6">
  <section>
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Attachment</p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold">Attachment partners</h1>
    <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600">Organisations providing attachment ask to take your students as attachees or interns. Your students only see an organisation after you approve it, and only for the categories you tick.</p>
  </section>

  <section class="space-y-3">
    <h2 class="font-serif-heading text-lg font-bold">Waiting for you <span class="text-sm text-neutral-500">(<?= count($pending) ?>)</span></h2>
    <?php if (!$pending): ?>
      <p class="rounded-xl border border-dashed p-5 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No requests waiting.</p>
    <?php endif; ?>
    <?php foreach ($pending as $request) { $card($request, true); } ?>
  </section>

  <?php if ($partners): ?>
    <section class="space-y-3">
      <h2 class="font-serif-heading text-lg font-bold">Approved partners <span class="text-sm text-neutral-500">(<?= count($partners) ?>)</span></h2>
      <?php foreach ($partners as $request) { $card($request, false); } ?>
    </section>
  <?php endif; ?>

  <?php if ($declined): ?>
    <section class="space-y-3">
      <h2 class="font-serif-heading text-lg font-bold">Declined <span class="text-sm text-neutral-500">(<?= count($declined) ?>)</span></h2>
      <?php foreach ($declined as $request) { $card($request, false); } ?>
    </section>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
