<?php
/**
 * A removable-chip category picker: shows only the currently selected
 * categories (not the whole catalogue), with an "×" to drop one and a "+"
 * dropdown to add another — only from categories that actually exist for
 * this organisation. Submits as category_ids[] inside whatever <form> wraps
 * this include. Requires $allCategories and $selectedCategoryIds in scope.
 */
$allCategories = is_array($allCategories ?? null) ? $allCategories : [];
$selectedCategoryIds = is_array($selectedCategoryIds ?? null) ? $selectedCategoryIds : [];
$namesById = [];
foreach ($allCategories as $cat) {
    $namesById[(int) $cat['id']] = (string) $cat['name'];
}
?>
<div class="category-picker" data-all="<?= e(json_encode(array_map(
    static fn(array $cat): array => ['id' => (int) $cat['id'], 'name' => (string) $cat['name']],
    $allCategories
))) ?>">
  <div class="category-chips flex flex-wrap gap-2 mb-2">
    <?php if (!$selectedCategoryIds): ?>
      <span class="text-xs font-bold text-neutral-500 category-empty-hint">No categories selected.</span>
    <?php endif; ?>
    <?php foreach ($selectedCategoryIds as $categoryId): ?>
      <?php if (!isset($namesById[(int) $categoryId])) continue; ?>
      <span class="category-chip inline-flex items-center gap-1 rounded-full border border-neutral-200 bg-neutral-50 px-2 py-1 text-[11px] font-bold whitespace-nowrap" data-id="<?= (int) $categoryId ?>">
        <?= e($namesById[(int) $categoryId]) ?>
        <button type="button" class="category-chip-remove font-black text-neutral-500 hover:text-rose-600" aria-label="Remove">×</button>
        <input type="hidden" name="category_ids[]" value="<?= (int) $categoryId ?>" />
      </span>
    <?php endforeach; ?>
  </div>
  <select class="category-add w-full sm:w-auto rounded border border-neutral-200 p-1 text-xs">
    <option value="">+ Add category</option>
    <?php foreach ($allCategories as $cat): ?>
      <?php if (in_array((int) $cat['id'], $selectedCategoryIds, true)) continue; ?>
      <option value="<?= (int) $cat['id'] ?>"><?= e($cat['name']) ?></option>
    <?php endforeach; ?>
  </select>
</div>
