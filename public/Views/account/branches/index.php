<?php
$branches = $branches ?? [];
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Organisation</p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold">Branches</h1>
      <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600">Create and manage branches for your account. A branch can be created independently without selecting an organisation.</p>
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
            <?php $deletion = \App\Models\BranchDeletionRequest::pendingForBranch((int) $branch['id']); ?>
            <?php if ($deletion): ?>
              <form method="post" action="<?= url('/account/branches/' . (int) $branch['id'] . '/delete') ?>">
                <?= csrfField() ?><input type="hidden" name="cancel" value="1">
                <span class="text-xs font-bold" style="color:#92400e">Deletion waiting for Super Admin</span>
                <button type="submit" class="btn-secondary" style="padding:0.25rem 0.6rem;font-size:0.72rem;">Withdraw</button>
              </form>
            <?php else: ?>
              <form method="post" action="<?= url('/account/branches/' . (int) $branch['id'] . '/delete') ?>" onsubmit="var r = prompt('Why should this branch be deleted? Super Admin approves deletions.'); if (r === null) return false; this.reason.value = r; return true;">
                <?= csrfField() ?><input type="hidden" name="reason" value="">
                <button type="submit" class="btn-danger" style="padding:0.25rem 0.6rem;font-size:0.72rem;">Request deletion</button>
              </form>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
