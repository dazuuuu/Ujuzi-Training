<?php
/** Requires $forced, $hasEmail, $codeSent and $email. */
$forced = !empty($forced);
require __DIR__ . '/layout-header.php';
?>

<div class="mx-auto max-w-md space-y-5">
  <section>
    <h1 class="font-serif-heading text-2xl font-bold"><?= $forced ? 'Set your password' : 'Change password' ?></h1>
    <p class="mt-1 text-sm font-medium text-neutral-600">
      <?= $forced
        ? 'You signed in with a default password. Set your own password to continue.'
        : 'For your security we email you a one-time code first.' ?>
    </p>
  </section>

  <?php if (!$forced && !$hasEmail): ?>
    <section class="learn-card p-5 text-sm font-semibold text-neutral-700">
      Your account has no email address, so we can't send a code. <a href="<?= url('/account/profile') ?>" class="font-black underline" style="color:var(--ke-green)">Add one on your profile</a>, then come back.
    </section>
  <?php else: ?>
    <?php if (!$forced): ?>
      <section class="learn-card flex items-center gap-3 p-4">
        <span class="app-tile-icon shrink-0"><?= icon('mail') ?></span>
        <div class="min-w-0 flex-1">
          <p class="text-sm font-black text-gray-800">1. Get a code</p>
          <p class="truncate text-xs font-semibold text-neutral-500">Sent to <?= e($email) ?></p>
        </div>
        <form method="post" action="<?= url('/account/change-password/code') ?>">
          <?= csrfField() ?>
          <button type="submit" class="<?= $codeSent ? 'btn-secondary' : 'btn-primary' ?>"><?= $codeSent ? 'Resend' : 'Send code' ?></button>
        </form>
      </section>
    <?php endif; ?>

    <section class="learn-card p-5">
      <form method="post" action="<?= url('/account/change-password') ?>" class="space-y-4">
        <?= csrfField() ?>
        <?php if (!$forced): ?>
          <p class="text-sm font-black text-gray-800">2. Enter the code and your new password</p>
          <div>
            <label class="text-[11px] font-bold uppercase text-neutral-600" for="cp-code">6-digit code</label>
            <input id="cp-code" type="text" name="code" required inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7" autocomplete="one-time-code" <?= $codeSent ? 'autofocus' : 'disabled' ?> class="mt-1 block w-full rounded-lg border border-neutral-300 p-2.5 text-center text-lg font-black tracking-[0.4em]" />
          </div>
        <?php endif; ?>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600" for="cp-new">New password</label>
          <input id="cp-new" type="password" name="password" required minlength="8" class="mt-1 block w-full rounded-lg border border-neutral-300 p-2.5 text-sm" autocomplete="new-password" <?= !$forced && !$codeSent ? 'disabled' : '' ?> />
        </div>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600" for="cp-confirm">Confirm new password</label>
          <input id="cp-confirm" type="password" name="password_confirm" required minlength="8" class="mt-1 block w-full rounded-lg border border-neutral-300 p-2.5 text-sm" autocomplete="new-password" <?= !$forced && !$codeSent ? 'disabled' : '' ?> />
        </div>
        <button type="submit" class="btn-primary w-full" <?= !$forced && !$codeSent ? 'disabled' : '' ?>>Save password</button>
      </form>
    </section>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
