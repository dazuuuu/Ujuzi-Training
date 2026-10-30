<?php
/**
 * Requires $portals ([key => ['label', 'items' => [id => [icon, label, href]], 'defaults' => [id => label]]]),
 * $publicLinks ([['label', 'url']]) and $tab (the open portal key, or 'public').
 */
require __DIR__ . '/../layout-header.php';
$tabs = array_map(static fn(array $p): string => $p['label'], $portals) + ['public' => 'Public website menu'];
?>

<div class="max-w-3xl space-y-4">
  <section>
    <h1 class="text-xl font-bold">Navigation</h1>
    <p class="text-sm text-neutral-600">Rename any menu item and drag <span aria-hidden="true">⠿</span> to reorder it. Pick a menu below. Dashboard, Profile and Logout always stay in place. Leave a name blank to return to the original name.</p>
  </section>

  <nav class="flex flex-wrap gap-1.5" aria-label="Menus">
    <?php foreach ($tabs as $key => $label): ?>
      <a href="<?= url('/admin/navigation?portal=' . rawurlencode($key)) ?>" class="nav-tab <?= $key === $tab ? 'is-on' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>

  <?php if ($tab === 'public'): ?>
    <form method="post" action="<?= url('/admin/navigation/public') ?>" class="rounded-lg border border-neutral-200 bg-white p-4">
      <?= csrfField() ?>
      <p class="mb-2 text-xs text-neutral-500">The links at the top of the public website. A link can be a page (e.g. <code>/courses</code>, <code>/p/your-page</code>) or a full web address.</p>
      <ul class="nav-list space-y-1.5" id="publicList">
        <?php foreach ($publicLinks as $link): ?>
          <li class="nav-row" draggable="true">
            <span class="nav-grip" aria-hidden="true">⠿</span>
            <input name="link_label[]" value="<?= e($link['label'] ?? '') ?>" placeholder="Name" class="nav-input" aria-label="Link name">
            <input name="link_url[]" value="<?= e($link['url'] ?? '') ?>" placeholder="/page or https://…" class="nav-input" aria-label="Link address">
            <button type="button" class="nav-remove" onclick="this.closest('li').remove()" aria-label="Remove link">✕</button>
          </li>
        <?php endforeach; ?>
      </ul>
      <template id="publicRow">
        <li class="nav-row" draggable="true">
          <span class="nav-grip" aria-hidden="true">⠿</span>
          <input name="link_label[]" placeholder="Name" class="nav-input" aria-label="Link name">
          <input name="link_url[]" placeholder="/page or https://…" class="nav-input" aria-label="Link address">
          <button type="button" class="nav-remove" onclick="this.closest('li').remove()" aria-label="Remove link">✕</button>
        </li>
      </template>
      <div class="mt-3 flex gap-2">
        <button type="button" class="btn-secondary" onclick="document.getElementById('publicList').append(document.getElementById('publicRow').content.cloneNode(true))">+ Add link</button>
        <button type="submit" class="btn-primary">Save menu</button>
      </div>
    </form>
  <?php else: $portal = $portals[$tab]; ?>
    <form method="post" action="<?= url('/admin/navigation/' . $tab) ?>" class="rounded-lg border border-neutral-200 bg-white p-4">
      <?= csrfField() ?>
      <ul class="nav-list space-y-1.5">
        <?php foreach ($portal['items'] as $itemId => [$icon, $label, $href]): $default = $portal['defaults'][$itemId] ?? $label; ?>
          <li class="nav-row" draggable="true">
            <span class="nav-grip" aria-hidden="true">⠿</span>
            <span class="text-neutral-500"><?= icon($icon, 'h-4 w-4') ?></span>
            <input type="hidden" name="order[]" value="<?= e($itemId) ?>">
            <input name="label[<?= e($itemId) ?>]" value="<?= e($label) ?>" placeholder="<?= e($default) ?>" maxlength="60" class="nav-input" aria-label="Name for <?= e($default) ?>">
            <span class="nav-meta" title="Opens <?= e($href) ?>"><?= $label !== $default ? 'was “' . e($default) . '”' : e($href) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
      <button type="submit" class="btn-primary mt-3">Save menu</button>
    </form>
  <?php endif; ?>
</div>

<style>
  .nav-tab{padding:.3rem .7rem;border:1px solid #e5e7eb;border-radius:99px;font-size:.75rem;font-weight:700;color:#374151;background:#fff;text-decoration:none;}
  .nav-tab.is-on{background:var(--ke-green);border-color:var(--ke-green);color:#fff;}
  .nav-row{display:flex;align-items:center;gap:.5rem;padding:.35rem .5rem;border:1px solid #e5e7eb;border-radius:.5rem;background:#fafafa;}
  .nav-row.dragging{opacity:.4;}
  .nav-grip{cursor:grab;color:#9ca3af;user-select:none;}
  .nav-input{flex:1;min-width:0;padding:.3rem .5rem;border:1px solid #d1d5db;border-radius:.375rem;font-size:.85rem;background:#fff;}
  .nav-meta{flex:0 1 40%;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.7rem;color:#6b7280;}
  .nav-remove{padding:0 .4rem;color:#9ca3af;}
  .nav-remove:hover{color:var(--ke-red);}
  @media (max-width:560px){.nav-meta{display:none;}}
</style>
<script>
  document.querySelectorAll('.nav-list').forEach(function (list) {
    var dragged = null;
    list.addEventListener('dragstart', function (e) {
      if (e.target.tagName === 'INPUT') { e.preventDefault(); return; }
      dragged = e.target.closest('.nav-row');
      if (dragged) { dragged.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; }
    });
    list.addEventListener('dragend', function () { if (dragged) dragged.classList.remove('dragging'); dragged = null; });
    list.addEventListener('dragover', function (e) {
      e.preventDefault();
      var over = e.target.closest('.nav-row');
      if (!dragged || !over || over === dragged) return;
      var rect = over.getBoundingClientRect();
      list.insertBefore(dragged, (e.clientY - rect.top) < rect.height / 2 ? over : over.nextSibling);
    });
  });
</script>

<?php require __DIR__ . '/../layout-footer.php'; ?>
