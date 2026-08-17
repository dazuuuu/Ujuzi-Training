<?php
$categories = $categories ?? [];
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Organisation</p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold">Categories</h1>
      <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600">These categories belong only to <?= e($currentUser['organisation_name'] ?? 'your organisation') ?>. Approved tutors see this list when they create a course — not categories from other organisations.</p>
    </div>
    <a href="<?= url('/account/categories/create') ?>" class="btn-primary">Add category</a>
  </section>

  <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white shadow-sm">
    <table class="w-full min-w-[640px] text-left text-sm">
      <thead class="text-[11px] uppercase tracking-wider" style="background:#e8f5ee;color:var(--ke-green-dark)">
        <tr>
          <th class="px-5 py-3">Name</th>
          <th class="px-5 py-3">Status</th>
          <th class="px-5 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-neutral-100">
        <?php if (!$categories): ?>
          <tr><td colspan="3" class="px-5 py-8 text-center font-bold text-neutral-600">No categories yet. Add subjects such as Engineering, Hospitality, or ICT so tutors can classify their courses.</td></tr>
        <?php endif; ?>
        <?php foreach ($categories as $category): ?>
          <tr>
            <td class="px-5 py-4">
              <p class="font-black"><?= e($category['name']) ?></p>
              <?php if (!empty($category['description'])): ?>
                <p class="text-xs font-semibold text-neutral-600"><?= e($category['description']) ?></p>
              <?php endif; ?>
            </td>
            <td class="px-5 py-4 font-bold"><?= !empty($category['is_active']) ? 'Visible to tutors' : 'Hidden' ?></td>
            <td class="px-5 py-4">
              <div class="flex items-center gap-2">
                <a href="<?= url('/account/categories/' . (int) $category['id'] . '/edit') ?>" class="btn-secondary" style="padding:0.35rem 0.65rem;">Edit</a>
                <form method="post" action="<?= url('/account/categories/' . (int) $category['id'] . '/delete') ?>" onsubmit="return confirm('Delete this category?');">
                  <?= csrfField() ?>
                  <button type="submit" class="btn-danger" style="padding:0.35rem 0.65rem;">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
