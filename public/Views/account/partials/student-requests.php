<?php
$pendingStudentRequests = is_array($pendingStudentRequests ?? null) ? $pendingStudentRequests : [];
$categories = is_array($categories ?? null) ? $categories : [];
?>
<section class="space-y-3">
  <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
    <div>
      <h2 class="font-serif-heading text-lg font-bold">Student requests</h2>
      <p class="mt-1 text-xs font-semibold" style="color:var(--ke-muted)">Approve students who chose your organisation. Shown below is only what the student selected — remove any you don't want to grant, or use + Add category to grant one they didn't ask for.</p>
    </div>
    <?php if ($pendingStudentRequests): ?>
      <span class="rounded-full px-2 py-1 text-[10px] font-black uppercase text-white shrink-0" style="background:var(--ke-red)"><?= count($pendingStudentRequests) ?> pending</span>
    <?php endif; ?>
  </div>

  <?php if (!$pendingStudentRequests): ?>
    <p class="rounded-xl border border-dashed p-4 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No pending student requests.</p>
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
          <?php foreach ($pendingStudentRequests as $request):
            $requestedCategoryIds = $request['category_ids'] ?? [];
          ?>
            <tr>
              <td class="font-black"><a href="<?= url('/account/trainer-requests/' . (int) $request['id']) ?>" style="color:var(--ke-green)"><?= e(userDisplayName($request)) ?></a></td>
              <td><?= e(implode(' · ', array_filter([$request['phone'] ?? '', $request['email'] ?? ''])) ?: '—') ?></td>
              <td><?= e($request['branch_title'] ?? '—') ?></td>
              <td><span class="text-[11px] font-black uppercase" style="color:var(--ke-red)">Pending</span></td>
              <td>
                <form method="post" action="<?= url('/account/trainer-requests/' . (int) $request['id'] . '/approve-student') ?>" id="student-request-<?= (int) $request['id'] ?>">
                  <?= csrfField() ?>
                  <?php
                    $allCategories = $categories;
                    $selectedCategoryIds = $requestedCategoryIds;
                    require __DIR__ . '/category-picker.php';
                  ?>
                </form>
              </td>
              <td>
                <div class="excel-row-actions-visible">
                  <a href="<?= url('/account/trainer-requests/' . (int) $request['id']) ?>" class="btn-secondary" style="padding:0.25rem 0.5rem;font-size:0.7rem;">View</a>
                  <button type="submit" form="student-request-<?= (int) $request['id'] ?>" class="btn-primary" style="padding:0.25rem 0.5rem;font-size:0.7rem;">Approve</button>
                  <form method="post" action="<?= url('/account/trainer-requests/' . (int) $request['id'] . '/reject') ?>">
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
</section>
