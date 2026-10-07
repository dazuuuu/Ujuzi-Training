<?php
/** Requires $branch. Optional $headingTag. */
$headingTag = $headingTag ?? 'h3';
$extras = \App\Models\OrganisationBranch::extras($branch);
$title = (string) ($branch['title'] ?? $branch['name'] ?? '');
$location = (string) ($branch['location'] ?? '');
?>
<?php if (!empty($branch['cover_image'])): ?>
  <img src="<?= e(imageUrl($branch['cover_image'])) ?>" alt="">
<?php endif; ?>
<<?= $headingTag ?> class="font-serif-heading text-lg font-bold"><?= e($title) ?></<?= $headingTag ?>>
<p class="text-sm font-medium text-neutral-700"><?= e($location) ?></p>
<?php if (!empty($branch['branch_admin_name'])): ?>
  <p class="mt-1 text-xs font-semibold text-neutral-600">Branch admin: <?= e($branch['branch_admin_name']) ?></p>
<?php endif; ?>
<?php if (!empty($branch['branch_admin_phone'])): ?>
  <p class="mt-1 text-xs font-semibold text-neutral-600">📞 <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $branch['branch_admin_phone'])) ?>" class="underline"><?= e($branch['branch_admin_phone']) ?></a></p>
<?php endif; ?>
<?php if (!empty($branch['branch_admin_email'])): ?>
  <p class="mt-1 break-all text-xs font-semibold text-neutral-600">✉️ <a href="mailto:<?= e($branch['branch_admin_email']) ?>" class="underline"><?= e($branch['branch_admin_email']) ?></a></p>
<?php endif; ?>
<?php if ($extras): ?>
  <dl class="mt-2 space-y-1 text-xs font-semibold text-neutral-600">
    <?php foreach ($extras as $label => $value): ?>
      <div>
        <dt class="uppercase tracking-wider text-[10px] text-neutral-500"><?= e((string) $label) ?></dt>
        <dd><?= e((string) $value) ?></dd>
      </div>
    <?php endforeach; ?>
  </dl>
<?php endif; ?>
