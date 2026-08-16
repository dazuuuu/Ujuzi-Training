<?php
/** Requires $formRecord, $roles, $errors, $form in scope. */
require __DIR__ . '/../layout-header.php';
$action = $formRecord ? url('/admin/forms/' . (int) $formRecord['id']) : url('/admin/forms');
$fields = $form['fields'] ?? [];
if (!$fields) {
    $fields = [['label' => '', 'field_type' => 'text', 'is_required' => false, 'options' => []]];
}
$fieldTypes = [
    'text' => 'Short text',
    'paragraph' => 'Paragraph',
    'dropdown' => 'Dropdown',
    'number' => 'Number',
    'date' => 'Date',
    'datetime' => 'Date & time',
];
?>

<form method="post" action="<?= $action ?>" class="max-w-4xl space-y-6" id="form-builder">
  <?= csrfField() ?>
  <?php foreach ($errors as $err): ?>
    <div class="rounded-lg border border-rose-300 bg-rose-50 p-3 text-sm font-semibold text-rose-800"><?= e($err) ?></div>
  <?php endforeach; ?>

  <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
    <h2 class="font-serif-heading text-lg font-bold text-[#0a0a0a] border-b border-neutral-100 pb-3">Form</h2>
    <div>
      <label class="text-[11px] font-bold uppercase text-neutral-600">Form title</label>
      <input type="text" name="title" required value="<?= e($form['title'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm focus:border-black focus:outline-none" />
    </div>
    <div>
      <label class="text-[11px] font-bold uppercase text-neutral-600">Description</label>
      <textarea name="description" rows="3" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm focus:border-black focus:outline-none"><?= e($form['description'] ?? '') ?></textarea>
    </div>
    <input type="hidden" name="is_active" value="0" />
    <label class="flex items-center gap-2 text-sm font-bold text-neutral-800">
      <input type="checkbox" name="is_active" value="1" <?= !empty($form['is_active']) ? 'checked' : '' ?> class="h-4 w-4 accent-black" />
      Active — show on assigned profiles
    </label>
  </div>

  <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
    <h2 class="font-serif-heading text-lg font-bold text-[#0a0a0a] border-b border-neutral-100 pb-3">Assign to roles</h2>
    <p class="text-xs font-medium text-neutral-600">The form is saved to the database and attached to every matching user’s profile page.</p>
    <div class="grid gap-2 sm:grid-cols-2">
      <?php foreach ($roles as $role): ?>
        <label class="flex items-center gap-2 rounded-lg border border-neutral-300 bg-white p-2.5 text-sm font-semibold text-neutral-800">
          <input type="checkbox" name="role_ids[]" value="<?= (int) $role['id'] ?>" <?= in_array((int) $role['id'], array_map('intval', $form['role_ids'] ?? []), true) ? 'checked' : '' ?> class="h-4 w-4 accent-black" />
          <?= e($role['name']) ?>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
    <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
      <h2 class="font-serif-heading text-lg font-bold text-[#0a0a0a]">Fields</h2>
      <button type="button" id="add-field" class="rounded-lg border border-neutral-300 px-3 py-2 text-[11px] font-black uppercase tracking-widest text-black hover:border-black">Add field</button>
    </div>
    <div id="fields-list" class="space-y-4">
      <?php foreach ($fields as $index => $field):
        $options = $field['options'] ?? [];
        if (is_array($options)) {
            $options = implode("\n", $options);
        }
        $type = $field['field_type'] ?? 'text';
      ?>
        <div class="field-row rounded-lg border border-neutral-200 p-4 space-y-3" data-field>
          <div class="grid gap-3 sm:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_auto]">
            <div>
              <label class="text-[11px] font-bold uppercase text-neutral-600">Field name</label>
              <input type="text" name="fields[<?= (int) $index ?>][label]" value="<?= e($field['label'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm focus:border-black focus:outline-none" placeholder="e.g. National ID" />
            </div>
            <div>
              <label class="text-[11px] font-bold uppercase text-neutral-600">Field type</label>
              <select name="fields[<?= (int) $index ?>][field_type]" class="field-type mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm focus:border-black focus:outline-none">
                <?php foreach ($fieldTypes as $value => $label): ?>
                  <option value="<?= e($value) ?>" <?= $type === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="flex items-end">
              <button type="button" class="remove-field rounded-lg border border-rose-200 px-3 py-2.5 text-[11px] font-black uppercase tracking-widest text-rose-700">Remove</button>
            </div>
          </div>
          <label class="flex items-center gap-2 text-sm font-bold text-neutral-800">
            <input type="checkbox" name="fields[<?= (int) $index ?>][is_required]" value="1" <?= !empty($field['is_required']) ? 'checked' : '' ?> class="h-4 w-4 accent-black" />
            Required
          </label>
          <div class="options-wrap <?= $type === 'dropdown' ? '' : 'hidden' ?>">
            <label class="text-[11px] font-bold uppercase text-neutral-600">Dropdown options (one per line)</label>
            <textarea name="fields[<?= (int) $index ?>][options]" rows="3" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm font-mono focus:border-black focus:outline-none"><?= e($options) ?></textarea>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="flex items-center gap-3">
    <button type="submit" class="rounded-lg bg-black px-6 py-3 text-xs font-black uppercase tracking-widest text-white hover:bg-neutral-900"><?= $formRecord ? 'Save form' : 'Create form' ?></button>
    <a href="<?= url('/admin/forms') ?>" class="text-xs font-black text-neutral-700 hover:text-black">Cancel</a>
  </div>
