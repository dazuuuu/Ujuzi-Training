<?php
/** Requires $roles, $errors in scope. */
require __DIR__ . '/../layout-header.php';
?>

<div class="max-w-xl space-y-6">
  <section>
    <h1 class="font-serif-heading text-2xl font-bold">Import people</h1>
    <p class="mt-1 text-sm font-medium text-neutral-600">
      Upload a CSV file with two columns: <span class="font-mono">name</span> and <span class="font-mono">email</span>.
      Each row gets a login with the default password, and an email with their sign-in details — same as adding one person at a time.
      In Excel: File → Save As → CSV (Comma delimited) before uploading.
    </p>
  </section>

  <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
    <form method="post" action="<?= url('/account/people/import') ?>" enctype="multipart/form-data" class="space-y-4">
      <?= csrfField() ?>
      <?php foreach ($errors as $err): ?>
        <div class="rounded-lg border border-rose-300 bg-rose-50 p-3 text-sm font-semibold text-rose-800"><?= e($err) ?></div>
      <?php endforeach; ?>

      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Role for everyone in this file</label>
        <select name="role_id" required class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm">
          <option value="">Choose role</option>
          <?php foreach ($roles as $role): ?>
            <option value="<?= (int) $role['id'] ?>"><?= e($role['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">CSV file</label>
        <input type="file" name="csv_file" accept=".csv,text/csv" required class="mt-1 block w-full text-sm" />
      </div>

      <button type="submit" class="btn-primary">Import</button>
    </form>
  </section>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
