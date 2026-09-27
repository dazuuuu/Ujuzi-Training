<?php
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest text-neutral-600"><?= e($user['role_name'] ?? '') ?></p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold"><?= e(userDisplayName($user)) ?></h1>
      <p class="mt-1 text-sm font-medium text-neutral-600"><?= e($user['organisation_name'] ?? 'No organisation') ?></p>
    </div>
    <a href="<?= url('/admin/users/' . (int) $user['id'] . '/edit') ?>" class="btn-secondary">Edit user</a>
  </div>

  <section class="rounded-xl border border-neutral-300 bg-white p-6 shadow-sm">
    <h2 class="font-serif-heading text-lg font-bold border-b border-neutral-100 pb-3">Credentials and account</h2>
    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
      <div><dt class="text-[11px] font-bold uppercase text-neutral-600">Email</dt><dd class="mt-1 text-sm font-semibold"><?= e($user['email'] ?: 'Not provided') ?></dd></div>
      <div><dt class="text-[11px] font-bold uppercase text-neutral-600">Phone</dt><dd class="mt-1 text-sm font-semibold"><?= e($user['phone'] ?: 'Not provided') ?></dd></div>
      <div><dt class="text-[11px] font-bold uppercase text-neutral-600">Status</dt><dd class="mt-1 text-sm font-semibold"><?= e(ucfirst((string) ($user['account_status'] ?? (!empty($user['is_active']) ? 'active' : 'blocked')))) ?></dd></div>
      <div><dt class="text-[11px] font-bold uppercase text-neutral-600">Password</dt><dd class="mt-1 text-sm font-semibold"><?= !empty($user['has_password']) ? 'Set' : 'Sign-in code only' ?></dd></div>
    </dl>
  </section>

  <section class="flex flex-wrap gap-2">
    <?php foreach (['active' => 'Activate', 'blocked' => 'Block', 'suspended' => 'Suspend'] as $status => $label): ?>
      <?php if (($user['account_status'] ?? '') !== $status): ?>
        <form method="post" action="<?= url('/admin/users/' . (int) $user['id'] . '/status') ?>"><?= csrfField() ?><input type="hidden" name="status" value="<?= e($status) ?>" /><button type="submit" class="btn-secondary"><?= e($label) ?></button></form>
      <?php endif; ?>
    <?php endforeach; ?>
    <form method="post" action="<?= url('/admin/users/' . (int) $user['id'] . '/delete') ?>" onsubmit="return confirm('Delete this user permanently?');"><?= csrfField() ?><button type="submit" class="btn-danger">Delete user</button></form>
  </section>

  <?php foreach ($forms as $form):
    $answers = $form['response']['answers'] ?? [];
  ?>
    <section class="rounded-xl border border-neutral-300 bg-white p-6 shadow-sm">
      <div class="flex items-center justify-between gap-3 border-b border-neutral-100 pb-3">
        <h2 class="font-serif-heading text-lg font-bold"><?= e($form['title']) ?></h2>
        <span class="text-[10px] font-black uppercase" style="color: <?= !empty($form['response']['submitted_at']) ? 'var(--ke-green)' : 'var(--ke-red)' ?>"><?= !empty($form['response']['submitted_at']) ? 'Submitted' : 'Not filled' ?></span>
      </div>
      <dl class="mt-4 space-y-3">
        <?php foreach ($form['fields'] as $field): ?>
          <?php if (\App\Models\FormFieldTypes::isLayout($field['field_type'])) continue; ?>
          <div><dt class="text-[11px] font-bold uppercase text-neutral-600"><?= e($field['label']) ?></dt><dd class="mt-1 text-sm font-semibold"><?= e(formatFormAnswer($field, $answers[$field['field_key']] ?? '')) ?></dd></div>
        <?php endforeach; ?>
      </dl>
    </section>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
