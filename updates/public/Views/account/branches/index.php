<?php
$branches = $branches ?? [];
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Organisation</p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold">Branches</h1>
      <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600">List campuses on this page, or add an Organisation branches field to the organisation admin profile form (name, location, and extra details you define). Cover images stay on this page.</p>
    </div>
    <a href="<?= url('/account/branches/create') ?>" class="btn-primary">Add branch</a>
  </section>

  <?php if (!$branches): ?>
    <div class="rounded-xl border border-dashed p-8 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No branches yet.</div>
  <?php else: ?>
    <div class="course-grid">
      <?php foreach ($branches as $branch): ?>
        <article class="course-card">
          <?php $headingTag = 'h2'; require __DIR__ . '/../partials/branch-card.php'; ?>
          <div class="flex items-center gap-2">
            <a href="<?= url('/account/branches/' . (int) $branch['id'] . '/edit') ?>" class="btn-secondary" style="padding:0.35rem 0.65rem;">Edit</a>
            <form method="post" action="<?= url('/account/branches/' . (int) $branch['id'] . '/delete') ?>" onsubmit="return confirm('Delete this branch?');">
              <?= csrfField() ?>
              <button type="submit" class="btn-danger" style="padding:0.35rem 0.65rem;">Delete</button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
