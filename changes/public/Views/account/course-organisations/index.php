<?php
/** Requires $organisations, $branchesByOrg, $categoriesByOrg, $memberships, $isAttachmentProviderView in scope. */
require __DIR__ . '/../layout-header.php';
$isAttachmentProviderView = !empty($isAttachmentProviderView);

$statusLabels = $isAttachmentProviderView ? [
    'pending' => 'Waiting for approval',
    'approved' => 'Approved — you appear as an attachment option for these categories',
    'rejected' => 'Rejected — you can request again',
] : [
    'pending' => 'Waiting for approval',
    'approved' => 'Approved — you can see their courses',
    'rejected' => 'Rejected — you can request again',
];
?>

<div class="space-y-6">
  <section>
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Courses</p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold">Organisation providing courses</h1>
    <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600">
      <?= $isAttachmentProviderView
        ? "Choose the organisation whose students you want to appear as an attachment option for, then the categories your attachment covers (pick more than one if it applies to several). They must approve you — once approved, you'll show up as an attachment choice for any course under those categories."
        : "Click an organisation to see the categories of courses they offer, choose the ones you're interested in (you can pick more than one), and send a request. They must approve you before you can see their courses." ?>
    </p>
  </section>

  <?php if (!$organisations): ?>
    <section class="rounded-xl border border-dashed p-6 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">
      No organisation is providing courses yet.
    </section>
  <?php else: ?>
    <div class="space-y-3">
      <?php foreach ($organisations as $organisation):
        $orgId = (int) $organisation['id'];
        $branches = $branchesByOrg[$orgId] ?? [];
        $categories = $categoriesByOrg[$orgId] ?? [];
        $membership = $memberships[$orgId] ?? null;
        $status = $membership ? (string) ($membership['status'] ?? '') : '';
        $selectedCategoryIds = $membership['category_ids'] ?? [];
        $selectedCategoryNames = $selectedCategoryIds
          ? array_values(array_intersect_key(
              array_column($categories, 'name', 'id'),
              array_flip($selectedCategoryIds)
            ))
          : [];
        $categoriesLocked = $status === 'approved' && $selectedCategoryIds;
      ?>
        <details class="rounded-xl border border-neutral-200 bg-white shadow-sm group">
          <summary class="cursor-pointer list-none p-4 flex items-center justify-between gap-3">
            <div>
              <h3 class="font-bold text-gray-800"><?= e($organisation['name']) ?></h3>
              <?php if (!empty($organisation['description'])): ?>
                <p class="text-xs text-gray-500 mt-1"><?= e($organisation['description']) ?></p>
              <?php endif; ?>
              <?php if ($status): ?>
                <p class="text-[11px] font-black uppercase mt-1" style="color:var(--ke-green)"><?= e($statusLabels[$status] ?? $status) ?></p>
              <?php endif; ?>
              <?php if ($selectedCategoryNames): ?>
                <p class="mt-1 flex flex-wrap gap-1">
                  <?php foreach ($selectedCategoryNames as $name): ?>
                    <span class="rounded-full bg-blue-50 text-blue-700 px-2 py-0.5 text-[10px] font-bold"><?= e($name) ?></span>
                  <?php endforeach; ?>
                </p>
              <?php endif; ?>
            </div>
            <span class="text-xs font-bold text-gray-500 shrink-0">Tap to view categories ▾</span>
          </summary>

          <div class="border-t border-neutral-100 p-4 space-y-3">
            <?php if (!$categories): ?>
              <p class="text-xs font-bold text-gray-500">This organisation hasn't listed any course categories yet.</p>
            <?php elseif ($categoriesLocked): ?>
              <p class="text-xs font-bold text-gray-500">Your categories are approved and can only be changed by the organisation.</p>
            <?php else: ?>
              <form method="post" action="<?= url('/account/course-organisations/request') ?>" class="space-y-3">
                <?= csrfField() ?>
                <input type="hidden" name="organisation_id" value="<?= $orgId ?>" />

                <p class="text-[11px] font-bold uppercase text-gray-500 mb-2">Categories (choose one or more)</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <?php foreach ($categories as $category): ?>
                    <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 text-sm cursor-pointer hover:bg-blue-50 hover:border-blue-200 transition-colors">
                      <input type="checkbox" name="category_ids[]" value="<?= (int) $category['id'] ?>" class="h-4 w-4 text-blue-600 focus:ring-blue-500"
                        <?= in_array((int) $category['id'], $selectedCategoryIds, true) ? 'checked' : '' ?> />
                      <span class="font-bold text-gray-700"><?= e($category['name']) ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>

                <?php if (!$isAttachmentProviderView): ?>
                  <?php if (count($branches) > 1): ?>
                    <p class="text-[11px] font-bold uppercase text-gray-500 mt-4 mb-2">Select Branch</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                      <?php foreach ($branches as $branch): ?>
                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 cursor-pointer hover:bg-blue-50 hover:border-blue-200 transition-colors">
                          <input type="radio" name="branch_id" value="<?= (int) $branch['id'] ?>" class="mt-0.5 h-4 w-4 text-blue-600 focus:ring-blue-500" <?= (int) ($membership['branch_id'] ?? 0) === (int) $branch['id'] ? 'checked' : '' ?> required />
                          <div>
                            <p class="text-sm font-bold text-gray-800 leading-none"><?= e($branch['title']) ?></p>
                            <?php if (!empty($branch['location'])): ?>
                                <p class="text-[10px] text-gray-500 mt-1">📍 <?= e($branch['location']) ?></p>
                            <?php endif; ?>
                          </div>
                        </label>
                      <?php endforeach; ?>
                    </div>
                  <?php elseif (count($branches) === 1): ?>
                    <input type="hidden" name="branch_id" value="<?= (int) $branches[0]['id'] ?>" />
                    <p class="text-[11px] font-bold uppercase text-gray-500 mt-4 mb-2">Branch</p>
                    <div class="inline-flex items-center gap-2 text-xs font-bold text-gray-700 bg-gray-50 rounded-lg p-3 border border-gray-100">
                      <span>📍</span>
                      <?= e($branches[0]['title']) ?>
                      <?php if (!empty($branches[0]['location'])): ?>
                        <span class="text-gray-400 font-normal ml-1">— <?= e($branches[0]['location']) ?></span>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>

                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-xs transition">
                  <?= $status === 'pending' ? 'Update request' : 'Send request' ?>
                </button>
              </form>
            <?php endif; ?>
          </div>
        </details>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
