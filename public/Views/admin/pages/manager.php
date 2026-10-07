<?php
/** Every page of the website. Requires $customPages. */
use App\Core\AccountNav;
use App\Services\PageBuilder;

require __DIR__ . '/../layout-header.php';
$builtIn = PageBuilder::PAGES;
?>
<div class="max-w-5xl space-y-6">
  <section class="flex flex-wrap items-end justify-between gap-3">
    <div>
      <p class="text-xs font-black uppercase tracking-widest text-neutral-600">Website</p>
      <h1 class="mt-1 text-2xl font-black text-black">Pages</h1>
      <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-700">Every page of the website. Build any public page with the section editor, make new pages, and set the look of the whole site — portals included — under Theme.</p>
    </div>
    <a href="<?= url('/admin/theme') ?>" class="btn-primary">🎨 Theme &amp; template import</a>
  </section>

  <section class="rounded-xl border bg-white p-5 shadow-sm space-y-3" style="border-color:var(--ke-line)">
    <h2 class="font-serif-heading text-lg font-bold">Create a page</h2>
    <form method="post" action="<?= url('/admin/pages/new') ?>" class="flex flex-wrap items-end gap-3">
      <?= csrfField() ?>
      <label class="min-w-0 flex-1 text-[11px] font-bold uppercase text-neutral-600">Page title<input name="title" required placeholder="e.g. Partners, FAQs, Contact us" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case"></label>
      <label class="text-[11px] font-bold uppercase text-neutral-600">Address (optional)<span class="mt-1 flex items-center rounded-lg border border-neutral-300 bg-neutral-50 pl-2 text-sm normal-case">/p/<input name="slug" placeholder="partners" class="w-32 rounded-r-lg border-0 bg-white p-2 text-sm"></span></label>
      <label class="flex items-center gap-2 pb-2 text-sm font-bold"><input type="checkbox" name="in_nav" value="1" checked> Show in navbar</label>
      <button type="submit" class="btn-primary">Create &amp; open editor</button>
    </form>
  </section>

  <section class="space-y-2">
    <h2 class="font-serif-heading text-lg font-bold">Your pages (<?= count($customPages) ?>)</h2>
    <?php if (!$customPages): ?><p class="rounded-xl border border-dashed p-5 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No pages yet. Create one above.</p><?php endif; ?>
    <?php foreach ($customPages as $slug => $meta): ?>
      <article class="rounded-xl border bg-white p-4 shadow-sm space-y-3" style="border-color:var(--ke-line)">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div class="min-w-0">
            <p class="font-black"><?= e($meta['title']) ?> <span class="ml-1 rounded-full px-2 py-0.5 text-[10px] font-black uppercase" style="<?= $meta['published'] ? 'background:#ecf7f0;color:var(--ke-green)' : 'background:#f3f4f6;color:#6b7280' ?>"><?= $meta['published'] ? 'Published' : 'Draft' ?></span></p>
            <p class="text-xs font-semibold text-neutral-500">/p/<?= e($slug) ?><?= $meta['in_nav'] ? ' · in navbar' : '' ?></p>
          </div>
          <div class="flex flex-wrap gap-2">
            <a href="<?= url('/admin/pages?page=c-' . $slug) ?>" class="btn-primary">Edit page</a>
            <?php if ($meta['published']): ?><a href="<?= url('/p/' . $slug) ?>" target="_blank" rel="noopener" class="btn-secondary">View</a><?php endif; ?>
            <form method="post" action="<?= url('/admin/pages/custom/' . $slug . '/delete') ?>" onsubmit="return confirm('Delete this page for good?');"><?= csrfField() ?><button type="submit" class="btn-danger">Delete</button></form>
          </div>
        </div>
        <details>
          <summary class="cursor-pointer text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Page settings</summary>
          <form method="post" action="<?= url('/admin/pages/custom/' . $slug) ?>" class="mt-3 grid gap-3 sm:grid-cols-2">
            <?= csrfField() ?>
            <label class="text-[11px] font-bold uppercase text-neutral-600">Title<input name="title" value="<?= e($meta['title']) ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case"></label>
            <label class="text-[11px] font-bold uppercase text-neutral-600">Description (for search engines)<input name="description" value="<?= e($meta['description']) ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case"></label>
            <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" name="published" value="1" <?= $meta['published'] ? 'checked' : '' ?>> Published (visitors can open it)</label>
            <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" name="in_nav" value="1" <?= $meta['in_nav'] ? 'checked' : '' ?>> Show in navbar (when published)</label>
            <div><button type="submit" class="btn-secondary">Save settings</button></div>
          </form>
        </details>
      </article>
    <?php endforeach; ?>
  </section>

  <section class="space-y-2">
    <h2 class="font-serif-heading text-lg font-bold">Website pages</h2>
    <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
      <table class="excel-table admin-data-table">
        <thead><tr><th>Page</th><th>Address</th><th>What you can change</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($builtIn as $key => $label): ?>
            <tr>
              <td class="font-black"><?= e($label) ?></td>
              <td><?= e(PageBuilder::pageUrl($key)) ?></td>
              <td>Every section, words, pictures, sizes, order</td>
              <td><a href="<?= url('/admin/pages?page=' . $key) ?>" class="btn-primary">Edit</a> <a href="<?= url(PageBuilder::pageUrl($key)) ?>" target="_blank" rel="noopener" class="btn-secondary">View</a></td>
            </tr>
          <?php endforeach; ?>
          <tr><td class="font-black">Navbar &amp; footer</td><td>Every public page</td><td>Links, buttons, colours, footer text</td><td><a href="<?= url('/admin/pages?page=home') ?>" class="btn-primary">Edit</a></td></tr>
        </tbody>
      </table>
    </div>
  </section>

  <section class="space-y-2">
    <h2 class="font-serif-heading text-lg font-bold">Portal pages</h2>
    <p class="text-sm font-medium text-neutral-600">These are the working screens people use after signing in (courses, wallet, attachment…). Their layout is part of how they work, so it isn't drag-and-drop; you control their look with the <a href="<?= url('/admin/theme') ?>" class="font-black underline">Theme</a> and their menus with <a href="<?= url('/admin/navigation') ?>" class="font-black underline">Navigation</a>.</p>
    <div class="grid gap-3 sm:grid-cols-2">
      <?php foreach (AccountNav::portals() as $portal => $portalLabel): ?>
        <article class="rounded-xl border bg-white p-4 shadow-sm" style="border-color:var(--ke-line)">
          <p class="font-black"><?= e($portalLabel) ?></p>
          <p class="mt-1 text-xs font-semibold text-neutral-500">Dashboard · <?= e(implode(' · ', array_map(static fn(array $item): string => $item[1], AccountNav::orderedItems($portal)))) ?> · Profile</p>
          <div class="mt-3 flex flex-wrap gap-2">
            <a href="<?= url('/admin/theme') ?>" class="btn-secondary" style="padding:.3rem .7rem;font-size:.72rem">Look (theme)</a>
            <a href="<?= url('/admin/navigation') ?>" class="btn-secondary" style="padding:.3rem .7rem;font-size:.72rem">Menu order</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
