<?php
/** Standalone gate login. Requires $error. */
$error = $error ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Console</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#0f1a14;margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
    .card{width:100%;max-width:340px;background:#16241c;border:1px solid #27402f;border-radius:16px;padding:28px;color:#e8efe9}
    h1{font-size:16px;margin:0 0 18px;letter-spacing:.14em;text-transform:uppercase;color:#8fb89d;text-align:center}
    label{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#8fb89d;margin:0 0 4px}
    input{width:100%;box-sizing:border-box;padding:10px 12px;margin:0 0 14px;border:1px solid #2c4636;border-radius:9px;background:#0f1a14;color:#e8efe9;font-size:14px}
    input:focus{outline:none;border-color:#c7a008}
    button{width:100%;padding:11px;border:0;border-radius:9px;background:#c7a008;color:#160f00;font-weight:800;font-size:14px;cursor:pointer}
    .err{background:#3a1a1a;border:1px solid #5a2a2a;color:#f0c8c8;font-size:13px;padding:9px 11px;border-radius:9px;margin:0 0 14px}
  </style>
  <script defer src="<?= asset('assets/js/password-reveal.js') ?>"></script>
</head>
<body>
  <form class="card" method="post" action="<?= url('/devs') ?>" autocomplete="off">
    <h1>Console</h1>
    <?php if ($error !== ''): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
    <?= csrfField() ?>
    <label for="g-email">Email</label>
    <input id="g-email" type="email" name="email" required autofocus>
    <label for="g-password">Password</label>
    <input id="g-password" type="password" name="password" required>
    <button type="submit">Enter</button>
  </form>
</body>
</html>
