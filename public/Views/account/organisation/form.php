<?php
/** Requires $organisation, $errors, $form, $isCourseOrganisation in scope. */
$isCourseOrganisation = !empty($isCourseOrganisation);
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <section>
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)"><?= $isCourseOrganisation ? 'Organisation providing courses' : 'Attachment provider' ?></p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold"><?= $organisation ? 'Your organisation' : 'Register your organisation' ?></h1>
    <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600">
      <?= $organisation
          ? 'Update your organisation details. Add branches and assign branch admins from the Branches page.'
          : 'Register your organisation once, then create branches and assign a branch admin (with a password) for each one.' ?>
    </p>
  </section>

  <form method="post" action="<?= url('/account/organisation') ?>" class="max-w-2xl space-y-4 rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
    <?= csrfField() ?>
    <?php foreach ($errors as $err): ?>
      <div class="rounded-lg border border-rose-300 bg-rose-50 p-3 text-sm font-semibold text-rose-800"><?= e($err) ?></div>
    <?php endforeach; ?>

    <div>
      <label class="text-[11px] font-bold uppercase text-neutral-600">Organisation name</label>
      <input type="text" name="name" required value="<?= e($form['name'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" />
    </div>
    <div>
      <label class="text-[11px] font-bold uppercase text-neutral-600">Description</label>
      <textarea name="description" rows="3" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm"><?= e($form['description'] ?? '') ?></textarea>
    </div>

    <?php if ($isCourseOrganisation): ?>
      <input type="hidden" name="visible_to_students" value="0" />
      <label class="flex items-center gap-2 text-sm font-bold text-neutral-800">
        <input type="checkbox" name="visible_to_students" value="1" <?= !empty($form['visible_to_students']) ? 'checked' : '' ?> class="h-4 w-4 accent-black" />
        Visible on the student dashboard
      </label>
      <p class="text-xs font-medium text-neutral-500">Uncheck this if you don't want students requesting to join you publicly. You can still add students directly from People.</p>
    <?php endif; ?>

    <div class="flex items-center gap-3">
      <button type="submit" class="btn-primary"><?= $organisation ? 'Save organisation' : 'Register organisation' ?></button>
      <?php if ($organisation): ?>
        <a href="<?= url('/account/branches') ?>" class="btn-secondary">Manage branches</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
