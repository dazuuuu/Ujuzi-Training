<?php
/** Requires $organisation, optional $invite, $freshInvite, $returnTo. */
$organisation = $organisation ?? null;
$invite = $invite ?? null;
$freshInvite = $freshInvite ?? null;
$returnTo = $returnTo ?? 'share';
if (!$organisation) {
    return;
}
$orgId = (int) $organisation['id'];
$freshForOrg = ($freshInvite && (int) $freshInvite['organisation_id'] === $orgId) ? $freshInvite : null;
$urlId = 'invite-url-' . $orgId;
?>
<section id="org-<?= $orgId ?>" class="rounded-xl border bg-white p-6 shadow-sm space-y-4" style="border-color:var(--ke-line)">
  <div>
    <h2 class="font-serif-heading text-lg font-bold"><?= e($organisation['name']) ?> — registration form</h2>
    <p class="mt-1 text-sm font-medium" style="color:var(--ke-muted)">One-use organisation admin registration. Email and password only. After 5 minutes the URL breaks; only Super Admin can generate a new one.</p>
  </div>

  <?php if ($freshForOrg): ?>
    <div class="rounded-lg p-4 space-y-3" style="background:#e8f5ee;border:1px solid var(--ke-green)">
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Live registration form URL</p>
      <input id="<?= e($urlId) ?>" type="text" readonly value="<?= e($freshForOrg['url']) ?>" class="w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm font-mono" />
      <p class="text-xs font-bold" style="color:var(--ke-muted)">Expires at <?= e(date('g:i:s A', strtotime($freshForOrg['expires_at']))) ?>. Accepts only one registration.</p>
      <button type="button" class="btn-secondary" data-copy-invite="<?= e($urlId) ?>">Copy URL</button>
      <form method="post" action="<?= url('/admin/organisations/' . $orgId . '/invite/email') ?>" class="grid gap-2 sm:grid-cols-[1fr_auto] sm:items-end pt-2">
        <?= csrfField() ?>
        <input type="hidden" name="return_to" value="<?= e($returnTo) ?>" />
        <div>
          <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Email this form to a client (SMTP)</label>
          <input type="email" name="invite_email" required placeholder="client@example.com" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" />
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
    <p class="text-sm font-semibold" style="color:var(--ke-muted)">No registration form link has been generated yet.</p>
  <?php endif; ?>

  <form method="post" action="<?= url('/admin/organisations/' . $orgId . '/invite') ?>" class="space-y-3">
    <?= csrfField() ?>
    <input type="hidden" name="return_to" value="<?= e($returnTo) ?>" />
    <div class="grid gap-2 sm:grid-cols-[1fr_auto] sm:items-end">
      <div>
        <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Optional: email client while generating</label>
        <input type="email" name="invite_email" placeholder="client@example.com" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" />
      </div>
      <button type="submit" class="btn-primary" <?= empty($organisation['is_active']) ? 'disabled' : '' ?>>Generate registration form</button>
    </div>
  </form>
  <?php if (empty($organisation['is_active'])): ?>
    <p class="text-xs font-bold" style="color:var(--ke-red)">Activate the organisation before generating a link.</p>
  <?php endif; ?>
</section>
<?php if (empty($GLOBALS['invite_copy_script'])): $GLOBALS['invite_copy_script'] = true; ?>
<script>
document.addEventListener('click', function (event) {
  var btn = event.target.closest('[data-copy-invite]');
  if (!btn) return;
  var input = document.getElementById(btn.getAttribute('data-copy-invite'));
  if (!input) return;
  input.select();
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(input.value).then(function () { btn.textContent = 'Copied'; });
  } else {
    document.execCommand('copy');
    btn.textContent = 'Copied';
  }
});
</script>
<?php endif; ?>
