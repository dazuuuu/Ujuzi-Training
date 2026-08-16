<?php
/**
 * Super Admin: share a one-use organisation registration form.
 * Requires $organisations, $invites, $freshInvite.
 */
require __DIR__ . '/../layout-header.php';
$invites = $invites ?? [];
$freshInvite = $freshInvite ?? null;
$activeOrgs = array_values(array_filter($organisations, fn($org) => !empty($org['is_active'])));
?>

<div class="space-y-6">
  <section>
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Share with a client</p>
    <h2 class="mt-2 text-2xl font-black">Organisation registration form</h2>
    <p class="mt-1 max-w-3xl text-sm font-medium" style="color:var(--ke-muted)">Generate a registration form URL for an organisation, copy it, or email it to a client with SMTP. Each link expires in 5 minutes and accepts only one registration. After that, only Super Admin can create a new secure URL.</p>
  </section>

  <section class="rounded-xl border bg-white p-6 shadow-sm space-y-4" style="border:2px solid var(--ke-green)">
    <h3 class="font-serif-heading text-lg font-bold">Email the registration form to a client</h3>
    <p class="text-sm font-medium" style="color:var(--ke-muted)">This generates a fresh one-use link and sends it through the SMTP settings on Super Admin → Settings.</p>
    <?php if (!$activeOrgs): ?>
      <p class="text-sm font-bold">Create and activate an organisation first, then you can email its registration form.</p>
      <a href="<?= url('/admin/organisations/create') ?>" class="btn-primary">Add organisation</a>
    <?php else: ?>
      <form method="post" id="email-client-form" class="grid gap-4 md:grid-cols-2">
        <?= csrfField() ?>
        <input type="hidden" name="return_to" value="share" />
        <div>
          <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Organisation</label>
          <select id="share-org" required class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm">
            <?php foreach ($activeOrgs as $org): ?>
              <option value="<?= (int) $org['id'] ?>"><?= e($org['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Client email</label>
          <input type="email" name="invite_email" required placeholder="client@example.com" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" />
        </div>
        <div class="md:col-span-2">
          <button type="submit" class="btn-primary">Generate form and email client</button>
        </div>
      </form>
    <?php endif; ?>
  </section>

  <?php if ($organisations): ?>
    <section class="space-y-4" id="share">
      <h3 class="font-serif-heading text-lg font-bold">Or generate a URL to copy</h3>
      <?php foreach ($organisations as $org):
        $organisation = $org;
        $invite = $invites[(int) $org['id']] ?? null;
        $returnTo = 'share';
        require __DIR__ . '/invite-panel.php';
      endforeach; ?>
    </section>
  <?php endif; ?>
</div>

<script>
(function () {
  var form = document.getElementById('email-client-form');
  var select = document.getElementById('share-org');
  if (!form || !select) return;
  function syncAction() {
    form.action = <?= json_encode(url('/admin/organisations/')) ?> + select.value + '/invite';
  }
  select.addEventListener('change', syncAction);
  syncAction();
})();
</script>

<?php require __DIR__ . '/../layout-footer.php'; ?>
