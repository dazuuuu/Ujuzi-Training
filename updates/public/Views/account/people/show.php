<?php
/** Requires $person, $forms in scope. */
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest text-neutral-600"><?= e($person['role_name']) ?></p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold"><?= e(userDisplayName($person)) ?></h1>
      <p class="mt-1 text-sm font-medium text-neutral-600"><?= e($person['email'] ?: $person['phone'] ?: '') ?></p>
    </div>
    <a href="<?= url('/account/people/' . (int) $person['id'] . '/edit') ?>" class="btn-secondary">Edit</a>
  </div>

  <?php foreach ($forms as $form):
    $answers = $form['response']['answers'] ?? [];
    $saved = !empty($form['response']['submitted_at']);
  ?>
    <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
      <div class="flex items-center justify-between gap-3 border-b border-neutral-100 pb-3">
        <h2 class="font-serif-heading text-lg font-bold"><?= e($form['title']) ?></h2>
        <span class="text-[10px] font-black uppercase" style="color: <?= $saved ? 'var(--ke-green)' : 'var(--ke-red)' ?>"><?= $saved ? 'Submitted' : 'Not filled' ?></span>
      </div>
      <dl class="mt-4 space-y-3">
        <?php foreach ($form['fields'] as $field): ?>
          <?php if (\App\Models\FormFieldTypes::isLayout($field['field_type'])) continue; ?>
          <div>
            <dt class="text-[11px] font-bold uppercase text-neutral-600"><?= e($field['label']) ?></dt>
            <dd class="mt-1 text-sm font-semibold text-black">
              <?php
                $display = formatFormAnswer($field, $answers[$field['field_key']] ?? '');
                $raw = $answers[$field['field_key']] ?? '';
                if (\App\Models\FormFieldTypes::isFile($field['field_type']) && $raw):
              ?>
                <a href="<?= e(imageUrl((string) $raw)) ?>" target="_blank" style="color:var(--ke-green)"><?= e($display) ?></a>
              <?php else: ?>
                <?= e($display) ?>
              <?php endif; ?>
            </dd>
          </div>
        <?php endforeach; ?>
      </dl>
    </section>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
