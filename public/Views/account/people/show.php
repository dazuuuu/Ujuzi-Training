<?php
/** Requires $person, $forms in scope. */
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest text-neutral-600"><?= e($person['role_name']) ?></p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold"><?= e(userFullName($person)) ?></h1>
      <p class="mt-1 text-sm font-medium text-neutral-600"><?= e($person['email'] ?: $person['phone'] ?: '') ?></p>
    </div>
    <a href="<?= url('/account/people/' . (int) $person['id'] . '/edit') ?>" class="btn-secondary">Edit</a>
  </div>

  <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
    <div class="flex items-center justify-between gap-3 border-b border-neutral-100 pb-3">
      <h2 class="font-serif-heading text-lg font-bold">Account</h2>
      <span class="text-[10px] font-black uppercase" style="color: <?= ($person['account_status'] ?? 'active') === 'active' ? 'var(--ke-green)' : 'var(--ke-red)' ?>">
        <?= e(ucfirst((string) ($person['account_status'] ?? 'active'))) ?>
      </span>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <p class="text-[11px] font-bold uppercase text-neutral-600 mb-2">Password</p>
        <form method="post" action="<?= url('/account/people/' . (int) $person['id'] . '/reset-password') ?>" class="space-y-2">
          <?= csrfField() ?>
          <input type="text" name="password" placeholder="Leave blank to reset to the default password" class="w-full rounded-lg border border-neutral-300 p-2 text-xs" autocomplete="off" />
          <button type="submit" class="btn-secondary w-full" onclick="return confirm('Set a new password for this account? They will be asked to change it on next login.');">Reset password</button>
        </form>
      </div>

      <div>
        <p class="text-[11px] font-bold uppercase text-neutral-600 mb-2">Account status</p>
        <div class="flex flex-wrap gap-2">
          <?php foreach (['active' => 'Activate', 'suspended' => 'Suspend', 'blocked' => 'Block'] as $statusValue => $label): ?>
            <?php if (($person['account_status'] ?? 'active') !== $statusValue): ?>
              <form method="post" action="<?= url('/account/people/' . (int) $person['id'] . '/status/' . $statusValue) ?>">
                <?= csrfField() ?>
                <button type="submit" class="btn-secondary" style="padding:0.4rem 0.75rem;font-size:0.75rem;"><?= e($label) ?></button>
              </form>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

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
