<?php
/** Requires $portals in scope: [portalKey => ['label' => string, 'items' => [id => [icon, label, href]]]]. */
require __DIR__ . '/../layout-header.php';
?>

<div class="max-w-3xl space-y-6">
  <section>
    <h1 class="font-serif-heading text-2xl font-bold">Navigation</h1>
    <p class="mt-1 text-sm font-medium text-neutral-600">Drag items up or down to reorder each portal's side menu. Home, Dashboard, Profile, and Logout always stay fixed in place.</p>
  </section>

  <?php foreach ($portals as $portalKey => $portal): ?>
    <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
      <h2 class="font-serif-heading text-lg font-bold"><?= e($portal['label']) ?></h2>

      <form method="post" action="<?= url('/admin/navigation/' . $portalKey) ?>" class="nav-order-form" data-portal="<?= e($portalKey) ?>">
        <?= csrfField() ?>
        <ul class="nav-order-list space-y-2" data-portal="<?= e($portalKey) ?>">
          <?php foreach ($portal['items'] as $itemId => [$icon, $label, $href]): ?>
            <li class="nav-order-item flex items-center gap-3 rounded-lg border border-neutral-200 bg-neutral-50 p-3 cursor-move" draggable="true" data-id="<?= e($itemId) ?>">
              <span class="text-neutral-400" aria-hidden="true">⠿⠿</span>
              <span class="text-lg"><?= $icon ?></span>
              <span class="font-bold text-sm"><?= e($label) ?></span>
              <input type="hidden" name="order[]" value="<?= e($itemId) ?>" />
            </li>
          <?php endforeach; ?>
        </ul>
        <button type="submit" class="btn-primary mt-4">Save order</button>
      </form>
    </section>
  <?php endforeach; ?>
</div>

<style>
  .nav-order-item.dragging { opacity: 0.4; }
  .nav-order-item.drag-over { border-color: var(--ke-green); border-style: dashed; }
</style>
<script>
  document.querySelectorAll('.nav-order-list').forEach(function (list) {
    let dragged = null;
    list.addEventListener('dragstart', function (e) {
      const item = e.target.closest('.nav-order-item');
      if (!item) return;
      dragged = item;
      item.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
    });
    list.addEventListener('dragend', function (e) {
      const item = e.target.closest('.nav-order-item');
      if (item) item.classList.remove('dragging');
      list.querySelectorAll('.drag-over').forEach(function (el) { el.classList.remove('drag-over'); });
      dragged = null;
    });
    list.addEventListener('dragover', function (e) {
      e.preventDefault();
      const over = e.target.closest('.nav-order-item');
      if (!over || over === dragged) return;
      list.querySelectorAll('.drag-over').forEach(function (el) { el.classList.remove('drag-over'); });
      over.classList.add('drag-over');
      const rect = over.getBoundingClientRect();
      const before = (e.clientY - rect.top) < rect.height / 2;
      list.insertBefore(dragged, before ? over : over.nextSibling);
    });
  });
</script>

<?php require __DIR__ . '/../layout-footer.php'; ?>
