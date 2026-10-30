<?php /* The real student sign-up form (posts to /account/register). */ ?>
<div class="pb-signup">
  <?php if (($c['title'] ?? '') !== ''): ?><h2 class="pb-title" style="font-size:1.2em;margin-bottom:.8em"><?= e($c['title']) ?></h2><?php endif; ?>
  <form method="post" action="<?= url('/account/register') ?>" class="pb-signup-form">
    <?= csrfField() ?>
    <label>Email<input type="email" name="email" required autocomplete="email" placeholder="you@example.com"></label>
    <label>Password<input type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="At least 8 characters"></label>
    <label>Confirm password<input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label>
    <button type="submit" class="pb-btn pb-btn-main" style="width:100%"><?= e(($c['button_label'] ?? '') ?: 'Create my account') ?></button>
  </form>
  <p class="pb-note" style="text-align:center;opacity:1;font-weight:600">Already registered? <a href="<?= url('/account/login') ?>" style="color:var(--pb-accent)">Sign in</a></p>
  <?php if (($c['note'] ?? '') !== ''): ?><p class="pb-text" style="font-size:.8em;text-align:center;margin:1em auto 0"><?= e($c['note']) ?></p><?php endif; ?>
</div>
