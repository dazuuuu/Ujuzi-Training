<?php
/** The website's look. Requires $theme, $animations and $imported (an import waiting to be applied, or null). */
require __DIR__ . '/../layout-header.php';
$swatch = static fn(string $hex): string => '<span style="display:inline-block;width:18px;height:18px;border-radius:5px;vertical-align:-4px;border:1px solid rgba(0,0,0,.1);background:' . e($hex) . '"></span>';
?>
<div class="max-w-5xl space-y-6">
  <section class="flex flex-wrap items-end justify-between gap-3">
    <div>
      <a href="<?= url('/admin/pages') ?>" class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">← Pages</a>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold">Theme</h1>
      <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600">The look of the whole website — public pages and every portal. Upload a template to borrow its colours, fonts, corners, shadows and animations, or set them by hand. Your pages and content never change.</p>
    </div>
    <?php if (!empty($theme['source'])): ?><span class="text-xs font-bold text-neutral-500">Last imported from <?= e($theme['source']) ?></span><?php endif; ?>
  </section>

  <section class="rounded-xl border bg-white p-6 shadow-sm space-y-3" style="border-color:var(--ke-line)">
    <h2 class="font-serif-heading text-lg font-bold">Import a template's style</h2>
    <p class="text-sm font-medium text-neutral-600">Upload a <strong>.zip</strong> (a static HTML site, a built React / Vue app — zip its <code>dist</code> or <code>build</code> folder — or a Tailwind project), an <strong>.html</strong> page or a <strong>.css</strong> file. Only its styles are read; nothing in it runs or is published.</p>
    <form method="post" action="<?= url('/admin/theme/import') ?>" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
      <?= csrfField() ?>
      <input type="file" name="template" accept=".zip,.html,.htm,.css" required class="text-sm">
      <button type="submit" class="btn-primary">Read template</button>
    </form>
  </section>

  <?php if ($imported): ?>
    <section id="imported" class="rounded-xl border-2 bg-white p-6 shadow-sm space-y-4" style="border-color:var(--ke-green)">
      <div>
        <h2 class="font-serif-heading text-lg font-bold">Found in <?= e($imported['source'] ?? 'the template') ?></h2>
        <p class="text-xs font-bold text-neutral-500">Read: <?= e(implode(', ', $imported['found'] ?? [])) ?></p>
      </div>
      <?php if (!empty($imported['palette'])): ?>
        <div><p class="text-[11px] font-black uppercase text-neutral-500">Colours used, most first</p>
          <div class="mt-1 flex flex-wrap gap-1"><?php foreach ($imported['palette'] as $hex): ?><span title="<?= e($hex) ?>"><?= $swatch($hex) ?></span><?php endforeach; ?></div></div>
      <?php endif; ?>
      <form method="post" action="<?= url('/admin/theme/apply') ?>" class="space-y-4">
        <?= csrfField() ?>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <label class="text-[11px] font-bold uppercase text-neutral-600">Main colour<input type="color" name="accent" value="<?= e($imported['accent'] ?? $theme['accent']) ?>" class="mt-1 block h-10 w-full rounded-lg border border-neutral-300"></label>
          <label class="text-[11px] font-bold uppercase text-neutral-600">Second colour<input type="color" name="accent2" value="<?= e($imported['accent2'] ?? $theme['accent2']) ?>" class="mt-1 block h-10 w-full rounded-lg border border-neutral-300"></label>
          <label class="text-[11px] font-bold uppercase text-neutral-600">Heading font<input name="heading_font" value="<?= e($imported['heading_font'] ?? $theme['heading_font']) ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case"></label>
          <label class="text-[11px] font-bold uppercase text-neutral-600">Body font<input name="body_font" value="<?= e($imported['body_font'] ?? $theme['body_font']) ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case"></label>
        </div>
        <p class="text-sm font-medium text-neutral-700">
          Corners: <strong><?= isset($imported['radius']) ? (int) $imported['radius'] . 'px' : 'not found (kept)' ?></strong> ·
          Shadow: <strong><?= isset($imported['shadow']) ? e($imported['shadow']) : 'not found (kept)' ?></strong> ·
          Animations: <strong><?= !empty($imported['keyframes']) ? preg_match_all('/@keyframes/', $imported['keyframes']) . ' found' : 'none found' ?></strong>
        </p>
        <?php if (!empty($imported['keyframes'])): ?>
          <label class="block text-[11px] font-bold uppercase text-neutral-600">Section entrance animation
            <select name="animation" class="mt-1 w-full max-w-sm rounded-lg border border-neutral-300 p-2 text-sm normal-case">
              <?php foreach (\App\Services\SiteTheme::animations(['keyframes' => $imported['keyframes']]) as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $value === ($imported['animation'] ?? 'none') ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        <?php endif; ?>
        <div class="flex flex-wrap gap-2">
          <button type="submit" class="btn-primary">Apply this style to the website</button>
          <button type="submit" formaction="<?= url('/admin/theme/discard') ?>" class="btn-secondary">Discard</button>
        </div>
      </form>
    </section>
  <?php endif; ?>

  <section class="rounded-xl border bg-white p-6 shadow-sm space-y-4" style="border-color:var(--ke-line)">
    <h2 class="font-serif-heading text-lg font-bold">Current theme</h2>
    <form method="post" action="<?= url('/admin/theme') ?>" class="space-y-4">
      <?= csrfField() ?>
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <label class="text-[11px] font-bold uppercase text-neutral-600">Main colour<input type="color" name="accent" value="<?= e($theme['accent']) ?>" class="mt-1 block h-10 w-full rounded-lg border border-neutral-300"></label>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Second colour<input type="color" name="accent2" value="<?= e($theme['accent2']) ?>" class="mt-1 block h-10 w-full rounded-lg border border-neutral-300"></label>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Heading font<input name="heading_font" value="<?= e($theme['heading_font']) ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case"></label>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Body font<input name="body_font" value="<?= e($theme['body_font']) ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case"></label>
      </div>
      <label class="block text-[11px] font-bold uppercase text-neutral-600">Google Fonts link (for the fonts above)
        <input name="font_url" value="<?= e($theme['font_url']) ?>" placeholder="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case"></label>
      <div class="grid gap-3 sm:grid-cols-3">
        <label class="text-[11px] font-bold uppercase text-neutral-600">Corner rounding (px)<input type="number" name="radius" min="0" max="40" value="<?= (int) $theme['radius'] ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm"></label>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Card shadow<input name="shadow" value="<?= e($theme['shadow']) ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case"></label>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Section entrance animation
          <select name="animation" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case">
            <?php foreach ($animations as $value => $label): ?><option value="<?= e($value) ?>" <?= $value === $theme['animation'] ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
          </select></label>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <button type="submit" class="btn-primary">Save theme</button>
        <button type="submit" formaction="<?= url('/admin/theme/reset') ?>" class="btn-secondary" onclick="return confirm('Go back to the original design?');">Reset to original</button>
        <a href="<?= url('/') ?>" target="_blank" rel="noopener" class="btn-secondary">View website</a>
      </div>
    </form>
  </section>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
