<?php
/** Requires $request, $person, $forms, $categories in scope. */
require __DIR__ . '/../layout-header.php';
$pending = ($request['status'] ?? '') === 'pending';
?>

<div class="space-y-6">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Student request</p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold"><?= e(userDisplayName($person)) ?></h1>
      <p class="mt-1 text-sm font-medium text-neutral-600"><?= e(implode(' · ', array_filter([$person['phone'] ?? '', $person['email'] ?? ''])) ?: '') ?><?= !empty($request['branch_title']) ? ' · Branch: ' . e($request['branch_title']) : '' ?> · <?= e(ucfirst((string) $request['status'])) ?></p>
    </div>
    <a href="<?= url('/account/trainer-requests') ?>" class="btn-secondary">Back to requests</a>
  </section>

  <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-3">
    <h2 class="font-serif-heading text-lg font-bold">Categories of courses</h2>
    <p class="text-xs font-semibold" style="color:var(--ke-muted)">These are the categories the student asked for. Remove any, or add another. The student will only see courses in the categories you approve.</p>
    <form method="post" action="<?= url('/account/trainer-requests/' . (int) $request['id'] . '/approve-student') ?>" class="space-y-4">
      <?= csrfField() ?>
      <?php
        $allCategories = $categories;
        $selectedCategoryIds = $request['category_ids'];
        require __DIR__ . '/../partials/category-picker.php';
      ?>
      <?php if ($pending): ?>
        <div class="flex flex-wrap gap-2">
          <button type="submit" class="btn-primary">Approve</button>
        </div>
      <?php endif; ?>
    </form>
    <?php if ($pending): ?>
      <form method="post" action="<?= url('/account/trainer-requests/' . (int) $request['id'] . '/reject') ?>" onsubmit="return confirm('Reject this student?');">
        <?= csrfField() ?>
        <button type="submit" class="btn-danger">Reject</button>
      </form>
    <?php endif; ?>
  </section>

  <?php foreach ($forms as $form):
    $answers = $form['response']['answers'] ?? [];
  ?>
    <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
      <h2 class="font-serif-heading text-lg font-bold border-b border-neutral-100 pb-3"><?= e($form['title']) ?></h2>
      <dl class="mt-4 space-y-3">
        <?php foreach ($form['fields'] as $field): ?>
          <?php if (\App\Models\FormFieldTypes::isLayout($field['field_type'])) continue; ?>
          <div>
            <dt class="text-[11px] font-bold uppercase text-neutral-600"><?= e($field['label']) ?></dt>
            <dd class="mt-1 text-sm font-semibold text-black"><?= e(formatFormAnswer($field, $answers[$field['field_key']] ?? '')) ?></dd>
          </div>
        <?php endforeach; ?>
      </dl>
    </section>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
