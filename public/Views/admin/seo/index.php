<?php
/** Requires $pages in scope. */
require __DIR__ . '/../layout-header.php';

$grouped = ['Storefront' => [], 'Category' => [], 'Product' => []];
foreach ($pages as $page) {
    $grouped[$page['type']][] = $page;
}
?>

<p class="text-sm text-neutral-500 mb-6">
  Manage the meta title, description, keywords, featured image and tags search engines and social previews use for each page.
</p>

<?php foreach ($grouped as $type => $items): if (!$items) continue; ?>
  <div class="bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-neutral-200">
      <h2 class="font-serif-heading text-lg font-bold text-[#0a0a0a]"><?= e($type) ?> Pages</h2>
    </div>
    <table class="w-full text-left text-xs">
      <tbody class="divide-y divide-neutral-100">
        <?php foreach ($items as $page): ?>
          <tr class="hover:bg-neutral-50">
            <td class="px-5 py-3">
              <p class="font-bold text-neutral-900"><?= e($page['label']) ?></p>
              <p class="text-[10px] text-neutral-400 font-mono"><?= e($page['key']) ?></p>
            </td>
            <td class="px-5 py-3">
              <?php if ($page['hasCustomSeo']): ?>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-neutral-100 text-black">Custom SEO Set</span>
              <?php else: ?>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-neutral-100 text-neutral-500">Using Defaults</span>
              <?php endif; ?>
            </td>
            <td class="px-5 py-3 text-right">
              <a href="<?= url('/admin/seo/' . rawurlencode($page['key']) . '/edit') ?>" class="font-bold text-black hover:underline">Edit SEO</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../layout-footer.php'; ?>
