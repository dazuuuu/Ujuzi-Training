<?php
/** Certificates for anyone, by name. Requires $organisations. */
require __DIR__ . '/../layout-header.php';
$field = 'mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm';
?>
<div class="max-w-3xl space-y-5">
  <section>
    <a href="<?= url('/admin/certificates?tab=generated') ?>" class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">← Certificates</a>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold">Generate certificates</h1>
    <p class="mt-1 text-sm font-medium text-neutral-600">Make certificates for anyone — they don't need an account. Type the names, the skills or course, and generate them all at once. They open ready to download as one PDF, and stay listed under Certificates → Generated here.</p>
  </section>
  <form method="post" action="<?= url('/admin/certificates/generate') ?>" class="space-y-4 rounded-xl border bg-white p-5 shadow-sm" style="border-color:var(--ke-line)">
    <?= csrfField() ?>
    <label class="block text-sm font-bold">Names — one person per line
      <span class="block text-xs font-semibold text-neutral-600">Optionally add an ID or registration number after a comma: <span class="font-mono">Jane Wanjiku Kamau, 12345678</span></span>
      <textarea name="names" rows="10" required class="<?= $field ?> font-mono" placeholder="Jane Wanjiku Kamau, 12345678&#10;Peter Otieno&#10;Amina Hassan, ID 99887766" id="gen-names"></textarea>
      <span class="text-xs font-bold text-neutral-500" id="gen-count">0 names</span>
    </label>
    <label class="block text-sm font-bold">Skills / course
      <span class="block text-xs font-semibold text-neutral-600">Printed on every certificate. Separate several skills with commas.</span>
      <input name="skills" required class="<?= $field ?>" placeholder="e.g. Professional Driving, First Aid"></label>
    <div class="grid gap-4 sm:grid-cols-2">
      <label class="block text-sm font-bold">Design
        <select name="organisation_id" class="<?= $field ?>">
          <option value="">Platform certificate</option>
          <?php foreach ($organisations as $o): ?><option value="<?= (int) $o['id'] ?>">Offered by <?= e($o['name']) ?> (their design)</option><?php endforeach; ?>
        </select></label>
      <label class="block text-sm font-bold">Issue date
        <input type="date" name="issued_on" value="<?= date('Y-m-d') ?>" class="<?= $field ?>"></label>
    </div>
    <button type="submit" class="btn-primary">Generate certificates</button>
  </form>
</div>
<script>
(function () {
  var t = document.getElementById('gen-names'), c = document.getElementById('gen-count');
  t.addEventListener('input', function () { var n = t.value.split(/\n/).filter(function (l) { return l.trim(); }).length; c.textContent = n + ' name' + (n === 1 ? '' : 's'); });
})();
</script>
<?php require __DIR__ . '/../layout-footer.php'; ?>
