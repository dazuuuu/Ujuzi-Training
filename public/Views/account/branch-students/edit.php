<?php
/** Edit one student's details (branch admin). Requires $student. */
require __DIR__ . '/../layout-header.php';
?>
<div class="max-w-xl space-y-4">
  <h1 class="font-serif-heading text-2xl font-bold">Edit student</h1>
  <form method="post" action="<?= url('/account/branch-students/' . (int) $student['id'] . '/edit') ?>" class="space-y-3 rounded-xl border border-neutral-300 bg-white p-5">
    <?= csrfField() ?>
    <?php foreach ([['first_name', 'First name'], ['other_names', 'Other names'], ['last_name', 'Last name'], ['email', 'Email'], ['phone', 'Phone']] as [$k, $label]): ?>
      <label class="block text-[11px] font-black uppercase tracking-wide text-neutral-600"><?= e($label) ?>
        <input name="<?= e($k) ?>" value="<?= e((string) ($student[$k] ?? '')) ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm normal-case font-semibold">
      </label>
    <?php endforeach; ?>
    <div class="flex gap-2"><button class="btn-primary">Save</button><a class="btn-secondary" href="<?= url('/account/branch-students') ?>">Cancel</a></div>
  </form>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
