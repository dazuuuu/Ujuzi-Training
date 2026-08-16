<?php
/** Requires $formRecord, $roles, $errors, $form in scope. */
use App\Models\FormFieldTypes;

require __DIR__ . '/../layout-header.php';
$action = $formRecord ? url('/admin/forms/' . (int) $formRecord['id']) : url('/admin/forms');
$fields = $form['fields'] ?? [];
if (!$fields) {
    $fields = [['label' => '', 'field_type' => 'text', 'is_required' => false, 'options' => ['', ''], 'placeholder' => '', 'help_text' => '']];
}
$fieldGroups = FormFieldTypes::groups();
$choiceTypes = ['dropdown', 'multiselect', 'radio', 'checkboxes'];

function fieldChoices(array $field): array
{
    $choices = $field['choices'] ?? $field['options'] ?? [];
    if (!is_array($choices)) {
        $choices = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $choices) ?: [])));
    }
    $choices = array_values($choices);
    if (count($choices) < 2) {
        $choices = array_pad($choices, 2, '');
    }
    return $choices;
}
?>

<form method="post" action="<?= $action ?>" class="max-w-4xl space-y-6" id="form-builder">
  <?= csrfField() ?>
  <?php foreach ($errors as $err): ?>
    <div class="flash-error"><?= e($err) ?></div>
  <?php endforeach; ?>

  <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
    <h2 class="font-serif-heading text-lg font-bold border-b border-neutral-100 pb-3">Form</h2>
    <div>
      <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Form title</label>
      <input type="text" name="title" required value="<?= e($form['title'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" />
    </div>
    <div>
      <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Description</label>
      <textarea name="description" rows="3" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm"><?= e($form['description'] ?? '') ?></textarea>
    </div>
    <input type="hidden" name="is_active" value="0" />
    <label class="flex items-center gap-2 text-sm font-bold">
      <input type="checkbox" name="is_active" value="1" <?= !empty($form['is_active']) ? 'checked' : '' ?> class="h-4 w-4" />
      Active — show on assigned profiles
    </label>
  </div>

  <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
    <h2 class="font-serif-heading text-lg font-bold border-b border-neutral-100 pb-3">Assign to roles</h2>
    <p class="text-xs font-medium" style="color:var(--ke-muted)">The form is saved and attached to every matching user’s profile page.</p>
    <div class="grid gap-2 sm:grid-cols-2">
      <?php foreach ($roles as $role): ?>
        <label class="flex items-center gap-2 rounded-lg border border-neutral-300 bg-white p-2.5 text-sm font-semibold">
          <input type="checkbox" name="role_ids[]" value="<?= (int) $role['id'] ?>" <?= in_array((int) $role['id'], array_map('intval', $form['role_ids'] ?? []), true) ? 'checked' : '' ?> class="h-4 w-4" />
          <?= e($role['name']) ?>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
    <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
      <div>
        <h2 class="font-serif-heading text-lg font-bold">Fields</h2>
        <p class="text-xs font-medium mt-1" style="color:var(--ke-muted)">Pick a type the way WordPress form builders do. Dropdowns, radios, and checkboxes need the choice values you want people to pick — you can change them later.</p>
      </div>
      <button type="button" id="add-field" class="btn-secondary">Add field</button>
    </div>
    <div id="fields-list" class="space-y-4">
      <?php foreach ($fields as $index => $field):
        $type = $field['field_type'] ?? 'text';
        $choices = fieldChoices($field);
        $needsChoices = in_array($type, $choiceTypes, true);
        $rangeMin = $field['range_min'] ?? '';
        $rangeMax = $field['range_max'] ?? '';
        if ($type === 'range' && $rangeMin === '') {
            $rangeMin = '0';
        }
        if ($type === 'range' && $rangeMax === '') {
            $rangeMax = '100';
        }
        if ($type === 'rating' && $rangeMin === '') {
            $rangeMin = '1';
        }
        if ($type === 'rating' && $rangeMax === '') {
            $rangeMax = '5';
        }
        $columns = max(1, min(3, (int) ($field['columns'] ?? 1)));
      ?>
        <div class="field-row rounded-lg border p-4 space-y-3" data-field style="border-color:var(--ke-line)">
          <input type="hidden" name="fields[<?= (int) $index ?>][field_key]" value="<?= e($field['field_key'] ?? '') ?>" data-name="field_key" />
          <div class="grid gap-3 sm:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_auto]">
            <div>
              <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Field name</label>
              <input type="text" name="fields[<?= (int) $index ?>][label]" value="<?= e($field['label'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" placeholder="e.g. National ID" />
            </div>
            <div>
              <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Field type</label>
              <select name="fields[<?= (int) $index ?>][field_type]" class="field-type mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm">
                <?php foreach ($fieldGroups as $group => $types): ?>
                  <optgroup label="<?= e($group) ?>">
                    <?php foreach ($types as $value => $label): ?>
                      <option value="<?= e($value) ?>" <?= $type === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="flex items-end">
              <button type="button" class="remove-field btn-danger">Remove</button>
            </div>
          </div>
          <div class="grid gap-3 sm:grid-cols-2">
            <div class="placeholder-wrap <?= FormFieldTypes::needsPlaceholder($type) ? '' : 'hidden' ?>">
              <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Placeholder</label>
              <input type="text" name="fields[<?= (int) $index ?>][placeholder]" value="<?= e($field['placeholder'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" />
            </div>
            <div>
              <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Help text</label>
              <input type="text" name="fields[<?= (int) $index ?>][help_text]" value="<?= e($field['help_text'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" placeholder="Shown under the field" />
            </div>
          </div>
          <label class="required-wrap flex items-center gap-2 text-sm font-bold <?= FormFieldTypes::isLayout($type) ? 'hidden' : '' ?>">
            <input type="checkbox" name="fields[<?= (int) $index ?>][is_required]" value="1" <?= !empty($field['is_required']) ? 'checked' : '' ?> class="h-4 w-4" />
            Required
          </label>
          <div class="choices-wrap <?= $needsChoices ? '' : 'hidden' ?>">
            <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Choice values (what the user can pick)</label>
            <p class="field-hint">Add each option. You can change these later from this same form.</p>
            <div class="choices-list mt-2">
              <?php foreach ($choices as $choice): ?>
                <div class="choice-row">
                  <input type="text" data-choice="1" name="fields[<?= (int) $index ?>][choices][]" value="<?= e($choice) ?>" class="rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" placeholder="e.g. Nairobi" />
                  <button type="button" class="remove-choice btn-danger" style="padding:0.4rem 0.7rem;">Remove</button>
                </div>
              <?php endforeach; ?>
            </div>
            <button type="button" class="add-choice btn-secondary mt-2" style="padding:0.4rem 0.75rem;">Add choice</button>
            <div class="choice-settings mt-3 space-y-3 rounded-lg p-3" style="background:#f6f7f4;border:1px solid var(--ke-line)">
              <label class="allow-other-wrap flex items-center gap-2 text-sm font-bold <?= FormFieldTypes::allowsOther($type) ? '' : 'hidden' ?>">
                <input type="checkbox" name="fields[<?= (int) $index ?>][allow_other]" value="1" <?= !empty($field['allow_other']) ? 'checked' : '' ?> class="h-4 w-4" data-name="allow_other" />
                Allow “Other” with a write-in box
              </label>
              <div class="columns-wrap <?= FormFieldTypes::hasColumns($type) ? '' : 'hidden' ?>">
                <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Layout</label>
                <select name="fields[<?= (int) $index ?>][columns]" data-name="columns" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm">
                  <option value="1" <?= $columns === 1 ? 'selected' : '' ?>>One column</option>
                  <option value="2" <?= $columns === 2 ? 'selected' : '' ?>>Two columns</option>
                  <option value="3" <?= $columns === 3 ? 'selected' : '' ?>>Three columns</option>
                </select>
              </div>
              <div class="minmax-wrap grid gap-3 sm:grid-cols-2 <?= FormFieldTypes::hasMinMaxSelect($type) ? '' : 'hidden' ?>">
                <div>
                  <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Minimum selections</label>
                  <input type="number" min="0" name="fields[<?= (int) $index ?>][min_select]" data-name="min_select" value="<?= e((string) ($field['min_select'] ?? 0)) ?>" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" />
                </div>
                <div>
                  <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Maximum selections</label>
                  <input type="number" min="0" name="fields[<?= (int) $index ?>][max_select]" data-name="max_select" value="<?= e((string) ($field['max_select'] ?? 0)) ?>" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" />
                  <p class="field-hint">0 means no limit.</p>
                </div>
              </div>
              <label class="select-all-wrap flex items-center gap-2 text-sm font-bold <?= $type === 'checkboxes' ? '' : 'hidden' ?>">
                <input type="checkbox" name="fields[<?= (int) $index ?>][select_all]" value="1" <?= !empty($field['select_all']) ? 'checked' : '' ?> class="h-4 w-4" data-name="select_all" />
                Show “Select all”
              </label>
            </div>
          </div>
          <div class="range-wrap grid gap-3 sm:grid-cols-2 <?= FormFieldTypes::needsRange($type) ? '' : 'hidden' ?>">
            <div>
              <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Minimum</label>
              <input type="number" name="fields[<?= (int) $index ?>][range_min]" value="<?= e((string) $rangeMin) ?>" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" />
            </div>
            <div>
              <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Maximum</label>
              <input type="number" name="fields[<?= (int) $index ?>][range_max]" value="<?= e((string) $rangeMax) ?>" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" />
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="flex items-center gap-3">
    <button type="submit" class="btn-primary"><?= $formRecord ? 'Save form' : 'Create form' ?></button>
    <a href="<?= url('/admin/forms') ?>" class="btn-secondary">Cancel</a>
  </div>