</form>

<template id="field-template">
  <div class="field-row rounded-lg border border-neutral-200 p-4 space-y-3" data-field>
    <div class="grid gap-3 sm:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_auto]">
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Field name</label>
        <input type="text" data-name="label" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm focus:border-black focus:outline-none" placeholder="e.g. National ID" />
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Field type</label>
        <select data-name="field_type" class="field-type mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm focus:border-black focus:outline-none">
          <option value="text">Short text</option>
          <option value="paragraph">Paragraph</option>
          <option value="dropdown">Dropdown</option>
          <option value="number">Number</option>
          <option value="date">Date</option>
          <option value="datetime">Date &amp; time</option>
        </select>
      </div>
      <div class="flex items-end">
        <button type="button" class="remove-field rounded-lg border border-rose-200 px-3 py-2.5 text-[11px] font-black uppercase tracking-widest text-rose-700">Remove</button>
      </div>
    </div>
    <label class="flex items-center gap-2 text-sm font-bold text-neutral-800">
      <input type="checkbox" data-name="is_required" value="1" class="h-4 w-4 accent-black" />
      Required
    </label>
    <div class="options-wrap hidden">
      <label class="text-[11px] font-bold uppercase text-neutral-600">Dropdown options (one per line)</label>
      <textarea data-name="options" rows="3" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm font-mono focus:border-black focus:outline-none"></textarea>
    </div>
  </div>
</template>

<script>
(function () {
  var list = document.getElementById('fields-list');
  var template = document.getElementById('field-template');
  var addBtn = document.getElementById('add-field');

  function reindex() {
    Array.prototype.forEach.call(list.querySelectorAll('[data-field]'), function (row, index) {
      row.querySelectorAll('[name], [data-name]').forEach(function (el) {
        var key = el.getAttribute('data-name') || (el.getAttribute('name') || '').replace(/^fields\[\d+\]\[(.+)\]$/, '$1');
        if (!key) return;
        el.setAttribute('name', 'fields[' + index + '][' + key + ']');
      });
    });
  }

  function bindRow(row) {
    var type = row.querySelector('.field-type');
    var options = row.querySelector('.options-wrap');
    function sync() {
      if (options) options.classList.toggle('hidden', type.value !== 'dropdown');
    }
    if (type) type.addEventListener('change', sync);
    sync();
    var remove = row.querySelector('.remove-field');
    if (remove) {
      remove.addEventListener('click', function () {
        if (list.querySelectorAll('[data-field]').length === 1) return;
        row.remove();
        reindex();
      });
    }
  }

  Array.prototype.forEach.call(list.querySelectorAll('[data-field]'), bindRow);
  addBtn.addEventListener('click', function () {
    var node = template.content.firstElementChild.cloneNode(true);
    list.appendChild(node);
    bindRow(node);
    reindex();
  });
  reindex();
})();
</script>

<?php require __DIR__ . '/../layout-footer.php'; ?>
