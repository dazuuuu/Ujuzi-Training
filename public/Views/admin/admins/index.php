<?php
/** Requires $admins (hydrated), $sections (key => label) and $me (the signed-in owner). */
require __DIR__ . '/../layout-header.php';

/** Section tick boxes, hidden while "Full access" is on. */
$sectionBoxes = static function (array $checked, string $prefix) use ($sections): string {
    $html = '<div class="admin-sections mt-2 grid gap-2 sm:grid-cols-2">';
    foreach ($sections as $key => $label) {
        $id = $prefix . '-' . $key;
        $html .= '<label for="' . e($id) . '" class="flex items-center gap-2 rounded-lg border border-neutral-200 px-3 py-2 text-sm font-semibold">'
            . '<input type="checkbox" id="' . e($id) . '" name="permissions[]" value="' . e($key) . '"' . (in_array($key, $checked, true) ? ' checked' : '') . '> '
            . e($label) . '</label>';
    }
    return $html . '</div>';
};
?>
<div class="space-y-6">
  <section>
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Admins</p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold">Super Admins</h1>
    <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600">
      Add people who help run the platform. A <strong>main Super Admin</strong> has full access and manages admins.
      Anyone else only sees the sections you tick — the rest are hidden from their menu and blocked if they type the address.
    </p>
  </section>

  <form method="post" action="<?= url('/admin/admins') ?>" class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm space-y-4 js-admin-form">
    <?= csrfField() ?>
    <h2 class="font-serif-heading text-lg font-bold">Add an admin</h2>
    <div class="grid gap-3 sm:grid-cols-3">
      <label class="text-xs font-bold uppercase text-neutral-600">Name
        <input type="text" name="name" maxlength="120" class="mt-1 block w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case">
      </label>
      <label class="text-xs font-bold uppercase text-neutral-600">Email
        <input type="email" name="email" required class="mt-1 block w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case">
      </label>
      <label class="text-xs font-bold uppercase text-neutral-600">Password (8+ characters)
        <input type="password" name="password" minlength="8" required autocomplete="new-password" class="mt-1 block w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case">
      </label>
    </div>
    <label class="flex items-center gap-2 text-sm font-bold">
      <input type="checkbox" name="is_owner" value="1" class="js-owner-toggle"> Full access (main Super Admin — can also manage admins)
    </label>
    <div class="js-sections">
      <p class="text-xs font-bold uppercase text-neutral-600">Sections this admin may use</p>
      <?= $sectionBoxes([], 'new') ?>
    </div>
    <button type="submit" class="btn-primary">Add admin</button>
  </form>

  <section class="space-y-3">
    <h2 class="font-serif-heading text-lg font-bold">Current admins (<?= count($admins) ?>)</h2>
    <?php foreach ($admins as $a):
      $isMe = $a['id'] === (int) $me['id'];
    ?>
      <details class="rounded-xl border border-neutral-200 bg-white shadow-sm">
        <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3 p-4">
          <div class="min-w-0">
            <p class="font-bold text-gray-800"><?= e($a['name'] ?: $a['email']) ?><?= $isMe ? ' <span class="text-xs font-bold text-neutral-500">(you)</span>' : '' ?></p>
            <p class="text-xs font-semibold text-neutral-500"><?= e($a['email']) ?></p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <?php if (!$a['is_active']): ?>
              <span class="rounded-full border px-2 py-0.5 text-[11px] font-black uppercase" style="border-color:var(--ke-red);color:var(--ke-red)">Deactivated</span>
            <?php endif; ?>
            <span class="rounded-full border px-2 py-0.5 text-[11px] font-black uppercase" style="border-color:var(--ke-green);color:var(--ke-green)">
              <?= $a['is_owner'] ? 'Full access' : count($a['permissions']) . ' section' . (count($a['permissions']) === 1 ? '' : 's') ?>
            </span>
            <span class="text-xs font-bold" style="color:var(--ke-green)">Edit ▾</span>
          </div>
        </summary>
        <div class="border-t border-neutral-100 p-4 space-y-4">
          <form method="post" action="<?= url('/admin/admins/' . $a['id']) ?>" class="space-y-3 js-admin-form">
            <?= csrfField() ?>
            <div class="grid gap-3 sm:grid-cols-2">
              <label class="text-xs font-bold uppercase text-neutral-600">Name
                <input type="text" name="name" value="<?= e($a['name'] ?? '') ?>" maxlength="120" class="mt-1 block w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case">
              </label>
              <label class="text-xs font-bold uppercase text-neutral-600">New password (leave blank to keep)
                <input type="password" name="password" minlength="8" autocomplete="new-password" class="mt-1 block w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case">
              </label>
            </div>
            <label class="flex items-center gap-2 text-sm font-bold">
              <input type="checkbox" name="is_owner" value="1" class="js-owner-toggle" <?= $a['is_owner'] ? 'checked' : '' ?> <?= $isMe ? 'disabled' : '' ?>> Full access (main Super Admin)
            </label>
            <?php if ($isMe): ?><input type="hidden" name="is_owner" value="1"><?php endif; ?>
            <div class="js-sections">
              <p class="text-xs font-bold uppercase text-neutral-600">Sections</p>
              <?= $sectionBoxes($a['permissions'], 'admin' . $a['id']) ?>
            </div>
            <label class="flex items-center gap-2 text-sm font-bold">
              <input type="hidden" name="is_active" value="0">
              <input type="checkbox" name="is_active" value="1" <?= $a['is_active'] ? 'checked' : '' ?> <?= $isMe ? 'disabled' : '' ?>> Active (can sign in)
            </label>
            <?php if ($isMe): ?><input type="hidden" name="is_active" value="1"><?php endif; ?>
            <button type="submit" class="btn-primary">Save</button>
          </form>
          <?php if (!$isMe): ?>
            <form method="post" action="<?= url('/admin/admins/' . $a['id'] . '/delete') ?>" onsubmit="return confirm('Remove this admin? They will no longer be able to sign in.');">
              <?= csrfField() ?>
              <button type="submit" class="btn-danger">Remove admin</button>
            </form>
          <?php endif; ?>
        </div>
      </details>
    <?php endforeach; ?>
  </section>
</div>

<script>
// "Full access" covers every section, so the section boxes hide while it's on.
document.querySelectorAll('.js-admin-form').forEach(function (form) {
  var toggle = form.querySelector('.js-owner-toggle');
  var boxes = form.querySelector('.js-sections');
  if (!toggle || !boxes) return;
  var sync = function () { boxes.hidden = toggle.checked; };
  toggle.addEventListener('change', sync);
  sync();
});
</script>
<?php require __DIR__ . '/../layout-footer.php'; ?>
