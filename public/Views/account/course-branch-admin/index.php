<?php
/** Requires $branches, $requests, $categoriesByOrg in scope. */
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Branch admin</p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold">Branch Requests</h1>
      <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600">
        Approve students who requested your branch<?= count($branches) > 1 ? 'es' : '' ?>. Shown below is only what the student selected — remove any you don't want to grant, or use + Add category to grant one they didn't ask for.
      </p>
    </div>
    <a href="<?= url('/account/categories') ?>" class="btn-secondary">Manage categories</a>
  </section>

  <?php if (!$branches): ?>
    <section class="rounded-xl border border-dashed p-6 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">
      No branch has been assigned to you yet. Ask the organisation admin to assign you as a branch admin.
    </section>
  <?php elseif (!$requests): ?>
    <section class="rounded-xl border border-dashed p-6 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">
      No pending student requests for your branch<?= count($branches) > 1 ? 'es' : '' ?>.
    </section>
  <?php else: ?>
    <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
      <table class="excel-table admin-data-table">
        <thead>
          <tr>
            <th>Student</th>
            <th>Contact</th>
            <th>Branch</th>
            <th>Status</th>
            <th>Categories</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($requests as $request):
            $requestedCategoryIds = $request['category_ids'] ?? [];
            $categories = $categoriesByOrg[(int) $request['organisation_id']] ?? [];
          ?>
            <tr>
              <td class="font-black"><?= e(userDisplayName($request)) ?></td>
              <td><?= e($request['email'] ?: ($request['phone'] ?? '—')) ?></td>
              <td><?= e($request['branch_title'] ?? '—') ?></td>
              <td><span class="text-[11px] font-black uppercase" style="color:var(--ke-red)">Pending</span></td>
              <td>
                <form method="post" action="<?= url('/account/course-branch-admin/' . (int) $request['id'] . '/approve') ?>" id="branch-request-<?= (int) $request['id'] ?>">
                  <?= csrfField() ?>
                  <?php
                    $allCategories = $categories;
                    $selectedCategoryIds = $requestedCategoryIds;
                    require __DIR__ . '/../partials/category-picker.php';
                  ?>
                </form>
              </td>
              <td>
                <div class="excel-row-actions-visible">
                  <button type="submit" form="branch-request-<?= (int) $request['id'] ?>" class="btn-primary" style="padding:0.25rem 0.5rem;font-size:0.7rem;">Approve</button>
                  <form method="post" action="<?= url('/account/course-branch-admin/' . (int) $request['id'] . '/reject') ?>">
                    <?= csrfField() ?>
                    <button type="submit" class="btn-danger" style="padding:0.25rem 0.5rem;font-size:0.7rem;">Reject</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
  <?php
    $categoryLookup = static fn(int $orgId): array => $categoriesByOrg[$orgId] ?? [];
    $categoriesAction = '/account/course-branch-admin/';
    require __DIR__ . '/../partials/approved-students.php';
  ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
