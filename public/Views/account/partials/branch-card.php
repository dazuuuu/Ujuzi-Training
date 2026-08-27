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