</form>

<template id="field-template">
  <div class="field-row rounded-lg border p-4 space-y-3" data-field style="border-color:var(--ke-line)">
    <input type="hidden" data-name="field_key" value="" />
    <div class="grid gap-3 sm:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_auto]">
      <div>
        <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Field name</label>
        <input type="text" data-name="label" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" placeholder="e.g. National ID" />
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Field type</label>
        <select data-name="field_type" class="field-type mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm">
          <?php foreach ($fieldGroups as $group => $types): ?>
            <optgroup label="<?= e($group) ?>">
              <?php foreach ($types as $value => $label): ?>
                <option value="<?= e($value) ?>"><?= e($label) ?></option>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="flex items-end">
        <button type="button" class="remove-field btn-danger">Remove</button>
      </div>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
      <div class="placeholder-wrap">
        <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Placeholder</label>
        <input type="text" data-name="placeholder" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" />
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Help text</label>
        <input type="text" data-name="help_text" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" placeholder="Shown under the field" />
      </div>
    </div>
    <label class="required-wrap flex items-center gap-2 text-sm font-bold">
      <input type="checkbox" data-name="is_required" value="1" class="h-4 w-4" />
      Required
    </label>
    <div class="choices-wrap hidden">
      <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Choice values (what the user can pick)</label>
      <p class="field-hint">Add each option. You can change these later from this same form.</p>
      <div class="choices-list mt-2">
        <div class="choice-row">
          <input type="text" data-choice="1" class="rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" placeholder="e.g. Nairobi" />
          <button type="button" class="remove-choice btn-danger" style="padding:0.4rem 0.7rem;">Remove</button>
        </div>
        <div class="choice-row">
          <input type="text" data-choice="1" class="rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" placeholder="e.g. Mombasa" />
          <button type="button" class="remove-choice btn-danger" style="padding:0.4rem 0.7rem;">Remove</button>
        </div>
      </div>
      <button type="button" class="add-choice btn-secondary mt-2" style="padding:0.4rem 0.75rem;">Add choice</button>
      <div class="choice-settings mt-3 space-y-3 rounded-lg p-3" style="background:#f6f7f4;border:1px solid var(--ke-line)">
        <label class="allow-other-wrap flex items-center gap-2 text-sm font-bold">
          <input type="checkbox" value="1" class="h-4 w-4" data-name="allow_other" />
          Allow “Other” with a write-in box
        </label>
        <div class="columns-wrap hidden">
          <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Layout</label>
          <select data-name="columns" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm">
            <option value="1">One column</option>
            <option value="2">Two columns</option>
            <option value="3">Three columns</option>
          </select>
        </div>
        <div class="minmax-wrap grid gap-3 sm:grid-cols-2 hidden">
          <div>
            <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Minimum selections</label>
            <input type="number" min="0" data-name="min_select" value="0" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" />
          </div>
          <div>
            <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Maximum selections</label>
            <input type="number" min="0" data-name="max_select" value="0" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" />
            <p class="field-hint">0 means no limit.</p>
          </div>
        </div>
        <label class="select-all-wrap flex items-center gap-2 text-sm font-bold hidden">
          <input type="checkbox" value="1" class="h-4 w-4" data-name="select_all" />
          Show “Select all”
        </label>
      </div>
    </div>
    <div class="range-wrap grid gap-3 sm:grid-cols-2 hidden">
      <div>
        <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Minimum</label>
        <input type="number" data-name="range_min" value="" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" />
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Maximum</label>
        <input type="number" data-name="range_max" value="" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" />
      </div>
    </div>
  </div>
