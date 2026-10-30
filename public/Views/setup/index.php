<?php
/**
 * The installer (SetupController). Requires $step, $steps, $errors,
 * $requirements, $dbOk, $db, $manualConfig, $pending, $platform, $timezones,
 * $mail and $old.
 */
$stepKeys = array_keys($steps);
$current = array_search($step, $stepKeys, true);
$flash = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);
$input = 'mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold text-black focus:border-black focus:outline-none';
$label = 'text-[11px] font-black uppercase tracking-widest text-neutral-700';
$optional = '<span class="font-semibold normal-case text-neutral-500">(optional)</span>';
$back = static fn(string $to): string => '<a href="' . e(url('/setup?step=' . $to)) . '" class="btn-secondary">Back</a>';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex" />
  <title>Setup | <?= e(appName()) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/tailwind.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
  <script defer src="<?= asset('assets/js/password-reveal.js') ?>"></script>
</head>
<body class="min-h-screen antialiased" style="background:var(--ke-paper);color:var(--ke-ink)">
  <div class="flag-stripe" aria-hidden="true"></div>
  <main class="mx-auto grid min-h-screen max-w-6xl items-start gap-8 px-4 py-8 lg:grid-cols-[0.75fr_1.25fr]">
    <section class="rounded-2xl p-8 text-white lg:sticky lg:top-8" style="background:var(--ke-black)">
      <div class="mb-8 flex h-14 w-14 items-center justify-center rounded-xl text-white" style="background:var(--ke-red)">
        <?= defaultLogoSvg('w-8 h-8 text-white') ?>
      </div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:#b8e0cc">Setup</p>
      <h1 class="mt-3 font-serif-heading text-4xl font-black leading-tight">Prepare your LMS.</h1>
      <p class="mt-3 text-sm font-semibold text-neutral-300">A few short steps. You can change everything later from Super Admin.</p>
      <ol class="mt-8 space-y-2 text-sm font-semibold">
        <?php foreach ($stepKeys as $i => $key):
          $state = $i < $current ? 'done' : ($i === $current ? 'now' : 'todo');
        ?>
          <li class="flex items-center gap-3 rounded-lg border p-3 <?= $state === 'now' ? 'border-white/60 bg-white/10' : 'border-white/15' ?>">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-black"
                  style="background:<?= $state === 'done' ? 'var(--ke-green)' : ($state === 'now' ? '#fff' : 'transparent') ?>;color:<?= $state === 'now' ? 'var(--ke-black)' : '#fff' ?>;border:1px solid rgba(255,255,255,.35)">
              <?= $state === 'done' ? '✓' : $i + 1 ?>
            </span>
            <?php if ($i < $current): ?>
              <a href="<?= e(url('/setup?step=' . $key)) ?>" class="text-neutral-200 underline decoration-dotted"><?= e($steps[$key]) ?></a>
            <?php else: ?>
              <span class="<?= $state === 'now' ? 'text-white' : 'text-neutral-400' ?>"><?= e($steps[$key]) ?></span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ol>
    </section>

    <section class="rounded-2xl border border-neutral-300 bg-white p-6 shadow-sm sm:p-8">
      <p class="text-xs font-black uppercase tracking-widest text-neutral-600">Step <?= $current + 1 ?> of <?= count($steps) ?></p>
      <h2 class="mt-2 text-2xl font-black text-black"><?= e($steps[$step]) ?></h2>

      <?php if ($flash): ?><div class="flash-success mt-4"><?= e($flash) ?></div><?php endif; ?>
      <?php foreach ($errors as $error): ?><div class="flash-error mt-4"><?= e($error) ?></div><?php endforeach; ?>

      <form method="post" action="<?= e(url('/setup?step=' . $step)) ?>" enctype="multipart/form-data" class="mt-6 space-y-5">
        <?= csrfField() ?>

        <?php if ($step === 'requirements'): ?>
          <p class="text-sm font-medium text-neutral-700">The server needs these to run the LMS. Items marked "Recommended" can be added later.</p>
          <ul class="divide-y rounded-lg border border-neutral-300">
            <?php foreach ($requirements as $check): ?>
              <li class="flex items-start justify-between gap-3 p-3">
                <span class="min-w-0"><span class="block text-sm font-bold text-black"><?= e($check['label']) ?></span><span class="block break-all text-xs font-medium text-neutral-500"><?= e($check['detail']) ?></span></span>
                <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-black uppercase"
                      style="background:<?= $check['ok'] ? '#ecf7f0' : ($check['required'] ? '#fdecec' : '#fff7e6') ?>;color:<?= $check['ok'] ? 'var(--ke-green)' : ($check['required'] ? '#b91c1c' : '#a16207') ?>">
                  <?= $check['ok'] ? 'OK' : ($check['required'] ? 'Needed' : 'Recommended') ?>
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
          <div class="flex flex-wrap gap-2">
            <a href="<?= e(url('/setup?step=requirements')) ?>" class="btn-secondary">Check again</a>
            <button type="submit" class="btn-primary">Continue</button>
          </div>

        <?php elseif ($step === 'database'): ?>
          <p class="text-sm font-medium text-neutral-700">Your hosting control panel (e.g. cPanel → MySQL Databases) shows these. <?= $dbOk ? '<b style="color:var(--ke-green)">The current settings connect.</b>' : '' ?></p>
          <div class="grid gap-4 sm:grid-cols-[1fr_8rem]">
            <div><label class="<?= $label ?>">Database server</label><input type="text" name="db_host" value="<?= e($db['host']) ?>" required class="<?= $input ?>" /></div>
            <div><label class="<?= $label ?>">Port</label><input type="number" name="db_port" value="<?= e($db['port']) ?>" required class="<?= $input ?>" /></div>
          </div>
          <div><label class="<?= $label ?>">Database name</label><input type="text" name="db_name" value="<?= e($db['name']) ?>" required pattern="[A-Za-z0-9_$-]+" class="<?= $input ?>" /></div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="<?= $label ?>">User</label><input type="text" name="db_user" value="<?= e($db['user']) ?>" required autocomplete="off" class="<?= $input ?>" /></div>
            <div>
              <label class="<?= $label ?>">Password</label>
              <input type="password" name="db_pass" autocomplete="off" readonly onfocus="this.removeAttribute('readonly')" data-lpignore="true" placeholder="<?= $db['has_pass'] ? 'Blank = use the password in Database.php' : '' ?>" class="<?= $input ?>" />
            </div>
          </div>
          <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="create_db" value="1" checked class="h-4 w-4"> Create the database if it doesn't exist (needs permission on shared hosting)</label>
          <div class="flex flex-wrap gap-2">
            <?= $back('requirements') ?>
            <button type="submit" class="btn-primary">Test &amp; save connection</button>
            <?php if ($dbOk): ?><a href="<?= e(url('/setup?step=migrations')) ?>" class="btn-secondary">Keep current settings</a><?php endif; ?>
          </div>

        <?php elseif ($step === 'migrations'): ?>
          <?php if (!$dbOk): ?>
            <p class="text-sm font-bold" style="color:#b91c1c">The database is not reachable. Go back and check the connection.</p>
            <?= $back('database') ?>
          <?php elseif ($pending): ?>
            <p class="text-sm font-medium text-neutral-700"><?= count($pending) ?> database update<?= count($pending) === 1 ? '' : 's' ?> will create or update the tables. This can take a minute on a new install.</p>
            <details class="rounded-lg border border-neutral-300 p-3 text-xs"><summary class="cursor-pointer font-bold">Show the updates</summary>
              <ul class="mt-2 space-y-0.5 font-mono"><?php foreach ($pending as $name): ?><li><?= e($name) ?></li><?php endforeach; ?></ul>
            </details>
            <div class="flex flex-wrap gap-2">
              <?= $back('database') ?>
              <button type="submit" class="btn-primary" onclick="this.disabled=true;this.textContent='Running…';this.form.submit()">Run database updates</button>
            </div>
          <?php else: ?>
            <p class="text-sm font-bold" style="color:var(--ke-green)">The database is up to date.</p>
            <div class="flex flex-wrap gap-2"><?= $back('database') ?><a href="<?= e(url('/setup?step=platform')) ?>" class="btn-primary">Continue</a></div>
          <?php endif; ?>

        <?php elseif ($step === 'platform'): ?>
          <div><label class="<?= $label ?>">Platform name</label><input type="text" name="platform_name" value="<?= e($platform['name']) ?>" required maxlength="120" class="<?= $input ?>" /></div>
          <div>
            <label class="<?= $label ?>">Site address</label>
            <input type="url" name="app_url" value="<?= e($platform['url']) ?>" placeholder="https://example.com" class="<?= $input ?>" />
            <p class="mt-1 text-xs font-medium text-neutral-600">The address the site opens at — used in emails and on certificates. Leave blank to detect it.</p>
          </div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="<?= $label ?>">Time zone</label>
              <select name="timezone" class="<?= $input ?>">
                <?php foreach ($timezones as $tz): ?><option value="<?= e($tz) ?>" <?= $tz === $platform['timezone'] ? 'selected' : '' ?>><?= e($tz) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div><label class="<?= $label ?>">Contact email <?= $optional ?></label><input type="email" name="contact_email" value="<?= e($platform['contact']) ?>" class="<?= $input ?>" /></div>
          </div>
          <div>
            <label class="<?= $label ?>">Logo <?= $optional ?></label>
            <input type="file" name="store_logo" accept="image/*" class="mt-2 block w-full rounded-lg border border-neutral-400 bg-white p-3 text-sm font-semibold text-black" />
            <p class="mt-1 text-xs font-medium text-neutral-600">Shown on the site, sign-in pages and dashboards.</p>
          </div>
          <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="app_debug" value="1" <?= $platform['debug'] ? 'checked' : '' ?> class="h-4 w-4"> Show error details on screen (turn off on a live site)</label>
          <div class="flex flex-wrap gap-2"><?= $back('migrations') ?><button type="submit" class="btn-primary">Save &amp; continue</button></div>

        <?php elseif ($step === 'mail'): ?>
          <p class="text-sm font-medium text-neutral-700">Sign-in codes, password resets and invitations are sent by email. Use your host's SMTP details (or a service such as Gmail, Zoho or Mailgun).</p>
          <div class="grid gap-4 sm:grid-cols-[1fr_7rem_7rem]">
            <div><label class="<?= $label ?>">SMTP server</label><input type="text" name="mail_host" value="<?= e($mail['host'] ?? '') ?>" placeholder="mail.example.com" class="<?= $input ?>" /></div>
            <div><label class="<?= $label ?>">Port</label><input type="number" name="mail_port" value="<?= e($mail['port'] ?? '587') ?>" class="<?= $input ?>" /></div>
            <div><label class="<?= $label ?>">Security</label>
              <select name="mail_encryption" class="<?= $input ?>"><option value="tls" <?= ($mail['encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option><option value="ssl" <?= ($mail['encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option></select>
            </div>
          </div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="<?= $label ?>">Username</label><input type="text" name="mail_username" value="<?= e($mail['username'] ?? '') ?>" autocomplete="off" class="<?= $input ?>" /></div>
            <div><label class="<?= $label ?>">Password</label><input type="password" name="mail_password" autocomplete="new-password" placeholder="<?= ($mail['password'] ?? '') !== '' ? 'Leave blank to keep the saved one' : '' ?>" class="<?= $input ?>" /></div>
          </div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="<?= $label ?>">From address</label><input type="email" name="mail_from_address" value="<?= e(($mail['from_address'] ?? '') === 'no-reply@example.com' ? '' : ($mail['from_address'] ?? '')) ?>" class="<?= $input ?>" /></div>
            <div><label class="<?= $label ?>">From name</label><input type="text" name="mail_from_name" value="<?= e($mail['from_name'] ?? appName()) ?>" class="<?= $input ?>" /></div>
          </div>
          <div><label class="<?= $label ?>">Send a test email to <?= $optional ?></label><input type="email" name="test_to" class="<?= $input ?>" /></div>
          <div class="flex flex-wrap gap-2">
            <?= $back('platform') ?>
            <button type="submit" class="btn-primary">Save &amp; continue</button>
            <button type="submit" name="skip" value="1" formnovalidate class="btn-secondary">Skip for now</button>
          </div>

        <?php elseif ($step === 'devs'): ?>
          <p class="text-sm font-medium text-neutral-700">A private developer account, separate from the Super Admin. It can lock the whole site from a hidden page if ever needed. It appears in no menu or list, and the Super Admin never sees it.</p>
          <div><label class="<?= $label ?>">Developer email</label><input type="email" name="email" required value="<?= e($devsAccount['email'] ?? '') ?>" autocomplete="off" class="<?= $input ?>" /></div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="<?= $label ?>">Password</label><input type="password" name="password" <?= !empty($devsAccount['configured']) ? '' : 'required' ?> minlength="8" autocomplete="new-password" placeholder="<?= !empty($devsAccount['configured']) ? 'Leave blank to keep the current one' : '' ?>" class="<?= $input ?>" /></div>
            <div><label class="<?= $label ?>">Confirm password</label><input type="password" name="password_confirm" minlength="8" autocomplete="new-password" class="<?= $input ?>" /></div>
          </div>
          <div class="flex flex-wrap gap-2"><?= $back('mail') ?><button type="submit" class="btn-primary">Save &amp; continue</button></div>

        <?php elseif ($step === 'admin'): ?>
          <p class="text-sm font-medium text-neutral-700">The Super Admin owns the platform: roles, forms, organisations, payments and settings. You'll be signed in straight after.</p>
          <div><label class="<?= $label ?>">Your name <?= $optional ?></label><input type="text" name="name" value="<?= e($old['name'] ?? '') ?>" class="<?= $input ?>" /></div>
          <div><label class="<?= $label ?>">Email</label><input type="email" name="email" required value="<?= e($old['email'] ?? '') ?>" autocomplete="username" class="<?= $input ?>" /></div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="<?= $label ?>">Password</label><input type="password" name="password" required minlength="8" autocomplete="new-password" class="<?= $input ?>" /></div>
            <div><label class="<?= $label ?>">Confirm password</label><input type="password" name="password_confirm" required minlength="8" autocomplete="new-password" class="<?= $input ?>" /></div>
          </div>
          <div class="flex flex-wrap gap-2"><?= $back('devs') ?><button type="submit" class="btn-primary">Finish setup &amp; sign in</button></div>
        <?php endif; ?>
      </form>
    </section>
  </main>
</body>
</html>
