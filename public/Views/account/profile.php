<?php
/** Requires $forms, $currentUser in scope. */
require __DIR__ . '/layout-header.php';
?>

<div class="profile-page">
  <div class="profile-heading">
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Personal information</p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold">My profile</h1>
    <p class="mt-2 text-sm font-medium" style="color:var(--ke-muted)">This is the registration form assigned to <?= e($currentUser['role_name']) ?>. Fill it in to finish signing in. You can re-edit your answers later.</p>
  </div>

  <div class="profile-layout">
    <div class="profile-form-column">
      <?php if (!$forms): ?>
        <div class="rounded-xl border border-dashed p-8 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No forms have been assigned to your role yet.</div>
      <?php endif; ?>

      <?php foreach ($forms as $form):
    $answers = $form['response']['answers'] ?? [];
    $saved = !empty($form['response']['submitted_at']);
    $hasFiles = false;
    foreach ($form['fields'] as $field) {
        if (\App\Models\FormFieldTypes::isFile($field['field_type'])) {
            $hasFiles = true;
            break;
        }
    }
      ?>
        <form method="post" action="<?= url('/account/profile') ?>" <?= $hasFiles ? 'enctype="multipart/form-data"' : '' ?> class="profile-form-card rounded-xl border bg-white p-4 shadow-sm space-y-4" style="border-color:var(--ke-line)">
      <?= csrfField() ?>
      <input type="hidden" name="form_id" value="<?= (int) $form['id'] ?>" />
      <div class="flex items-start justify-between gap-3 border-b border-neutral-100 pb-3">
        <div>
          <h2 class="font-serif-heading text-lg font-bold"><?= e($form['title']) ?></h2>
          <?php if (!empty($form['description'])): ?>
            <p class="mt-1 text-sm font-medium" style="color:var(--ke-muted)"><?= e($form['description']) ?></p>
          <?php endif; ?>
        </div>
        <span class="rounded-full px-2 py-1 text-[10px] font-black uppercase <?= $saved ? 'text-white' : '' ?>" style="<?= $saved ? 'background:var(--ke-green)' : 'background:#e8f5ee;color:var(--ke-green-dark);border:1px solid var(--ke-green)' ?>">
          <?= $saved ? 'Saved' : 'Not filled' ?>
        </span>
      </div>
      <?php foreach ($form['fields'] as $field): ?>
        <div>
          <?php
            $value = $answers[$field['field_key']] ?? '';
            require __DIR__ . '/partials/field.php';
          ?>
        </div>
      <?php endforeach; ?>
      <button type="submit" class="btn-primary"><?= $saved ? 'Update details' : 'Save details' ?></button>
        </form>
      <?php endforeach; ?>
    </div>

    <aside class="profile-details-column">
      <section class="rounded-xl border bg-white p-4 shadow-sm" style="border-color:var(--ke-line)">
        <h2 class="font-serif-heading text-lg font-bold">Account details</h2>
        <p class="mt-1 text-xs font-semibold" style="color:var(--ke-muted)">These details are used when you sign in. You can update them here.</p>
        <form method="post" action="<?= url('/account/profile') ?>" class="mt-4 space-y-3">
          <?= csrfField() ?>
          <input type="hidden" name="account_update" value="1" />
          <div>
            <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Email</label>
            <input type="email" name="email" value="<?= e($currentUser['email'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" />
          </div>
          <div>
            <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Phone</label>
            <input type="tel" name="phone" value="<?= e($currentUser['phone'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" />
          </div>
          <button type="submit" class="btn-primary w-full">Update sign-in details</button>
        </form>
        <div class="mt-5 border-t pt-4" style="border-color:var(--ke-line)">
          <dl class="space-y-3">
            <div><dt class="text-[10px] font-bold uppercase" style="color:var(--ke-muted)">Name</dt><dd class="mt-1 text-sm font-bold"><?= e(userDisplayName($currentUser)) ?></dd></div>
            <div><dt class="text-[10px] font-bold uppercase" style="color:var(--ke-muted)">Role</dt><dd class="mt-1 text-sm font-bold"><?= e($currentUser['role_name'] ?? '') ?></dd></div>
            <div><dt class="text-[10px] font-bold uppercase" style="color:var(--ke-muted)">Organisation</dt><dd class="mt-1 text-sm font-bold"><?= e($currentUser['organisation_name'] ?? 'Not selected') ?></dd></div>
          </dl>
        </div>
      </section>
    </aside>
  </div>
