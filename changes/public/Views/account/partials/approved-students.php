<?php
/** Requires $approvedStudents, $categoryLookup (callable org id => categories) and $categoriesAction (URL prefix) in scope. */
$approvedStudents = is_array($approvedStudents ?? null) ? $approvedStudents : [];
?>
<section class="space-y-3">
  <div>
    <h2 class="font-serif-heading text-lg font-bold">Approved students</h2>
    <p class="mt-1 text-xs font-semibold" style="color:var(--ke-muted)">Add or remove categories for a student already approved, then Save. Their course list changes straight away.</p>
  </div>
  <?php if (!$approvedStudents): ?>
    <p class="rounded-xl border border-dashed p-4 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No approved students yet.</p>
  <?php else: ?>
    <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
      <table class="excel-table admin-data-table">
        <thead><tr><th>Student</th><th>Contact</th><th>Branch</th><th>Categories</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($approvedStudents as $student): ?>
            <tr>
              <td class="font-black"><?= e(userDisplayName($student)) ?></td>
              <td><?= e($student['email'] ?: ($student['phone'] ?? '—')) ?></td>
              <td><?= e($student['branch_title'] ?? '—') ?></td>
              <td>
                <form method="post" action="<?= url($categoriesAction . (int) $student['id'] . '/categories') ?>" id="approved-student-<?= (int) $student['id'] ?>">
                  <?= csrfField() ?>
                  <?php
                    $allCategories = $categoryLookup((int) $student['organisation_id']);
                    $selectedCategoryIds = $student['category_ids'];
                    require __DIR__ . '/category-picker.php';
                  ?>
                </form>
              </td>
              <td><div class="excel-row-actions-visible"><button type="submit" form="approved-student-<?= (int) $student['id'] ?>" class="btn-primary" style="padding:0.25rem 0.5rem;font-size:0.7rem;">Save</button></div></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
