<?php
/** Requires $organisation, optional $invite, $freshInvite. */
$organisation = $organisation ?? null;
$invite = $invite ?? null;
$freshInvite = $freshInvite ?? null;
if (!$organisation) {
    return;
}
$freshForOrg = ($freshInvite && (int) $freshInvite['organisation_id'] === (int) $organisation['id']) ? $freshInvite : null;
?>
<section id="invite" class="rounded-xl border bg-white p-6 shadow-sm space-y-4" style="border-color:var(--ke-line)">
  <div>
    <h2 class="font-serif-heading text-lg font-bold">Organisation admin registration link</h2>
    <p class="mt-1 text-sm font-medium" style="color:var(--ke-muted)">Generate a secure URL to share or email. It expires after 5 minutes and accepts only one registration (email and password). After that, only Super Admin can mint a new link.</p>
  </div>

  <?php if ($freshForOrg): ?>
    <div class="rounded-lg p-4 space-y-3" style="background:#e8f5ee;border:1px solid var(--ke-green)">
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Live link — copy or email it now</p>
      <input id="invite-url" type="text" readonly value="<?= e($freshForOrg['url']) ?>" class="w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm font-mono" />
      <p class="text-xs font-bold" style="color:var(--ke-muted)">Expires at <?= e(date('g:i:s A', strtotime($freshForOrg['expires_at']))) ?> (5 minutes from generation).</p>
      <div class="flex flex-wrap gap-2">
        <button type="button" class="btn-secondary" data-copy-invite>Copy URL</button>
      </div>
      <form method="post" action="<?= url('/admin/organisations/' . (int) $organisation['id'] . '/invite/email') ?>" class="grid gap-2 sm:grid-cols-[1fr_auto] sm:items-end pt-2">
        <?= csrfField() ?>
        <div>
          <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Email this URL</label>
          <input type="email" name="invite_email" required placeholder="admin@example.com" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" />
        </div>
        <button type="submit" class="btn-primary">Send email</button>
      </form>
    </div>
  <?php elseif ($invite): ?>
    <p class="text-sm font-semibold"><?= e(\App\Models\OrganisationAdminInvite::statusMessage($invite)) ?></p>
    <?php if (!empty($invite['used_at'])): ?>
      <p class="text-xs font-medium" style="color:var(--ke-muted)">Used <?= e(date('M j, Y g:i A', strtotime($invite['used_at']))) ?>.</p>
    <?php elseif (!empty($invite['expires_at'])): ?>
      <p class="text-xs font-medium" style="color:var(--ke-muted)">Last link expiry: <?= e(date('M j, Y g:i A', strtotime($invite['expires_at']))) ?>.</p>
    <?php endif; ?>
  <?php else: ?>
    <p class="text-sm font-semibold" style="color:var(--ke-muted)">No registration link has been generated yet.</p>
  <?php endif; ?>

  <form method="post" action="<?= url('/admin/organisations/' . (int) $organisation['id'] . '/invite') ?>">
    <?= csrfField() ?>
    <button type="submit" class="btn-primary" <?= empty($organisation['is_active']) ? 'disabled' : '' ?>>
      Generate new 5-minute URL
    </button>
  </form>
  <?php if (empty($organisation['is_active'])): ?>
    <p class="text-xs font-bold" style="color:var(--ke-red)">Activate the organisation before generating a link.</p>
  <?php endif; ?>
</section>
<script>
(function () {
  var btn = document.querySelector('[data-copy-invite]');
  var input = document.getElementById('invite-url');
  if (!btn || !input) return;
  btn.addEventListener('click', function () {
    input.select();
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(input.value).then(function () {
        btn.textContent = 'Copied';
      });
    } else {
      document.execCommand('copy');
      btn.textContent = 'Copied';
    }
  });
})();
</script>
