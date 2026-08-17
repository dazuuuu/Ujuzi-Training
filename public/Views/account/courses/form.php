<?php
$forms = $forms ?? [];
$course = $course ?? null;
$errors = $errors ?? [];
$action = $course ? url('/account/courses/' . (int) $course['id']) : url('/account/courses');
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-8">
  <div>
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Courses</p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold"><?= $course ? 'Edit course' : 'Create a course' ?></h1>
    <p class="mt-2 text-sm font-medium" style="color:var(--ke-muted)">Fill the form Super Admin assigned to tutors. Categories listed here belong only to organisations that have approved you.</p>
  </div>

  <?php foreach ($errors as $err): ?>
    <div class="rounded-lg border border-rose-300 bg-rose-50 p-3 text-sm font-semibold text-rose-800"><?= e($err) ?></div>
  <?php endforeach; ?>

  <?php if (!$forms): ?>
    <div class="rounded-xl border border-dashed p-8 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No course form is assigned to your role yet. Ask Super Admin to create a form, set its purpose to Course creation, and assign it to Trainer / Tutor / Teacher.</div>
  <?php endif; ?>

  <?php foreach ($forms as $form):
    $answers = $form['answers'] ?? [];
    $hasFiles = false;
    foreach ($form['fields'] as $field) {
        if (\App\Models\FormFieldTypes::isFile($field['field_type'] ?? '')) {
            $hasFiles = true;
            break;
        }
    }
  ?>
    <form method="post" action="<?= $action ?>" <?= $hasFiles ? 'enctype="multipart/form-data"' : '' ?> class="rounded-xl border bg-white p-6 shadow-sm space-y-4" style="border-color:var(--ke-line)">
      <?= csrfField() ?>
      <input type="hidden" name="form_id" value="<?= (int) $form['id'] ?>" />
      <div class="border-b border-neutral-100 pb-3">
        <h2 class="font-serif-heading text-lg font-bold"><?= e($form['title']) ?></h2>
        <?php if (!empty($form['description'])): ?>
          <p class="mt-1 text-sm font-medium" style="color:var(--ke-muted)"><?= e($form['description']) ?></p>
        <?php endif; ?>
      </div>
      <?php foreach ($form['fields'] as $field): ?>
        <div>
          <?php
            $value = $answers[$field['field_key']] ?? '';
            require dirname(__DIR__) . '/partials/field.php';
          ?>
        </div>
      <?php endforeach; ?>
      <input type="hidden" name="is_published" value="0" />
      <label class="flex items-center gap-2 text-sm font-bold">
        <input type="checkbox" name="is_published" value="1" <?= empty($course) || !empty($course['is_published']) ? 'checked' : '' ?> class="h-4 w-4" />
        Published — visible to your organisation
      </label>
      <button type="submit" class="btn-primary"><?= $course ? 'Save course' : 'Create course' ?></button>
    </form>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
