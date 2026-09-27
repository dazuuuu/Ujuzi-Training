<?php
/** Requires $settings in scope. */
require __DIR__ . '/../layout-header.php';
$logo = $settings['store_logo'] ?? null;
$heroImage = $settings['hero_image'] ?? null;
$platformName = $settings['platform_name'] ?? appName();
$coursePaymentsEnabled = ($settings['course_payments_enabled'] ?? '0') === '1';
$coursePaymentProvider = $settings['course_payment_provider'] ?? 'mpesa';
$courseKshUsdRate = $settings['course_ksh_usd_rate'] ?? '130';
$darajaEnvironment = $settings['daraja_environment'] ?? 'sandbox';
$hasDarajaConsumerKey = !empty($settings['daraja_consumer_key']);
$hasDarajaConsumerSecret = !empty($settings['daraja_consumer_secret']);
$hasDarajaPasskey = !empty($settings['daraja_passkey']);
$hasStripeSecretKey = !empty($settings['stripe_secret_key']);
$hasStripeWebhookSecret = !empty($settings['stripe_webhook_secret']);
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
              <?= defaultLogoSvg('w-7 h-7 text-white') ?>
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

  <!-- Hero Image Upload -->
  <form method="post" action="<?= url('/admin/settings') ?>" enctype="multipart/form-data" class="rounded-xl border border-neutral-300 bg-white p-6 shadow-sm space-y-6">
    <?= csrfField() ?>
    <div class="flex flex-col gap-4 border-b border-neutral-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <p class="text-xs font-black uppercase tracking-widest text-neutral-600">Homepage</p>
        <h2 class="mt-2 text-2xl font-black text-black">Hero Section Image</h2>
        <p class="mt-1 text-sm font-medium text-neutral-700">This image appears on the right side of the homepage hero section. Use a high-quality photo (1200&times;800px or wider recommended).</p>
      </div>
      <button type="submit" class="btn-primary">Save</button>
    </div>
    <div class="grid gap-5 sm:grid-cols-[220px_minmax(0,1fr)] sm:items-start">
      <div class="rounded-xl border border-neutral-300 bg-neutral-50 p-4">
        <p class="mb-3 text-[11px] font-black uppercase tracking-widest text-neutral-700">Current Image</p>
        <div class="flex h-36 items-center justify-center rounded-lg border border-neutral-300 bg-white overflow-hidden">
          <?php if ($heroImage): ?>
            <img src="<?= e(imageUrl($heroImage)) ?>" alt="Hero image" class="w-full h-full object-cover" />
          <?php else: ?>
            <div class="text-center p-4">
              <div class="text-3xl mb-2">&#128444;&#65039;</div>
              <p class="text-xs text-neutral-500 font-semibold">No hero image set</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="space-y-4">
        <div>
          <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">Upload Hero Image</label>
          <input type="file" name="hero_image" accept="image/*" class="mt-2 block w-full rounded-lg border border-neutral-400 bg-white p-3 text-sm font-semibold text-black" />
          <p class="mt-2 text-xs font-medium text-neutral-600">PNG, JPG, or WEBP up to 8MB. The image fills the right panel of the homepage hero section.</p>
        </div>
        <?php if ($heroImage): ?>
          <label class="flex items-center gap-2 text-sm font-bold text-neutral-800">
            <input type="checkbox" name="remove_hero_image" value="1" class="h-4 w-4 accent-black" />
            Remove hero image (will show default illustration)
          </label>
        <?php endif; ?>
      </div>
    </div>
  </form>
  </form>

  <form method="post" action="<?= url('/admin/settings') ?>" class="rounded-xl border bg-white p-6 shadow-sm space-y-6" style="border:2px solid var(--ke-green)">
    <?= csrfField() ?>
    <input type="hidden" name="save_course_payments" value="1" />
    <div class="flex flex-col gap-4 border-b border-neutral-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Course payments</p>
        <h2 class="mt-2 text-2xl font-black text-black">Enrollment checkout</h2>
        <p class="mt-1 text-sm font-medium text-neutral-700">Open payments when credentials are ready. Close payments for testing so students can enroll without checkout.</p>
      </div>
      <button type="submit" class="btn-primary">Save payments</button>
    </div>
    <fieldset class="grid gap-3 sm:grid-cols-2">
      <label class="rounded-lg border p-4 text-sm font-bold" style="border-color:<?= $coursePaymentsEnabled ? 'var(--ke-green)' : 'var(--ke-line)' ?>">
        <input type="radio" name="course_payments_enabled" value="1" <?= $coursePaymentsEnabled ? 'checked' : '' ?> class="mr-2 h-4 w-4" />
        Payments open
        <span class="mt-1 block text-xs font-medium text-neutral-600">Paid courses go through the selected checkout provider.</span>
      </label>
      <label class="rounded-lg border p-4 text-sm font-bold" style="border-color:<?= !$coursePaymentsEnabled ? 'var(--ke-green)' : 'var(--ke-line)' ?>">
        <input type="radio" name="course_payments_enabled" value="0" <?= !$coursePaymentsEnabled ? 'checked' : '' ?> class="mr-2 h-4 w-4" />
        Payments closed
        <span class="mt-1 block text-xs font-medium text-neutral-600">Testing mode: students can enroll in paid courses without paying.</span>
      </label>
    </fieldset>
    <fieldset class="grid gap-3 sm:grid-cols-2">
      <label class="rounded-lg border p-4 text-sm font-bold" style="border-color:var(--ke-line)">
        <input type="radio" name="course_payment_provider" value="mpesa" <?= $coursePaymentProvider !== 'stripe' ? 'checked' : '' ?> class="mr-2 h-4 w-4" />
        M-Pesa Daraja
        <span class="mt-1 block text-xs font-medium text-neutral-600">Prepared for Daraja integration. Checkout currently confirms in sandbox mode.</span>
      </label>
      <label class="rounded-lg border p-4 text-sm font-bold" style="border-color:var(--ke-line)">
        <input type="radio" name="course_payment_provider" value="stripe" <?= $coursePaymentProvider === 'stripe' ? 'checked' : '' ?> class="mr-2 h-4 w-4" />
        Stripe sandbox
        <span class="mt-1 block text-xs font-medium text-neutral-600">Shows KSH fee and estimated USD charge before sandbox confirmation.</span>
      </label>
    </fieldset>
    <div>
      <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">KSH per USD conversion rate</label>
      <input type="number" name="course_ksh_usd_rate" min="1" step="0.01" value="<?= e($courseKshUsdRate) ?>" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold" />
      <p class="mt-2 text-xs font-medium text-neutral-600">Used only for Stripe sandbox checkout display until live payment APIs are connected.</p>
    </div>
    <section class="rounded-xl border border-neutral-300 bg-neutral-50 p-5 space-y-5">
      <div>
        <p class="text-xs font-black uppercase tracking-widest text-neutral-600">Secure credentials</p>
        <h3 class="mt-1 text-xl font-black text-black">Daraja and Stripe keys</h3>
        <p class="mt-1 text-xs font-medium text-neutral-600">Secret fields stay saved when left blank.</p>
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">Daraja environment</label>
          <select name="daraja_environment" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold">
            <option value="sandbox" <?= $darajaEnvironment !== 'live' ? 'selected' : '' ?>>Sandbox</option>
            <option value="live" <?= $darajaEnvironment === 'live' ? 'selected' : '' ?>>Live</option>
          </select>
        </div>
        <div>
          <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">Daraja shortcode</label>
          <input type="text" name="daraja_shortcode" value="<?= e($settings['daraja_shortcode'] ?? '') ?>" autocomplete="off" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold" />
        </div>
        <div>
          <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">Daraja consumer key</label>
          <input type="password" name="daraja_consumer_key" value="" placeholder="<?= $hasDarajaConsumerKey ? 'Saved - leave blank to keep' : 'Consumer key' ?>" autocomplete="new-password" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold" />
        </div>
        <div>
          <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">Daraja consumer secret</label>
          <input type="password" name="daraja_consumer_secret" value="" placeholder="<?= $hasDarajaConsumerSecret ? 'Saved - leave blank to keep' : 'Consumer secret' ?>" autocomplete="new-password" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold" />
        </div>
        <div>
          <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">Daraja passkey</label>
          <input type="password" name="daraja_passkey" value="" placeholder="<?= $hasDarajaPasskey ? 'Saved - leave blank to keep' : 'Lipa na M-Pesa passkey' ?>" autocomplete="new-password" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold" />
        </div>
        <div>
          <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">Daraja callback URL</label>
          <input type="url" name="daraja_callback_url" value="<?= e($settings['daraja_callback_url'] ?? '') ?>" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold" />
        </div>
        <div>
          <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">Stripe publishable key</label>
          <input type="text" name="stripe_publishable_key" value="<?= e($settings['stripe_publishable_key'] ?? '') ?>" autocomplete="off" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold" />
        </div>
        <div>
          <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">Stripe secret key</label>
          <input type="password" name="stripe_secret_key" value="" placeholder="<?= $hasStripeSecretKey ? 'Saved - leave blank to keep' : 'Secret key' ?>" autocomplete="new-password" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold" />
        </div>
        <div class="sm:col-span-2">
          <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">Stripe webhook secret</label>
          <input type="password" name="stripe_webhook_secret" value="" placeholder="<?= $hasStripeWebhookSecret ? 'Saved - leave blank to keep' : 'Webhook secret' ?>" autocomplete="new-password" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold" />
        </div>
      </div>
    </section>
    <button type="submit" class="btn-primary">Save payments</button>
  </form>

  <form method="post" action="<?= url('/admin/settings') ?>" class="rounded-xl border bg-white p-6 shadow-sm space-y-6" style="border:2px solid var(--ke-green)">
    <?= csrfField() ?>
    <input type="hidden" name="save_smtp" value="1" />
    <div class="flex flex-col gap-4 border-b border-neutral-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Email / SMTP</p>
        <h2 class="mt-2 text-2xl font-black text-black">Mail credentials</h2>
        <p class="mt-1 text-sm font-medium text-neutral-700">Used to email organisation registration forms, login codes, and password-reset OTPs. Put your SMTP host, username, and password here.</p>
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

  <form method="post" action="<?= url('/admin/settings') ?>" class="rounded-xl border bg-white p-6 shadow-sm space-y-4" style="border:2px solid var(--ke-green)">
    <?= csrfField() ?>
    <input type="hidden" name="save_whatsapp" value="1" />
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">WhatsApp</p>
      <h2 class="mt-2 text-2xl font-black text-black">Sharing number</h2>
      <p class="mt-1 text-sm font-medium text-neutral-700">When an admin creates a login for someone, they get a "Share via WhatsApp" link as a fallback to email — opened from this number's WhatsApp.</p>
    </div>
    <div>
      <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">WhatsApp number (with country code, digits only)</label>
      <input type="text" name="whatsapp_share_number" value="<?= e($settings['whatsapp_share_number'] ?? '') ?>" placeholder="2547XXXXXXXX" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold" />
    </div>
    <button type="submit" class="btn-primary">Save WhatsApp number</button>
  </form>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
