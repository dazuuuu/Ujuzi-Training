<?php
/** The public contact form; messages are emailed to the contact email in Navbar & footer. */
$sent = (string) ($_GET['sent'] ?? '');
$notices = [
    '1' => ['ok', 'Thank you — your message was sent. We will get back to you soon.'],
    'invalid' => ['bad', 'Please fill in your name, a valid email and your message.'],
    'wait' => ['bad', 'Please wait a minute before sending another message.'],
    '0' => ['bad', 'Your message could not be sent right now. Please call or email us instead.'],
];
?>
<div class="pb-contact-form" id="contact-form">
  <?php require __DIR__ . '/_head.php'; ?>
  <?php if (isset($notices[$sent])): ?><p class="pb-form-notice is-<?= $notices[$sent][0] ?>" role="status"><?= e($notices[$sent][1]) ?></p><?php endif; ?>
  <form method="post" action="<?= url('/contact') ?>" class="pb-signup-form">
    <?= csrfField() ?>
    <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px">
    <label>Your name<input type="text" name="name" required maxlength="120" autocomplete="name"></label>
    <label>Email<input type="email" name="email" required autocomplete="email" placeholder="you@example.com"></label>
    <label>Phone (optional)<input type="tel" name="phone" maxlength="40" autocomplete="tel"></label>
    <label>Message<textarea name="message" required rows="5" maxlength="4000" style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:.65em .8em;font:inherit"></textarea></label>
    <button type="submit" class="pb-btn pb-btn-main" style="width:100%"><?= e(($c['button_label'] ?? '') ?: 'Send message') ?></button>
  </form>
</div>
