<?php
/** Requires $fields (section => [key => [label, default, type]]), $logos and $courses (published, with featured_on_home). */
use App\Services\HomepageContent;

require __DIR__ . '/../layout-header.php';
$featuredCount = count(array_filter($courses, static fn(array $c): bool => (int) $c['featured_on_home'] === 1));
?>
<div class="space-y-8">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Homepage</p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold">Homepage content</h1>
      <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600">
        Change the words, the "Trusted by" logos and the featured courses. The page's layout stays the same.
        Leave a field blank to go back to its original wording.
      </p>
    </div>
    <a href="<?= url('/') ?>" target="_blank" rel="noopener" class="btn-secondary">View homepage ↗</a>
  </section>

  <nav class="flex flex-wrap gap-2 text-xs font-black uppercase" aria-label="Jump to">
    <a href="#text" class="rounded-full border px-3 py-1.5" style="border-color:var(--ke-line)">Text</a>
    <a href="#courses" class="rounded-full border px-3 py-1.5" style="border-color:var(--ke-line)">Featured courses (<?= $featuredCount ?>)</a>
    <a href="#logos" class="rounded-full border px-3 py-1.5" style="border-color:var(--ke-line)">Trusted-by logos (<?= count($logos) ?>)</a>
  </nav>

  <!-- Text -->
  <form id="text" method="post" action="<?= url('/admin/homepage/content') ?>" class="space-y-4 scroll-mt-4">
    <?= csrfField() ?>
    <?php foreach ($fields as $section => $sectionFields): ?>
      <fieldset class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
        <legend class="px-1 font-serif-heading text-lg font-bold"><?= e($section) ?></legend>
        <div class="grid gap-3 md:grid-cols-2">
          <?php foreach ($sectionFields as $key => [$label, $default, $type]):
            $value = HomepageContent::get($key);
            $changed = $value !== $default;
          ?>
            <label class="block text-xs font-bold uppercase text-neutral-600 <?= $type === 'textarea' ? 'md:col-span-2' : '' ?>">
              <?= e($label) ?><?= $changed ? ' <span class="normal-case" style="color:var(--ke-green)">· edited</span>' : '' ?>
              <?php if ($type === 'textarea'): ?>
                <textarea name="content[<?= e($key) ?>]" rows="2" maxlength="600" placeholder="<?= e($default) ?>" class="mt-1 block w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case"><?= e($value) ?></textarea>
              <?php else: ?>
                <input type="text" name="content[<?= e($key) ?>]" value="<?= e($value) ?>" maxlength="600" placeholder="<?= e($default) ?>" class="mt-1 block w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case">
              <?php endif; ?>
            </label>
          <?php endforeach; ?>
        </div>
      </fieldset>
    <?php endforeach; ?>
    <div class="sticky bottom-0 z-10 bg-white/90 py-3 backdrop-blur-sm">
      <button type="submit" class="btn-primary">Save homepage text</button>
    </div>
  </form>

  <!-- Featured courses -->
  <form id="courses" method="post" action="<?= url('/admin/homepage/courses') ?>" class="space-y-3 scroll-mt-4">
    <?= csrfField() ?>
    <div>
      <h2 class="font-serif-heading text-xl font-bold">Featured courses</h2>
      <p class="mt-1 text-sm font-medium text-neutral-600">
        Tick the courses to show in the "Popular courses" section right under the homepage headline (up to 8 show, newest first).
        With none ticked, that section is hidden.
      </p>
    </div>
    <?php if (!$courses): ?>
      <p class="rounded-xl border border-dashed p-6 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No published courses yet. Organisations providing courses publish them from their tutors' accounts.</p>
    <?php else: ?>
      <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
        <table class="excel-table admin-data-table">
          <thead><tr><th>Show</th><th>Course</th><th>Organisation</th><th>Category</th><th>Who can take it</th><th>Fee</th></tr></thead>
          <tbody>
            <?php foreach ($courses as $c):
              $cover = (string) ($c['cover_image'] ?? '');
              $hasCover = $cover !== '' && preg_match('/\.(jpe?g|png|webp|gif)$/i', $cover);
            ?>
              <tr>
                <td><input type="checkbox" name="course_ids[]" value="<?= (int) $c['id'] ?>" <?= (int) $c['featured_on_home'] === 1 ? 'checked' : '' ?> aria-label="Show <?= e($c['title']) ?> on the homepage"></td>
                <td>
                  <div class="flex items-center gap-3">
                    <?php if ($hasCover): ?>
                      <img src="<?= e(imageUrl($cover)) ?>" alt="" class="h-10 w-16 shrink-0 rounded object-cover">
                    <?php else: ?>
                      <span class="flex h-10 w-16 shrink-0 items-center justify-center rounded text-sm font-black text-white" style="background:var(--ke-green)"><?= e(mb_strtoupper(mb_substr(trim($c['title']), 0, 1))) ?></span>
                    <?php endif; ?>
                    <span class="font-bold"><?= e($c['title']) ?></span>
                  </div>
                </td>
                <td><?= e($c['organisation_name']) ?></td>
                <td><?= e($c['category_name'] ?? '—') ?></td>
                <td><?= $c['visibility'] === 'global' ? 'Every student' : 'That organisation\'s students' ?></td>
                <td><?= (float) $c['enrollment_fee_ksh'] > 0 ? 'Ksh ' . number_format((float) $c['enrollment_fee_ksh']) : 'Free' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <button type="submit" class="btn-primary">Save featured courses</button>
    <?php endif; ?>
  </form>

  <!-- Logos -->
  <section id="logos" class="space-y-3 scroll-mt-4">
    <div>
      <h2 class="font-serif-heading text-xl font-bold">"Trusted by" logos</h2>
      <p class="mt-1 text-sm font-medium text-neutral-600">
        Upload each company's logo (JPG, PNG, WEBP or GIF; a transparent PNG looks best). They show in full colour, in this order.
        Until you add one, the page shows its original placeholder names.
      </p>
    </div>
    <?php if ($logos): ?>
      <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($logos as $i => $logo): ?>
          <li class="flex items-center gap-3 rounded-xl border border-neutral-200 bg-white p-3 shadow-sm">
            <span class="flex h-14 w-28 shrink-0 items-center justify-center rounded bg-neutral-50 p-1">
              <img src="<?= e(imageUrl($logo['image'])) ?>" alt="<?= e($logo['name'] ?: 'Logo') ?>" class="max-h-12 max-w-full object-contain">
            </span>
            <span class="min-w-0 flex-1 truncate text-sm font-bold"><?= e($logo['name'] ?: 'Logo ' . ($i + 1)) ?></span>
            <span class="flex shrink-0 gap-1">
              <?php foreach (['up' => '↑', 'down' => '↓'] as $dir => $arrow):
                $disabled = ($dir === 'up' && $i === 0) || ($dir === 'down' && $i === count($logos) - 1);
              ?>
                <form method="post" action="<?= url('/admin/homepage/logos/' . $i . '/' . $dir) ?>">
                  <?= csrfField() ?>
                  <button type="submit" class="btn-secondary" style="padding:0.2rem 0.5rem;" <?= $disabled ? 'disabled' : '' ?> aria-label="Move <?= $dir ?>"><?= $arrow ?></button>
                </form>
              <?php endforeach; ?>
              <form method="post" action="<?= url('/admin/homepage/logos/' . $i . '/delete') ?>" onsubmit="return confirm('Remove this logo?');">
                <?= csrfField() ?>
                <button type="submit" class="btn-danger" style="padding:0.2rem 0.5rem;" aria-label="Remove">✕</button>
              </form>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <form method="post" action="<?= url('/admin/homepage/logos') ?>" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3 rounded-xl border border-neutral-200 bg-white p-4 shadow-sm">
      <?= csrfField() ?>
      <label class="text-xs font-bold uppercase text-neutral-600">Company name
        <input type="text" name="name" maxlength="80" class="mt-1 block rounded-lg border border-neutral-300 p-2 text-sm normal-case">
      </label>
      <label class="text-xs font-bold uppercase text-neutral-600">Logo image
        <input type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/gif" required class="mt-1 block text-sm normal-case">
      </label>
      <button type="submit" class="btn-primary">Add logo</button>
    </form>
  </section>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