</template>

<script>
(function () {
  var list = document.getElementById('fields-list');
  var template = document.getElementById('field-template');
  var addBtn = document.getElementById('add-field');
  var choiceTypes = { dropdown: 1, multiselect: 1, radio: 1, checkboxes: 1 };
  var placeholderTypes = { text: 1, paragraph: 1, email: 1, phone: 1, url: 1, number: 1, password: 1, hidden: 1, signature: 1, list: 1 };
  var layoutTypes = { heading: 1, instructions: 1 };
  var rangeTypes = { range: 1, number: 1, rating: 1 };
  var otherTypes = { dropdown: 1, radio: 1, checkboxes: 1 };
  var columnTypes = { radio: 1, checkboxes: 1 };
  var minmaxTypes = { checkboxes: 1, multiselect: 1 };
  var rangeDefaults = { range: ['0', '100'], rating: ['1', '5'], number: ['', ''] };

  function reindex() {
    Array.prototype.forEach.call(list.querySelectorAll('[data-field]'), function (row, index) {
      row.querySelectorAll('[data-choice]').forEach(function (el) {
        el.setAttribute('name', 'fields[' + index + '][choices][]');
      });
      row.querySelectorAll('[name], [data-name]').forEach(function (el) {
        if (el.hasAttribute('data-choice')) return;
        var key = el.getAttribute('data-name') || (el.getAttribute('name') || '').replace(/^fields\[\d+\]\[([^\]]+)\](?:\[\])?$/, '$1');
        if (!key || key === 'choices') return;
        el.setAttribute('name', 'fields[' + index + '][' + key + ']');
      });
    });
  }

  function syncRow(row) {
    var type = row.querySelector('.field-type');
    var value = type ? type.value : 'text';
    var choices = row.querySelector('.choices-wrap');
    var range = row.querySelector('.range-wrap');
    var placeholder = row.querySelector('.placeholder-wrap');
    var required = row.querySelector('.required-wrap');
    var allowOther = row.querySelector('.allow-other-wrap');
    var columns = row.querySelector('.columns-wrap');
    var minmax = row.querySelector('.minmax-wrap');
    var selectAll = row.querySelector('.select-all-wrap');
    if (choices) choices.classList.toggle('hidden', !choiceTypes[value]);
    if (range) range.classList.toggle('hidden', !rangeTypes[value]);
    if (placeholder) placeholder.classList.toggle('hidden', !placeholderTypes[value]);
    if (required) required.classList.toggle('hidden', !!layoutTypes[value]);
    if (allowOther) allowOther.classList.toggle('hidden', !otherTypes[value]);
    if (columns) columns.classList.toggle('hidden', !columnTypes[value]);
    if (minmax) minmax.classList.toggle('hidden', !minmaxTypes[value]);
    if (selectAll) selectAll.classList.toggle('hidden', value !== 'checkboxes');
    if (range && rangeTypes[value] && rangeDefaults[value]) {
      var minInput = range.querySelector('[name$="[range_min]"], [data-name="range_min"]');
      var maxInput = range.querySelector('[name$="[range_max]"], [data-name="range_max"]');
      if (minInput && minInput.value === '' && rangeDefaults[value][0] !== '') minInput.value = rangeDefaults[value][0];
      if (maxInput && maxInput.value === '' && rangeDefaults[value][1] !== '') maxInput.value = rangeDefaults[value][1];
    }
  }

  function bindRow(row) {
    var type = row.querySelector('.field-type');
    if (type) type.addEventListener('change', function () { syncRow(row); });
    syncRow(row);

    var remove = row.querySelector('.remove-field');
    if (remove) {
      remove.addEventListener('click', function () {
        if (list.querySelectorAll('[data-field]').length === 1) return;
        row.remove();
        reindex();
      });
    }

    row.addEventListener('click', function (event) {
      if (event.target.classList.contains('add-choice')) {
        var holder = row.querySelector('.choices-list');
        var first = holder.querySelector('.choice-row');
        var copy = first.cloneNode(true);
        var input = copy.querySelector('input');
        if (input) input.value = '';
        holder.appendChild(copy);
        reindex();
      }
      if (event.target.classList.contains('remove-choice')) {
        var holder = row.querySelector('.choices-list');
        if (holder.querySelectorAll('.choice-row').length === 1) return;
        event.target.closest('.choice-row').remove();
        reindex();
      }
    });
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
