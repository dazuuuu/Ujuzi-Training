<?php
/** Requires $organisations (each with 'categories') and $selectedCategoryIds. */
require __DIR__ . '/../layout-header.php';
$selected = array_fill_keys(array_map('intval', $selectedCategoryIds ?? []), true);
?>

<style>
  .aud-switch { position: relative; display: inline-flex; align-items: center; cursor: pointer; flex-shrink: 0; }
  .aud-switch input { position: absolute; opacity: 0; width: 0; height: 0; }
  .aud-switch .aud-track { width: 2.5rem; height: 1.4rem; border-radius: 999px; background: #d4d4d4; transition: background .15s; position: relative; }
  .aud-switch .aud-track::after { content: ""; position: absolute; top: .2rem; left: .2rem; width: 1rem; height: 1rem; border-radius: 999px; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.25); transition: transform .15s; }
  .aud-switch input:checked + .aud-track { background: var(--ke-green); }
  .aud-switch input:checked + .aud-track::after { transform: translateX(1.1rem); }
  .aud-switch input:focus-visible + .aud-track { outline: 2px solid var(--ke-green); outline-offset: 2px; }
  .aud-switch.is-small .aud-track { width: 2.1rem; height: 1.2rem; }
  .aud-switch.is-small .aud-track::after { width: .8rem; height: .8rem; }
  .aud-switch.is-small input:checked + .aud-track::after { transform: translateX(.9rem); }
</style>

<div class="space-y-6">
  <section>
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Attachment</p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold">Where you appear</h1>
    <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600">
      Turn on each organisation providing courses whose students you accept for attachment, then choose which of its course categories.
      There is no approval step — as soon as you save, students enrolled in a course under those categories see you under Attachment and can send you a request.
    </p>
  </section>

  <?php if (!$organisations): ?>
    <section class="rounded-xl border border-dashed p-6 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">
      No organisation providing courses has published categories yet. Check back once they have.
    </section>
  <?php else: ?>
    <form method="post" action="<?= url('/account/attachment-audience') ?>" class="space-y-4" id="audience-form">
      <?= csrfField() ?>

      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <input type="search" id="audience-filter" placeholder="Search organisations or categories…" class="w-full sm:max-w-sm rounded-lg border border-neutral-300 p-2 text-sm" aria-label="Search organisations or categories">
        <p class="text-xs font-bold text-neutral-600"><span id="audience-count"><?= count($selected) ?></span> categor<span id="audience-plural"><?= count($selected) === 1 ? 'y' : 'ies' ?></span> on</p>
      </div>

      <?php foreach ($organisations as $organisation):
        $orgId = (int) $organisation['id'];
        $orgOn = false;
        foreach ($organisation['categories'] as $category) {
            if (!empty($selected[(int) $category['id']])) { $orgOn = true; break; }
        }
      ?>
        <section class="rounded-xl border border-neutral-200 bg-white shadow-sm audience-org" data-search="<?= e(strtolower($organisation['name'] . ' ' . implode(' ', array_column($organisation['categories'], 'name')))) ?>">
          <div class="flex items-center justify-between gap-3 p-4">
            <div>
              <h2 class="font-bold text-gray-800"><?= e($organisation['name']) ?></h2>
              <p class="text-[11px] font-bold uppercase tracking-widest text-gray-500"><?= count($organisation['categories']) ?> categor<?= count($organisation['categories']) === 1 ? 'y' : 'ies' ?></p>
            </div>
            <label class="aud-switch" title="Accept students from this organisation">
              <input type="checkbox" class="org-toggle" data-org="<?= $orgId ?>" <?= $orgOn ? 'checked' : '' ?> aria-label="Accept students from <?= e($organisation['name']) ?>">
              <span class="aud-track"></span>
            </label>
          </div>
          <div class="border-t border-gray-100 p-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3 org-categories" data-org="<?= $orgId ?>" <?= $orgOn ? '' : 'hidden' ?>>
            <?php foreach ($organisation['categories'] as $category): $catId = (int) $category['id']; ?>
              <label class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                <span class="text-sm font-semibold text-gray-700"><?= e($category['name']) ?></span>
                <span class="aud-switch is-small">
                  <input type="checkbox" name="category_ids[]" value="<?= $catId ?>" class="cat-toggle" data-org="<?= $orgId ?>" <?= !empty($selected[$catId]) ? 'checked' : '' ?>>
                  <span class="aud-track"></span>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endforeach; ?>

      <div class="sticky bottom-0 bg-white/90 py-3 backdrop-blur">
        <button type="submit" class="btn-primary">Save where I appear</button>
      </div>
    </form>
  <?php endif; ?>
</div>

<script>
(function () {
  var form = document.getElementById('audience-form');
  if (!form) return;
  var cats = function (org) { return form.querySelectorAll('.cat-toggle[data-org="' + org + '"]'); };
  var panel = function (org) { return form.querySelector('.org-categories[data-org="' + org + '"]'); };
  var orgToggle = function (org) { return form.querySelector('.org-toggle[data-org="' + org + '"]'); };

  function recount() {
    var n = form.querySelectorAll('.cat-toggle:checked').length;
    document.getElementById('audience-count').textContent = n;
    document.getElementById('audience-plural').textContent = n === 1 ? 'y' : 'ies';
  }

  // Turning an organisation on opens its categories with all of them on;
  // turning it off clears them.
  form.querySelectorAll('.org-toggle').forEach(function (toggle) {
    toggle.addEventListener('change', function () {
      var org = toggle.dataset.org;
      panel(org).hidden = !toggle.checked;
      cats(org).forEach(function (c) { c.checked = toggle.checked; });
      recount();
    });
  });

  // Turning off the last category of an organisation turns the organisation off.
  form.querySelectorAll('.cat-toggle').forEach(function (toggle) {
    toggle.addEventListener('change', function () {
      var org = toggle.dataset.org;
      var anyOn = Array.prototype.some.call(cats(org), function (c) { return c.checked; });
      if (!anyOn) { orgToggle(org).checked = false; panel(org).hidden = true; }
      recount();
    });
  });

  var filter = document.getElementById('audience-filter');
  filter.addEventListener('input', function () {
    var q = filter.value.trim().toLowerCase();
    form.querySelectorAll('.audience-org').forEach(function (card) {
      card.hidden = q !== '' && card.dataset.search.indexOf(q) === -1;
    });
  });
})();
</script>

<?php require __DIR__ . '/../layout-footer.php'; ?>
