<?php
/** A course tutor as students see them. Requires $tutor and $courses. */
require __DIR__ . '/../layout-header.php';

$name = userDisplayName($tutor);
$photo = (string) ($tutor['photo_path'] ?? '');
$links = array_filter([
    !empty($tutor['phone']) ? ['phone', $tutor['phone'], 'tel:' . preg_replace('/[^0-9+]/', '', $tutor['phone'])] : null,
    !empty($tutor['email']) ? ['mail', $tutor['email'], 'mailto:' . $tutor['email']] : null,
    !empty($tutor['linkedin_url']) ? ['link', 'LinkedIn', $tutor['linkedin_url']] : null,
    !empty($tutor['social_url']) ? ['globe', preg_replace('#^https?://(www\.)?#', '', $tutor['social_url']), $tutor['social_url']] : null,
]);
?>

<div class="mx-auto max-w-4xl space-y-5">
  <a href="javascript:history.back()" class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">← Back</a>

  <section class="learn-card flex flex-col items-center gap-4 p-6 text-center sm:flex-row sm:text-left">
    <?php if ($photo !== ''): ?>
      <img src="<?= e(imageUrl($photo)) ?>" alt="<?= e($name) ?>" class="h-28 w-28 shrink-0 rounded-full object-cover">
    <?php else: ?>
      <span class="flex h-28 w-28 shrink-0 items-center justify-center rounded-full text-4xl font-black text-white" style="background:var(--ke-green)" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($name, 0, 1))) ?></span>
    <?php endif; ?>
    <div class="min-w-0">
      <p class="text-[11px] font-black uppercase tracking-widest" style="color:var(--ke-green)">Tutor</p>
      <h1 class="font-serif-heading text-3xl font-bold"><?= e($name) ?></h1>
      <?php if (!empty($tutor['headline'])): ?><p class="mt-1 text-sm font-bold text-neutral-600"><?= e($tutor['headline']) ?></p><?php endif; ?>
      <?php if (!empty($tutor['organisation_name'])): ?>
        <a href="<?= url('/account/organisations/' . (int) $tutor['organisation_id']) ?>" class="mt-1 inline-flex items-center gap-1 text-xs font-bold underline" style="color:var(--ke-green)"><?= icon('building', 'h-3.5 w-3.5') ?> <?= e($tutor['organisation_name']) ?></a>
      <?php endif; ?>
    </div>
  </section>

  <div class="grid gap-5 md:grid-cols-3">
    <div class="space-y-5 md:col-span-2">
      <section class="learn-card space-y-2 p-5">
        <h2 class="font-serif-heading text-lg font-bold">About</h2>
        <?php if (trim((string) ($tutor['bio'] ?? '')) !== ''): ?>
          <div class="learn-prose"><?= nl2br(e($tutor['bio'])) ?></div>
        <?php else: ?>
          <p class="text-sm font-semibold text-neutral-500">The tutor hasn't added this yet.</p>
        <?php endif; ?>
      </section>
      <section class="learn-card space-y-2 p-5">
        <h2 class="font-serif-heading text-lg font-bold">Experience</h2>
        <?php if (trim((string) ($tutor['experience'] ?? '')) !== ''): ?>
          <div class="learn-prose"><?= nl2br(e($tutor['experience'])) ?></div>
        <?php else: ?>
          <p class="text-sm font-semibold text-neutral-500">The tutor hasn't added this yet.</p>
        <?php endif; ?>
      </section>
    </div>

    <aside class="space-y-5">
      <section class="learn-card space-y-2 p-5">
        <h2 class="font-serif-heading text-lg font-bold">Contact</h2>
        <ul class="space-y-2">
          <?php foreach ($links as [$linkIcon, $label, $href]): ?>
            <li class="flex items-start gap-2 text-sm font-semibold text-gray-800">
              <span class="mt-0.5 shrink-0" style="color:var(--ke-green)"><?= icon($linkIcon, 'h-4 w-4') ?></span>
              <a href="<?= e($href) ?>" class="min-w-0 break-words underline" <?= str_starts_with($href, 'http') ? 'target="_blank" rel="noopener"' : '' ?>><?= e($label) ?></a>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
      <?php if ($courses): ?>
        <section class="learn-card p-5">
          <h2 class="font-serif-heading text-lg font-bold">Courses</h2>
          <ul class="mt-2 space-y-1">
            <?php foreach ($courses as $course): ?>
              <li><a href="<?= url('/account/courses/' . (int) $course['id']) ?>" class="block truncate rounded-md px-2 py-1.5 text-sm font-bold text-gray-800 hover:bg-neutral-50"><?= e($course['title']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endif; ?>
    </aside>
  </div>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
