<?php
/** Requires $settings in scope. */
require __DIR__ . '/../layout-header.php';
$logo = $settings['store_logo'] ?? null;
$platformName = $settings['platform_name'] ?? appName();
?>

<div class="max-w-3xl space-y-6">
  <form method="post" action="<?= url('/admin/settings') ?>" enctype="multipart/form-data" class="rounded-xl border border-neutral-300 bg-white p-6 shadow-sm space-y-6">
    <?= csrfField() ?>

    <div class="flex flex-col gap-4 border-b border-neutral-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <p class="text-xs font-black uppercase tracking-widest text-neutral-600">Platform settings</p>
        <h2 class="mt-2 text-2xl font-black text-black">LMS branding</h2>
        <p class="mt-1 text-sm font-medium text-neutral-700">This name and logo appear on the public LMS, logins, and dashboards.</p>
      </div>
      <button type="submit" class="btn-primary">Save</button>
    </div>

    <div>
      <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">Platform name</label>
      <input type="text" name="platform_name" value="<?= e($platformName) ?>" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold text-black focus:border-black focus:outline-none" />
    </div>

    <div class="grid gap-5 sm:grid-cols-[180px_minmax(0,1fr)] sm:items-start">
      <div class="rounded-xl border border-neutral-300 bg-neutral-50 p-4">
        <p class="mb-3 text-[11px] font-black uppercase tracking-widest text-neutral-700">Current Logo</p>
        <div class="flex h-28 items-center justify-center rounded-lg border border-neutral-300 bg-white p-4">
          <?php if ($logo): ?>
            <img src="<?= e(imageUrl($logo)) ?>" alt="Current logo" class="max-h-full max-w-full object-contain" />
          <?php else: ?>
            <div class="flex h-12 w-12 items-center justify-center rounded-lg text-white" style="background:var(--ke-green)">
              <?= pentagonLogoSvg('w-7 h-7 text-white') ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="space-y-4">
        <div>
          <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">Upload Logo</label>
          <input type="file" name="store_logo" accept="image/*" class="mt-2 block w-full rounded-lg border border-neutral-400 bg-white p-3 text-sm font-semibold text-black" />
          <p class="mt-2 text-xs font-medium text-neutral-600">Use PNG, JPG, WEBP, or GIF up to 8MB.</p>
        </div>
        <?php if ($logo): ?>
          <label class="flex items-center gap-2 text-sm font-bold text-neutral-800">
            <input type="checkbox" name="remove_logo" value="1" class="h-4 w-4 accent-black" />
            Remove current logo and use the default mark
          </label>
        <?php endif; ?>
      </div>
    </div>
  </form>

  <form method="post" action="<?= url('/admin/settings') ?>" class="rounded-xl border bg-white p-6 shadow-sm space-y-6" style="border:2px solid var(--ke-green)">
    <?= csrfField() ?>
    <input type="hidden" name="save_smtp" value="1" />
    <div class="flex flex-col gap-4 border-b border-neutral-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Email / SMTP</p>
        <h2 class="mt-2 text-2xl font-black text-black">Mail credentials</h2>
        <p class="mt-1 text-sm font-medium text-neutral-700">Used to email organisation registration forms and login codes. Put your SMTP host, username, and password here.</p>
        <p class="mt-2 text-xs font-bold <?= !empty($smtpConfigured) ? '' : '' ?>" style="color: <?= !empty($smtpConfigured) ? 'var(--ke-green)' : 'var(--ke-red)' ?>">
          <?= !empty($smtpConfigured) ? 'SMTP looks configured.' : 'SMTP is not configured yet — emails will fail until you save credentials below.' ?>
        </p>
      </div>
      <button type="submit" class="btn-primary">Save SMTP</button>
    </div>

    <?php $smtp = $smtp ?? []; ?>
    <div class="grid gap-4 sm:grid-cols-2">
      <div class="sm:col-span-2">
        <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">SMTP host</label>
        <input type="text" name="mail_host" value="<?= e($smtp['host'] ?? '') ?>" placeholder="smtp.gmail.com" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold" />
      </div>
      <div>
        <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">Port</label>
        <input type="number" name="mail_port" value="<?= e($smtp['port'] ?? '587') ?>" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold" />
      </div>
      <div>
        <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">Encryption</label>
        <select name="mail_encryption" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold">
          <option value="tls" <?= ($smtp['encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS (port 587)</option>
          <option value="ssl" <?= ($smtp['encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (port 465)</option>
        </select>
      </div>
      <div>
        <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">SMTP username</label>
        <input type="text" name="mail_username" value="<?= e($smtp['username'] ?? '') ?>" placeholder="you@gmail.com" autocomplete="off" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold" />
      </div>
      <div>
        <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">SMTP password</label>
        <input type="password" name="mail_password" value="" placeholder="<?= !empty($smtpHasPassword) ? 'Saved — leave blank to keep' : 'App password or mailbox password' ?>" autocomplete="new-password" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold" />
      </div>
      <div>
        <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">From email</label>
        <input type="email" name="mail_from_address" value="<?= e($smtp['from_address'] ?? '') ?>" placeholder="no-reply@yourdomain.com" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold" />
      </div>
      <div>
        <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">From name</label>
        <input type="text" name="mail_from_name" value="<?= e($smtp['from_name'] ?? '') ?>" placeholder="<?= e(appName()) ?>" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold" />
      </div>
    </div>
    <p class="text-xs font-medium text-neutral-600">Gmail example: host <span class="font-mono">smtp.gmail.com</span>, port <span class="font-mono">587</span>, encryption TLS, username your full Gmail, password a Google App Password.</p>
    <button type="submit" class="btn-primary">Save SMTP</button>
  </form>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