</div>

<script>
(function () {
  document.addEventListener('change', function (event) {
    var target = event.target;
    var field = target.closest('.js-choice-field');
    if (target.classList.contains('js-has-other') && field) {
      var selected = false;
      if (target.tagName === 'SELECT') {
        selected = target.value === '__other__';
      } else if (target.type === 'radio') {
        var checked = field.querySelector('input[type="radio"]:checked');
        selected = !!(checked && checked.value === '__other__');
      } else if (target.type === 'checkbox') {
        selected = target.checked && target.value === '__other__';
      }
      var otherInput = field.querySelector('.js-other-input');
      if (otherInput) otherInput.classList.toggle('hidden', !selected);
    }
    if (target.classList.contains('js-select-all') && field) {
      field.querySelectorAll('.js-choice-box').forEach(function (box) {
        if (box.value !== '__other__') box.checked = target.checked;
      });
    }
    if (target.classList.contains('js-range')) {
      var label = target.parentElement.querySelector('.js-range-label');
      if (label) {
        label.textContent = 'Value: ' + target.value + ' (range ' + target.min + ' – ' + target.max + ')';
      }
    }
  });

  document.addEventListener('click', function (event) {
    if (event.target.classList.contains('add-list-row')) {
      var key = event.target.getAttribute('data-list-add');
      var holder = document.querySelector('[data-list="' + key + '"]');
      if (!holder) return;
      var first = holder.querySelector('.list-row');
      var copy = first.cloneNode(true);
      var input = copy.querySelector('input');
      if (input) input.value = '';
      holder.appendChild(copy);
    }
    if (event.target.classList.contains('remove-list-row')) {
      var holder = event.target.closest('[data-list]');
      if (!holder || holder.querySelectorAll('.list-row').length === 1) return;
      event.target.closest('.list-row').remove();
    }
    if (event.target.classList.contains('add-branch-row')) {
      var key = event.target.getAttribute('data-branches-add');
      var holder = document.querySelector('[data-branches="' + key + '"]');
      if (!holder) return;
      var first = holder.querySelector('.branch-row');
      if (!first) return;
      var copy = first.cloneNode(true);
      copy.querySelectorAll('input').forEach(function (input) {
        if (input.type === 'hidden') {
          input.remove();
          return;
        }
        input.value = '';
      });
      holder.appendChild(copy);
      holder.querySelectorAll('.branch-row').forEach(function (row, index) {
        row.querySelectorAll('input[name]').forEach(function (input) {
          input.name = input.name.replace(/answers\[[^\]]+\]\[\d+\]/, 'answers[' + key + '][' + index + ']');
        });
      });
    }
    if (event.target.classList.contains('remove-branch-row')) {
      var holder = event.target.closest('[data-branches]');
      if (!holder || holder.querySelectorAll('.branch-row').length === 1) return;
      event.target.closest('.branch-row').remove();
      var key = holder.getAttribute('data-branches');
      holder.querySelectorAll('.branch-row').forEach(function (row, index) {
        row.querySelectorAll('input[name]').forEach(function (input) {
          input.name = input.name.replace(/answers\[[^\]]+\]\[\d+\]/, 'answers[' + key + '][' + index + ']');
        });
      });
    }
  });
})();
</script>

<?php require __DIR__ . '/layout-footer.php'; ?>
