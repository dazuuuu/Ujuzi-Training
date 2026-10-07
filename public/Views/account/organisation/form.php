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

  <form method="post" action="<?= url('/account/organisation') ?>" enctype="multipart/form-data" class="max-w-2xl space-y-4 rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
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

    <fieldset class="space-y-3 rounded-lg border p-4" style="border-color:var(--ke-line)">
      <legend class="px-1 text-[11px] font-black uppercase text-neutral-600">What students see</legend>
      <p class="text-xs font-medium text-neutral-500">Students open your organisation from their courses and attachment pages and see these details.</p>
      <div class="grid gap-3 sm:grid-cols-2">
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600" for="org-phone">Phone number</label>
          <input id="org-phone" type="tel" name="phone" value="<?= e($form['phone'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" placeholder="0712 345 678" />
        </div>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600" for="org-email">Email</label>
          <input id="org-email" type="email" name="email" value="<?= e($form['email'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" />
        </div>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600" for="org-location">Location</label>
          <input id="org-location" type="text" name="location" maxlength="190" value="<?= e($form['location'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" placeholder="e.g. Moi Avenue, Nairobi" />
        </div>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600" for="org-website">Website</label>
          <input id="org-website" type="text" name="website" value="<?= e($form['website'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" placeholder="https://" />
        </div>
      </div>
      <div class="flex items-center gap-3">
        <?php if (!empty($form['logo_path'])): ?>
          <img src="<?= e(imageUrl($form['logo_path'])) ?>" alt="Current logo" class="h-12 w-12 rounded-lg border object-contain" style="border-color:var(--ke-line)" />
        <?php endif; ?>
        <div class="min-w-0">
          <label class="text-[11px] font-bold uppercase text-neutral-600" for="org-logo">Logo</label>
          <input id="org-logo" type="file" name="logo" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm" />
        </div>
      </div>
    </fieldset>

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
