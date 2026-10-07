<?php
/** An organisation's details for students. Requires $organisation, $isAttachmentOrg, $branches, $categories, $courseCount. */
require __DIR__ . '/../layout-header.php';

$org = $organisation;
$logo = (string) ($org['logo_path'] ?? '');
$website = (string) ($org['website'] ?? '');
$contacts = array_filter([
    'phone' => !empty($org['phone']) ? ['phone', $org['phone'], 'tel:' . preg_replace('/[^0-9+]/', '', $org['phone'])] : null,
    'email' => !empty($org['email']) ? ['mail', $org['email'], 'mailto:' . $org['email']] : null,
    'location' => !empty($org['location']) ? ['map-pin', $org['location'], null] : null,
    'website' => $website !== '' ? ['globe', preg_replace('#^https?://#', '', $website), $website] : null,
]);
?>

<div class="mx-auto max-w-4xl space-y-5">
  <a href="javascript:history.back()" class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">← Back</a>

  <section class="learn-card flex flex-col gap-4 p-5 sm:flex-row sm:items-center">
    <?php if ($logo !== ''): ?>
      <img src="<?= e(imageUrl($logo)) ?>" alt="" class="h-20 w-20 shrink-0 rounded-xl border object-contain bg-white p-1" style="border-color:var(--ke-line)">
    <?php else: ?>
      <span class="flex h-20 w-20 shrink-0 items-center justify-center rounded-xl text-3xl font-black text-white" style="background:var(--ke-green)" aria-hidden="true"><?= e(mb_strtoupper(mb_substr(trim($org['name']), 0, 1))) ?></span>
    <?php endif; ?>
    <div class="min-w-0">
      <p class="text-[11px] font-black uppercase tracking-widest" style="color:var(--ke-green)"><?= $isAttachmentOrg ? 'Organisation providing attachment' : 'Organisation providing courses' ?></p>
      <h1 class="font-serif-heading text-2xl font-bold sm:text-3xl"><?= e($org['name']) ?></h1>
      <?php if (!$isAttachmentOrg): ?>
        <p class="text-sm font-semibold text-neutral-500"><?= (int) $courseCount ?> course<?= $courseCount === 1 ? '' : 's' ?> · <?= count($categories) ?> categor<?= count($categories) === 1 ? 'y' : 'ies' ?></p>
      <?php endif; ?>
    </div>
  </section>

  <div class="grid gap-5 md:grid-cols-3">
    <section class="learn-card space-y-3 p-5 md:col-span-2">
      <h2 class="font-serif-heading text-lg font-bold">About</h2>
      <?php if (trim((string) ($org['description'] ?? '')) !== ''): ?>
        <div class="learn-prose"><?= nl2br(e($org['description'])) ?></div>
      <?php else: ?>
        <p class="text-sm font-semibold text-neutral-500">This organisation hasn't added a description yet.</p>
      <?php endif; ?>

      <?php if ($categories): ?>
        <h3 class="learn-section-title pt-2">Course categories</h3>
        <div class="flex flex-wrap gap-1.5">
          <?php foreach ($categories as $category): ?>
            <span class="rounded-full border px-2.5 py-0.5 text-xs font-bold" style="border-color:var(--ke-green);color:var(--ke-green)"><?= e($category['name']) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="learn-card space-y-2 p-5">
      <h2 class="font-serif-heading text-lg font-bold">Contact</h2>
      <?php if (!$contacts): ?>
        <p class="text-sm font-semibold text-neutral-500">No contact details yet.</p>
      <?php endif; ?>
      <ul class="space-y-2">
        <?php foreach ($contacts as [$contactIcon, $label, $href]): ?>
          <li class="flex items-start gap-2 text-sm font-semibold text-gray-800">
            <span class="mt-0.5 shrink-0" style="color:var(--ke-green)"><?= icon($contactIcon, 'h-4 w-4') ?></span>
            <?php if ($href): ?>
              <a href="<?= e($href) ?>" class="min-w-0 break-words underline" <?= str_starts_with($href, 'http') ? 'target="_blank" rel="noopener"' : '' ?>><?= e($label) ?></a>
            <?php else: ?>
              <span class="min-w-0 break-words"><?= e($label) ?></span>
            <?php endif; ?>
            <?php if (!empty($branch['branch_admin_email'])): ?>
              <p class="mt-1 flex items-center gap-1.5 break-all text-xs font-semibold text-neutral-600"><?= icon('mail', 'h-3.5 w-3.5 shrink-0') ?> <a href="mailto:<?= e($branch['branch_admin_email']) ?>"><?= e($branch['branch_admin_email']) ?></a></p>
            <?php endif; ?>
            <?php if (!empty($branch['branch_admin_name'])): ?>
              <p class="mt-1 text-xs font-semibold text-neutral-500">Branch admin: <?= e($branch['branch_admin_name']) ?></p>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  </div>

  <?php if ($branches): ?>
    <section class="learn-card p-5">
      <h2 class="font-serif-heading text-lg font-bold">Branches</h2>
      <ul class="mt-3 grid gap-3 sm:grid-cols-2">
        <?php foreach ($branches as $branch): ?>
          <li class="rounded-lg border p-3" style="border-color:var(--ke-line)">
            <p class="font-bold text-gray-800"><?= e($branch['title']) ?></p>
            <?php if (!empty($branch['location'])): ?>
              <p class="mt-1 flex items-center gap-1.5 text-xs font-semibold text-neutral-600"><?= icon('map-pin', 'h-3.5 w-3.5 shrink-0') ?> <?= e($branch['location']) ?></p>
            <?php endif; ?>
            <?php if (!empty($branch['branch_admin_phone'])): ?>
              <p class="mt-1 flex items-center gap-1.5 text-xs font-semibold text-neutral-600"><?= icon('phone', 'h-3.5 w-3.5 shrink-0') ?> <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $branch['branch_admin_phone'])) ?>"><?= e($branch['branch_admin_phone']) ?></a></p>
            <?php endif; ?>
            <?php if (!empty($branch['branch_admin_email'])): ?>
              <p class="mt-1 flex items-center gap-1.5 break-all text-xs font-semibold text-neutral-600"><?= icon('mail', 'h-3.5 w-3.5 shrink-0') ?> <a href="mailto:<?= e($branch['branch_admin_email']) ?>"><?= e($branch['branch_admin_email']) ?></a></p>
            <?php endif; ?>
            <?php if (!empty($branch['branch_admin_name'])): ?>
              <p class="mt-1 text-xs font-semibold text-neutral-500">Branch admin: <?= e($branch['branch_admin_name']) ?></p>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
