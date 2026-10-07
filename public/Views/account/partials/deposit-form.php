<?php
/** A slim M-Pesa deposit form. Optional $depositReturn: where to come back to afterwards. */
$depositReturn = $depositReturn ?? '/account/wallet';
?>
<form method="post" action="<?= url('/account/wallet/deposit') ?>" class="deposit-row">
  <?= csrfField() ?>
  <input type="hidden" name="return_to" value="<?= e($depositReturn) ?>">
  <span class="deposit-title"><?= icon('wallet', 'h-4 w-4') ?> Top up (M-Pesa)
    <?php if (\App\Services\PaymentGateway::mode() === 'simulation'): ?><span class="deposit-tag" title="No real money is charged; deposits are approved instantly.">test mode</span><?php endif; ?>
  </span>
  <input type="number" name="amount_ksh" min="10" step="1" required placeholder="Amount (Ksh)" aria-label="Amount in Ksh">
  <input type="tel" name="phone" required placeholder="M-Pesa phone" value="<?= e($currentUser['phone'] ?? '') ?>" aria-label="M-Pesa phone">
  <button type="submit" class="btn-primary">Deposit</button>
</form>
