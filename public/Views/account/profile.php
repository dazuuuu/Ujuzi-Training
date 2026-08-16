<?php
/** Requires $forms, $currentUser in scope. */
require __DIR__ . '/layout-header.php';

if (!function_exists('renderProfileField')) {
    function renderProfileField(array $field, $value): void
    {
        $name = 'answers[' . $field['field_key'] . ']';
        $required = !empty($field['is_required']) ? 'required' : '';
        $class = 'w-full mt-1 bg-white border border-neutral-300 rounded-lg p-2.5 text-sm focus:outline-none focus:border-black';
        echo '<label class="text-[11px] font-bold uppercase text-neutral-600">' . e($field['label']) . ($field['is_required'] ? ' *' : '') . '</label>';
        switch ($field['field_type']) {
            case 'paragraph':
                echo '<textarea name="' . e($name) . '" rows="4" ' . $required . ' class="' . $class . '">' . e((string) $value) . '</textarea>';
                break;
            case 'dropdown':
                echo '<select name="' . e($name) . '" ' . $required . ' class="' . $class . '"><option value="">Choose</option>';
                foreach ($field['options'] as $option) {
                    $selected = (string) $value === (string) $option ? 'selected' : '';
                    echo '<option value="' . e($option) . '" ' . $selected . '>' . e($option) . '</option>';
                }
                echo '</select>';
                break;
            case 'number':
                echo '<input type="number" name="' . e($name) . '" value="' . e((string) $value) . '" ' . $required . ' class="' . $class . '" />';
                break;
            case 'date':
                echo '<input type="date" name="' . e($name) . '" value="' . e((string) $value) . '" ' . $required . ' class="' . $class . '" />';
                break;
            case 'datetime':
                $dt = (string) $value;
                if ($dt !== '' && strpos($dt, 'T') === false) {
                    $dt = str_replace(' ', 'T', substr($dt, 0, 16));
                }
                echo '<input type="datetime-local" name="' . e($name) . '" value="' . e($dt) . '" ' . $required . ' class="' . $class . '" />';
                break;
            default:
                echo '<input type="text" name="' . e($name) . '" value="' . e((string) $value) . '" ' . $required . ' class="' . $class . '" />';
        }
    }
}
?>

<div class="space-y-8">
  <div>
    <p class="text-xs font-black uppercase tracking-widest text-neutral-600">Personal information</p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold text-[#0a0a0a]">My profile</h1>
    <p class="mt-2 text-sm font-medium text-neutral-600">Fill the forms assigned to <?= e($currentUser['role_name']) ?>. You can re-edit your answers any time.</p>
  </div>

  <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
    <p class="text-sm font-black text-black"><?= e(userDisplayName($currentUser)) ?></p>
    <p class="text-xs font-semibold text-neutral-600"><?= e($currentUser['email'] ?: $currentUser['phone'] ?: '') ?> · <?= e($currentUser['organisation_name'] ?? '') ?></p>
  </div>

  <?php if (!$forms): ?>
    <div class="rounded-xl border border-dashed border-neutral-300 p-8 text-sm font-bold text-neutral-600">No forms have been assigned to your role yet.</div>
  <?php endif; ?>

  <?php foreach ($forms as $form):
    $answers = $form['response']['answers'] ?? [];
    $saved = !empty($form['response']['submitted_at']);
  ?>
    <form method="post" action="<?= url('/account/profile') ?>" class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
      <?= csrfField() ?>
      <input type="hidden" name="form_id" value="<?= (int) $form['id'] ?>" />
      <div class="flex items-start justify-between gap-3 border-b border-neutral-100 pb-3">
        <div>
          <h2 class="font-serif-heading text-lg font-bold"><?= e($form['title']) ?></h2>
          <?php if (!empty($form['description'])): ?>
            <p class="mt-1 text-sm font-medium text-neutral-600"><?= e($form['description']) ?></p>
          <?php endif; ?>
        </div>
        <span class="rounded-full border px-2 py-1 text-[10px] font-black uppercase <?= $saved ? 'border-black bg-black text-white' : 'border-neutral-300 text-neutral-700' ?>">
          <?= $saved ? 'Saved' : 'Not filled' ?>
        </span>
      </div>
      <?php foreach ($form['fields'] as $field): ?>
        <div>
          <?php renderProfileField($field, $answers[$field['field_key']] ?? ''); ?>
        </div>
      <?php endforeach; ?>
      <button type="submit" class="rounded-lg bg-black px-6 py-3 text-xs font-black uppercase tracking-widest text-white hover:bg-neutral-900"><?= $saved ? 'Update details' : 'Save details' ?></button>
    </form>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
